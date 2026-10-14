<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela DODATNI_TROSAK (postoji u bazi po relacionom modelu; u aplikaciji se ne koristi)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dodatni_trosak', function (Blueprint $table) {
            $table->increments('TrosakID');                   // PK
            $table->unsignedInteger('RezervacijaID');         // FK -> rezervacija
            $table->enum('VrstaTroska', ['ciscenje', 'steta', 'dodatne_usluge']);
            $table->double('Iznos');                          // real

            $table->foreign('RezervacijaID')->references('RezervacijaID')->on('rezervacija');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dodatni_trosak');
    }
};
