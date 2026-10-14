<?php

namespace App\Http\Controllers;

use App\Models\Boravak;
use App\Models\Rezervacija;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Modul 4.0 – Evidencija gostiju (check-in i check-out) – ZAPOSLENI
class BoravakController extends Controller
{
    // POST /api/rezervacije/{id}/check-in
    // 4.1 Provera rezervacije i gosta → 4.2 Evidentiranje ulaska
    public function checkIn(Request $request, int $id)
    {
        $rezervacija = Rezervacija::with('gost')->findOrFail($id);
        $danas = now()->toDateString();

        // 4.1 – provera rezervacije
        if ($rezervacija->Status !== 'potvrdjena') {
            return response()->json(['message' => 'Check-in je moguć samo za plaćenu (potvrđenu) rezervaciju. Status je: ' . $rezervacija->Status . '.'], 422);
        }
        if ($rezervacija->boravak()->exists()) {
            return response()->json(['message' => 'Gost je već prijavljen (check-in je već urađen).'], 422);
        }
        if ($danas < $rezervacija->DatumPocetka->toDateString() || $danas >= $rezervacija->DatumZavrsetka->toDateString()) {
            return response()->json(['message' => 'Check-in je moguć od ' . $rezervacija->DatumPocetka->format('d.m.Y.') . ' do ' . $rezervacija->DatumZavrsetka->copy()->subDay()->format('d.m.Y.') . '.'], 422);
        }

        // 4.2 – "Podaci o ulasku" → sBoravci
        $boravak = Boravak::create([
            'RezervacijaID'    => $rezervacija->RezervacijaID,
            'DatumVremeUlaska' => now(),
            'ZaposleniPrijem'  => $request->user()->KorisnikID,
        ]);

        return response()->json([
            'message' => "Check-in je evidentiran: {$rezervacija->gost->Ime} {$rezervacija->gost->Prezime}, apartman {$rezervacija->SifraApartmana}.",
            'boravak' => $boravak,
        ], 201);
    }

    // POST /api/rezervacije/{id}/check-out
    // 4.2 Evidentiranje izlaska → "Promena statusa rezervacije" (realizovana)
    public function checkOut(Request $request, int $id)
    {
        $rezervacija = Rezervacija::with('gost')->findOrFail($id);
        $boravak = $rezervacija->boravak;

        if (! $boravak) {
            return response()->json(['message' => 'Gost nije prijavljen – prvo mora biti urađen check-in.'], 422);
        }
        if ($boravak->DatumVremeIzlaska) {
            return response()->json(['message' => 'Check-out je već urađen.'], 422);
        }

        DB::transaction(function () use ($boravak, $rezervacija, $request) {
            $boravak->update([
                'DatumVremeIzlaska' => now(),
                'ZaposleniIspracaj' => $request->user()->KorisnikID,
            ]);
            $rezervacija->update(['Status' => 'realizovana']);
        });

        return response()->json([
            'message' => "Check-out je evidentiran: {$rezervacija->gost->Ime} {$rezervacija->gost->Prezime}. Rezervacija je realizovana.",
            'boravak' => $boravak->fresh(),
        ]);
    }

    // GET /api/pregled-dolazaka?od=...&do=...  – 4.3 Pregled dolazaka i odlazaka
    // (bez datuma: danas)
    public function pregled(Request $request)
    {
        $podaci = $request->validate([
            'od' => ['nullable', 'date'],
            'do' => ['nullable', 'date', 'after_or_equal:od'],
        ]);

        $od = isset($podaci['od']) ? Carbon::parse($podaci['od'])->toDateString() : now()->toDateString();
        $do = isset($podaci['do']) ? Carbon::parse($podaci['do'])->toDateString() : $od;

        $sa = ['apartman:SifraApartmana,Naziv', 'gost', 'boravak.zaposleniPrijem:KorisnikID,Ime,Prezime', 'boravak.zaposleniIspracaj:KorisnikID,Ime,Prezime'];

        // očekivani i obavljeni dolasci u periodu
        $dolasci = Rezervacija::with($sa)
            ->whereIn('Status', ['potvrdjena', 'realizovana', 'kreirana'])
            ->whereBetween('DatumPocetka', [$od, $do])
            ->orderBy('DatumPocetka')->get();

        // očekivani i obavljeni odlasci u periodu
        $odlasci = Rezervacija::with($sa)
            ->whereIn('Status', ['potvrdjena', 'realizovana'])
            ->whereBetween('DatumZavrsetka', [$od, $do])
            ->orderBy('DatumZavrsetka')->get();

        // trenutni boravak: ušli, a još nisu izašli
        $trenutno = Rezervacija::with($sa)
            ->whereHas('boravak', fn ($q) => $q->whereNull('DatumVremeIzlaska'))
            ->orderBy('SifraApartmana')->get();

        return response()->json([
            'od'       => $od,
            'do'       => $do,
            'dolasci'  => $dolasci,
            'odlasci'  => $odlasci,
            'trenutno' => $trenutno,
        ]);
    }
}
