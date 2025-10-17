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

    public static function determinar_sentido_unidad($lat, $lng, $puntoRetorno, $sentidoAnterior = 'i', $puntoInicio = null)
    {
        // ====================================================
        //  Validaciones básicas
        // ====================================================
        if (!$lat || !$lng || !$puntoRetorno || empty($puntoRetorno->tipo_mar)) {
            return $sentidoAnterior;
        }

        // ====================================================
        //  Verificar si ya está en regreso y llegó al punto de inicio
        // ====================================================
        if ($sentidoAnterior === 'r' && $puntoInicio) {
            $estaEnInicio = false;

            // Si el punto de inicio es radial
            if ($puntoInicio->tipo_mar === 1) {
                if (isset($puntoInicio->latitud, $puntoInicio->longitud, $puntoInicio->radio)) {
                    $distInicio = self::haversine_distance($lat, $lng, $puntoInicio->latitud, $puntoInicio->longitud);
                    if ($distInicio <= $puntoInicio->radio) {
                        $estaEnInicio = true;
                    }
                }
            }
            // Si el punto de inicio es poligonal
            elseif ($puntoInicio->tipo_mar === 2 && !empty($puntoInicio->poligono)) {
                $estaEnInicio = self::point_in_polygon($lat, $lng, $puntoInicio->poligono);
            }

            // Si ya llegó al punto de inicio → vuelve a IDA
            if ($estaEnInicio) {
                return 'i';
            }
        }

        // ====================================================
        //  Verificar si llega al punto de retorno
        // ====================================================
        $estaDentro = false;

        // Punto radial
        if ($puntoRetorno->tipo_mar === 1) {
            if (isset($puntoRetorno->latitud, $puntoRetorno->longitud, $puntoRetorno->radio)) {
                $distancia = self::haversine_distance($lat, $lng, $puntoRetorno->latitud, $puntoRetorno->longitud);
                if ($distancia <= $puntoRetorno->radio) {
                    $estaDentro = true;
                }
            }
        }
        // Punto poligonal
        elseif ($puntoRetorno->tipo_mar === 2 && !empty($puntoRetorno->poligono)) {
            $estaDentro = self::point_in_polygon($lat, $lng, $puntoRetorno->poligono);
        }

        // ====================================================
        //  Lógica de cambio de sentido
        // ====================================================
        if ($estaDentro && $sentidoAnterior === 'i') {
            return 'r'; // Cambia a regreso
        }

        // Mantener sentido actual
        return $sentidoAnterior;
    }

    public static function update_pto_control_despacho($ruta, $despacho)
    {
        // Convertir puntos_control a array si no lo es
        $puntos_control = is_array($despacho->puntos_control)
            ? $despacho->puntos_control
            : (array) $despacho->puntos_control;

        $punto_control_ruta = is_array($ruta->puntos_control)
            ? $ruta->puntos_control
            : (array) $ruta->puntos_control;

        if (empty($puntos_control) || empty($punto_control_ruta)) {
            return ['data' => $despacho];
        }

        // Crear índice por ID + índice desde la ruta
        $rutasPorId = [];
        foreach ($punto_control_ruta as $index => $puntoRuta) {
            $idRuta = isset($puntoRuta['id']) ? $puntoRuta['id'] : null;
            if ($idRuta) {
                // Clave única: id + índice
                $clave = $idRuta . '-' . $index;
                $rutasPorId[$clave] = $puntoRuta;
            }
        }

        // Actualizar los campos de los puntos del despacho
        foreach ($puntos_control as $index => &$puntoDespacho) {
            $idDespacho = isset($puntoDespacho['id']) ? $puntoDespacho['id'] : null;
            $claveDespacho = $idDespacho . '-' . $index;

            if ($idDespacho && isset($rutasPorId[$claveDespacho])) {
                $puntoRuta = $rutasPorId[$claveDespacho];
                $camposActualizar = ['calculo', 'redondeo', 'retorno', 'adelanto', 'atraso'];

                foreach ($camposActualizar as $campo) {
                    if (isset($puntoRuta[$campo])) {
                        $puntoDespacho[$campo] = $puntoRuta[$campo];
                    }
                }
            }
        }

        // Actualizar el objeto despacho
        $despacho->puntos_control = $puntos_control;
        return ['data' => $despacho];
    }



    static function haversine_distance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

  
     static  function point_in_polygon($lat, $lng, $polygon)
    {
        $inside = false;
        $x = $lat;
        $y = $lng;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];
            $intersect = (($yi > $y) != ($yj > $y)) &&
                         ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) $inside = !$inside;
        }
        return $inside;
    }

}