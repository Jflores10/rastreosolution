<?php

namespace App;

use Moloquent;
use Auth;
class PuntoControlAtmOficial extends Moloquent
{
    protected $collection = 'PuntoControlAtmOficial';
    protected $fillable = [
        'descripcion','latitud','longitud','estado',
        'radio','cooperativa_id','pdi', '_id', 'codigo','fecha_importado'
    ];

    public function scopePermitido($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        else if ($tipo_usuario != 1)
            $query->where('cooperativa_id', $user->cooperativa_id);
        return $query;
    }
    
    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }

    protected $dates = [
        'fecha_importado'
    ];
}