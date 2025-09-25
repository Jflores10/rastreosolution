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
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\Cache;
class RecorridoController extends Controller
{

    public function index()
    {
       return view('panel.reportes-unidades-alg',
        [
            'cooperativas' => Cooperativa::orderBy('descripcion')->where('estado', 'A')->get()
        ]
        );
    }

    public function listar(Request $request)
    {
        $unidad=$request->input('unidad_id');
        $fechai=$request->input('fecha_inicio');
        $fechaf=$request->input('fecha_fin');

        $fecha_inicio_str = Carbon::parse($fechai, 'America/Guayaquil')->format('Y-m-d\TH:i:s.vP');
        $fecha_fin_str    = Carbon::parse($fechaf, 'America/Guayaquil')->format('Y-m-d\TH:i:s.vP');

        $query = PuntosRecorrido::with('punto_control');
        if(!empty($unidad)){
            $query->where('unidad_id',$unidad);
        }
        if (!empty($fechai) && !empty($fechaf)) {
            $query->whereBetween('fecha', [$fecha_inicio_str, $fecha_fin_str]);
        }
        $cursor = $query->orderBy('fecha', 'asc')->paginate(50);

        return response()->json([
            'error'          => false,
            'data'           => $cursor->items(),
            'per_page'       => $cursor->perPage(),
            'current_page'   => $cursor->currentPage(),
            'last_page'      => $cursor->lastPage(),
            'next_page_url'  => $cursor->nextPageUrl(),
            'prev_page_url'  => $cursor->previousPageUrl(),
        ]);
    }


        
    public function notify(Request $request)
    {
        $data = $request->only(['latitud', 'longitud', 'imei']);

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
        if ($puntos->isEmpty()) {
            return;
        }

        $fecha_actual = Carbon::now();

        $margen = 3; // metros
        foreach ($puntos as $punto) {
            // Calcular distancia al punto (Haversine)
            $distancia = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);

            // Estado actual
            $estadoActual = ($distancia <= ($punto->radio + $margen)) ? 'E' : 'S';

            // Último registro de este punto para esta unidad
            $ultimo = PuntosRecorrido::where('unidad_id', $unidad->_id)
                ->where('pto_control_id', $punto->_id)
                ->orderBy('fecha', 'desc')
                ->first();

            $estadoAnterior = $ultimo ? $ultimo->tipo : 'S';

            // Evitar duplicados muy seguidos (ej. mismo evento en < 2 seg)
            if ($ultimo && $estadoActual === $estadoAnterior) {
                $segundos = $fecha_actual->diffInSeconds($ultimo->fecha->toDateTime());
                if ($segundos < 2) {
                    continue; // ignorar duplicado
                }
            }

            // Registrar solo si hubo cambio de estado
            if ($estadoActual !== $estadoAnterior) {
                $permanencia = null;

                // Si sale, calcular permanencia desde la última entrada
                if ($estadoActual === 'S' && $ultimo && $ultimo->tipo === 'E') {
                    $permanencia = $fecha_actual->diffInSeconds($ultimo->fecha->toDateTime());
                }

                PuntosRecorrido::create([
                    'unidad_id'      => $unidad->_id,
                    'pto_control_id' => $punto->_id,
                    'latitud'        => $lat,
                    'longitud'       => $lon,
                    'fecha'          => new UTCDateTime($fecha_actual->timestamp * 1000),
                    'tipo'           => $estadoActual,
                    'distancia'      => $distancia,
                    'permanencia'    => $permanencia
                ]);

                \Log::info("Unidad {$unidad->_id}, Punto {$punto->_id}, Dist={$distancia}m, Cambio={$estadoAnterior}→{$estadoActual}, Permanencia={$permanencia}");
            }
        }

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
