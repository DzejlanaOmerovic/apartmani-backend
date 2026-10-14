<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu DODATNI_TROSAK (postoji zbog relacionog modela; u aplikaciji se ne koristi)
class DodatniTrosak extends Model
{
    protected $table = 'dodatni_trosak';
    protected $primaryKey = 'TrosakID';
    public $timestamps = false;

    protected $fillable = ['RezervacijaID', 'VrstaTroska', 'Iznos'];

    protected $casts = [
        'RezervacijaID' => 'integer',
        'Iznos' => 'float',
    ];

    public function rezervacija()
    {
        return $this->belongsTo(Rezervacija::class, 'RezervacijaID', 'RezervacijaID');
    }
}
