<?php

namespace App;

use  Moloquent;

class Recorrido extends Moloquent
{
    protected $fillable = [
        'unidad_id','latitud','longitud','fecha_gps', 'fecha', 'evento', 'gps_address'
    ];
    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }
    public function getEventoAttribute($value) {
        if ($value == 'PUERTA ABIERTA' || $value == 'PUERTA CERRADA')
            return ucfirst(strtolower($value));
        else 
            return $value;
    }
}