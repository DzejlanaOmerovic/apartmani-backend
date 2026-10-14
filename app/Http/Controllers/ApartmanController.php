<?php

namespace App\Http\Controllers;

use App\Models\Apartman;
use App\Models\Fotografija;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Modul 2.0 – Upravljanje apartmanima i galerijom
class ApartmanController extends Controller
{
    // pravila za podatke o apartmanu (iz rečnika podataka)
    // Status se NE unosi ovde – menja ga samo zaposleni
    private function pravila(): array
    {
        return [
            'Naziv'         => ['required', 'string', 'max:100'],
            'Adresa'        => ['required', 'string', 'max:150'],
            'Sprat'         => ['required', 'integer', 'min:0'],
            'Kapacitet'     => ['required', 'integer', 'min:1'],
            'Opis'          => ['nullable', 'string', 'max:500'],
            'CenaPoNocenju' => ['required', 'numeric', 'gt:0'],
        ];
    }

    // pronalazi apartman koji pripada prijavljenom vlasniku (tuđi apartman = greška 404)
    private function mojApartman(Request $request, string $sifra): Apartman
    {
        return Apartman::where('SifraApartmana', $sifra)
            ->where('VlasnikID', $request->user()->KorisnikID)
            ->firstOrFail();
    }

    // ================= VLASNIK =================

    // GET /api/moji-apartmani – 2.1 svi apartmani prijavljenog vlasnika, sa fotografijama
    public function mojiApartmani(Request $request)
    {
        return Apartman::with('fotografije')
            ->where('VlasnikID', $request->user()->KorisnikID)
            ->orderBy('SifraApartmana')
            ->get();
    }

    // POST /api/moji-apartmani – 2.1 unos novog apartmana
    public function store(Request $request)
    {
        $podaci = $request->validate(array_merge([
            'SifraApartmana' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:apartman,SifraApartmana'],
        ], $this->pravila()));

        $podaci['VlasnikID'] = $request->user()->KorisnikID;
        $podaci['Status'] = 'slobodan';                 // novi apartman je uvek slobodan

        $apartman = Apartman::create($podaci);

        return response()->json([
            'message'  => 'Apartman je uspešno dodat.',
            'apartman' => $apartman->load('fotografije'),
        ], 201);
    }

    // PUT /api/moji-apartmani/{sifra} – 2.1 izmena podataka (šifra i status se ne menjaju ovde)
    public function update(Request $request, string $sifra)
    {
        $apartman = $this->mojApartman($request, $sifra);
        $podaci = $request->validate($this->pravila());

        // kapacitet ne sme biti manji od broja gostiju u aktivnim rezervacijama
        $maxGostiju = $apartman->rezervacije()
            ->whereIn('Status', ['kreirana', 'potvrdjena'])
            ->max('BrojGostiju');
        if ($maxGostiju && $podaci['Kapacitet'] < $maxGostiju) {
            return response()->json([
                'message' => "Kapacitet ne može biti manji od $maxGostiju jer postoji aktivna rezervacija sa toliko gostiju.",
            ], 422);
        }

        $apartman->update($podaci);

        return response()->json([
            'message'  => 'Podaci o apartmanu su sačuvani.',
            'apartman' => $apartman->load('fotografije'),
        ]);
    }

    // DELETE /api/moji-apartmani/{sifra} – brisanje apartmana (samo ako nema rezervacija ni održavanja)
    public function destroy(Request $request, string $sifra)
    {
        $apartman = $this->mojApartman($request, $sifra);
        $putanje = $apartman->fotografije()->pluck('Putanja')->all();

        try {
            DB::transaction(fn () => $apartman->delete());   // fotografije se brišu iz baze automatski (cascade)
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Apartman se ne može obrisati jer ima rezervacije ili evidentirano održavanje.',
            ], 409);
        }

        Storage::disk('public')->delete($putanje);          // brišu se i fajlovi slika
        return response()->json(['message' => 'Apartman je obrisan.']);
    }

    // POST /api/moji-apartmani/{sifra}/fotografije – 2.2 upload nove fotografije
    public function dodajFotografiju(Request $request, string $sifra)
    {
        $apartman = $this->mojApartman($request, $sifra);

        $request->validate([
            'fotografija' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],   // do 5 MB
        ]);

        // fajl se čuva u storage/app/public/fotografije, a u bazu ide putanja
        $putanja = $request->file('fotografija')->store('fotografije', 'public');

        $fotografija = Fotografija::create([
            'SifraApartmana' => $apartman->SifraApartmana,
            'Putanja'        => $putanja,
        ]);

        return response()->json([
            'message'     => 'Fotografija je dodata.',
            'fotografija' => $fotografija,
        ], 201);
    }

    // DELETE /api/fotografije/{id} – brisanje fotografije (samo iz sopstvenog apartmana)
    public function obrisiFotografiju(Request $request, int $id)
    {
        $fotografija = Fotografija::where('FotografijaID', $id)
            ->whereHas('apartman', fn ($q) => $q->where('VlasnikID', $request->user()->KorisnikID))
            ->firstOrFail();

        Storage::disk('public')->delete($fotografija->Putanja);
        $fotografija->delete();

        return response()->json(['message' => 'Fotografija je obrisana.']);
    }

    // ================= ZAPOSLENI =================

    // GET /api/apartmani – spisak svih apartmana (sa imenom vlasnika)
    public function sviApartmani()
    {
        return Apartman::with('vlasnik:KorisnikID,Ime,Prezime')
            ->orderBy('SifraApartmana')
            ->get();
    }

    // PUT /api/apartmani/{sifra}/status – tok "Status (zauzetosti) apartmana"
    public function promeniStatus(Request $request, string $sifra)
    {
        $apartman = Apartman::findOrFail($sifra);

        $podaci = $request->validate([
            'Status' => ['required', Rule::in(['slobodan', 'zauzet', 'van_upotrebe'])],
        ]);

        $apartman->update(['Status' => $podaci['Status']]);

        return response()->json([
            'message'  => 'Status apartmana je promenjen.',
            'apartman' => $apartman,
        ]);
    }

    // ================= GOST =================

    // GET /api/ponuda – 2.3 Prikaz galerije apartmana (svi koji nisu van upotrebe)
    public function ponuda()
    {
        return Apartman::with('fotografije')
            ->where('Status', '!=', 'van_upotrebe')
            ->orderBy('SifraApartmana')
            ->get();
    }

    // GET /api/ponuda/{sifra} – jedan apartman sa galerijom
    public function ponudaJedan(string $sifra)
    {
        return Apartman::with('fotografije')
            ->where('Status', '!=', 'van_upotrebe')
            ->findOrFail($sifra);
    }
}
