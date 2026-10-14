<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela RACUN
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racun', function (Blueprint $table) {
            $table->increments('RacunID');                    // PK
            $table->unsignedInteger('RezervacijaID');         // FK -> rezervacija
            $table->date('DatumIzdavanja');
            $table->double('UkupanIznos');                    // real

            $table->foreign('RezervacijaID')->references('RezervacijaID')->on('rezervacija');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racun');
    }
};
