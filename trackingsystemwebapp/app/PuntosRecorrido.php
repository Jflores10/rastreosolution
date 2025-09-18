<?php

namespace App;
use App\Unidad;
use App\PuntoControl;
use Illuminate\Database\Eloquent\Model;

class PuntosRecorrido extends Model
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
