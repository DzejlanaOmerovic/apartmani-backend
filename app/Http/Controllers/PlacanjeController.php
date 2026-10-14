<?php

namespace App\Http\Controllers;

use App\Models\Placanje;
use App\Models\Racun;
use App\Models\Rezervacija;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Modul 6.0 – Upravljanje finansijama (GOST: plaćanje i račun)
class PlacanjeController extends Controller
{
    // pronalazi rezervaciju koja pripada prijavljenom gostu
    private function mojaRezervacija(Request $request, int $id): Rezervacija
    {
        return Rezervacija::where('RezervacijaID', $id)
            ->where('GostID', $request->user()->gost?->GostID)
            ->firstOrFail();
    }

    // POST /api/moje-rezervacije/{id}/plati
    // 6.1 Evidentiranje uplate → 6.3 Generisanje računa → rezervacija postaje "potvrdjena"
    public function plati(Request $request, int $id)
    {
        $rezervacija = $this->mojaRezervacija($request, $id);

        $podaci = $request->validate([
            'NacinPlacanja' => ['required', Rule::in(['gotovina', 'kartica', 'online'])],
        ]);

        if ($rezervacija->Status !== 'kreirana') {
            return response()->json([
                'message' => 'Plaćanje je moguće samo za rezervaciju sa statusom "kreirana" (ova je: ' . $rezervacija->Status . ').',
            ], 422);
        }

        $racun = DB::transaction(function () use ($rezervacija, $podaci) {
            $danas = now()->toDateString();

            // 6.1 – "Podaci o uplati" → sPlacanja
            Placanje::create([
                'RezervacijaID' => $rezervacija->RezervacijaID,
                'Iznos'         => $rezervacija->Cena,
                'DatumPlacanja' => $danas,
                'NacinPlacanja' => $podaci['NacinPlacanja'],
            ]);

            // 6.3 – "Novi račun" → sRacuni
            // ukupan iznos = cena rezervacije + eventualni dodatni troškovi (u aplikaciji ih nema, pa je 0)
            $ukupno = $rezervacija->Cena + $rezervacija->dodatniTroskovi()->sum('Iznos');

            $racun = Racun::create([
                'RezervacijaID'  => $rezervacija->RezervacijaID,
                'DatumIzdavanja' => $danas,
                'UkupanIznos'    => $ukupno,
            ]);

            // rezervacija je plaćena → potvrđena
            $rezervacija->update(['Status' => 'potvrdjena']);

            return $racun;
        });

        return response()->json([
            'message' => 'Plaćanje je uspešno. Rezervacija je potvrđena i račun je izdat.',
            'racun'   => $this->racunSaPodacima($racun->RacunID),
        ], 201);
    }

    // GET /api/racuni/{id} – "Račun" za gosta (samo njegov), za prikaz i štampu
    public function prikaziRacun(Request $request, int $id)
    {
        $racun = Racun::where('RacunID', $id)
            ->whereHas('rezervacija', fn ($q) => $q->where('GostID', $request->user()->gost?->GostID))
            ->firstOrFail();

        return $this->racunSaPodacima($racun->RacunID);
    }

    // račun sa svim podacima potrebnim za prikaz (rezervacija, apartman, gost, uplate)
    private function racunSaPodacima(int $racunId): Racun
    {
        return Racun::with([
            'rezervacija.apartman',
            'rezervacija.gost',
            'rezervacija.placanja',
        ])->findOrFail($racunId);
    }
}
