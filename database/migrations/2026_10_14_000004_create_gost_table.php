<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela GOST
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gost', function (Blueprint $table) {
            $table->increments('GostID');                     // PK
            $table->unsignedInteger('KorisnikID')->unique();  // FK -> korisnik (nalog gosta) – NOVO u odnosu na dokumentaciju
            $table->string('Ime', 30);
            $table->string('Prezime', 40);
            $table->string('Telefon', 20);
            $table->string('Email', 120)->nullable();
            $table->string('Drzava', 50);

            $table->foreign('KorisnikID')->references('KorisnikID')->on('korisnik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gost');
    }
};
