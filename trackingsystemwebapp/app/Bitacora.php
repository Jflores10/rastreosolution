<?php

namespace App;
use Moloquent;
use Auth;
class Bitacora  extends Moloquent
{
    protected $fillable = [
        'unidad_id', 'fechaInicio', 'estado', 'fechaFin', 'creador_id', 'modificador_id',
        'descripcion', 'tipo_bitacora','compartido'
    ];

    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }

    public function creador()
    {
        return $this->belongsTo('App\User');
    }

    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    protected $dates = [
        'fechaInicio','fechaFin'
    ];
}
