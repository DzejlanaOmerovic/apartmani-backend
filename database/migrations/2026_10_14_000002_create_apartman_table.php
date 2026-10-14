<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela APARTMAN
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartman', function (Blueprint $table) {
            $table->string('SifraApartmana', 10)->primary();  // PK
            $table->unsignedInteger('VlasnikID');             // FK -> korisnik
            $table->string('Naziv', 100);
            $table->string('Adresa', 150);
            $table->unsignedInteger('Sprat');                 // >= 0
            $table->unsignedInteger('Kapacitet');             // broj osoba
            $table->string('Opis', 500)->nullable();
            $table->double('CenaPoNocenju');                  // real
            $table->enum('Status', ['slobodan', 'zauzet', 'van_upotrebe'])->default('slobodan');

            $table->foreign('VlasnikID')->references('KorisnikID')->on('korisnik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartman');
    }
};
