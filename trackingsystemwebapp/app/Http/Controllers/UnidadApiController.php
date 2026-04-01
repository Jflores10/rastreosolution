<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Unidad;
use Validator;
use App\Recorrido;
use App\Despacho;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use App\User;
class UnidadApiController extends Controller
{
    public function index(Request $request)
    {
    	set_time_limit(0);  
		$validator = Validator::make($request->all(), [
			'usuario_id' => 'required',
			'cooperativa_id'=>'required'
		]);
		
		$user_id=$request->input('usuario_id');
		$cooperativa_id=$request->input('cooperativa_id');
    	$user = User::findOrFail(trim($user_id));
		if($user->estado=='A'){
			$tipo = $user->tipo_usuario->valor;
			$desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
			$hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
				
			
			if ($tipo == '1')
				$unidades = Unidad::with('cooperativa', 'tipo_unidad')->where('estado','A')->get();
			else if ($tipo == '4')
				$unidades = Unidad::with('cooperativa', 'tipo_unidad')->where('estado','A')->whereIn('_id', $user->unidades_pertenecientes)->get();
			// else 
			// 	$unidades = Unidad::with('cooperativa', 'tipo_unidad')->where('estado','A')->where('cooperativa_id',trim($cooperativa_id))->get();
			
			$rutaunidad=array();
			$array = array();
			foreach($unidades as $unidad)
			{
				$ruta_actual=Despacho::orderBy('fecha', 'asc')->where('estado','P')->where('unidad_id',$unidad['_id'])
						->where('fecha','>=',$desde)
						->where('fecha','<=',$hasta)->first();
						
				$ruta='';
				$ruta_fecha='';
				$ruta_conductor='';

				if(isset($ruta_actual)){
					$ruta=$ruta_actual->ruta->descripcion;
					$ruta_fecha=$ruta_actual->fecha;
					date_add($ruta_fecha, date_interval_create_from_date_string('5 hours'));
					$ruta_fecha=$ruta_fecha->format('H:i');
					$ruta_conductor=$ruta_actual->conductor->nombre;
				}
				array_push($rutaunidad,["unidad_id"=>$unidad["_id"],"ruta_actual"=>$ruta,"ruta_fecha"=>$ruta_fecha,"ruta_conductor"=>$ruta_conductor]);
			}

			return response()->json(['unidades' => $unidades,'rutasUnidad'=>$rutaunidad]);
		}else{
			abort(500);
		}
    	// return response()->json($unidades);
	}
	
    public function obtenerHistorial(Request $request, $id)
    {
    	$validator = Validator::make($request->all(), [
    		'desde' => 'required|date',
    		'hasta' => 'required|date|after:desde'
    	]);
    	if ($validator->fails())
    		return response()->json(['error' => true, 'messages' => $validator->errors()]);
    	else 
    	{
    		$unidadId = new ObjectID($id);
    		$desde = new Carbon($request->input('desde'));
    		$hasta = new Carbon($request->input('hasta'));
    		$desde->addHours(5);
    		$hasta->addHours(5);
    		$hasta = new Carbon($request->input('hasta'));
    		$recorridos = Recorrido::where('unidad_id', $unidadId)->where('fecha_gps', '>=', $desde)->where('fecha_gps', '<=', $hasta)->get();
    		return response()->json(['error' => false, 'recorridos' => $recorridos]);
    	}
    }

    /**
     * v2: listado de unidades con respuesta homogénea y validación explícita.
     */
    public function index_v2(Request $request)
    {
        set_time_limit(0);
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required',
            'cooperativa_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $user_id = trim($request->input('usuario_id'));
        $cooperativa_id = $request->input('cooperativa_id');

        $user = User::findOrFail($user_id);

        if ($user->estado !== 'A') {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'Usuario inactivo.',
            ], 403);
        }

        $tipo = $user->tipo_usuario->valor;
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        $unidades = [];
        if ($tipo == '1') {
            $unidades = Unidad::with('cooperativa', 'tipo_unidad')->where('estado', 'A')->get();
        } elseif ($tipo == '4') {
            $unidades_pertenecientes = is_array($user->unidades_pertenecientes) ? $user->unidades_pertenecientes : [];
            $unidades = Unidad::with('cooperativa', 'tipo_unidad')->where('estado', 'A')
                ->whereIn('_id', $unidades_pertenecientes)->get();
        } else {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'Tipo de usuario no permitido para este recurso.',
            ], 403);
        }

        $rutaunidad = array();
        foreach ($unidades as $unidad) {
            $ruta_actual = Despacho::orderBy('fecha', 'asc')->where('estado', 'P')->where('unidad_id', $unidad['_id'])
                ->where('fecha', '>=', $desde)
                ->where('fecha', '<=', $hasta)->first();

            $ruta = '';
            $ruta_fecha = '';
            $ruta_conductor = '';

            if (isset($ruta_actual)) {
                $ruta = $ruta_actual->ruta->descripcion;
                $ruta_fecha = $ruta_actual->fecha;
                date_add($ruta_fecha, date_interval_create_from_date_string('5 hours'));
                $ruta_fecha = $ruta_fecha->format('H:i');
                $ruta_conductor = $ruta_actual->conductor->nombre;
            }
            $rutaunidad[] = array(
                'unidad_id' => $unidad['_id'],
                'ruta_actual' => $ruta,
                'ruta_fecha' => $ruta_fecha,
                'ruta_conductor' => $ruta_conductor,
            );
        }

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'unidades' => $unidades,
            'rutasUnidad' => $rutaunidad,
        ], 200);
    }

    /**
     * v2: historial corrigiendo zona horaria (sin reasignar $hasta tras addHours).
     */
    public function obtenerHistorial_v2(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'desde' => 'required|date',
            'hasta' => 'required|date|after:desde',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $unidadId = new ObjectID($id);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'Identificador de unidad inválido.',
            ], 400);
        }

        $desde = (new Carbon($request->input('desde')))->copy()->addHours(5);
        $hasta = (new Carbon($request->input('hasta')))->copy()->addHours(5);

        $recorridos = Recorrido::where('unidad_id', $unidadId)
            ->where('fecha_gps', '>=', $desde)
            ->where('fecha_gps', '<=', $hasta)
            ->get();

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'recorridos' => $recorridos,
        ], 200);
    }
}
