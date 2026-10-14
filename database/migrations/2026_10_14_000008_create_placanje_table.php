<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela PLACANJE
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placanje', function (Blueprint $table) {
            $table->increments('PlacanjeID');                 // PK
            $table->unsignedInteger('RezervacijaID');         // FK -> rezervacija
            $table->double('Iznos');                          // real
            $table->date('DatumPlacanja');
            $table->enum('NacinPlacanja', ['gotovina', 'kartica', 'online']);

            $table->foreign('RezervacijaID')->references('RezervacijaID')->on('rezervacija');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placanje');
    }
};
