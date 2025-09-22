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
       $tiempoEsperado_str = $tiempoEsperado->format('Y-m-d\TH:i:s.vP');

    // Construir condiciones comunes (aplicar tipo='E' cuando index != 0)
    $baseQuery = PuntosRecorrido::where('unidad_id', $unidad_id)
        ->where('pto_control_id', $pto_control['id']);
    if ($index != 0) {
        $baseQuery = $baseQuery->where('tipo', 'E');
    }

    //
    // 1) Buscar candidatos dentro del rango: el más cercano antes y el más cercano después
    //
    $antesEnRango = (clone $baseQuery)
        ->whereBetween('fecha', [$fecha_inicio_str, $fecha_fin_str])
        ->where('fecha', '<=', $tiempoEsperado_str)
        ->orderBy('fecha', 'desc')
        ->first();

    $despuesEnRango = (clone $baseQuery)
        ->whereBetween('fecha', [$fecha_inicio_str, $fecha_fin_str])
        ->where('fecha', '>=', $tiempoEsperado_str)
        ->orderBy('fecha', 'asc')
        ->first();

    // calcular diffs de candidatos en rango
    $candidatos = [];
    if ($antesEnRango) {
        $f = Carbon::parse($antesEnRango->fecha, 'America/Guayaquil');
        $candidatos[] = ['p' => $antesEnRango, 'diff' => abs($f->diffInSeconds($tiempoEsperado)), 'fecha' => $f, 'from' => 'before_in_range'];
    }
    if ($despuesEnRango) {
        $f = Carbon::parse($despuesEnRango->fecha, 'America/Guayaquil');
        $candidatos[] = ['p' => $despuesEnRango, 'diff' => abs($f->diffInSeconds($tiempoEsperado)), 'fecha' => $f, 'from' => 'after_in_range'];
    }

    //
    // 2) También obtener (si hace falta) candidatos fuera del rango: más cercano antes y más cercano después
    //    (esto nos sirve para cuando el verdadero más cercano está fuera del rango)
    //
    // Solo haremos estas consultas si no hay candidatos en rango o para comparar diffs.
    $antesGlobal = (clone $baseQuery)
        ->where('fecha', '<=', $tiempoEsperado_str)
        ->orderBy('fecha', 'desc')
        ->first();

    $despuesGlobal = (clone $baseQuery)
        ->where('fecha', '>=', $tiempoEsperado_str)
        ->orderBy('fecha', 'asc')
        ->first();

    if ($antesGlobal) {
        $f = Carbon::parse($antesGlobal->fecha, 'America/Guayaquil');
        $candidatos[] = ['p' => $antesGlobal, 'diff' => abs($f->diffInSeconds($tiempoEsperado)), 'fecha' => $f, 'from' => 'before_global'];
    }
    if ($despuesGlobal) {
        $f = Carbon::parse($despuesGlobal->fecha, 'America/Guayaquil');
        $candidatos[] = ['p' => $despuesGlobal, 'diff' => abs($f->diffInSeconds($tiempoEsperado)), 'fecha' => $f, 'from' => 'after_global'];
    }

    if (empty($candidatos)) {
        return null;
    }

    // Escoger el candidato con menor diff
    usort($candidatos, function ($a, $b) {
        return $a['diff'] <=> $b['diff'];
    });
    $best = $candidatos[0];

    $punto = $best['p'];
    $fechaRecorrido = $best['fecha']; // Carbon
    $diffSeconds = $best['diff'];
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