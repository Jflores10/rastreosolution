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
            'imei'     => 'required|string',
            'fecha'    => 'nullable|date' // opcional si tu GPS envía timestamp
        ]);

        if ($validator->fails()) {
            return;
        }

        $lat = $data['latitud'];
        $lon = $data['longitud'];
        $imei = $data['imei'];
        $fecha_actual = $data['fecha'] ?? Carbon::now()->format('Y-m-d\TH:i:s.vP');

        // Buscar la unidad por IMEI
        $unidad = Unidad::where('imei', $imei)->first();
        if (!$unidad) return;

        // Buscar la cooperativa
        $cooperativa = Cooperativa::find($unidad->cooperativa_id);
        if (!$cooperativa || !$cooperativa->distancia_haversine) return;

        // Traer todos los puntos de control de la cooperativa
        $puntos = PuntoControl::where('cooperativa_id', $cooperativa->_id)->get();

        // Traer últimos registros de la unidad (una sola consulta)
        $ultimos = PuntosRecorrido::where('unidad_id', $unidad->_id)
                    ->orderBy('fecha', 'desc')
                    ->get();

        foreach ($puntos as $punto) {
            // Distancia desde la unidad al punto de control
            $distancia = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);
            $inside = $distancia <= $punto->radio;

            // Buscar el último registro de este punto manualmente (sin firstWhere)
            $ultimo = null;
            foreach ($ultimos as $item) {
                if ($item->pto_control_id == $punto->_id) {
                    $ultimo = $item;
                    break;
                }
            }

            if ($inside) {
                // Registrar entrada si no hay último registro o si el último fue salida
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
                // Registrar salida si el último fue entrada
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
