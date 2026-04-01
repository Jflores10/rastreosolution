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

    /**
     * v2: despachos con descripciones de puntos sin N+1 y respuesta uniforme.
     */
    public function getDespachosSocios_v2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unidad_id' => 'required',
            'desde' => 'required',
            'hasta' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        set_time_limit(0);

        $desde = new Carbon($request->input('desde') . '00:00:00');
        $hasta = new Carbon($request->input('hasta') . '23:59:59');
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');

        $despachosTemp = Despacho::with('unidad', 'conductor', 'ruta.rutapadre')->where('unidad_id', trim($unidad_id))
            ->where('estado', '!=', 'I')
            ->where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->orderBy('fecha', 'asc')->get();

        $ids = array();
        foreach ($despachosTemp as $d) {
            if (!is_array($d->puntos_control)) {
                continue;
            }
            foreach ($d->puntos_control as $item) {
                if (isset($item['id'])) {
                    $ids[(string) $item['id']] = $item['id'];
                }
            }
        }

        $descripciones = array();
        if (!empty($ids)) {
            $oidList = array();
            foreach ($ids as $rawId) {
                try {
                    $oidList[] = new ObjectID(trim((string) $rawId));
                } catch (\Exception $e) {
                    // id inválido omitido
                }
            }
            if (!empty($oidList)) {
                $puntos = PuntoControl::select('_id', 'descripcion')->whereIn('_id', $oidList)->get();
                foreach ($puntos as $p) {
                    $descripciones[(string) $p->_id] = $p->descripcion;
                }
            }
        }

        $despachos = array();
        foreach ($despachosTemp as $despacho) {
            $fin = $despacho->fecha;
            date_add($fin, date_interval_create_from_date_string('5 hours'));
            $despacho->fecha = $fin;

            $arrayPuntos = collect($despacho->puntos_control)->transform(function ($item) use ($descripciones) {
                $key = isset($item['id']) ? (string) $item['id'] : null;
                $item['descripcion'] = ($key !== null && isset($descripciones[$key]))
                    ? $descripciones[$key]
                    : null;
                return $item;
            });
            $despacho->puntos_controles = $arrayPuntos;
            $despachos[] = $despacho;
        }

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'despacho' => $despachos,
        ], 200);
    }

    public function getPuntoControl_v2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'punto_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        set_time_limit(0);

        $punto_id = $request->input('punto_id');

        try {
            $punto = PuntoControl::select('descripcion')->where('_id', new ObjectID(trim($punto_id)))->first();
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'punto_id inválido.',
            ], 400);
        }

        if ($punto === null) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'Punto de control no encontrado.',
            ], 404);
        }

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'data' => $punto,
        ], 200);
    }
}
