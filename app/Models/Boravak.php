<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu BORAVAK (slabi entitet, ključ je RezervacijaID)
class Boravak extends Model
{
    protected $table = 'boravak';
    protected $primaryKey = 'RezervacijaID';
    public $incrementing = false;            // ključ se ne generiše, preuzima se od rezervacije
    public $timestamps = false;

    protected $fillable = ['RezervacijaID', 'DatumVremeUlaska', 'DatumVremeIzlaska',
                           'ZaposleniPrijem', 'ZaposleniIspracaj'];

    protected $casts = [
        'RezervacijaID' => 'integer',
        'DatumVremeUlaska' => 'datetime:Y-m-d H:i',
        'DatumVremeIzlaska' => 'datetime:Y-m-d H:i',
        'ZaposleniPrijem' => 'integer',
        'ZaposleniIspracaj' => 'integer',
    ];

    public function rezervacija()
    {
        return $this->belongsTo(Rezervacija::class, 'RezervacijaID', 'RezervacijaID');
    }

    // KORISNIK – vrši prijem – BORAVAK
    public function zaposleniPrijem()
    {
        return $this->belongsTo(Korisnik::class, 'ZaposleniPrijem', 'KorisnikID');
    }

    // KORISNIK – vrši ispraćaj – BORAVAK
    public function zaposleniIspracaj()
    {
        return $this->belongsTo(Korisnik::class, 'ZaposleniIspracaj', 'KorisnikID');
    }
}
