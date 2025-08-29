<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Conductor;
use App\Recorrido;
use App\Despacho;
use App\Unidad;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use DateInterval;
use Auth;
use App\PuntoControl;
use App\TipoUsuario;
use Carbon\Carbon;
use Validator;

class DespachoApiController extends Controller
{
    public function getDespachosSocios(Request $request){
        $validator = Validator::make($request->all(), [
            'unidad_id' => 'required',
            'desde' => 'required',
            'hasta' => 'required'
    	]);
    	if ($validator->fails())
    		return response()->json(['error' => true, 'messages' => $validator->errors()]);
    	else {
            set_time_limit(0);

            $desde = new Carbon($request->input('desde') . '00:00:00');
            $hasta = new Carbon($request->input('hasta') . '23:59:59');
            date_sub($desde, date_interval_create_from_date_string('5 hours'));
            date_sub($hasta, date_interval_create_from_date_string('5 hours'));
            $unidad_id=$request->input('unidad_id');
        
            $despachos = array();
            
            $despachosTemp=Despacho::with('unidad','conductor', 'ruta.rutapadre')->where('unidad_id',trim($unidad_id))
            ->where('estado','!=','I')
            ->where('fecha', '>=', $desde)->where('fecha', '<=',$hasta)->orderBy('fecha','asc')->get();

            foreach ($despachosTemp as $despacho){

                $fin = $despacho->fecha;
                date_add($fin, date_interval_create_from_date_string('5 hours'));
                $despacho->fecha=$fin;
                $arrayPuntos = collect($despacho->puntos_control)->transform(function ($item) {
                    $item['descripcion'] =  $punto=PuntoControl::select('descripcion')->findOrFail( $item['id'])->descripcion;
                    return $item;
                });
                $despacho->puntos_controles = $arrayPuntos;
                array_push($despachos, $despacho);
            }
            
            return response()->json(['error' => false, 'despacho' => $despachos]);

        }
    }

    public function getPuntoControl(Request $request){
        $validator = Validator::make($request->all(), [
            'punto_id' => 'required'
    	]);
    	if ($validator->fails())
    		return response()->json(['error' => true, 'messages' => $validator->errors()]);
    	else {
            set_time_limit(0);

            $punto_id=$request->input('punto_id');
            
            // $punto=PuntoControl::findOrFail(trim($punto_id));
            $punto=PuntoControl::select('descripcion')->where('_id',new ObjectID(trim($punto_id)))->first();
            
            return response()->json($punto);

        }
    }
}
