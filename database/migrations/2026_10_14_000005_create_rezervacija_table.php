<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela REZERVACIJA
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rezervacija', function (Blueprint $table) {
            $table->increments('RezervacijaID');              // PK
            $table->string('SifraApartmana', 10);             // FK -> apartman
            $table->unsignedInteger('GostID');                // FK -> gost
            $table->date('DatumPocetka');
            $table->date('DatumZavrsetka');
            $table->unsignedInteger('BrojGostiju');
            $table->double('Cena');                           // real
            $table->enum('Status', ['kreirana', 'potvrdjena', 'otkazana', 'realizovana'])->default('kreirana');

            $table->foreign('SifraApartmana')->references('SifraApartmana')->on('apartman');
            $table->foreign('GostID')->references('GostID')->on('gost');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezervacija');
    }
};
