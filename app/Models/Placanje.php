<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu PLACANJE
class Placanje extends Model
{
    protected $table = 'placanje';
    protected $primaryKey = 'PlacanjeID';
    public $timestamps = false;

    protected $fillable = ['RezervacijaID', 'Iznos', 'DatumPlacanja', 'NacinPlacanja'];

    protected $casts = [
        'RezervacijaID' => 'integer',
        'Iznos' => 'float',
        'DatumPlacanja' => 'date:Y-m-d',
    ];

    public function rezervacija()
    {
        return $this->belongsTo(Rezervacija::class, 'RezervacijaID', 'RezervacijaID');
    }
}
