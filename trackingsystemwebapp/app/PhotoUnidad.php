<?php

namespace App;

use Carbon\Carbon;
use Moloquent as Model;

class PhotoUnidad extends Model
{
    protected $collection = 'photos_unidad';

    protected $fillable = array(
        'imei',
        'tipo',
        'tipo_evento',
        'fecha_gps',
        'photo_time',
        'photo_time_str',
        'latitud',
        'longitud',
        'imagen',
        'fecha',
        'js',
        'marcada',
        'fecha_marca',
        'num_img',
    );

    protected $attributes = array(
        'marcada' => false,
    );

    protected $dates = array(
        'fecha_gps',
        'photo_time',
        'fecha',
        'fecha_marca',
        'created_at',
        'updated_at',
    );

    /**
     * Rango del día actual (inicio/fin) para conteo diario de marcaciones.
     */
    public static function rangoFechaMarcaHoy()
    {
        return array(
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay(),
        );
    }

    /** Fotos marcadas hoy (marcada=true y fecha_marca en el día actual) por IMEI. */
    public static function contadorMarcadasHoyPorImei($imei)
    {
        $imei = trim((string) $imei);
        if ($imei === '') {
            return 0;
        }

        list($inicio, $fin) = static::rangoFechaMarcaHoy();

        return (int) static::where('imei', $imei)
            ->where('marcada', true)
            ->where('fecha_marca', '>=', $inicio)
            ->where('fecha_marca', '<=', $fin)
            ->count();
    }

    /**
     * Conteo diario por IMEI: [ 'imei1' => N, ... ]
     *
     * @param array $imeis
     * @return array
     */
    public static function contadoresMarcadasHoyPorImeis(array $imeis)
    {
        $imeis = array_values(array_unique(array_filter(array_map(function ($v) {
            return trim((string) $v);
        }, $imeis))));

        if (count($imeis) === 0) {
            return array();
        }

        list($inicio, $fin) = static::rangoFechaMarcaHoy();

        $filas = static::whereIn('imei', $imeis)
            ->where('marcada', true)
            ->where('fecha_marca', '>=', $inicio)
            ->where('fecha_marca', '<=', $fin)
            ->get(array('imei'));

        $conteos = array();
        foreach ($imeis as $imei) {
            $conteos[$imei] = 0;
        }
        foreach ($filas as $fila) {
            $k = trim((string) $fila->imei);
            if ($k === '' || !isset($conteos[$k])) {
                continue;
            }
            $conteos[$k]++;
        }

        return $conteos;
    }
}
