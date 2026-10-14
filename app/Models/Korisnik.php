<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Model za tabelu KORISNIK – ovaj model se koristi za prijavu (email + lozinka)
class Korisnik extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'korisnik';
    protected $primaryKey = 'KorisnikID';
    public $timestamps = false;              // tabela nema created_at / updated_at

    protected $fillable = ['Ime', 'Prezime', 'Email', 'Lozinka', 'Uloga'];

    protected $hidden = ['Lozinka'];         // lozinka se nikad ne šalje Angular-u

    // Laravel podrazumevano traži kolonu "password" – kod nas se zove "Lozinka"
    public function getAuthPassword()
    {
        return $this->Lozinka;
    }

    public function getAuthPasswordName()
    {
        return 'Lozinka';
    }

    // VLASNIK – poseduje – APARTMAN (1:N)
    public function apartmani()
    {
        return $this->hasMany(Apartman::class, 'VlasnikID', 'KorisnikID');
    }

    // nalog gosta – GOST (1:1)
    public function gost()
    {
        return $this->hasOne(Gost::class, 'KorisnikID', 'KorisnikID');
    }
}
