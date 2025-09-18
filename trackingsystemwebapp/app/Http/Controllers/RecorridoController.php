<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Despacho;
use App\PuntoControl;
use App\Cooperativa;
use Carbon\Carbon;
use App\PuntosRecorrido;
use App\Unidad; // Modelo donde está el IMEI
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RecorridoController extends Controller
{
    public function notify(Request $request)
    {
        $data = $request->all();

        // Validación de datos
        $validator = Validator::make($data, [
            'latitud'  => 'required|numeric',
            'longitud' => 'required|numeric',
            'imei'     => 'required|string'
        ]);

        if ($validator->fails()) return;

        $lat = $data['latitud'];
        $lon = $data['longitud'];
        $imei = $data['imei'];
        $fecha_actual = Carbon::now()->format('Y-m-d\TH:i:s.vP');

        // Buscar unidad
        $unidad = Unidad::where('imei', $imei)->first();
        if (!$unidad) return;

        $cooperativa = Cooperativa::find($unidad->cooperativa_id);
        if (!$cooperativa || !$cooperativa->distancia_haversine) return;

        $puntos = PuntoControl::where('cooperativa_id', $cooperativa->_id)->get();

        // Traer últimos registros de todos los PDIs de la unidad
        $ultimos = PuntosRecorrido::where('unidad_id', $unidad->_id)
            ->orderBy('fecha', 'desc')
            ->get()
            ->keyBy('pto_control_id');

        $distancias = [];
        foreach ($puntos as $punto) {
            $distancias[$punto->_id] = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);
        }

        // Ordenar PDIs por distancia ascendente
        asort($distancias);

        foreach ($distancias as $punto_id => $distancia) {
            $punto = $puntos->firstWhere('_id', $punto_id);
            $inside = $distancia <= $punto->radio;
            $ultimo = $ultimos->get($punto->_id);

            if ($inside) {
                // Entrada si último fue salida o no existe
                if (!$ultimo || $ultimo->tipo === 'S') {
                    PuntosRecorrido::create([
                        'unidad_id'     => $unidad->_id,
                        'pto_control_id'=> $punto->_id,
                        'latitud'       => $lat,
                        'longitud'      => $lon,
                        'fecha'         => $fecha_actual,
                        'tipo'          => 'E'
                    ]);
                    Log::info("Entrada al PDI {$punto->_id} por IMEI {$imei}", [
                        'fecha' => $fecha_actual,
                        'latitud' => $lat,
                        'longitud'=> $lon
                    ]);
                }
            } else {
                // Salida si último fue entrada y realmente salió
                if ($ultimo && $ultimo->tipo === 'E') {
                    PuntosRecorrido::create([
                        'unidad_id'     => $unidad->_id,
                        'pto_control_id'=> $punto->_id,
                        'latitud'       => $lat,
                        'longitud'      => $lon,
                        'fecha'         => $fecha_actual,
                        'tipo'          => 'S'
                    ]);
                    Log::info("Salida del PDI {$punto->_id} por IMEI {$imei}", [
                        'fecha' => $fecha_actual,
                        'latitud' => $lat,
                        'longitud'=> $lon
                    ]);
                }
            }
        }

        Log::info("Procesamiento avanzado completado para IMEI {$imei}");
    }


    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000; // metros
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}
