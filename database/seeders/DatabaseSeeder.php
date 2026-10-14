<?php

namespace Database\Seeders;

use App\Models\Korisnik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Početni podaci: samo jedan administratorski nalog
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Korisnik::firstOrCreate(
            ['Email' => 'admin@apartmani.rs'],
            [
                'Ime'     => 'Admin',
                'Prezime' => 'Sistema',
                'Lozinka' => Hash::make('admin123'),
                'Uloga'   => 'administrator',
            ]
        );
    }
}
