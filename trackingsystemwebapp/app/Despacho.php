<?php

namespace App;

use Moloquent as Model;
use Carbon\Carbon;

class Despacho extends Model
{
    protected $fillable = [
        'ruta_id', 'unidad_id', 'conductor_id', 'fecha', 'estado', 'contador_inicial',
        'contador_final', 'puntos_control', 'contador_ayer', 'marcas', 'multa', 'salida', 'error_ATM',
        'corte_tubo', 'creador_id', 'modificador_id', 'fecha_exportacion', 'estado_exportacion', 'coord_corte_tubo',
        'fecha_exportado', 'motivo_cancelar'
    ];

    public function getPuntosControlAttribute($value)
    {
        $array = array();
        if ($value && is_array($value)) {
            foreach ($value as $p) {
                $punto = PuntoControl::find($p['id']);
                $p['original_descripcion'] = $punto->descripcion;
                if (isset($punto)) {
                    if (!isset($punto->codigo) || $punto->codigo == null) {
                        $trimed = trim($punto->descripcion);
                        if (strlen($trimed) >= 2)
                            $descripcion = substr($trimed, 0, 2);
                        else
                            $descripcion = $trimed;
                        $p['descripcion'] = strtoupper($descripcion);
                    } else {
                        $p['descripcion'] = strtoupper($punto->codigo);
                    }
                } else
                    $p['descripcion'] = '-';
                array_push($array, $p);
            }
        }
        return $array;
    }

    public function getPuntosControlUltimoAttribute($value)
    {
        $array = array();
        foreach ($value as $p) {
            $punto = PuntoControl::find($p['id']);
            if (isset($punto)) {
                if (!isset($punto->codigo) || $punto->codigo == null) {
                    $trimed = trim($punto->descripcion);
                    if (strlen($trimed) >= 2)
                        $descripcion = substr($trimed, 0, 2);
                    else
                        $descripcion = $trimed;
                    $p['descripcion'] = strtoupper($descripcion);
                } else {
                    $p['descripcion'] = strtoupper($punto->codigo);
                }
            } else
                $p['descripcion'] = '-';
            array_push($array, $p);
        }
        return $array;
    }

    public function getDisplayFechaAsignacionAttribute()
    {
        $fecha = $this->fecha;
        if ($fecha) {
            date_add($fecha, date_interval_create_from_date_string('5 hours'));
            return $fecha;
        }
        return null;
    }

    public function getDisplayFechaSalidaAttribute()
    {
        $puntosControl = $this->puntos_control;
        $fecha = null;
        if (isset($puntosControl) && count($puntosControl) > 0) {
            $ultimoPunto = null;
            foreach (array_reverse($puntosControl) as $punto) {
                if (isset($punto['marca']) && $punto['marca']) {
                    $ultimoPunto = $punto;
                    break;
                }
            }
            if (isset($ultimoPunto['marca']) && $ultimoPunto['marca']) {
                $fecha = new Carbon($ultimoPunto['marca']);
            }
        }
        else {
            $fecha = $this->salida;
            if (isset($fecha)) {
                date_add($fecha, date_interval_create_from_date_string('5 hours'));
            }            
        }
        return $fecha;
    }

    public function ruta()
    {
        return $this->belongsTo('App\Ruta');
    }
    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }
    public function conductor()
    {
        return $this->belongsTo('App\Conductor');
    }
    public function creador()
    {
        return $this->belongsTo('App\User');
    }

    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    protected $dates = [
        'fecha', 'salida', 'fecha_exportacion'
    ];

    protected $appends = [
        'display_fecha_asignacion', 'display_fecha_salida'
    ];
}
