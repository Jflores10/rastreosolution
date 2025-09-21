<?php

namespace App\Helper;

use App\PuntosRecorrido;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
class FunctionsHelper
{
    public static function diferenciaPuntoRecorrido($pto_control, $unidad_id, $fecha_inicio, $fecha_fin, $index)
    {

        $fecha_inicio_str = Carbon::parse($fecha_inicio, 'America/Guayaquil')->format('Y-m-d\TH:i:s.vP');
        $fecha_fin_str    = Carbon::parse($fecha_fin, 'America/Guayaquil')->format('Y-m-d\TH:i:s.vP');
        $tiempoEsperado = Carbon::instance($pto_control['tiempo_esperado']->toDateTime())
        ->addHours(5) // este ajuste lo estás aplicando en tu código
        ->setTimezone('America/Guayaquil');

        // Buscar el punto recorrido que cumpla condiciones
        $puntos = PuntosRecorrido::where('unidad_id', $unidad_id)
            ->where('pto_control_id', $pto_control['id'])
            ->whereBetween('fecha', [$fecha_inicio_str, $fecha_fin_str]);
        
        if ($index != 0) {
            $query->where('tipo', 'E');
        }

        $puntos = $query->get();

        if ($puntos->isEmpty()) {
            return null;
        }

        $punto = $puntos->sortBy(function ($item) use ($tiempoEsperado) {
        return abs(Carbon::parse($item->fecha)->diffInSeconds($tiempoEsperado, false));
        })->first();


        // Convertir fechas a Carbon
        $fechaRecorrido = Carbon::parse($punto->fecha);
        // Calcular diferencia en segundos
        $diferenciaSegundos =$tiempoEsperado->diffInSeconds($fechaRecorrido, false); // false = puede ser negativo
        // Determinar símbolo
        $simbolo = $diferenciaSegundos >= 0 ? 'p' : 'n';
        // Convertir segundos absolutos a H:i:s
        $tiempo = gmdate("H:i:s", abs($diferenciaSegundos));
        $diferenciaMinutos = abs($tiempoEsperado->diffInMinutes($fechaRecorrido, false));
        // Retornar objeto
        return (object) [
            'fecha'   => $fechaRecorrido,
            'tiempo'  => (($simbolo=='p')?'+':'-') ."". $tiempo,
            'minutos'   => $diferenciaMinutos,
            'estado' => $simbolo
            
        ];
    }

}