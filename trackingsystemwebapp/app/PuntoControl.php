<?php

namespace App;

use Moloquent;
use Auth;
class PuntoControl extends Moloquent
{
    protected $fillable = [
        'descripcion','latitud','longitud','estado',
        'radio','creador_id', 'modificador_id','cooperativa_id','pdi', 'entrada', 'salida', 
        'mt', 'estado_exportacion', 'fecha_exportacion'
    ];
    protected $dates = [
        'fecha_exportacion'
    ];
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
    public function scopePermitido($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1)
            $query->where('cooperativa_id', $user->cooperativa_id);
        else if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        return $query;
    }
}