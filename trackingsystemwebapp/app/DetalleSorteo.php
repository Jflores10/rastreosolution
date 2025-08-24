<?php

namespace App;

use Moloquent as Model;

class DetalleSorteo extends Model
{
    protected $fillable = [
        'intervalo',
        'hora_inicio',
        'numero_unidades',
        'unidades',
        'sorteo_id'
    ];
    public function sorteo() {
        return $this->belongsTo('App\Sorteo');
    }
}
