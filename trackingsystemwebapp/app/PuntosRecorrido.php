<?php

namespace App;
use App\Unidad;
use App\PuntoControl;
use Moloquent;
use Auth;
class PuntosRecorrido extends Moloquent
{
    
    protected $fillable = [
        'latitud','longitud','unidad_id','pto_control_id',
        'fecha','tipo'
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function punto_control()
    {
        return $this->belongsTo(PuntoControl::class, 'pto_control_id');
    }
}
