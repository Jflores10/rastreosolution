<?php

namespace App;

use Moloquent as Model;
use Auth;
class PuntoVirtual extends Model
{
    protected $fillable = [
        'cooperativa_id', 'descripcion', 'tipo_posicion', 'pista', 'clave_equipo', 'radio', 'latitud', 'longitud', 'estado'
    ];

    public function cooperativa() 
    {
        return $this->belongsTo('App\Cooperativa');
    }
    public function scopePermitido($query) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1) 
            $query->where('cooperativa_id', $user->cooperativa_id);
        return $query;
    }
    protected $hidden = [
        'clave_equipo'
    ];
}
