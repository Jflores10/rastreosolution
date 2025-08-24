<?php

namespace App;

use Moloquent;

class PreDespacho extends Moloquent
{
    protected $fillable = [
        'cooperativa_id','unidad_id','ruta_id','conductor_id','hora_salida',
        'creador_id', 'modificador_id'
    ];
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }
    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }
    public function ruta()
    {
        return $this->belongsTo('App\Ruta');
    }
    public function conductor()
    {
        return $this->belongsTo('App\Conductor');
    }
    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
}
