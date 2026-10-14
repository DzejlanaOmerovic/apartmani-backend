<?php

// Poruke o greškama na srpskom (Laravel ih koristi jer je APP_LOCALE=sr)
return [
    'required'       => 'Polje :attribute je obavezno.',
    'required_if'    => 'Polje :attribute je obavezno.',
    'string'         => 'Polje :attribute mora biti tekst.',
    'email'          => 'Polje :attribute mora biti ispravna email adresa.',
    'unique'         => 'Vrednost u polju :attribute već postoji.',
    'regex'          => 'Polje :attribute nije u ispravnom formatu.',
    'in'             => 'Izabrana vrednost za :attribute nije dozvoljena.',
    'integer'        => 'Polje :attribute mora biti ceo broj.',
    'numeric'        => 'Polje :attribute mora biti broj.',
    'date'           => 'Polje :attribute mora biti ispravan datum.',
    'after'          => 'Polje :attribute mora biti datum posle :date.',
    'after_or_equal' => 'Polje :attribute mora biti isti ili kasniji datum od :date.',
    'exists'         => 'Izabrana vrednost za :attribute ne postoji.',
    'image'          => 'Polje :attribute mora biti slika.',
    'mimes'          => 'Polje :attribute mora biti fajl tipa: :values.',
    'uploaded'       => 'Fajl :attribute nije uspešno poslat (možda je prevelik).',
    'gt' => [
        'numeric' => 'Polje :attribute mora biti veće od :value.',
    ],
    'max' => [
        'numeric' => 'Polje :attribute ne sme biti veće od :max.',
        'string'  => 'Polje :attribute ne sme imati više od :max karaktera.',
        'file'    => 'Fajl :attribute ne sme biti veći od :max KB.',
    ],
    'min' => [
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'string'  => 'Polje :attribute mora imati najmanje :min karaktera.',
    ],
    'attributes' => [],
];
