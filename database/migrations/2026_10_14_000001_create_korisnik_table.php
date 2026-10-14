<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela KORISNIK – nalozi za prijavu (administrator, vlasnik, zaposleni, gost)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('korisnik', function (Blueprint $table) {
            $table->increments('KorisnikID');                 // integer, PK, auto increment
            $table->string('Ime', 30);
            $table->string('Prezime', 40);
            $table->string('Email', 120)->unique();
            $table->string('Lozinka', 255);                   // čuva se šifrovana (hash)
            $table->enum('Uloga', ['administrator', 'vlasnik', 'zaposleni', 'gost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('korisnik');
    }
};
