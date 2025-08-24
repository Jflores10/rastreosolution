<?php

namespace App;

use Moloquent;
use Auth;
class Ruta extends Moloquent
{
    protected $fillable = [
        'descripcion','puntos_control' ,'creador_id', 'modificador_id', 'estado','cooperativa_id','unidad_id',
        'recorrido', 'estado_exportacion', 'fecha_exportacion','tipo_ruta','ruta_padre','ruta_atm',"color"
    ];

    protected $appends = [
        'tipo', 'puntos'
    ];
    public function getPuntosAttribute() {
        $array = array();
        $puntosControl = $this->puntos_control;
        if ($puntosControl !== null && is_array($puntosControl)) {
            foreach ($puntosControl as $puntoControl)
                array_push($array, [
                    'puntoControl' => PuntoControl::find($puntoControl['id']),
                    'adelanto' => $puntoControl['adelanto'],
                    'atraso' => $puntoControl['atraso'],
                    'secuencia' => $puntoControl['secuencia'],
                    'tiempo_llegada' => $puntoControl['tiempo_llegada']
                ]);
        }
        return $array;
    }

    public function getTipoAttribute() {
        switch ($this->tipo_ruta) {
            case 'P':
                return 'Padre';
            case 'H':
                return 'Hija';
            case 'I':
                return 'Independiente';
            case 'C':
                return 'Cooperativa';
            default :
                return 'Normal';
        }
    }
    
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function punto_control()
    {
        return $this->belongsTo('App\PuntoControl');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
    
    public function rutapadre(){
        return $this->belongsTo('App\Ruta', 'ruta_padre');
    }

    public function hijas() {
        return $this->hasMany('App\Ruta', 'ruta_padre');
    }
    
    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }

    public function scopePermitida($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1)
            $query->where('cooperativa_id', $user->cooperativa_id);
        else if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        return $query;
    }

    public function cronogramas() {
        return $this->embedsMany('App\CronogramaRuta');
    }
}