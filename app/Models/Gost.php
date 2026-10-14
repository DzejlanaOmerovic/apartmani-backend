<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu GOST
class Gost extends Model
{
    protected $table = 'gost';
    protected $primaryKey = 'GostID';
    public $timestamps = false;

    protected $fillable = ['KorisnikID', 'Ime', 'Prezime', 'Telefon', 'Email', 'Drzava'];

    protected $casts = ['KorisnikID' => 'integer'];

    public function korisnik()
    {
        return $this->belongsTo(Korisnik::class, 'KorisnikID', 'KorisnikID');
    }

    // GOST – pravi – REZERVACIJA (1:N)
    public function rezervacije()
    {
        return $this->hasMany(Rezervacija::class, 'GostID', 'GostID');
    }
}
