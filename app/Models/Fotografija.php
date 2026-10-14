<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu FOTOGRAFIJA
class Fotografija extends Model
{
    protected $table = 'fotografija';
    protected $primaryKey = 'FotografijaID';
    public $timestamps = false;

    protected $fillable = ['SifraApartmana', 'Putanja'];

    // u JSON odgovoru dodaje se i "Url" – puna adresa slike za prikaz u Angular-u
    protected $appends = ['Url'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->Putanja);
    }

    public function apartman()
    {
        return $this->belongsTo(Apartman::class, 'SifraApartmana', 'SifraApartmana');
    }
}
