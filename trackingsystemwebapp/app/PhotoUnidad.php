<?php

namespace App;

use Moloquent as Model;

class PhotoUnidad extends Model
{
    protected $collection = 'photos_unidad';

    protected $fillable = array(
        'imei',
        'tipo',
        'tipo_evento',
        'fecha_gps',
        'latitud',
        'longitud',
        'imagen',
        'fecha',
        'js',
    );

    protected $dates = array(
        'fecha_gps',
        'fecha',
        'created_at',
        'updated_at',
    );
}
