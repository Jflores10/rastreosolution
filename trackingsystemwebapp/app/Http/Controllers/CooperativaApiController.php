<?php

namespace App\Http\Controllers;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Cooperativa;
use App\PuntoControl;
use App\Ruta;
use Validator;
use MongoDB\BSON\ObjectID;
class CooperativaApiController extends Controller
{
    public function index(Request $request)
    {
    	set_time_limit(0);  
		$validator = Validator::make($request->all(), [
			'tipo_usuario' => 'required'
		]);
		
		$tipo_usuario=$request->input('tipo_usuario');
		$cooperativa_id=$request->input('cooperativa_id');

    	if (trim($tipo_usuario) == '1')
    		$cooperativas = Cooperativa::orderBy('descripcion', 'asc')->get();
    	else 
			$cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('_id', new ObjectID($cooperativa_id))->get();
			
    	
    	return response()->json($cooperativas);
	}
	
			
	public function getRutas(Request $request){
		set_time_limit(0);  
		$validator = Validator::make($request->all(), [
			'cooperativa_id' => 'required'
		]);
		$cooperativa_id=$request->input('cooperativa_id');
		$rutas=Ruta::where('estado','A')->whereIn('tipo_ruta',['P', 'I'])->where('cooperativa_id',trim($cooperativa_id))
		->select('_id','puntos_control','descripcion')->get();

		foreach($rutas as $ruta){
			$puntosControl = array();
			$variable=$ruta->puntos_control;
			if(is_array($variable)){
				foreach ($ruta->puntos_control as $puntoControl)
				{
					array_push($puntosControl, PuntoControl::find($puntoControl['id']));
				}
			}
			$ruta->puntos = $puntosControl;    		
		}
		
		return response()->json($rutas);
	}

	public function getCoordenadas(Request $request){
		set_time_limit(0);  
		$validator = Validator::make($request->all(), [
			'ruta_id' => 'required'
		]);
		$ruta_id=$request->input('ruta_id');

		$ruta=Ruta::where('_id',new ObjectID(trim($ruta_id)))
		->select('_id','recorrido')->first();

		return response()->json($ruta);

	}

	/**
	 * v2: cooperativas con validación de cooperativa_id cuando aplica.
	 */
	public function index_v2(Request $request)
	{
		set_time_limit(0);

		$validator = Validator::make($request->all(), [
			'tipo_usuario' => 'required',
			'cooperativa_id' => 'string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'error' => true,
				'api_version' => 'v2',
				'messages' => $validator->errors(),
			], 422);
		}

		$tipo_usuario = trim($request->input('tipo_usuario'));
		$cooperativa_id = $request->input('cooperativa_id');

		if ($tipo_usuario === '1') {
			$cooperativas = Cooperativa::orderBy('descripcion', 'asc')->get();
		} else {
			if ($cooperativa_id === null || trim($cooperativa_id) === '') {
				return response()->json([
					'error' => true,
					'api_version' => 'v2',
					'message' => 'cooperativa_id es obligatorio para este tipo de usuario.',
				], 422);
			}
			try {
				$cooperativas = Cooperativa::orderBy('descripcion', 'asc')
					->where('_id', new ObjectID(trim($cooperativa_id)))->get();
			} catch (\Exception $e) {
				return response()->json([
					'error' => true,
					'api_version' => 'v2',
					'message' => 'cooperativa_id inválido.',
				], 400);
			}
		}

		return response()->json([
			'error' => false,
			'api_version' => 'v2',
			'data' => $cooperativas,
		], 200);
	}

	public function getRutas_v2(Request $request)
	{
		set_time_limit(0);

		$validator = Validator::make($request->all(), [
			'cooperativa_id' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'error' => true,
				'api_version' => 'v2',
				'messages' => $validator->errors(),
			], 422);
		}

		$cooperativa_id = $request->input('cooperativa_id');
		$rutas = Ruta::where('estado', 'A')->whereIn('tipo_ruta', ['P', 'I'])
			->where('cooperativa_id', trim($cooperativa_id))
			->select('_id', 'puntos_control', 'descripcion')->get();

		foreach ($rutas as $ruta) {
			$puntosControl = array();
			$variable = $ruta->puntos_control;
			if (is_array($variable)) {
				foreach ($ruta->puntos_control as $puntoControl) {
					$pc = PuntoControl::find($puntoControl['id']);
					if ($pc !== null) {
						$puntosControl[] = $pc;
					}
				}
			}
			$ruta->puntos = $puntosControl;
		}

		return response()->json([
			'error' => false,
			'api_version' => 'v2',
			'rutas' => $rutas,
		], 200);
	}

	public function getCoordenadas_v2(Request $request)
	{
		set_time_limit(0);

		$validator = Validator::make($request->all(), [
			'ruta_id' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'error' => true,
				'api_version' => 'v2',
				'messages' => $validator->errors(),
			], 422);
		}

		$ruta_id = $request->input('ruta_id');

		try {
			$ruta = Ruta::where('_id', new ObjectID(trim($ruta_id)))
				->select('_id', 'recorrido')->first();
		} catch (\Exception $e) {
			return response()->json([
				'error' => true,
				'api_version' => 'v2',
				'message' => 'ruta_id inválido.',
			], 400);
		}

		if ($ruta === null) {
			return response()->json([
				'error' => true,
				'api_version' => 'v2',
				'message' => 'Ruta no encontrada.',
			], 404);
		}

		return response()->json([
			'error' => false,
			'api_version' => 'v2',
			'data' => $ruta,
		], 200);
	}
}
