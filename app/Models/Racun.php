<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu RACUN
class Racun extends Model
{
    protected $table = 'racun';
    protected $primaryKey = 'RacunID';
    public $timestamps = false;

    protected $fillable = ['RezervacijaID', 'DatumIzdavanja', 'UkupanIznos'];

    protected $casts = [
        'RezervacijaID' => 'integer',
        'DatumIzdavanja' => 'date:Y-m-d',
        'UkupanIznos' => 'float',
    ];

    public function rezervacija()
    {
        return $this->belongsTo(Rezervacija::class, 'RezervacijaID', 'RezervacijaID');
    }
}
