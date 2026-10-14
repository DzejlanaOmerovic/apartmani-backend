<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela BORAVAK (slabi entitet – ključ je RezervacijaID)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boravak', function (Blueprint $table) {
            $table->unsignedInteger('RezervacijaID')->primary(); // PK i FK -> rezervacija
            $table->dateTime('DatumVremeUlaska');
            $table->dateTime('DatumVremeIzlaska')->nullable();   // prazno dok gost ne izađe
            $table->unsignedInteger('ZaposleniPrijem');          // FK -> korisnik
            $table->unsignedInteger('ZaposleniIspracaj')->nullable(); // FK -> korisnik

            $table->foreign('RezervacijaID')->references('RezervacijaID')->on('rezervacija');
            $table->foreign('ZaposleniPrijem')->references('KorisnikID')->on('korisnik');
            $table->foreign('ZaposleniIspracaj')->references('KorisnikID')->on('korisnik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boravak');
    }
};
