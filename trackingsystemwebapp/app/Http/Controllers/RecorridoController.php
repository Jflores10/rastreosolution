<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Despacho;
use App\PuntoControl;
use App\Cooperativa;
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
            Log::warning("Datos GPS inválidos", ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 422);
        }

        $lat = $data['latitud'];
        $lon = $data['longitud'];
        $imei = $data['imei'];

        // Buscar la unidad por IMEI
        $unidad = Unidad::where('imei', $imei)->first();
        if (!$unidad) {
            Log::warning("Unidad no encontrada para IMEI {$imei}");
            return response()->json(['error' => 'Unidad no encontrada'], 404);
        }

        $unidad_id = $unidad->_id ?? $unidad->id; // según tu esquema de MongoDB

        $cooperativa = Cooperativa::find($unidad->cooperativa_id);
        if (!$cooperativa || !$cooperativa->distancia_haversine) {
            Log::info("Cooperativa sin validación de haversine para IMEI {$imei}");
            return response()->json(['message' => 'Cooperativa sin validación de distancia'], 200);
        }

        Log::info("Recorrido recibido para IMEI {$imei} (unidad_id {$unidad_id})", [
            'latitud' => $lat,
            'longitud'=> $lon
        ]);

        // Buscar despacho activo
        $despacho = Despacho::where('unidad_id', $unidad_id)
            ->where('estado', 'P')
            ->first();

        if (!$despacho) {
            Log::info("No hay despacho activo para IMEI {$imei}");
            return response()->json(['message' => 'No hay despacho activo'], 200);
        }

        $puntos = $despacho->puntos_control;

        foreach ($puntos as &$punto) {
            $p = PuntoControl::where('_id', $punto['id'])->first();
            if ($p) {
                $distancia = $this->haversine($lat, $lon, $p->latitud, $p->longitud);

                $inside = $distancia <= $p->radio;
                $punto['fecha_ingreso_gps'] = now();

                if ($inside && empty($punto['fecha_entrada'])) {
                    $punto['fecha_entrada'] = now();
                }

                if (!$inside && !empty($punto['fecha_entrada']) && empty($punto['fecha_salida'])) {
                    $punto['fecha_salida'] = now();
                }
            }
        }

        $despacho->puntos_control = $puntos;
        $despacho->save();

        Log::info("Puntos de control actualizados para despacho {$despacho->id}", [
            'imei' => $imei
        ]);

        return response()->json(['message' => 'Datos procesados correctamente'], 200);
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
