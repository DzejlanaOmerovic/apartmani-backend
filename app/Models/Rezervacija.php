<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu REZERVACIJA
class Rezervacija extends Model
{
    protected $table = 'rezervacija';
    protected $primaryKey = 'RezervacijaID';
    public $timestamps = false;

    protected $fillable = ['SifraApartmana', 'GostID', 'DatumPocetka', 'DatumZavrsetka',
                           'BrojGostiju', 'Cena', 'Status'];

    protected $casts = [
        'GostID' => 'integer',
        'DatumPocetka' => 'date:Y-m-d',
        'DatumZavrsetka' => 'date:Y-m-d',
        'BrojGostiju' => 'integer',
        'Cena' => 'float',
    ];

    public function apartman()
    {
        return $this->belongsTo(Apartman::class, 'SifraApartmana', 'SifraApartmana');
    }

    public function gost()
    {
        return $this->belongsTo(Gost::class, 'GostID', 'GostID');
    }

    // REZERVACIJA – ima – BORAVAK (1:1)
    public function boravak()
    {
        return $this->hasOne(Boravak::class, 'RezervacijaID', 'RezervacijaID');
    }

    // REZERVACIJA – plaća se – PLACANJE (1:N)
    public function placanja()
    {
        return $this->hasMany(Placanje::class, 'RezervacijaID', 'RezervacijaID');
    }

    // REZERVACIJA – obuhvata – DODATNI_TROSAK (1:N)
    public function dodatniTroskovi()
    {
        return $this->hasMany(DodatniTrosak::class, 'RezervacijaID', 'RezervacijaID');
    }

    // REZERVACIJA – generiše – RACUN (1:N)
    public function racuni()
    {
        return $this->hasMany(Racun::class, 'RezervacijaID', 'RezervacijaID');
    }
}
