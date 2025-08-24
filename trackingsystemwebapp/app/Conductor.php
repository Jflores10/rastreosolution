<?php

namespace App;

use Moloquent;
use Auth;
class Conductor extends Moloquent
{
    protected $fillable = [
        'cedula','nombre','estado' ,'creador_id', 'modificador_id', 'cooperativa_id',
        'direccion', 'operadora', 'telefono', 'celular', 'email','tipo_licencia',
        'exportado_atm','fecha_exportacion'
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
        {
            $query->where('cooperativa_id', $user->cooperativa_id);
            if ($tipo_usuario == 4) 
                $query->whereIn('_id', $user->unidades_pertenecientes);
        }
        else if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        return $query;
    }
}
