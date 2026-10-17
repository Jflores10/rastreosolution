<?php

namespace App;

use Moloquent;

/** Km recorridos por una unidad en un día cerrado (hora local America/Guayaquil). */
class KmDiarioHistorial extends Moloquent
{
    protected $collection = 'km_diario_historial';

    protected $fillable = [
        'unidad_id', 'imei', 'fecha', 'mileage_inicio', 'mileage_fin', 'odometro_inicio', 'odometro_fin', 'km_recorridos',
        'seg_encendido', 'seg_apagado'
    ];

    protected $casts = [
        'mileage_inicio' => 'float',
        'mileage_fin' => 'float',
        'odometro_inicio' => 'float',
        'odometro_fin' => 'float',
        'km_recorridos' => 'float',
        'seg_encendido' => 'integer',
        'seg_apagado' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }
}
