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
use App\Helper\FunctionsHelper;
use MongoDB\BSON\ObjectID;

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

        // Traer puntos de control de la cooperativa
        $puntos = PuntoControl::where('cooperativa_id', $cooperativa->_id)->get();
        if ($puntos->isEmpty()) {
            return;
        }

        $fecha_actual = Carbon::now();
        $tiempo_min_entre_eventos = 60; // segundos mínimos entre eventos

        foreach ($puntos as $punto) {
            // Usar radio configurado o fallback al de la cooperativa
            $radioBase = ($punto->radio && $punto->radio > 0) ? $punto->radio : 25;


            // Definir radio de entrada y salida (histeresis)
            $radioEntrada = $radioBase;        // para marcar "E"
            $radioSalida  = $radioBase + 20;   // debe alejarse un poco más para marcar "S"

            $distancia = $this->haversine($lat, $lon, $punto->latitud, $punto->longitud);

            // Último registro del bus en este punto
            $ultimo = PuntosRecorrido::where('unidad_id', $unidad->_id)
                ->where('pto_control_id', $punto->_id)
                ->orderBy('fecha', 'desc')
                ->first();

            $estadoAnterior = $ultimo ? $ultimo->tipo : 'S'; // por defecto "S" (fuera)
            $tiempoDesdeUltimo = $ultimo ? $fecha_actual->diffInSeconds($ultimo->fecha) : null;

            // Determinar nuevo estado con tolerancia
            $nuevoEstado = $estadoAnterior; // por defecto se mantiene
            if ($estadoAnterior == 'S' && $distancia <= $radioEntrada) {
                $nuevoEstado = 'E';
            } elseif ($estadoAnterior == 'E' && $distancia > $radioSalida) {
                $nuevoEstado = 'S';
            }

            // Guardar solo si cambió el estado y respetamos el tiempo mínimo
            if ($nuevoEstado !== $estadoAnterior) {
                if (!$ultimo || $tiempoDesdeUltimo === null || $tiempoDesdeUltimo >= $tiempo_min_entre_eventos) {
                    PuntosRecorrido::create([
                        'unidad_id'      => $unidad->_id,
                        'pto_control_id' => $punto->_id,
                        'latitud'        => $lat,
                        'longitud'       => $lon,
                        'fecha'          => new UTCDateTime($fecha_actual->timestamp * 1000),
                        'tipo'           => $nuevoEstado
                    ]);

                    \Log::info("Cambio detectado: Unidad={$unidad->_id}, Punto={$punto->_id}, Distancia={$distancia}m, Estado: {$estadoAnterior} => {$nuevoEstado}");
                }
            }
        }
    }

    public function update_sentido(Request $request)
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
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        $punto_retorno=false;
        $punto_inicio=false;
        $sentido=false;

        $ruta_actual=Despacho::orderBy('fecha', 'asc')->where('estado','P')->where('unidad_id',$unidad['_id'])
            ->where('fecha','>=',$desde)
            ->where('fecha','<=',$hasta)->first();
        if(!$ruta_actual){
            return;
        }

        foreach ($ruta_actual->ruta->puntos_control as $punto) {
            if($punto['retorno']==="1"){
                $punto_retorno = PuntoControl::where("_id",new ObjectID($punto['id']))->first();
            }
            if($punto['secuencia']==="1"){
                $punto_inicio = PuntoControl::where("_id",new ObjectID($punto['id']))->first();
            }
        }

        $sentidoActual = $unidad['sentido'] ?? 'i';
        $sentido = FunctionsHelper::determinar_sentido_unidad(
            $unidad['latitud'],
            $unidad['longitud'],
            $punto_retorno,
            $sentidoActual,
            $punto_inicio
        );
               
        if ($sentido && $unidad['sentido'] != $sentido) {
            $unidad->update(['sentido' => $sentido]);
        }
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000; // Radio de la tierra en metros
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat/2) * sin($dLat/2) +
            cos($lat1) * cos($lat2) *
            sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $R * $c;
    }
}
