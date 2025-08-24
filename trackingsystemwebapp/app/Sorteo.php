<?php

namespace App;

use Moloquent as Model;

class Sorteo extends Model
{
    protected $fillable = [
        'fecha', 'cantidad_sorteos', 'cooperativa_id', 'creador_id', 'eliminador_id', 'estado',
        'cabecera'
    ];
    public function cooperativa() {
        return $this->belongsTo('App\Cooperativa');
    }
    protected $dates = [
        'fecha'
    ];
    public function sorteos() {
        return $this->hasMany('App\DetalleSorteo');
    }
}
