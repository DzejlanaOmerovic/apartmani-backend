<?php

namespace App\Http\Controllers;

use App\Models\Apartman;
use App\Models\Odrzavanje;
use App\Models\Placanje;
use App\Models\Rezervacija;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Modul 7.0 – Izveštavanje
class IzvestajController extends Controller
{
    // čita period iz zahteva (?od=...&do=...), podrazumevano: tekući mesec
    private function period(Request $request): array
    {
        $podaci = $request->validate([
            'od' => ['nullable', 'date'],
            'do' => ['nullable', 'date', 'after_or_equal:od'],
        ]);

        $od = isset($podaci['od']) ? Carbon::parse($podaci['od'])->startOfDay() : now()->startOfMonth();
        $do = isset($podaci['do']) ? Carbon::parse($podaci['do'])->startOfDay() : now()->endOfMonth()->startOfDay();

        return [$od, $do];
    }

    // ================= VLASNIK =================

    // GET /api/izvestaji/prihodi?od=...&do=... – 7.3 Izveštaj o prihodima (po apartmanima vlasnika)
    public function prihodi(Request $request)
    {
        [$od, $do] = $this->period($request);
        $apartmani = Apartman::where('VlasnikID', $request->user()->KorisnikID)->orderBy('SifraApartmana')->get();

        $redovi = $apartmani->map(function (Apartman $a) use ($od, $do) {
            $uplate = Placanje::whereHas('rezervacija', fn ($q) => $q->where('SifraApartmana', $a->SifraApartmana))
                ->whereBetween('DatumPlacanja', [$od->toDateString(), $do->toDateString()]);

            return [
                'SifraApartmana' => $a->SifraApartmana,
                'Naziv'          => $a->Naziv,
                'BrojUplata'     => (clone $uplate)->count(),
                'Prihod'         => round((float) (clone $uplate)->sum('Iznos'), 2),
            ];
        });

        return response()->json([
            'od'      => $od->toDateString(),
            'do'      => $do->toDateString(),
            'redovi'  => $redovi,
            'ukupno'  => round($redovi->sum('Prihod'), 2),
        ]);
    }

    // GET /api/izvestaji/popunjenost?od=...&do=... – 7.2 Izveštaj o popunjenosti
    // popunjenost = broj rezervisanih noćenja u periodu / broj dana u periodu × 100
    public function popunjenost(Request $request)
    {
        [$od, $do] = $this->period($request);
        $krajPerioda = $do->copy()->addDay();                  // poslednji dan se računa
        $brojDana = (int) $od->diffInDays($krajPerioda);

        $apartmani = Apartman::where('VlasnikID', $request->user()->KorisnikID)->orderBy('SifraApartmana')->get();

        $redovi = $apartmani->map(function (Apartman $a) use ($od, $krajPerioda, $brojDana) {
            $rezervacije = Rezervacija::where('SifraApartmana', $a->SifraApartmana)
                ->where('Status', '!=', 'otkazana')
                ->where('DatumPocetka', '<', $krajPerioda->toDateString())
                ->where('DatumZavrsetka', '>', $od->toDateString())
                ->get();

            $nocenja = 0;
            foreach ($rezervacije as $r) {
                $pocetak = $r->DatumPocetka->greaterThan($od) ? $r->DatumPocetka : $od;
                $kraj = $r->DatumZavrsetka->lessThan($krajPerioda) ? $r->DatumZavrsetka : $krajPerioda;
                $nocenja += (int) $pocetak->diffInDays($kraj);
            }

            return [
                'SifraApartmana'   => $a->SifraApartmana,
                'Naziv'            => $a->Naziv,
                'BrojRezervacija'  => $rezervacije->count(),
                'ZauzetihNocenja'  => $nocenja,
                'DanaUPeriodu'     => $brojDana,
                'Popunjenost'      => $brojDana > 0 ? round($nocenja / $brojDana * 100, 1) : 0,
            ];
        });

        return response()->json([
            'od'     => $od->toDateString(),
            'do'     => $do->toDateString(),
            'redovi' => $redovi,
        ]);
    }

    // ================= ZAPOSLENI =================

    // GET /api/izvestaji/rezervacije?od=...&do=... – 7.1 Izveštaj o rezervacijama
    // (rezervacije čiji boravak pada u period)
    public function rezervacije(Request $request)
    {
        [$od, $do] = $this->period($request);

        $rezervacije = Rezervacija::with(['apartman:SifraApartmana,Naziv', 'gost'])
            ->where('DatumPocetka', '<=', $do->toDateString())
            ->where('DatumZavrsetka', '>=', $od->toDateString())
            ->orderBy('DatumPocetka')
            ->get();

        $poStatusu = [];
        foreach (['kreirana', 'potvrdjena', 'otkazana', 'realizovana'] as $s) {
            $poStatusu[$s] = $rezervacije->where('Status', $s)->count();
        }

        $poApartmanu = $rezervacije->groupBy('SifraApartmana')->map(fn ($grupa, $sifra) => [
            'SifraApartmana'  => $sifra,
            'Naziv'           => $grupa->first()->apartman->Naziv,
            'BrojRezervacija' => $grupa->count(),
        ])->values();

        return response()->json([
            'od'          => $od->toDateString(),
            'do'          => $do->toDateString(),
            'ukupno'      => $rezervacije->count(),
            'poStatusu'   => $poStatusu,
            'poApartmanu' => $poApartmanu,
            'rezervacije' => $rezervacije,
        ]);
    }

    // GET /api/izvestaji/odrzavanje?od=...&do=... – učestalost održavanja (iz korisničkog zahteva)
    public function odrzavanje(Request $request)
    {
        [$od, $do] = $this->period($request);

        $odrzavanja = Odrzavanje::with('apartman:SifraApartmana,Naziv')
            ->whereBetween('Datum', [$od->toDateString(), $do->toDateString()])
            ->get();

        $redovi = $odrzavanja->groupBy('SifraApartmana')->map(fn ($grupa, $sifra) => [
            'SifraApartmana'      => $sifra,
            'Naziv'               => $grupa->first()->apartman->Naziv,
            'ciscenje'            => $grupa->where('VrstaAktivnosti', 'ciscenje')->count(),
            'popravka'            => $grupa->where('VrstaAktivnosti', 'popravka')->count(),
            'tehnicko_odrzavanje' => $grupa->where('VrstaAktivnosti', 'tehnicko_odrzavanje')->count(),
            'ukupno'              => $grupa->count(),
        ])->values();

        return response()->json([
            'od'     => $od->toDateString(),
            'do'     => $do->toDateString(),
            'redovi' => $redovi,
            'ukupno' => $odrzavanja->count(),
        ]);
    }
}
