<?php

namespace App\Http\Controllers;

use App\Models\Gost;
use App\Models\Korisnik;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Modul 1.0 – Upravljanje korisnicima i pristupom (samo ADMINISTRATOR)
class KorisnikController extends Controller
{
    // uloge koje administrator sme da dodeli ručno
    // (gost se registruje sam, jer mu treba i zapis u tabeli GOST)
    private const ULOGE = ['administrator', 'vlasnik', 'zaposleni'];

    // GET /api/korisnici – spisak svih korisnika
    public function index()
    {
        return Korisnik::orderBy('KorisnikID')->get();
    }

    // POST /api/korisnici – 1.1 Unos novog korisnika (vlasnik, zaposleni ili administrator)
    public function store(Request $request)
    {
        $podaci = $request->validate([
            'Ime'     => ['required', 'string', 'max:30', 'regex:/^[\pL\s]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s]+$/u'],
            'Email'   => ['required', 'email', 'max:120', 'unique:korisnik,Email'],
            'Lozinka' => ['required', 'string', 'min:8', 'max:255'],
            'Uloga'   => ['required', Rule::in(self::ULOGE)],
        ]);

        $podaci['Lozinka'] = Hash::make($podaci['Lozinka']);
        $korisnik = Korisnik::create($podaci);

        return response()->json([
            'message'  => 'Korisnik je uspešno dodat.',
            'korisnik' => $korisnik,
        ], 201);
    }

    // PUT /api/korisnici/{id} – 1.1 Ažuriranje podataka o korisniku
    // (lozinka se menja samo ako je upisana nova)
    public function update(Request $request, int $id)
    {
        $korisnik = Korisnik::findOrFail($id);

        $podaci = $request->validate([
            'Ime'     => ['required', 'string', 'max:30', 'regex:/^[\pL\s]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s]+$/u'],
            'Email'   => ['required', 'email', 'max:120',
                          Rule::unique('korisnik', 'Email')->ignore($korisnik->KorisnikID, 'KorisnikID')],
            'Lozinka' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        if (! empty($podaci['Lozinka'])) {
            $podaci['Lozinka'] = Hash::make($podaci['Lozinka']);
        } else {
            unset($podaci['Lozinka']);
        }

        DB::transaction(function () use ($korisnik, $podaci) {
            $korisnik->update($podaci);

            // ako je korisnik gost, isti podaci se ažuriraju i u tabeli GOST
            if ($korisnik->Uloga === 'gost' && $korisnik->gost) {
                $korisnik->gost->update([
                    'Ime'     => $korisnik->Ime,
                    'Prezime' => $korisnik->Prezime,
                    'Email'   => $korisnik->Email,
                ]);
            }
        });

        return response()->json([
            'message'  => 'Potvrda ažuriranja korisnika: podaci su sačuvani.',
            'korisnik' => $korisnik->fresh(),
        ]);
    }

    // PUT /api/korisnici/{id}/uloga – 1.2 Dodela uloga i prava pristupa
    public function promeniUlogu(Request $request, int $id)
    {
        $korisnik = Korisnik::findOrFail($id);

        $podaci = $request->validate([
            'Uloga' => ['required', Rule::in(self::ULOGE)],
        ]);

        if ($korisnik->KorisnikID === $request->user()->KorisnikID) {
            return response()->json(['message' => 'Ne možete promeniti sopstvenu ulogu.'], 422);
        }

        if ($korisnik->Uloga === 'gost') {
            return response()->json(['message' => 'Gostu se ne može menjati uloga.'], 422);
        }

        $korisnik->update(['Uloga' => $podaci['Uloga']]);

        return response()->json([
            'message'  => 'Potvrda ažuriranja prava: uloga je promenjena.',
            'korisnik' => $korisnik,
        ]);
    }

    // DELETE /api/korisnici/{id} – brisanje korisnika
    public function destroy(Request $request, int $id)
    {
        $korisnik = Korisnik::findOrFail($id);

        if ($korisnik->KorisnikID === $request->user()->KorisnikID) {
            return response()->json(['message' => 'Ne možete obrisati sopstveni nalog.'], 422);
        }

        try {
            DB::transaction(function () use ($korisnik) {
                // za gosta se prvo briše zapis u tabeli GOST
                Gost::where('KorisnikID', $korisnik->KorisnikID)->delete();
                $korisnik->tokens()->delete();
                $korisnik->delete();
            });
        } catch (QueryException $e) {
            // strani ključ ne dozvoljava brisanje (npr. vlasnik ima apartmane, gost ima rezervacije)
            return response()->json([
                'message' => 'Korisnik se ne može obrisati jer postoje podaci povezani sa njim (apartmani, rezervacije ili boravci).',
            ], 409);
        }

        return response()->json(['message' => 'Korisnik je obrisan.']);
    }
}
