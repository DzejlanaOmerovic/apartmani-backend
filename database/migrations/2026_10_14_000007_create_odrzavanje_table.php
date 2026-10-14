<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela ODRZAVANJE
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odrzavanje', function (Blueprint $table) {
            $table->increments('OdrzavanjeID');               // PK
            $table->string('SifraApartmana', 10);             // FK -> apartman
            $table->enum('VrstaAktivnosti', ['ciscenje', 'popravka', 'tehnicko_odrzavanje']);
            $table->date('Datum');
            $table->string('OpisRadova', 500)->nullable();
            $table->string('OdgovornaOsoba', 100);            // osoba ili servis

            $table->foreign('SifraApartmana')->references('SifraApartmana')->on('apartman');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odrzavanje');
    }
};
