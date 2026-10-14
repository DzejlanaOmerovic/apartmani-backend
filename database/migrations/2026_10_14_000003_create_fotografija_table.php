<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela FOTOGRAFIJA
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotografija', function (Blueprint $table) {
            $table->increments('FotografijaID');              // PK
            $table->string('SifraApartmana', 10);             // FK -> apartman
            $table->string('Putanja', 255);

            // kad se obriše apartman, brišu se i njegove fotografije
            $table->foreign('SifraApartmana')->references('SifraApartmana')->on('apartman')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotografija');
    }
};
