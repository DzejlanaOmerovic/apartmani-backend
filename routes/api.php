<?php

use App\Http\Controllers\ApartmanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoravakController;
use App\Http\Controllers\IzvestajController;
use App\Http\Controllers\KorisnikController;
use App\Http\Controllers\OdrzavanjeController;
use App\Http\Controllers\PlacanjeController;
use App\Http\Controllers\RezervacijaController;
use Illuminate\Support\Facades\Route;

// Sve rute u ovom fajlu automatski počinju sa /api

// ---- javne rute (bez prijave) ----
Route::post('/registracija', [AuthController::class, 'registracija']);
Route::post('/prijava', [AuthController::class, 'prijava']);

// ---- rute za prijavljene korisnike (potreban token) ----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/odjava', [AuthController::class, 'odjava']);
    Route::get('/ja', [AuthController::class, 'ja']);

    // ---- ADMINISTRATOR: modul 1.0 Upravljanje korisnicima i pristupom ----
    Route::middleware('uloga:administrator')->group(function () {
        Route::get('/korisnici', [KorisnikController::class, 'index']);
        Route::post('/korisnici', [KorisnikController::class, 'store']);
        Route::put('/korisnici/{id}', [KorisnikController::class, 'update']);
        Route::put('/korisnici/{id}/uloga', [KorisnikController::class, 'promeniUlogu']);
        Route::delete('/korisnici/{id}', [KorisnikController::class, 'destroy']);
    });

    // ---- VLASNIK: modul 2.0 apartmani, fotografije, galerija + 7.0 izveštaji ----
    Route::middleware('uloga:vlasnik')->group(function () {
        Route::get('/moji-apartmani', [ApartmanController::class, 'mojiApartmani']);
        Route::post('/moji-apartmani', [ApartmanController::class, 'store']);
        Route::put('/moji-apartmani/{sifra}', [ApartmanController::class, 'update']);
        Route::delete('/moji-apartmani/{sifra}', [ApartmanController::class, 'destroy']);
        Route::post('/moji-apartmani/{sifra}/fotografije', [ApartmanController::class, 'dodajFotografiju']);
        Route::delete('/fotografije/{id}', [ApartmanController::class, 'obrisiFotografiju']);

        // modul 7.0 – izveštaji za vlasnika
        Route::get('/izvestaji/prihodi', [IzvestajController::class, 'prihodi']);
        Route::get('/izvestaji/popunjenost', [IzvestajController::class, 'popunjenost']);
    });

    // ---- ZAPOSLENI: status (2.0), dostupnost/rezervacije (3.0), boravak (4.0), održavanje (5.0), izveštaji (7.0) ----
    Route::middleware('uloga:zaposleni')->group(function () {
        Route::get('/apartmani', [ApartmanController::class, 'sviApartmani']);
        Route::put('/apartmani/{sifra}/status', [ApartmanController::class, 'promeniStatus']);

        Route::get('/dostupnost', [RezervacijaController::class, 'dostupnost']);
        Route::get('/rezervacije', [RezervacijaController::class, 'index']);
        Route::put('/rezervacije/{id}/otkazi', [RezervacijaController::class, 'otkaziZaposleni']);

        // modul 4.0 – check-in, check-out, pregled dolazaka i odlazaka
        Route::post('/rezervacije/{id}/check-in', [BoravakController::class, 'checkIn']);
        Route::post('/rezervacije/{id}/check-out', [BoravakController::class, 'checkOut']);
        Route::get('/pregled-dolazaka', [BoravakController::class, 'pregled']);

        // modul 5.0 – održavanje apartmana
        Route::get('/odrzavanja', [OdrzavanjeController::class, 'index']);
        Route::get('/odrzavanja/plan', [OdrzavanjeController::class, 'plan']);
        Route::post('/odrzavanja', [OdrzavanjeController::class, 'store']);
        Route::put('/odrzavanja/{id}', [OdrzavanjeController::class, 'update']);
        Route::delete('/odrzavanja/{id}', [OdrzavanjeController::class, 'destroy']);

        // modul 7.0 – izveštaji za zaposlenog
        Route::get('/izvestaji/rezervacije', [IzvestajController::class, 'rezervacije']);
        Route::get('/izvestaji/odrzavanje', [IzvestajController::class, 'odrzavanje']);
    });

    // ---- GOST: galerija (2.0), rezervacije (3.0), plaćanje i račun (6.0) ----
    Route::middleware('uloga:gost')->group(function () {
        Route::get('/ponuda', [ApartmanController::class, 'ponuda']);
        Route::get('/ponuda/{sifra}', [ApartmanController::class, 'ponudaJedan']);
        Route::get('/ponuda/{sifra}/zauzeto', [RezervacijaController::class, 'zauzetiTermini']);

        Route::post('/rezervacije', [RezervacijaController::class, 'store']);
        Route::get('/moje-rezervacije', [RezervacijaController::class, 'mojeRezervacije']);
        Route::put('/moje-rezervacije/{id}/otkazi', [RezervacijaController::class, 'otkaziMoju']);

        // modul 6.0 – plaćanje i račun
        Route::post('/moje-rezervacije/{id}/plati', [PlacanjeController::class, 'plati']);
        Route::get('/racuni/{id}', [PlacanjeController::class, 'prikaziRacun']);
    });
});
