<?php

namespace App;

use Moloquent;

class TipoUnidad extends Moloquent
{
    protected $fillable = [
        'descripcion', 'creador_id', 'modificador_id', 'estado'
    ];
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }
}
