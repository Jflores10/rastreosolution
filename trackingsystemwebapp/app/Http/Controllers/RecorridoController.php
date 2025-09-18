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

        if ($validator->fails()) {
            return;
        }

        $lat = $data['latitud'];
        $lon = $data['longitud'];
        $imei = $data['imei'];

        // Buscar la unidad
        $unidad = Unidad::where('imei', $imei)->first();
        if (!$unidad) {
            return;
        }

        $cooperativa = Cooperativa::find($unidad->cooperativa_id);
        if (!$cooperativa || !$cooperativa->distancia_haversine) {
            return;
        }

        // Traer todos los puntos de control de la cooperativa
        $puntos = PuntoControl::where('cooperativa_id', $cooperativa->_id)->get();

        $fecha_actual = Carbon::now()->format('Y-m-d\TH:i:s.vP');

        foreach ($puntos as $punto) {

            // Distancia desde la unidad al punto de control
            $distancia = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);
            $inside = $distancia <= $punto->radio;

            // Último registro de este punto de control
            $ultimo = PuntosRecorrido::where('unidad_id', $unidad->_id)
                ->where('pto_control_id', $punto->_id)
                ->latest('fecha')
                ->first();

            if ($inside) {
                // Registrar entrada solo si no hay último registro o el último fue salida
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
                // Registrar salida solo si el último registro fue entrada
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

        Log::info("Procesamiento completado para IMEI {$imei}");
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
