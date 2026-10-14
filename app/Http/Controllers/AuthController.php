<?php

namespace App\Http\Controllers;

use App\Models\Gost;
use App\Models\Korisnik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Prijava, registracija (gost i vlasnik), odjava i podaci o prijavljenom korisniku
class AuthController extends Controller
{
    // POST /api/registracija – javna registracija za GOSTA i VLASNIKA
    // (zaposlenog dodaje samo administrator)
    public function registracija(Request $request)
    {
        $podaci = $request->validate([
            'Uloga'   => ['required', 'in:gost,vlasnik'],
            'Ime'     => ['required', 'string', 'max:30', 'regex:/^[\pL\s]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s]+$/u'],
            'Email'   => ['required', 'email', 'max:120', 'unique:korisnik,Email'],
            'Lozinka' => ['required', 'string', 'min:8', 'max:255'],
            // Telefon i Drzava su obavezni samo za gosta (tabela GOST)
            'Telefon' => ['required_if:Uloga,gost', 'nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\/-]{6,20}$/'],
            'Drzava'  => ['required_if:Uloga,gost', 'nullable', 'string', 'max:50', 'regex:/^[\pL\s]+$/u'],
        ]);

        // svi upisi se rade zajedno – ili svi uspeju, ili nijedan
        $korisnik = DB::transaction(function () use ($podaci) {
            $korisnik = Korisnik::create([
                'Ime'     => $podaci['Ime'],
                'Prezime' => $podaci['Prezime'],
                'Email'   => $podaci['Email'],
                'Lozinka' => Hash::make($podaci['Lozinka']),
                'Uloga'   => $podaci['Uloga'],
            ]);

            // samo za gosta se pravi i zapis u tabeli GOST
            if ($podaci['Uloga'] === 'gost') {
                Gost::create([
                    'KorisnikID' => $korisnik->KorisnikID,
                    'Ime'        => $podaci['Ime'],
                    'Prezime'    => $podaci['Prezime'],
                    'Telefon'    => $podaci['Telefon'],
                    'Email'      => $podaci['Email'],
                    'Drzava'     => $podaci['Drzava'],
                ]);
            }

            return $korisnik;
        });

        return response()->json([
            'token'    => $korisnik->createToken('prijava')->plainTextToken,
            'korisnik' => $korisnik,
        ], 201);
    }

    // POST /api/prijava – email + lozinka, vraća token
    public function prijava(Request $request)
    {
        $podaci = $request->validate([
            'Email'   => ['required', 'email'],
            'Lozinka' => ['required', 'string'],
        ]);

        $korisnik = Korisnik::where('Email', $podaci['Email'])->first();

        if (! $korisnik || ! Hash::check($podaci['Lozinka'], $korisnik->Lozinka)) {
            throw ValidationException::withMessages([
                'Email' => ['Pogrešan email ili lozinka.'],
            ]);
        }

        return response()->json([
            'token'    => $korisnik->createToken('prijava')->plainTextToken,
            'korisnik' => $korisnik,
        ]);
    }

    // POST /api/odjava – briše trenutni token
    public function odjava(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Uspešno ste se odjavili.']);
    }

    // GET /api/ja – podaci o prijavljenom korisniku
    public function ja(Request $request)
    {
        return response()->json($request->user());
    }
}