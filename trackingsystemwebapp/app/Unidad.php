<?php

namespace App;

use Moloquent;
use Auth;
class Unidad extends Moloquent
{
    protected $fillable = [
        'placa','descripcion','cooperativa_id','marca','modelo','serie','motor','tipo_unidad_id',
        'email_alarma','sistema_energizado','contador_cero_manual','estado','desconexion_sistema',
        'creador_id', 'modificador_id','contador_diario', 'contador_total', 'velocidad_actual','imei',
        'estado_movil','voltaje', 'bateria', 'atm', 'velocidad','control_velocidad','contador_inicial',
        'alerta_cortetubo','alerta_fecha_cortetubo','climatizada','rampa','mileage','sentido','vigilante','tipo_in1',
        'contador_img', 'contpdabierta', 'mileage_inicio_dia', 'km_diario', 'fecha_km_diario'
    ];

    // Zona horaria del día del km diario (fecha_km_diario es 'Y-m-d' en esta zona).
    const ZONA_KM_DIARIO = 'America/Guayaquil';

    protected $casts = [
        'mileage_inicio_dia' => 'float',
    ];

    protected $attributes = array(
        'contador_img' => 0,
        'contpdabierta' => 0,
    );

    public function scopePermitida($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1)
        {
            $query->where('cooperativa_id', $user->cooperativa_id);
            if ($tipo_usuario == 4 || $tipo_usuario == 5) 
                $query->whereIn('_id', $user->unidades_pertenecientes);
        }
        else if ($cooperativa != null && $cooperativa != 'none')
            $query->where('cooperativa_id', $cooperativa);
        return $query;
    }

    public function scopeActiva($query) {
        return $query->where('estado', 'A');
    }

    public static function fechaHoyKmDiario()
    {
        return \Carbon\Carbon::now(self::ZONA_KM_DIARIO)->toDateString();
    }

    // Km recorridos hoy. Si fecha_km_diario no es hoy (unidad sin tramas desde
    // medianoche y el reinicio diario aún no corrió), hoy lleva 0.
    public function getKmDiarioAttribute($value)
    {
        $fecha = isset($this->attributes['fecha_km_diario']) ? (string) $this->attributes['fecha_km_diario'] : null;
        if ($fecha !== self::fechaHoyKmDiario())
            return 0.0;
        return round((float) $value, 2);
    }

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

    public function tipo_unidad()
    {
        return $this->belongsTo('App\TipoUnidad');
    }

    public function recorrido()
    {
        return $this->hasOne('App\Recorrido');
    }
    public function despachos()
    {
    	return $this->hasMany('App\Despacho');
    }
    public function user() {
        return $this->hasOne('App\User');
    }
    
}
