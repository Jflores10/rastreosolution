<?php

namespace App;

use Moloquent as Model;

/**
 * Caché de reverse geocoding (colección geocache).
 * lat/lng almacenados redondeados a 3 decimales.
 */
class Geocache extends Model
{
    protected $collection = 'geocache';

    public $timestamps = false;

    protected $fillable = array(
        'lat',
        'lng',
        'direccion',
        'createdAt',
    );

    protected $dates = array(
        'createdAt',
    );
}
