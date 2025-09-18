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
        // Usar Validator en lugar de $request->validate()
        $data = $request->all();

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

        // Buscar la unidad por IMEI
        $unidad = Unidad::where('imei', $imei)->first();
        if (!$unidad) {
            return;
        }

        $cooperativa = Cooperativa::find($unidad->cooperativa_id);
        if (!$cooperativa || !$cooperativa->distancia_haversine) {
            //Log::info("Cooperativa sin validación de haversine para IMEI {$imei}");
            return;
        }
        /*
        Log::info("Recorrido recibido para IMEI {$imei} (unidad_id {$unidad_id})", [
            'latitud' => $lat,
            'longitud'=> $lon
        ]);
        */

        $puntos = PuntoControl::where('cooperativa_id', $cooperativa->_id)->get();

        foreach ($puntos as &$punto) {
            $distancia = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);
            $inside = $distancia <= $punto->radio;
            $fecha_actual = Carbon::now()->format('Y-m-d\TH:i:s.vP');
            $ultimo = PuntosRecorrido::where('unidad_id', $unidad->_id)
                ->where('pto_control_id', $punto->_id)
                ->latest()
                ->first();

            if ($inside) {
                if (!$ultimo || $ultimo->tipo == 'S') {
                    // Registrar entrada solo si no hay último registro
                    // o si el último fue una salida
                    PuntosRecorrido::create([
                        'unidad_id'        => $unidad->_id,
                        'pto_control_id' => $punto->_id,
                        'latitud'          => $lat,
                        'longitud'         => $lon,
                        'fecha'            => $fecha_actual,
                        'tipo'             => 'E'
                    ]);
                    Log::info("Entrada al PDI {$punto->_id} por IMEI {$imei}", [
                        'fecha'    => $fecha_actual,
                        'latitud'  => $lat,
                        'longitud' => $lon
                    ]);
                }
            } else {
                if ($ultimo && $ultimo->tipo == 'E') {
                    // Registrar salida solo si el último registro fue entrada
                    PuntosRecorrido::create([
                        'unidad_id'        => $unidad->_id,
                        'pto_control_id' => $punto->_id,
                        'latitud'          => $lat,
                        'longitud'         => $lon,
                        'fecha'            => $fecha_actual,
                        'tipo'             => 'S'
                    ]);
                    Log::info("Salida del PDI {$punto->_id} por IMEI {$imei}", [
                        'fecha'    => $fecha_actual,
                        'latitud'  => $lat,
                        'longitud' => $lon
                    ]);
                }
            }

        }

        /*
        Log::info("Puntos de control actualizados para despacho {$despacho->id}", [
            'imei' => $imei
        ]);
        */

        //return response()->json(['message' => 'Datos procesados correctamente'], 200);
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
