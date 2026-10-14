<?php

namespace App\Http\Controllers;

use App\Models\Apartman;
use App\Models\Odrzavanje;
use App\Models\Rezervacija;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Modul 3.0 – Upravljanje rezervacijama i gostima
class RezervacijaController extends Controller
{
    // ================= 3.2 PROVERA DOSTUPNOSTI (zajednička funkcija) =================
    // Vraća null ako je apartman slobodan, ili tekst razloga zašto nije.
    // Datumi: $od = dan dolaska, $do = dan odlaska (dan odlaska je slobodan za sledećeg gosta).
    private function razlogNedostupnosti(Apartman $apartman, string $od, string $do, ?int $brojGostiju = null): ?string
    {
        if ($apartman->Status === 'van_upotrebe') {
            return 'Apartman je van upotrebe.';
        }

        if ($brojGostiju !== null && $brojGostiju > $apartman->Kapacitet) {
            return "Kapacitet apartmana je {$apartman->Kapacitet} osoba.";
        }

        // postoji li druga (neotkazana) rezervacija koja se preklapa sa ovim periodom
        $preklapanje = Rezervacija::where('SifraApartmana', $apartman->SifraApartmana)
            ->where('Status', '!=', 'otkazana')
            ->where('DatumPocetka', '<', $do)
            ->where('DatumZavrsetka', '>', $od)
            ->exists();
        if ($preklapanje) {
            return 'Apartman je već rezervisan u tom periodu.';
        }

        // tok "Termini održavanja": da li je u tim danima zakazano održavanje
        $odrzavanje = Odrzavanje::where('SifraApartmana', $apartman->SifraApartmana)
            ->where('Datum', '>=', $od)
            ->where('Datum', '<', $do)
            ->exists();
        if ($odrzavanje) {
            return 'U tom periodu je zakazano održavanje apartmana.';
        }

        return null;
    }

    // ================= GOST =================

    // GET /api/ponuda/{sifra}/zauzeto – zauzeti termini, da gost vidi kada apartman nije slobodan
    public function zauzetiTermini(string $sifra)
    {
        $danas = now()->toDateString();

        $rezervacije = Rezervacija::where('SifraApartmana', $sifra)
            ->where('Status', '!=', 'otkazana')
            ->where('DatumZavrsetka', '>', $danas)
            ->orderBy('DatumPocetka')
            ->get(['DatumPocetka', 'DatumZavrsetka']);

        $odrzavanja = Odrzavanje::where('SifraApartmana', $sifra)
            ->where('Datum', '>=', $danas)
            ->orderBy('Datum')
            ->pluck('Datum')
            ->map(fn ($datum) => Carbon::parse($datum)->toDateString());

        return response()->json([
            'rezervacije' => $rezervacije,
            'odrzavanja'  => $odrzavanja,
        ]);
    }

    // POST /api/rezervacije – 3.1 Zahtev za rezervaciju → 3.2 Provera → 3.3 Kreiranje
    public function store(Request $request)
    {
        $gost = $request->user()->gost;
        if (! $gost) {
            return response()->json(['message' => 'Za ovaj nalog ne postoje podaci o gostu.'], 403);
        }

        $podaci = $request->validate([
            'SifraApartmana' => ['required', 'string', 'exists:apartman,SifraApartmana'],
            'DatumPocetka'   => ['required', 'date', 'after_or_equal:today'],
            'DatumZavrsetka' => ['required', 'date', 'after:DatumPocetka'],
            'BrojGostiju'    => ['required', 'integer', 'min:1'],
        ]);

        $od = Carbon::parse($podaci['DatumPocetka'])->toDateString();
        $do = Carbon::parse($podaci['DatumZavrsetka'])->toDateString();

        // transakcija + zaključavanje apartmana: dva gosta ne mogu istovremeno da rezervišu isti termin
        return DB::transaction(function () use ($podaci, $od, $do, $gost) {
            $apartman = Apartman::where('SifraApartmana', $podaci['SifraApartmana'])->lockForUpdate()->first();

            $razlog = $this->razlogNedostupnosti($apartman, $od, $do, (int) $podaci['BrojGostiju']);
            if ($razlog) {
                return response()->json(['message' => $razlog], 422);
            }

            // 3.3 – cena = broj noćenja × cena po noćenju
            $brojNocenja = (int) Carbon::parse($od)->diffInDays(Carbon::parse($do));
            $cena = $brojNocenja * $apartman->CenaPoNocenju;

            $rezervacija = Rezervacija::create([
                'SifraApartmana' => $apartman->SifraApartmana,
                'GostID'         => $gost->GostID,
                'DatumPocetka'   => $od,
                'DatumZavrsetka' => $do,
                'BrojGostiju'    => $podaci['BrojGostiju'],
                'Cena'           => $cena,
                'Status'         => 'kreirana',          // postaje "potvrdjena" posle plaćanja
            ]);

            return response()->json([
                'message'     => "Rezervacija je kreirana ($brojNocenja noćenja, ukupno $cena €). Rezervacija će biti potvrđena posle plaćanja.",
                'rezervacija' => $rezervacija->load('apartman'),
            ], 201);
        });
    }

    // GET /api/moje-rezervacije – sve rezervacije prijavljenog gosta
    public function mojeRezervacije(Request $request)
    {
        $gost = $request->user()->gost;
        if (! $gost) {
            return response()->json([]);
        }

        return Rezervacija::with(['apartman', 'boravak', 'placanja', 'racuni'])
            ->where('GostID', $gost->GostID)
            ->orderByDesc('DatumPocetka')
            ->get();
    }

    // PUT /api/moje-rezervacije/{id}/otkazi – gost otkazuje SVOJU rezervaciju (pre dolaska)
    public function otkaziMoju(Request $request, int $id)
    {
        $gost = $request->user()->gost;
        $rezervacija = Rezervacija::where('RezervacijaID', $id)
            ->where('GostID', $gost?->GostID)
            ->firstOrFail();

        return $this->otkazi($rezervacija);
    }

    // ================= ZAPOSLENI =================

    // GET /api/rezervacije – sve rezervacije (filteri: od, do, status)
    public function index(Request $request)
    {
        $request->validate([
            'od'     => ['nullable', 'date'],
            'do'     => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['kreirana', 'potvrdjena', 'otkazana', 'realizovana'])],
        ]);

        $upit = Rezervacija::with(['apartman', 'gost', 'boravak'])->orderByDesc('DatumPocetka');

        if ($request->filled('od')) {
            $upit->where('DatumZavrsetka', '>', $request->od);
        }
        if ($request->filled('do')) {
            $upit->where('DatumPocetka', '<', $request->do);
        }
        if ($request->filled('status')) {
            $upit->where('Status', $request->status);
        }

        return $upit->get();
    }

    // GET /api/dostupnost?od=...&do=...&broj=... – tok "Dostupnost apartmana" (3.2 → ZAPOSLENI)
    public function dostupnost(Request $request)
    {
        $podaci = $request->validate([
            'od'   => ['required', 'date'],
            'do'   => ['required', 'date', 'after:od'],
            'broj' => ['nullable', 'integer', 'min:1'],
        ]);

        $od = Carbon::parse($podaci['od'])->toDateString();
        $do = Carbon::parse($podaci['do'])->toDateString();
        $broj = isset($podaci['broj']) ? (int) $podaci['broj'] : null;

        return Apartman::orderBy('SifraApartmana')->get()->map(function (Apartman $apartman) use ($od, $do, $broj) {
            $razlog = $this->razlogNedostupnosti($apartman, $od, $do, $broj);

            return [
                'SifraApartmana' => $apartman->SifraApartmana,
                'Naziv'          => $apartman->Naziv,
                'Kapacitet'      => $apartman->Kapacitet,
                'CenaPoNocenju'  => $apartman->CenaPoNocenju,
                'Status'         => $apartman->Status,
                'Dostupan'       => $razlog === null,
                'Razlog'         => $razlog,
            ];
        });
    }

    // PUT /api/rezervacije/{id}/otkazi – zaposleni otkazuje bilo koju rezervaciju (pre dolaska)
    public function otkaziZaposleni(int $id)
    {
        return $this->otkazi(Rezervacija::findOrFail($id));
    }

    // ================= zajedničko otkazivanje =================
    private function otkazi(Rezervacija $rezervacija)
    {
        if (! in_array($rezervacija->Status, ['kreirana', 'potvrdjena'], true)) {
            return response()->json(['message' => 'Ova rezervacija ne može da se otkaže (već je otkazana ili realizovana).'], 422);
        }

        if ($rezervacija->boravak()->exists()) {
            return response()->json(['message' => 'Rezervacija ne može da se otkaže jer je gost već ušao (check-in).'], 422);
        }

        $rezervacija->update(['Status' => 'otkazana']);

        return response()->json([
            'message'     => 'Rezervacija je otkazana.',
            'rezervacija' => $rezervacija,
        ]);
    }
}
