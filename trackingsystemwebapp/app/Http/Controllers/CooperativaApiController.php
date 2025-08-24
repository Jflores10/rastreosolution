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
}
