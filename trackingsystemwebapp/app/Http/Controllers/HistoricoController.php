<?php

namespace App\Http\Controllers;


use App\PuntoControl;
use App\TipoUsuario;
use App\Despacho;
use Illuminate\Http\Request;
use App\Cooperativa;
use App\Recorrido;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
use App\Unidad;
use MongoDB\BSON\ObjectID;
use Auth;
use Validator;
use DateTime;
use App\Ruta;
use App\PuntoVirtual;
use GuzzleHttp\Client;
use App\Bitacora;
use Excel;


class HistoricoController extends Controller
{
    public function historicoAtm(Request $request) {
        //add time limit
        set_time_limit(0);
        if ($request->isMethod('get')) {
            $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

            if ($tipo_usuario->valor == 1) {
                $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->get();
            } else {
                $cooperativas = Cooperativa::where('_id', Auth::user()->cooperativa_id)
                    ->orderBy('descripcion', 'asc')
                    ->where('estado', 'A')
                    ->get();
            }
            return view('panel.unidades.historico-atm', ['cooperativas' => $cooperativas]);
        }
        else {
            $this->validate($request, [
                'from' => 'required|date_format:Y-m-d H:i:s',
                'to' => 'required|date_format:Y-m-d H:i:s',
                'cooperativa_id' => 'required|exists:cooperativas,_id',
                'export_date' => 'nullable|boolean',
                //'unidad' => 'required|exists:unidads,_id'
            ]);

            $from = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('from'));
            $to = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('to'));
            date_add($from, date_interval_create_from_date_string('5 hours'));
            date_add($to, date_interval_create_from_date_string('5 hours'));
            $from = new UTCDateTime($from->getTimestamp() * 1000);
            $to = new UTCDateTime($to->getTimestamp() * 1000);
            $cooperativa_id = $request->input('cooperativa_id');
            $export_date = $request->input('export_date', false);
            //$unidadId = $request->input('unidad');
            if (!isset($unidadId)) {
                $unidades = Unidad::where('cooperativa_id', $cooperativa_id)
                ->where('estado', 'A')
                ->orderBy('descripcion', 'asc')
                ->get();
                $unidadesId = $unidades
                    ->pluck('_id')
                    ->map(function ($id) {
                        return new ObjectID($id);
                    });
            }
            else {
                $unidades = Unidad::where('_id', $unidadId)
                    ->where('cooperativa_id', $cooperativa_id)
                    ->where('estado', 'A')
                    ->orderBy('descripcion', 'asc')
                    ->get();
                $unidadesId = $unidades
                    ->pluck('_id')
                    ->map(function ($id) {
                        return new ObjectID($id);
                    });
            }
            
            
            $recorridos = Recorrido::where('fecha_gps', '>=', $from)
                ->where('fecha_gps', '<=', $to)
                ->whereIn('unidad_id', $unidadesId)
                ->whereNotNull('fecha_gps')
                ->whereNotNull('latitud')
                ->whereNotNull('longitud')
                ->whereIn('tipo', ['GTFRI'])
                ->orderBy('fecha_gps', 'asc')
                ->get()
                ->map(function ($recorrido) use ($unidades) {
                    $unidad = $unidades->where('_id', (string)$recorrido->unidad_id)->first();
                    $date = $recorrido->fecha_gps->toDateTime();
                    date_sub($date, date_interval_create_from_date_string('10 hours'));
                    return [
                        'latitud' => $recorrido->latitud,
                        'longitud' => $recorrido->longitud,
                        'gps_address' => $recorrido->gps_address,
                        'unidad' => $unidad ? $unidad->descripcion : 'Desconocida',
                        'placa' => $unidad ? $unidad->placa : 'Desconocida',
                        'velocidad' => $recorrido->velocidad,
                        'unidad_id' => (string)$recorrido->unidad_id,
                        'fecha_gps' => $date->format('Y-m-d H:i:s'),
                    ];
                });
            $fromDate = $from->toDateTime()->format('Y-m-d_His');
            $toDate = $to->toDateTime()->format('Y-m-d_His');
            $filename = 'Historico ATM -' . $fromDate . ' - ' . $toDate . '.xlsx';

            Excel::create($filename, function ($excel) use ($recorridos, $unidades, $export_date) {
                $excel->setTitle('Historico ATM');
                foreach ($unidades as $unidad) {
                    $excel->sheet($unidad->descripcion, function ($sheet) use ($recorridos, $unidad, $export_date) {
                        $sheet->loadView('panel.unidades.historico-atm-excel', [
                            'historico' => $recorridos->where('unidad_id', (string)$unidad->_id),
                            'export_date' => $export_date
                        ]);
                    });
                }
                $excel->download();
            });
        }
    }
    public function index()
    {
        if(Auth::user()->estado=='A') {
            $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

            switch($tipo_usuario->valor)
            {
                case 1:
                    return view('panel.reportes-unidades',
                        [
                            'unidades' => Unidad::orderBy('descripcion', 'asc')->where('estado','A')->get(),
                            'cooperativas' => Cooperativa::orderBy('descripcion', 'asc')->where('estado','A')->get(),
                            'tipo_usuario_valor' => $tipo_usuario->valor
                        ]);
                    break;
                case 2:
                    return view('panel.reportes-unidades',
                        [
                            'unidades' => Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',Auth::user()->cooperativa_id)->where('estado','A')->get(),
                            'tipo_usuario_valor' =>  $tipo_usuario->valor,
                            'cooperativa' => Cooperativa::findOrFail(Auth::user()->cooperativa_id)->first()
                        ]);
                    break;
                case 4:
                    $unidades_pertenecientes=Auth::user()->unidades_pertenecientes;
                    if($unidades_pertenecientes==null)
                        return view('panel.reportes-unidades',
                            [
                                'unidades' => Unidad::where('_id','')->get(),
                                'tipo_usuario_valor' =>  $tipo_usuario->valor,
                                'cooperativa' => Cooperativa::findOrFail(Auth::user()->cooperativa_id)->first()
                            ]);
                    else
                        return view('panel.reportes-unidades',
                            [
                                'unidades' => Unidad::whereIn('_id', Auth::user()->unidades_pertenecientes)->get(),
                                'tipo_usuario_valor' =>  $tipo_usuario->valor,
                                'cooperativa' => Cooperativa::findOrFail(Auth::user()->cooperativa_id)->first()
                            ]);
                    break;
                case 5:
                    $unidades_pertenecientes=Auth::user()->unidades_pertenecientes;
                    if($unidades_pertenecientes==null)
                        return view('panel.reportes-unidades',
                            [
                                'unidades' => Unidad::where('_id','')->get(),
                                'tipo_usuario_valor' =>  $tipo_usuario->valor,
                                'cooperativa' => Cooperativa::findOrFail(Auth::user()->cooperativa_id)->first()
                            ]);
                    else
                        return view('panel.reportes-unidades',
                            [
                                'unidades' => Unidad::whereIn('_id', Auth::user()->unidades_pertenecientes)->get(),
                                'tipo_usuario_valor' =>  $tipo_usuario->valor,
                                'cooperativa' => Cooperativa::findOrFail(Auth::user()->cooperativa_id)->first()
                            ]);
                    break;
                default:
                    return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
                    break;
            }
        }
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);

    }

    public function store(Request $request)
    {
    	set_time_limit(0);
        $unidades_en_ruta = array();
        $desde=null;
        $hasta=null;
        $despachos_pendientes=null;
        $unidades_id=array();$aa=0;
        if($request->input('opcion')=='getUnidades')
        {
            if($request->input('hay_rutas'))
            {
                
                $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
                $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                $array_rutas=array();
                $rutas_search=$request->input('rutas_ids');
                foreach($rutas_search as $rut){
                    $r = Ruta::find($rut);
                    if($r->tipo_ruta != 'P'){
                        array_push($array_rutas,$rut);
                    }else{
                        $ruta_hijas=Ruta::where('ruta_padre',$r->_id)->get();
                        foreach($ruta_hijas as $hijas)
                            array_push($array_rutas,$hijas->_id);
                    }
                }
                 if(Auth::user()->tipo_usuario->valor==4 || Auth::user()->tipo_usuario->valor==5)
                {
                    $unidades_pertenecientes=Auth::user()->unidades_pertenecientes;
                    $despachos_pendientes=Despacho::orderBy('fecha', 'asc')->where('estado','P')->whereIn('unidad_id',$unidades_pertenecientes)
                        ->whereIn('ruta_id',$array_rutas)->where('fecha','>=',$desde)->where('fecha','<=',$hasta)->get();

                    if($unidades_pertenecientes==null  || $despachos_pendientes==null)
                        $unidades=Unidad::where('cooperativa_id','');
                    else
                    {
                        foreach($unidades_pertenecientes as $unidad_id)
                        {
                            foreach($despachos_pendientes as $despacho)
                            {
                                if($unidad_id==$despacho->unidad_id)
                                {
                                    array_push($unidades_en_ruta,$unidad_id);
                                    break;                                    
                                }
                            }
                        }
                        $unidades= Unidad::orderBy('despachos.fecha', 'asc')->having('despachos.fecha', '>=', $desde)->where("estado","A")->whereIn('_id', $unidades_en_ruta)->get();
                    }
                }
                else
                {
                    $esta_despachado=false;
                    $array_aux=array();
                    
                    if(Auth::user()->tipo_usuario->valor==1)
                    {
                        $unidades2 = Unidad::orderBy('placa', 'asc')->where('cooperativa_id',$request->input('cooperativa_id'))->where('estado','A')->get();
                        
                        foreach($unidades2 as $unidad)
                        {
                            array_push($unidades_id,(string) $unidad->_id);
                        }

                        $despachos_pendientes=Despacho::orderBy('fecha', 'asc')->where('estado','P')->whereIn('unidad_id',$unidades_id)
                            ->whereIn('ruta_id',$array_rutas)->where('fecha','>=',$desde)
                            ->where('fecha','<=',$hasta)->get();

                        foreach($despachos_pendientes as $despacho)
                        {
                            for($i=0;$i<sizeof($unidades2);$i++)
                            {
                                if ((string)$unidades2[$i]->_id==(string)$despacho->unidad_id && !in_array((string)$despacho->unidad_id, $array_aux))
                                {
                                    array_push($array_aux,(string)$despacho->unidad_id);
                                    break;
                                }
                            }
                        }    
                        $aa=$array_aux;
                        $unidades=Unidad::whereIn('_id',$array_aux)->where('estado','A')->get();
                    }

                    else
                    {
                        $unidades2 = Unidad::orderBy('placa', 'asc')->where('cooperativa_id',Auth::user()->cooperativa_id)
                        ->where('estado','A')->get();
                        
                        foreach($unidades2 as $unidad)
                        {
                            array_push($unidades_id,(string) $unidad->_id);
                        }

                        $despachos_pendientes=Despacho::orderBy('fecha', 'asc')->where('estado','P')->whereIn('unidad_id',$unidades_id)
                            ->whereIn('ruta_id',$array_rutas)->where('fecha','>=',$desde)
                            ->where('fecha','<=',$hasta)->get();

                        foreach($despachos_pendientes as $despacho)
                        {
                            for($i=0;$i<sizeof($unidades2);$i++)
                            {
                                if ((string)$unidades2[$i]->_id==(string)$despacho->unidad_id && !in_array((string)$despacho->unidad_id, $array_aux))
                                {
                                    array_push($array_aux,(string)$despacho->unidad_id);
                                    break;
                                }
                            }
                        }

                        $aa=$array_aux;
                        $unidades=Unidad::whereIn('_id',$array_aux)->where('estado','A')->get();
                    }
                }

                if (isset($unidades) && isset($array_aux))
                {
                	$orden = 1;
                	foreach ($array_aux as $aux){
                		foreach ($unidades as $unidad)
                		{
                			if ($aux == (string) $unidad->_id)
                			{
                				$unidad->orden = $orden++;
                				break;
                			}
                		}
                	}
                	if (count($array_aux) > 0)
                		$unidades = $unidades->sortBy('orden')->values()->all();
                }
            }
            else
            {
                if(Auth::user()->tipo_usuario->valor==4 || Auth::user()->tipo_usuario->valor==5)
                {
                    $unidades_pertenecientes=Auth::user()->unidades_pertenecientes;
                    if($unidades_pertenecientes==null)
                        $unidades=Unidad::where('cooperativa_id','');
                    else
                        $unidades= Unidad::orderBy('descripcion', 'asc')->where("estado","A")->whereIn('_id', $unidades_pertenecientes)->get();
                }
                else
                {
                    if(Auth::user()->tipo_usuario->valor==1)
                        $unidades = Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',$request->input('cooperativa_id'))->where('estado','A')->get();
                    else
                        $unidades = Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',Auth::user()->cooperativa_id)->where('estado','A')->get();
                }
            }

            $array = array();
            $array_geocode=array();
            $array_notificaciones=array();
            $rutaunidad=array();
            $array_bitacora=array();
            $diff=null;
            $f_puerta_abierta=null;
            $f_puerta_cerrada=null;
            $place=null;
            $cooperativa_id=$request->input('cooperativa_id');
            $cooperativa= Cooperativa:: findOrFail($cooperativa_id);
            $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
            $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
            $f_puerta_abierta=null;
            $f_puerta_cerrada=null;
            $f_puerta_abierta_trasera=null;
            $f_puerta_cerrada_trasera=null;
            
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
                    $ruta_hora_final='';

                    if(isset($ruta_actual)){
                        $ruta=$ruta_actual->ruta->descripcion;
                        $ruta_fecha=$ruta_actual->fecha;
                        date_add($ruta_fecha, date_interval_create_from_date_string('5 hours'));
                        $ruta_fecha=$ruta_fecha->format('H:i');
                        $ruta_conductor=$ruta_actual->conductor->nombre;
                        //RECORRER LOS PUNTO DE CONTROL PARA OBTENER TIEMPO-PUNTO
                        $tiempo_final=0;
                        foreach ($ruta_actual->ruta->puntos_control as $punto) {
                            $tiempo_final+=$punto['tiempo_llegada'];  
                        }
                        //SUMAR MINUTOS A LA HORA DEL DESPACHO
                        $ruta_hora_final = Carbon::parse($ruta_actual->fecha); // conviertes a Carbon
                        $ruta_hora_final->addHours(5); 
                        $ruta_hora_final->addMinutes($tiempo_final); 
                        $ruta_hora_final = $ruta_hora_final->format('H:i'); // solo hora:minuto

                    }
                    array_push($rutaunidad,["ruta_actual"=>$ruta,"ruta_fecha"=>$ruta_fecha,"ruta_conductor"=>$ruta_conductor,"ruta_hora_fin"=>$ruta_hora_final]);

                    $f_gps=$unidad["fecha_gps"]->toDateTime();
                    $f_servidor=$unidad["fecha"]->toDateTime();
                    $f_puerta_abierta=null;
                    $f_puerta_cerrada=null;
                    $f_puerta_abierta_trasera=null;
                    $f_puerta_cerrada_trasera=null;
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

                    if($unidad["fecha_puerta_abierta_trasera"] != null && $unidad["fecha_puerta_cerrada_trasera"] != null)
                    {
                        $f_puerta_abierta_trasera=$unidad["fecha_puerta_abierta_trasera"]->toDateTime();
                        $f_puerta_cerrada_trasera=$unidad["fecha_puerta_cerrada_trasera"]->toDateTime();
                        date_sub($f_puerta_abierta_trasera, date_interval_create_from_date_string('10 hours'));
                        date_sub($f_puerta_cerrada_trasera, date_interval_create_from_date_string('10 hours'));
                    }


                    array_push($array,["fecha_servidor"=>$f_servidor, "fecha_gps"=>$f_gps, 'diferencia'=>$diff,
                    'fecha_puerta_abierta'=>$f_puerta_abierta,'fecha_puerta_cerrada'=>$f_puerta_cerrada,
                    'fecha_puerta_abierta_trasera'=>$f_puerta_abierta_trasera,'fecha_puerta_cerrada_trasera'=>$f_puerta_cerrada_trasera]);
                }
                else
                {
                    array_push($array,["fecha_servidor"=>null, "fecha_gps"=>null, 'diferencia'=>null,
                    'fecha_puerta_abierta'=>null,'fecha_puerta_cerrada'=>null]);

                    array_push($rutaunidad,["ruta_actual"=>'',"ruta_fecha"=>'',"ruta_conductor"=>'']);
                }

                if($unidad->fecha_gps!=null && $unidad->fecha!=null)
                 {
                    $f_gps=$unidad["fecha_gps"]->toDateTime();
                    $f_servidor=$unidad["fecha"]->toDateTime();
                    $f_puerta_abierta=null;
                    $f_puerta_cerrada=null;
                    $f_puerta_abierta_trasera=null;
                    $f_puerta_cerrada_trasera=null;
                    date_sub($f_gps, date_interval_create_from_date_string('10 hours'));
                    date_sub($f_servidor, date_interval_create_from_date_string('5 hours'));
                    $unidad["fecha_gps"]=$f_gps->format('d-m-Y H:i');
                    $unidad["fecha"]=$f_servidor->format('d-m-Y H:i');

                    if($unidad["fecha_puerta_abierta"] != null && $unidad["fecha_puerta_cerrada"] != null)
                    {
                        $f_puerta_abierta=$unidad["fecha_puerta_abierta"]->toDateTime();
                        $f_puerta_cerrada=$unidad["fecha_puerta_cerrada"]->toDateTime();
                        date_sub($f_puerta_abierta, date_interval_create_from_date_string('10 hours'));
                        date_sub($f_puerta_cerrada, date_interval_create_from_date_string('10 hours'));

                        $unidad["fecha_puerta_abierta"]=$f_gps->format('d-m-Y H:i:s');
                        $unidad["fecha_puerta_cerrada"]=$f_servidor->format('d-m-Y H:i:s');
                    }

                    if($unidad["fecha_puerta_abierta_trasera"] != null && $unidad["fecha_puerta_cerrada_trasera"] != null)
                    {
                        $f_puerta_abierta_trasera=$unidad["fecha_puerta_abierta_trasera"]->toDateTime();
                        $f_puerta_cerrada_trasera=$unidad["fecha_puerta_cerrada_trasera"]->toDateTime();
                        date_sub($f_puerta_abierta_trasera, date_interval_create_from_date_string('10 hours'));
                        date_sub($f_puerta_cerrada_trasera, date_interval_create_from_date_string('10 hours'));

                        $unidad["fecha_puerta_abierta_trasera"]=$f_gps->format('d-m-Y H:i:s');
                        $unidad["fecha_puerta_cerrada_trasera"]=$f_servidor->format('d-m-Y H:i:s');
                    }
                 }

                 $bitacora=Bitacora::orderBy('fechaInicio','desc')->where('unidad_id',$unidad['_id'])
                            ->where('estado','P')->first();
                        
                array_push($array_bitacora,["bitacora"=>( isset($bitacora) && $bitacora != null)?$bitacora->tipo_bitacora:'']);
                 $place='';
                 
                 array_push($array_geocode,["formatted_address"=>$place]);

                 $si_notificacion_velocidad=true;
                 $si_notificacion_puerta=true;
                 $si_notificacion_desconexion=true;
                 $si_notificacion_geocerca=true;
                 
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
               if($unidad["alerta_puerta_fecha_trasera"] != null){
                   $fecha_flag=$unidad["alerta_puerta_fecha_trasera"]->toDateTime();
                   date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
                   $unidad["alerta_puerta_fecha_trasera"]=$fecha_flag->format('d-m-Y H:i:s');
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
               if($unidad["alerta_panico_fecha_message"] != null){
                   $fecha_flag=$unidad["alerta_panico_fecha_message"]->toDateTime();
                   date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
                   $unidad["alerta_panico_fecha_message"]=$fecha_flag->format('Y-m-d H:i:s');
               }
            //    if($unidad["alerta_fecha_cortetubo"] != null){
            //         $fecha_flag=$unidad["alerta_fecha_cortetubo"];
            //         date_sub($fecha_flag, date_interval_create_from_date_string('10 hours'));
            //         $unidad["alerta_fecha_cortetubo"]=$fecha_flag->format('Y-m-d H:i:s');
            //     }


               array_push($array_notificaciones,[
                    "alerta_velocidad_message"=>($si_notificacion_velocidad)?$unidad["alerta_velocidad_message"]:null,
                    "alerta_velocidad_fecha"=>($si_notificacion_velocidad)?$unidad["alerta_velocidad_fecha"]:null,
                    "alerta_puerta_message"=>($si_notificacion_puerta)?$unidad["alerta_puerta_message"]:null,
                    "alerta_puerta_fecha"=>($si_notificacion_puerta)?$unidad["alerta_puerta_fecha"]:null,
                    "alerta_puerta_fecha_trasera"=>($si_notificacion_puerta)?$unidad["alerta_puerta_fecha_trasera"]:null,
                    "alerta_puerta_message_trasera"=>($si_notificacion_puerta)?$unidad["alerta_puerta_message_trasera"]:null,
                    "alerta_desconx_message"=>($si_notificacion_desconexion)?$unidad["alerta_desconx_message"]:null,
                    "alerta_desconx_fecha"=>($si_notificacion_desconexion)?$unidad["alerta_desconx_fecha"]:null,
                    "alerta_gtgeo_message"=>($si_notificacion_geocerca)?$unidad["alerta_gtgeo_message"]:null,
                    "alerta_gtgeo_fecha"=>($si_notificacion_geocerca)?$unidad["alerta_gtgeo_fecha"]:null,
                    "alerta_panico_message"=>$unidad["alerta_panico_message"],
                    "alerta_panico_fecha_message"=>$unidad["alerta_panico_fecha_message"],
                    "alerta_panico_number_message"=>$unidad["alerta_panico_number_message"],
                    "alerta_fecha_cortetubo"=>$unidad["alerta_fecha_cortetubo"],
                    "alerta_cortetubo"=>$unidad["alerta_cortetubo"]
                ]);

            }
            return response()->json(['unidades'=>$unidades,'array_fechas'=>$array,'diferencia'=>$diff,'uni'=>$aa,
            'fecha_puerta_abierta'=>$f_puerta_abierta,'fecha_puerta_cerrada'=>$f_puerta_cerrada,
            'fecha_puerta_abierta_trasera'=>$f_puerta_abierta_trasera,'fecha_puerta_cerrada_trasera'=>$f_puerta_cerrada_trasera,'array_formatted_address'=>$array_geocode,
            'notificaciones'=>$array_notificaciones,'array_rutas'=>$rutaunidad,'array_bitacora'=>$array_bitacora]);
        }
        elseif($request->input('opcion')=='getHistorico')
        {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required',
                'fecha_fin' => 'required',
                'unidad_id' => 'required',
                'evento' => 'required'
            ]);
            if ($validator->fails())
                return response()->json(['error' => true, 'messages' => $validator->errors()]);
            else
                {
                $ini = new Carbon($request->input('fecha_inicio'));
                $fin = new Carbon($request->input('fecha_fin'));
                date_add($ini, date_interval_create_from_date_string('5 hours'));
                date_add($fin, date_interval_create_from_date_string('5 hours'));
                $ini = new UTCDateTime(($ini->getTimestamp()) * 1000);
                $fin = new UTCDateTime(($fin->getTimestamp()) * 1000);

                 $cursor= Recorrido::where("unidad_id", new ObjectID($request->input('unidad_id')))
                        ->where('fecha_gps','>=',$ini)
                        ->where('fecha_gps','<=',$fin);
                        
                $ev = $request->input('evento');
                if ($ev != 'T')
                    $cursor->where('tipo', $ev);

                   /// $cursor->where('tipo', "GTDAT");
                $cursor = $cursor->paginate(50);
                $array_historico = [];
                $evento='--';
                $ubicacion= '';
                $angulo_traducido='-';
                $tipo='';
                $user=Auth::user();
                foreach ($cursor as $documento) {
                        $fecha = $documento["fecha"];
                        $voltaje = (isset($documento["voltaje"])?$documento["voltaje"]:'-');
                        $fecha = $fecha->toDateTime();
                        $tipo=$documento['tipo'];
                        //date_sub($fecha, date_interval_create_from_date_string('10 hours'));

                        $time = $fecha->format(DATE_RSS);
                        $dateInUTC = $time;
                        $time = strtotime($dateInUTC . ' UTC');
                        $fecha = date("d-m-Y H:i:s", $time);

                        $fecha_gps = $documento["fecha_gps"];
                        $fecha_gps = $fecha_gps->toDateTime();
                        date_sub($fecha_gps, date_interval_create_from_date_string('5 hours'));
                        $time = $fecha_gps->format(DATE_RSS);
                        $dateInUTC = $time;
                        $time = strtotime($dateInUTC . ' UTC');
                        $fecha_gps = date("d-m-Y H:i:s", $time);
                        $evento=(String)$documento["pdi"] ." PDI " .$request->input('cooperativa_id')." COOP ".(String)new ObjectID($request->input('unidad_id'))." UNIDAD";

                        if ($documento["tipo"] == 'GTGEO') {

              						if(Auth::user()->tipo_usuario->valor==1)
                           {
                             /*if (($documento["tipo"] === 'GPRMC' && $documento["evento"] >= 38))
                             {
                               $residuo = $documento["evento"] % 2;
                               if ($residuo != 0)
                                $documento["evento"] = $documento["evento"] - 1;
                               $punto_control = PuntoControl::
                                  where('cooperativa_id', $request->input('cooperativa_id'))
                                      ->where('pdi', (String)$documento["evento"])->first();
                             }
                             else*/
                             $punto_control = PuntoControl::
                                where('cooperativa_id', $request->input('cooperativa_id'))
                                    ->where('pdi', (String)$documento["pdi"])->first();
                           }
              						else
              							 {
                               /*if (($documento["tipo"] === 'GPRMC' && $documento["evento"] >= 38))
                               {
                                 $residuo = $documento["evento"] % 2;
                                 if ($residuo != 0)
                                  $documento["evento"] = $documento["evento"] - 1;
                                $punto_control = PuntoControl::
                                                 where('cooperativa_id', Auth::user()->cooperativa_id)
                                       ->where('pdi', (String)$documento["evento"])->first();
                               }
                               else*/
                               $punto_control = PuntoControl::
                                                where('cooperativa_id', Auth::user()->cooperativa_id)
                                      ->where('pdi', (String)$documento["pdi"])->first();
                             }


                          if($punto_control!=null)
                          {
                              if ($documento["entrada"] == 1)
                                  $evento = "Entrada al punto de control " . $punto_control->descripcion;
                              else
                                  $evento = "Salida del punto de control " . $punto_control->descripcion;
                          }
                        }
                        else
                        {
                            if ($documento['tipo'] == 'GTVIRTUAL') {
                                $puntoVirtual = PuntoVirtual::find($documento['punto_virtual_id']);
                                if (isset($puntoVirtual))
                                    $evento = 'Aproximación a punto virtual ' . $puntoVirtual->descripcion;
                            }
                            else if($documento["tipo"] == 'GTFRI' || ($documento["tipo"] === 'GPRMC'))
                            {                               

                                //if ($ev != 'T'){
                                    
                               // }
                                switch ($documento["evento"])
                                {
                                    case 'N<':
                                        $evento = 'Reporte por tiempo (móvil encendido) ';
                                        break;
                                    case 'D<':
                                        $evento = 'Reporte por tiempo (móvil apagado) ';
                                        break;
                                    case 'E<':
                                        $evento = 'Reporte por tiempo (encerado manual) ';
                                        break;
                                    case 'I<':
                                        $evento = 'Reporte por tiempo (conexión de energía) ';
                                        break;
                                    case 13:
                                      $evento = 'Reporte por tiempo (móvil apagado) ';
                                      break;
                                    case 14:
                                      $evento = 'Móvil encendido ';
                                      break;
                                    case 15:
                                      $evento ='Reporte móvil apagado  ';
                                      break;
                                    case 16:
                                      $evento = 'Distancia por distancia ';
                                      break;
                                    case 18:
                                      $evento = 'Reporte por tiempo (móvil encendido) ';
                                      break;
                                    default :
                                        $evento = 'Reporte por tiempo (móvil encendido) ';
                                        break;
                                }
                            }else{
                                if($documento["tipo"]=='GTDIS'){
                                    $evento=$documento["evento"];
                                }else if ($documento["tipo"]=='GTIGF'){
                                    $evento='Desconexion de dispositivo';
                                }else if ($documento["tipo"]=='GTIGN'){
                                    $evento='Conexion de dispositivo';
                                }else if ($documento["tipo"]=='GTSOS'){
                                    $evento='Botón de pánico activado';
                                }
                            }
                        }
                        if($documento["angulo"]==0)
                            $angulo_traducido="N";
                        else
                        {
                            if($documento["angulo"]!=null)
                            {
                                if($documento["angulo"]>90 && $documento["angulo"]<180)
                                    $angulo_traducido="SE";
                                else
                                {
                                    if($documento["angulo"]>180 && $documento["angulo"]<270)
                                        $angulo_traducido="SO";
                                    else
                                    {
                                        if($documento["angulo"]>270 && $documento["angulo"]<360)
                                            $angulo_traducido="NO";
                                        else
                                        {
                                            if($documento["angulo"]>0 && $documento["angulo"]<90)
                                                $angulo_traducido="NE";
                                            else
                                            {
                                                if($documento["angulo"]==90)
                                                    $angulo_traducido="E";
                                                else
                                                {
                                                    if($documento["angulo"]==270)
                                                        $angulo_traducido="O";
                                                    else
                                                    {
                                                      if($documento["angulo"]==180)
                                                         $angulo_traducido="S";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $ubicacion= '';
                        $latitud_geo='--';
                        $longitud_geo='--';
                        if(isset($documento["latitud"]) && isset($documento["longitud"])){
                            if($documento["latitud"] != null && $documento["longitud"] != null){
                                $latitud_geo=$documento["latitud"];
                                $longitud_geo=$documento["longitud"];
                                if($user->tipo_usuario->valor==1 && !isset($documento['gps_address'])){//OSM DIRECCIONES SOLO DISTRIBUIDORES
                                    $client = new Client();
                                    $urlFinal='https://nominatim.openstreetmap.org/reverse?format=json&lat='.$documento["latitud"]. '&lon='.$documento["longitud"];
                                    $res = $client->get($urlFinal, [
                                        'verify' => false
                                    ]);

                                    // $res = $client->request('GET',$urlFinal );

                                    $code = $res->getStatusCode();
                                    if ($code === 200) {
                                        $json = json_decode($res->getBody());
                                        if (!isset($json->error))
                                        {
                                            $ubicacion=$json->display_name;
                                            $documento['gps_address'] = $ubicacion;
                                            $documento->save();
                                        }
                                        else
                                            $ubicacion='Error al traer ubicación';
                                    }else{
                                        $ubicacion='Error al traer ubicación';
                                    }
                                    // return $ubicacion;
                                }
                            }
                        } 
                        array_push($array_historico, (Object)[
                            '_id' => $documento["_id"],
                            'fecha_servidor' => $fecha,
                            'fecha_gps' => $fecha_gps,
                            'evento' => $evento,
                            'ubicacion' => isset($documento['gps_address']) ? $documento['gps_address'] : $ubicacion,
                            'angulo' => $angulo_traducido,
                            'latitud'=>$latitud_geo,
                            'longitud'=>$longitud_geo,
                            'mileage' => ($documento["mileage"] != null) ? $documento["mileage"] . ' km/h' : '0 km/h',
                            'velocidad' => ($documento["velocidad"]!= null) ? $documento["velocidad"] . ' km/h' : '0 km/h',
                        	'voltaje' => $voltaje,
                            'contador_total' => ($documento["contador_total"] != null) ? $documento["contador_total"] : '-']);

                        $evento='-';
                        $ubicacion='-';
                        $angulo_traducido='-';
                    }

                return response()->json([
                'error' => false,
                'array_historico' => $array_historico,
                'tipo'=>$tipo,
                'ev'=>$ev ,  
                'total' => $cursor->total(),
                'per_page' => $cursor->perPage(),
                'current_page' => $cursor->currentPage(),
                'last_page' => $cursor->lastPage(),
                'next_page_url' => $cursor->nextPageUrl(),
                'prev_page_url' => $cursor->previousPageUrl()]);
            }
        }
        elseif($request->input('opcion')=='getRutas')
        {
            $rutas = Ruta::where("cooperativa_id",$request->input('cooperativa_id'))->where("estado","A")->get();


            return response()->json(['error' => false, 'rutas' => $rutas]);
        }

        elseif($request->input('opcion')=='getRuta')
        {
            $ruta = Ruta::findOrFail($request->input('ruta_id'));
            $puntos_control = PuntoControl::where("cooperativa_id",$request->input('cooperativa_id'))->where("estado","A")->get();

            if($ruta == null)
                return response()->json(['error'=>true,'ruta'=>$ruta,'puntos_control'=>$puntos_control]);
            else
                return response()->json(['error'=>false,'ruta'=>$ruta,'puntos_control'=>$puntos_control]);
        }

        elseif($request->input('opcion')=='getHistoricoReproductor')
        {
            if($request->input('opcion_fecha')!='P')
            {
                $validator = Validator::make($request->all(), [
                    'unidad_id' => 'required'
                ]);
            }
            else
            {
                $validator = Validator::make($request->all(), [
                    'fecha_inicio' => 'required | date',
                    'fecha_fin' => 'required | date',
                    'unidad_id' => 'required'
                ]);
            }
            if ($validator->fails())
                return response()->json(['error' => true]);
            else
            {
                if($request->input('opcion_fecha')=='P')//personalizado
                {
                    $ini = new Carbon($request->input('fecha_inicio'));
                    $fin = new Carbon($request->input('fecha_fin'));
                }
                else
                {
                    if($request->input('opcion_fecha')=='H')//hoy
                    {
                        $ini=Carbon::today();
                        $fin= Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                    }
                    else //ayer
                    {
                        $ini=Carbon::yesterday();
                        $fin=Carbon::today();
                        date_sub($fin, date_interval_create_from_date_string('1 minutes'));
                    }
                }

                date_add($ini, date_interval_create_from_date_string('5 hours'));
                date_add($fin, date_interval_create_from_date_string('5 hours'));
                $ini = new UTCDateTime(($ini->getTimestamp()) * 1000);
                $fin = new UTCDateTime(($fin->getTimestamp()) * 1000);

                $cursor= Recorrido::where("unidad_id", new ObjectID($request->input('unidad_id')))
                    ->where('fecha_gps','>=',$ini)
                    ->where('fecha_gps','<=',$fin);

                $ev = $request->input('evento');
                if ($ev != 'T')
                    $cursor->where('tipo', $ev);
                $cursor = $cursor->get();

                $unidad= Unidad::findOrFail($request->input('unidad_id'));

                $recorrido=[];

                foreach($cursor as $documento)
                {
					$fecha_gps=$documento["fecha_gps"]->toDateTime();
					$fecha=$documento["fecha"]->toDateTime();
					date_sub($fecha_gps, date_interval_create_from_date_string('10 hours'));
					date_sub($fecha, date_interval_create_from_date_string('5 hours'));
                    array_push($recorrido, (Object)[
                        'fecha' => $fecha_gps->format('Y-m-d H:i:s'),
                        'lat' => $documento["latitud"],
                        'lng' => $documento["longitud"],
                        'angulo' => ($documento["angulo"]!= null) ? $documento["angulo"] : '-',
                        'velocidad' => ($documento["velocidad"]!= null) ? $documento["velocidad"] : '-',
						'fecha_servidor' =>$fecha->format('Y-m-d H:i:s'),
						'placa'=>$unidad->placa,
                        'disco'=>$unidad->descripcion,
                        'tipo'=>$documento["tipo"],
                        'entrada'=>$documento["entrada"],
						'voltaje'=>$documento["voltaje"],
						'contador_diario'=>$documento["contador_diario"],
						'contador_total'=>$documento["contador_total"],
						'estado_movil'=>$documento["estado_movil"]
                    ]);
                }
                return response()->json(['error' => false, 'recorrido' => $recorrido]);
            }

        }

        elseif($request->input('opcion')=='getHistoricoCorteTubo')
        {
            if($request->input('opcion_fecha')!='P')
            {
                $validator = Validator::make($request->all(), [
                    'unidad_id' => 'required'
                ]);
            }
            else
            {
                $validator = Validator::make($request->all(), [
                    'fecha_inicio' => 'required | date',
                    'fecha_fin' => 'required | date',
                    'unidad_id' => 'required'
                ]);
            }
            if ($validator->fails())
                return response()->json(['error' => true]);
            else
            {
                if($request->input('opcion_fecha')=='P')//personalizado
                {
                    $despacho = Despacho::findOrFail($request->input('despacho_id'));//Obtengo el despacho

                    $fin =  $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime();
                    $ini =  $despacho->fecha;
                }

                date_add($ini, date_interval_create_from_date_string('10 hours'));
                date_add($fin, date_interval_create_from_date_string('10 hours'));
                $ini_corte = new UTCDateTime(($ini->getTimestamp()) * 1000);
                $fin_corte = new UTCDateTime(($fin->getTimestamp()) * 1000);

                $cursor= Recorrido::where("unidad_id", new ObjectID($request->input('unidad_id')))
                    ->orderBy('fecha_gps', 'asc')
                    ->whereIn('tipo', ['GTFRI'])
                    ->where('fecha_gps','>=',$ini_corte)
                    ->where('fecha_gps','<=',$fin_corte)
                    ->get();

                $recorrido=[];

                foreach($cursor as $documento)
                {
					$fecha_gps=$documento["fecha_gps"]->toDateTime();
					$fecha=$documento["fecha"]->toDateTime();
					date_sub($fecha_gps, date_interval_create_from_date_string('10 hours'));
					date_sub($fecha, date_interval_create_from_date_string('5 hours'));
                    array_push($recorrido, (Object)[
                        'fecha' => $fecha_gps->format('Y-m-d H:i:s'),
                        'lat' => $documento["latitud"],
                        'lng' => $documento["longitud"],
                        'angulo' => ($documento["angulo"]!= null) ? $documento["angulo"] : '-',
                        'velocidad' => ($documento["velocidad"]!= null) ? $documento["velocidad"] : '-',
						'fecha_servidor' =>$fecha->format('Y-m-d H:i:s'),
						'placa'=>$documento["placa"],
						'voltaje'=>$documento["voltaje"],
						'contador_diario'=>$documento["contador_diario"],
						'contador_total'=>$documento["contador_total"],
						'estado_movil'=>$documento["estado_movil"]
                    ]);
                }
                return response()->json(['error' => false, 'recorrido' => $recorrido]);
            }

        }
    }


}