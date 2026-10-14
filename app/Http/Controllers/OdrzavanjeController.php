<?php

namespace App\Http\Controllers;

use App\Models\Odrzavanje;
use App\Models\Rezervacija;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Modul 5.0 – Održavanje apartmana – ZAPOSLENI
class OdrzavanjeController extends Controller
{
    private function pravila(): array
    {
        return [
            'SifraApartmana'  => ['required', 'string', 'exists:apartman,SifraApartmana'],
            'VrstaAktivnosti' => ['required', Rule::in(['ciscenje', 'popravka', 'tehnicko_odrzavanje'])],
            'Datum'           => ['required', 'date'],
            'OpisRadova'      => ['nullable', 'string', 'max:500'],
            'OdgovornaOsoba'  => ['required', 'string', 'max:100'],
        ];
    }

    // održavanje ne sme da padne u dan kada je gost u apartmanu
    // (dan odlaska je dozvoljen – tada se apartman čisti)
    private function sukobSaRezervacijom(string $sifra, string $datum): bool
    {
        return Rezervacija::where('SifraApartmana', $sifra)
            ->whereIn('Status', ['kreirana', 'potvrdjena'])
            ->where('DatumPocetka', '<=', $datum)
            ->where('DatumZavrsetka', '>', $datum)
            ->exists();
    }

    // GET /api/odrzavanja?od=...&do=...&sifra=... – sva evidentirana održavanja
    public function index(Request $request)
    {
        $upit = Odrzavanje::with('apartman:SifraApartmana,Naziv')->orderByDesc('Datum');

        if ($request->filled('od'))    { $upit->where('Datum', '>=', $request->od); }
        if ($request->filled('do'))    { $upit->where('Datum', '<=', $request->do); }
        if ($request->filled('sifra')) { $upit->where('SifraApartmana', $request->sifra); }

        return $upit->get();
    }

    // GET /api/odrzavanja/plan – 5.1 Plan održavanja apartmana (od danas nadalje)
    public function plan()
    {
        return Odrzavanje::with('apartman:SifraApartmana,Naziv')
            ->where('Datum', '>=', now()->toDateString())
            ->orderBy('Datum')
            ->get();
    }

    // POST /api/odrzavanja – 5.2 Evidentiranje aktivnosti održavanja
    public function store(Request $request)
    {
        $podaci = $request->validate($this->pravila());
        $podaci['Datum'] = Carbon::parse($podaci['Datum'])->toDateString();

        if ($this->sukobSaRezervacijom($podaci['SifraApartmana'], $podaci['Datum'])) {
            return response()->json(['message' => 'Tog dana je apartman rezervisan – održavanje nije moguće.'], 422);
        }

        $odrzavanje = Odrzavanje::create($podaci);

        return response()->json([
            'message'    => 'Aktivnost održavanja je evidentirana.',
            'odrzavanje' => $odrzavanje,
        ], 201);
    }

    // PUT /api/odrzavanja/{id} – izmena
    public function update(Request $request, int $id)
    {
        $odrzavanje = Odrzavanje::findOrFail($id);
        $podaci = $request->validate($this->pravila());
        $podaci['Datum'] = Carbon::parse($podaci['Datum'])->toDateString();

        if ($this->sukobSaRezervacijom($podaci['SifraApartmana'], $podaci['Datum'])) {
            return response()->json(['message' => 'Tog dana je apartman rezervisan – održavanje nije moguće.'], 422);
        }

        $odrzavanje->update($podaci);

        return response()->json([
            'message'    => 'Održavanje je izmenjeno.',
            'odrzavanje' => $odrzavanje,
        ]);
    }

    // DELETE /api/odrzavanja/{id}
    public function destroy(int $id)
    {
        Odrzavanje::findOrFail($id)->delete();

        return response()->json(['message' => 'Održavanje je obrisano.']);
    }
}
