<?php

namespace App;

use Moloquent;
use Auth;
class Cooperativa extends Moloquent
{
    protected $fillable = [
        'descripcion','multa_tubo','estado' ,'creador_id', 'modificador_id', 'recorrido','cooperativa_id','punto_control_id',
        'taxis', 'ruc', 'despachos_atm', 'email','despachos_job','mascara',
        'importador_despachos', 'finalizacion_automatica',
        'redondear_tiempos_atraso', 'tolerancia_buffer_minutos'
    ];
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function rutas()
    {
    	return $this->hasMany('App\Ruta');
    }


    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }

    public function punto_control()
    {
        return $this->belongsTo('App\PuntoControl');
    }

    public  function fecha_inicial($fecha)
    {
        $date =new MongoDate(strtotime("2015-10-01 00:00:00"));
        return $date;

    }
    public function scopePermitida($query) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1)
            $query->where('_id', $user->cooperativa_id);
        return $query;
    }
    public function scopeActiva($query) {
        return $this->where('estado', 'A');
    }
} 
