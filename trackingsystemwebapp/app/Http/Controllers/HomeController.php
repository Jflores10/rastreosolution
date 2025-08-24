<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Auth;
use App\Cooperativa;
use App\Unidad;
use App\Despacho;
use App\Trama;
use DateTime;
use App\Recorrido;
use App\Ruta;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use App\PuntoControl;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use App\PuntoControlAtmOficial;
use App\RutaAtmOficial;
use App\Bitacora;

class HomeController extends Controller
{
    public function showEnLinea()
    {
        $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->permitida()->where('estado', 'A')->get();
        return view('panel.reporte-linea', [
            'cooperativas' => $cooperativas
        ]);
    }
    
    public function en_linea($ruta)
    {
        set_time_limit(0);
        $rutas_depur=array();

        $objRuta = Ruta::findOrFail($ruta);
        if($objRuta->tipo_ruta != 'P'){
            array_push($rutas_depur,$ruta);
        }else{
            $ruta_hijas=Ruta::where('ruta_padre',$objRuta->_id)->get();
            foreach($ruta_hijas as $hijas)
                array_push($rutas_depur,$hijas->_id);
        }

        $puntosControlCollection = new Collection();
        foreach ($objRuta->puntos_control as $puntoControl)
            $puntosControlCollection->add(PuntoControl::findOrFail($puntoControl['id']));

        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));

        $user = Auth::user();
        $unidades_pertenecientes = Auth::user()->unidades_pertenecientes;
        if($user->tipo_usuario->valor==4 || $user->tipo_usuario->valor==5)//socio despachador 
        {
            $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', 
            $desde)->where('fecha', '<=', $hasta)->whereIn('ruta_id', $rutas_depur)
            ->whereIn('unidad_id',$unidades_pertenecientes)->where('estado','=','P')->get();
        }else{
            $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', 
            $desde)->where('fecha', '<=', $hasta)->whereIn('ruta_id', $rutas_depur)->where('estado','=','P')->get();
        }

      // $despacho = Despacho::findOrFail("5a5e68212243df794f047144");
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('6 hours'));        

        
        return view('panel.table-en-linea', ['puntosControl' => $puntosControlCollection, 
       'ruta' => $objRuta, 'despachos' => $despachos, 'desde' => $desde, 'desde' => $hasta]);

        //return response()->json( ['puntosControl' => $puntosControlCollection, 
        //'ruta' => $objRuta, 'despachos' => $despachos, 'desde' => $desde, 'hasta' => $hasta]);
    }
	public function consola()
	{
		Trama::where('created_at', '<', Carbon::createFromFormat('Y-m-d', date('Y-m-d')))->delete();
		$count = Trama::where('visto', true);
		if ($count->count() >= 500)
			$count->delete();
		$builder = Trama::orderBy('created_at', 'asc')->where('visto', false);
		$tramas = $builder->get();
		$builder->update(['visto' => true]);
		return response()->json($tramas);
	}
	public function tramas(Request $request)
	{
		if ($request->user()->tipo_usuario->valor == 1)
			return view('tramas');
		else 
			abort(403);
	}
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function ubicacion()
    {
        return view('panel.ubicacion');

    }
    public function index()
    {
        $user = Auth::user();
        $unidades_pertenecientes = Auth::user()->unidades_pertenecientes;
        $rutas_atm=null;
        $despachos_atm=null;
        if($user->tipo_usuario->valor==4 || $user->tipo_usuario->valor==5 )//socio  -- despachador especial
        {
            if($unidades_pertenecientes==null)
                $unidades=Unidad::where('_id','');
            else
                $unidades=Unidad::orderBy('descripcion','asc')->whereIn('_id', $unidades_pertenecientes)->where('estado','A')->get();

            $cooperativas=Cooperativa::where('_id', $user->cooperativa_id)->where('estado','A')->get();
			$rutas = Ruta::where('cooperativa_id', $user->cooperativa_id)->whereIn('tipo_ruta',['P', 'I','C'])->where('estado','A')->get();
            $rutas_atm = RutaAtmOficial::where('cooperativa_id', $user->cooperativa_id)->where('estado','A')->get();
            $cooperativatmp = Cooperativa::where('_id', $user->cooperativa_id)->where('estado','A')->first();
            $despachos_atm=$cooperativatmp->despachos_atm;
        }
        else
        {
            if ($user->tipo_usuario->valor == 1) //Superadmin
            {
                $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('estado','A')->get();
                $unidades = Unidad::orderBy('descripcion', 'asc')->where('estado', 'A')->get();
				$rutas = Ruta::whereIn('tipo_ruta',['P', 'I','C'])->where('estado','A')->get();
            }
            else
            {
                if ($user->tipo_usuario->valor == 2 || $user->tipo_usuario->valor == 3)//Administrador/despachador
                {
                    $cooperativas = Cooperativa::where('_id', $user->cooperativa_id)->where('estado','A')->get();
                    $cooperativatmp = Cooperativa::where('_id', $user->cooperativa_id)->where('estado','A')->first();
                    $unidades = Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id', Auth::user()->cooperativa_id)->get();
					$rutas = Ruta::where('cooperativa_id', $user->cooperativa_id)->whereIn('tipo_ruta',['P', 'I','C'])->where('estado','A')->get();
                    $rutas_atm = RutaAtmOficial::where('cooperativa_id', $user->cooperativa_id)->where('estado','A')->get();
                    $despachos_atm=$cooperativatmp->despachos_atm;
                }
                else
                    return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
            }
        }
        foreach($unidades as $unidad)
        {
            if($unidad->fecha_gps!=null && $unidad->fecha!=null)
            {
                $f_gps=$unidad["fecha_gps"]->toDateTime();
                $f_servidor=$unidad["fecha"]->toDateTime();
                date_sub($f_gps, date_interval_create_from_date_string('10 hours'));
                date_sub($f_servidor, date_interval_create_from_date_string('5 hours'));
                $unidad["fecha"]=$f_servidor->format('d-m-Y H:i');
                $unidad["fecha_gps"]=$f_gps->format('d-m-Y H:i');
            }
        }
        return view('home', ['cooperativas' => $cooperativas, 'unidades' => $unidades, 'rutas'=>$rutas,'rutas_atm'=>$rutas_atm, 'atm'=>$despachos_atm]);
    }
    
    public function cargarPuntosControl(Request $request)
    {
    	$r = $request->input('rutas');
    	if (isset($r) && is_array($r))
    	{
    		$rutas = Ruta::whereIn('_id', $request->input('rutas'))->get();
    		$puntos = array();
    		$routes = array();
    		foreach ($rutas as $ruta)
    		{
    			$ids = array();
    			foreach ($ruta->puntos_control as $punto)
    				array_push($ids, $punto['id']);
    				array_push($puntos, PuntoControl::whereIn('_id', $ids)->get());
    				array_push($routes, $ruta);
    		}
    		return response()->json(['puntos' => $puntos, 'rutas' => $routes]);
    	}
    	else 
    		return response()->json([[]]);
    }

    public function cargarPuntosControlATM(Request $request)
    {
    	$r = $request->input('rutas');
    	if (isset($r) && is_array($r))
    	{
    		$rutas = RutaAtmOficial::whereIn('_id', $request->input('rutas'))->get();
    		$puntos = array();
    		$routes = array();
    		foreach ($rutas as $ruta)
    		{
                $ids = array();
                if(isset($ruta->puntos_control)){
                    foreach ($ruta->puntos_control as $punto)
                        array_push($ids, $punto['id']);
                
                    array_push($puntos, PuntoControlAtmOficial::whereIn('_id', $ids)->get());
                }
                array_push($routes, $ruta);
                    
    		}
    		return response()->json(['puntos' => $puntos, 'rutas' => $routes]);
    	}
    	else 
    		return response()->json([[]]);
    }

    public function search(Request $request)
    {
        date_default_timezone_set('America/Bogota');
        $this->validate($request, [
            'cooperativa' => 'required|exists:cooperativas,_id'
        ]);
        $cooperativa = $request->input('cooperativa');
        $consulta = $request->input('consulta');
        $user=Auth::user();
        $unidades_pertenecientes = Auth::user()->unidades_pertenecientes;

        if ($consulta!='')
        {
            if($user->tipo_usuario->valor!=4 && $user->tipo_usuario->valor!=5)
                $unidades = Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',
                $cooperativa)->where( function($query)use($consulta){
                    $query->where('descripcion', 'like','%'.  $consulta . '%')->
                    orWhere('imei', 'like','%'.  $consulta . '%')
                    ->orWhere('placa', 'like','%'.  $consulta . '%');
                })
                ->where('estado','A')->get();
            else
            {
                 if($unidades_pertenecientes==null)
                    $unidades=Unidad::where('_id','');
                 else
                    $unidades=Unidad::orderBy('descripcion','asc')->whereIn('_id', $unidades_pertenecientes)
                    ->where('estado','A')
                    ->where( function($query)use($consulta){
                        $query->where('descripcion', 'like','%'.  $consulta . '%')->
                        orWhere('imei', 'like','%'.  $consulta . '%')
                        ->orWhere('placa', 'like','%'.  $consulta . '%');
                    })->get();
            } 
        }
        else 
        {
            if($user->tipo_usuario->valor!=4 && $user->tipo_usuario->valor!=5)
                $unidades = Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',$cooperativa)
                 ->where('estado','A')->get();
            else
            {
                if($unidades_pertenecientes==null)
                    $unidades=Unidad::where('_id','');
                 else
                    $unidades=Unidad::orderBy('descripcion','asc')->whereIn('_id', $unidades_pertenecientes)
                    ->where('estado','A')->get();
            } 
        }
        $array=array();
        $array_geocode=array();
        $array_notificaciones=array();
        $rutaunidad=array();
        $array_bitacora=array();
        $f_puerta_abierta=null;
        $f_puerta_cerrada=null;
        $place=null;
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));

        foreach($unidades as $unidad)
        {
            if($unidad["fecha_gps"] != null && $unidad["fecha"] != null)
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
                array_push($rutaunidad,["ruta_actual"=>$ruta,"ruta_fecha"=>$ruta_fecha,"ruta_conductor"=>$ruta_conductor]);

                $f_gps=$unidad["fecha_gps"]->toDateTime();
                $f_servidor=$unidad["fecha"]->toDateTime();
                $f_puerta_abierta=null;
                $f_puerta_cerrada=null;
                date_sub($f_gps, date_interval_create_from_date_string('10 hours'));
                date_sub($f_servidor, date_interval_create_from_date_string('5 hours'));
                $diff = $f_gps->diff(new DateTime());
                $diff = ($diff->i + (($diff->h-5) * 60) + ($diff->d * 24 * 60));

                if($unidad["fecha_puerta_abierta"] != null && $unidad["fecha_puerta_cerrada"] != null)
                {
                    $f_puerta_abierta=$unidad["fecha_puerta_abierta"]->toDateTime();
                    $f_puerta_cerrada=$unidad["fecha_puerta_cerrada"]->toDateTime();
                    date_sub($f_puerta_abierta, date_interval_create_from_date_string('10 hours'));
                    date_sub($f_puerta_cerrada, date_interval_create_from_date_string('10 hours'));
                }


                array_push($array,["fecha_servidor"=>$f_servidor, "fecha_gps"=>$f_gps, 'diferencia'=>$diff,
                'fecha_puerta_abierta'=>$f_puerta_abierta,'fecha_puerta_cerrada'=>$f_puerta_cerrada]);
            }
            else
            {
                array_push($array,["fecha_servidor"=>null, "fecha_gps"=>null, 'diferencia'=>null,
                'fecha_puerta_abierta'=>null,'fecha_puerta_cerrada'=>null]);

                array_push($rutaunidad,["ruta_actual"=>'',"ruta_fecha"=>'',"ruta_conductor"=>'']);
            }

            if($unidad["alerta_velocidad_fecha"] != null){
                $fecha_flag=$unidad["alerta_velocidad_fecha"]->toDateTime();
                date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
                $unidad["alerta_velocidad_fecha"]=$fecha_flag->format('d-m-Y H:i:s');
            }
            if($unidad["alerta_puerta_fecha"] != null){
               $fecha_flag=$unidad["alerta_puerta_fecha"]->toDateTime();
               date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
               $unidad["alerta_puerta_fecha"]=$fecha_flag->format('d-m-Y H:i:s');
           }
           if($unidad["alerta_desconx_fecha"] != null){
               $fecha_flag=$unidad["alerta_desconx_fecha"]->toDateTime();
               date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
               $unidad["alerta_desconx_fecha"]=$fecha_flag->format('d-m-Y H:i:s');
           }
           if($unidad["alerta_gtgeo_fecha"] != null){
               $fecha_flag=$unidad["alerta_gtgeo_fecha"]->toDateTime();
               date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
               $unidad["alerta_gtgeo_fecha"]=$fecha_flag->format('d-m-Y H:i:s');
           }
        //    if($unidad["alerta_fecha_cortetubo"] != null){
        //         $fecha_flag=$unidad["alerta_fecha_cortetubo"]->toDateTime();
        //         date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
        //         $unidad["alerta_fecha_cortetubo"]=$fecha_flag->format('Y-m-d H:i:s');
        //     }

           array_push($array_notificaciones,[
                "alerta_velocidad_message"=>$unidad["alerta_velocidad_message"],"alerta_velocidad_fecha"=>$unidad["alerta_velocidad_fecha"],
                "alerta_puerta_message"=>$unidad["alerta_puerta_message"],"alerta_puerta_fecha"=>$unidad["alerta_puerta_fecha"],
                "alerta_desconx_message"=>$unidad["alerta_desconx_message"],"alerta_desconx_fecha"=>$unidad["alerta_desconx_fecha"],
                "alerta_gtgeo_message"=>$unidad["alerta_gtgeo_message"],"alerta_gtgeo_fecha"=>$unidad["alerta_gtgeo_fecha"],
                "alerta_fecha_cortetubo"=>$unidad["alerta_fecha_cortetubo"],
                    "alerta_cortetubo"=>$unidad["alerta_cortetubo"]
            ]);

            $place='';
            array_push($array_geocode,["formatted_address"=>$place]);

            $bitacora=Bitacora::orderBy('fechaInicio','desc')->where('unidad_id',$unidad['_id'])
                        ->where('estado','P')->first();
                    
            array_push($array_bitacora,["bitacora"=>( isset($bitacora) && $bitacora != null)?$bitacora->tipo_bitacora:'']);
        }
        
        return response()->json(['unidades'=>$unidades,'array_fechas'=>$array,'notificaciones'=>$array_notificaciones,
        'fecha_puerta_abierta'=>$f_puerta_abierta,'fecha_puerta_cerrada'=>$f_puerta_cerrada,'array_formatted_address'=>$array_geocode,
        'array_rutas'=>$rutaunidad,'array_bitacora'=>$array_bitacora]);
    }

    public function getVistaNueva(Request $request)
    {
         $cooperativas = Cooperativa::where('estado','A')->get();

        $cood_id=$request->input('cooperativa');
        $rutas = Ruta::where('cooperativa_id', $cood_id)->whereIn('tipo_ruta',['P', 'I','C'])->where('estado','A')->get();
        $rutas_atm = RutaAtmOficial::where('cooperativa_id', $cood_id)->where('estado','A')->get();
        $cooperativa = Cooperativa::findOrFail($cood_id);
        return view('home',['unidades'=>null,'id_coop'=>$cood_id,'cooperativas'=>$cooperativas,
        		'rutas' => $rutas,'rutas_atm'=>$rutas_atm, 'atm'=>$cooperativa->despachos_atm
        ]);
    }

    public function reverseProxy(Request $request)
    {
        $latitud = $request->input('lat');
        $longitud = $request->input('lon');
        $url = config('app.reverse_geocoding_url') . '?lat=' . $latitud . '&lon=' . $longitud . '&format=json';
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        return response()->json(json_decode($response->getBody()));
    }
}
