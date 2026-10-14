<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu APARTMAN
class Apartman extends Model
{
    protected $table = 'apartman';
    protected $primaryKey = 'SifraApartmana';
    public $incrementing = false;            // ključ se ne generiše automatski
    protected $keyType = 'string';           // ključ je tekst (npr. "A101")
    public $timestamps = false;

    protected $fillable = ['SifraApartmana', 'VlasnikID', 'Naziv', 'Adresa', 'Sprat',
                           'Kapacitet', 'Opis', 'CenaPoNocenju', 'Status'];

    protected $casts = [
        'VlasnikID' => 'integer',
        'Sprat' => 'integer',
        'Kapacitet' => 'integer',
        'CenaPoNocenju' => 'float',
    ];

    public function vlasnik()
    {
        return $this->belongsTo(Korisnik::class, 'VlasnikID', 'KorisnikID');
    }

    // APARTMAN – sadrži – FOTOGRAFIJA (1:N)
    public function fotografije()
    {
        return $this->hasMany(Fotografija::class, 'SifraApartmana', 'SifraApartmana');
    }

    // APARTMAN – za – REZERVACIJA (1:N)
    public function rezervacije()
    {
        return $this->hasMany(Rezervacija::class, 'SifraApartmana', 'SifraApartmana');
    }

    // APARTMAN – ima – ODRZAVANJE (1:N)
    public function odrzavanja()
    {
        return $this->hasMany(Odrzavanje::class, 'SifraApartmana', 'SifraApartmana');
    }
}
