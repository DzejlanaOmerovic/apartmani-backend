<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu ODRZAVANJE
class Odrzavanje extends Model
{
    protected $table = 'odrzavanje';
    protected $primaryKey = 'OdrzavanjeID';
    public $timestamps = false;

    protected $fillable = ['SifraApartmana', 'VrstaAktivnosti', 'Datum', 'OpisRadova', 'OdgovornaOsoba'];

    protected $casts = ['Datum' => 'date:Y-m-d'];

    public function apartman()
    {
        return $this->belongsTo(Apartman::class, 'SifraApartmana', 'SifraApartmana');
    }
}
