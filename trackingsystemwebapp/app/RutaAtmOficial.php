<?php

namespace App;

use Moloquent;
use App\PuntoControlAtmOficial;
use Auth;
class RutaAtmOficial extends Moloquent
{
    protected $collection = 'RutaAtmOficial';
    protected $fillable = [
        'descripcion','puntos_control' , 'estado','cooperativa_id',
        'recorrido',  'codigo','fecha_importado'
    ];

    protected $appends = [
        'puntos'
    ];

    public function getPuntosAttribute() {
        $array = array();
        $puntosControl = $this->puntos_control;
        if ($puntosControl !== null && is_array($puntosControl)) {
            foreach ($puntosControl as $puntoControl)
                array_push($array, [
                    'puntoControl' => PuntoControlAtmOficial::find($puntoControl['id']),
                    'secuencia' => $puntoControl['secuencia'],
                    'tiempo_llegada' => $puntoControl['tiempo_llegada']
                ]);
        }
        return $array;
    }
    public function punto_control()
    {
        return $this->belongsTo('App\PuntoControlAtmOficial');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }

    public function scopePermitida($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        else if ($tipo_usuario != 1)
            $query->where('cooperativa_id', $user->cooperativa_id);
        return $query;
    }

    protected $dates = [
        'fecha_importado'
    ];
}