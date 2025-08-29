<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Cooperativa;
use App\Unidad;
use App\User;
use App\Conductor;
use App\Ruta;
use Validator;
use App\Despacho;
use App\Recorrido;
use MongoDB\BSON\UTCDateTime;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use DateInterval;
use Auth;
use Mail;
use App\PuntoControl;
use App\TipoUsuario;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Bitacora;
use Excel;

class DespachoController extends Controller
{
    public function importar(Request $request)
    {
        if ($request->isMethod('get'))
        {
            $user = Auth::user();
            $cooperativas = Cooperativa::orderBy(
                'descripcion',
                'asc'
            )
            ->where('importador_despachos', true)
            ->where('estado', 'A')->get();
            if ($user->tipo_usuario->valor != 1) {
                $cooperativas = $cooperativas->filter(function ($cooperativa) use ($user) {
                    return $cooperativa->_id == $user->cooperativa_id;
                });
            }
            return view('panel.despachos.importar', [
                'cooperativas' => $cooperativas
            ]);
        }
        else {
            $this->validate($request, [
                'archivo' => 'required|file|mimes:xls,xlsx',
                'cooperativa' => 'required|exists:cooperativas,_id',
            ]);
            $path = $request->file('archivo')->getRealPath();

            $data = Excel::load($path, function($reader) {})->get();
            $logs = array();

            $cooperativa = Cooperativa::findOrFail($request->input('cooperativa'));
            //Validate headers, data is an array of objects
            $headers = $data->first()->keys()->toArray();
            $expectedHeaders = [
                'unidad', 'conductor', 'ruta', 'fecha', 'hora'
            ];
            if (array_diff($expectedHeaders, $headers)) {
                return redirect('importar')
                    ->with('error', 'El archivo no contiene las cabeceras esperadas: ' . implode(', ', $expectedHeaders));
            }

            // return [
            //     'headers' => $headers,
            //     'expectedHeaders' => $expectedHeaders,
            //     'data' => $data
            // ];
            //Loop through each row of the data
            foreach ($data as $index=>$row) {

                $key = $index + 2;

                $unidad = Unidad::where('descripcion', trim((string) $row['unidad']))
                    ->where('cooperativa_id', $cooperativa->_id)
                    ->first();
                if (!isset($unidad)) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Disco de unidad no encontrada en la cooperativa seleccionada: ' . $row['unidad']
                    ];
                    continue;
                }
                $unidad_id = $unidad->_id;
                
                $conductor = Conductor::where('cedula', trim((string) $row['conductor']))
                    ->where('cooperativa_id', $cooperativa->_id)
                    ->first();
                if (!isset($conductor)) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Cedula de conductor no encontrado en la cooperativa seleccionada: ' . $row['conductor']
                    ];
                    continue;
                }
                $conductor_id = $conductor->_id;

                $ruta = Ruta::where('descripcion', trim((string) $row['ruta']))
                    ->where('cooperativa_id', $cooperativa->_id)
                    ->first();
                if (!isset($ruta)) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Ruta no encontrada en la cooperativa seleccionada: ' . $row['ruta']
                    ];
                    continue;
                }
                $ruta_id = $ruta->_id;
                
                //Validate fecha and hora
                if (!isset($row['fecha']) || !isset($row['hora'])) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Fecha u hora no especificadas'
                    ];
                    continue;
                }

                if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $row['fecha'])) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Fecha no valida: ' . $row['fecha']
                    ];
                    continue;
                }

                if (!preg_match('/^\d{2}:\d{2}$/', $row['hora'])) {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Hora no valida: ' . $row['hora'] 
                    ];
                    continue;
                }

                $fecha = Carbon::createFromFormat('d/m/Y', $row['fecha']);
                $hora = $row['hora'];
                $fecha = new Carbon($fecha->format('Y-m-d') . ' ' . $hora . ':00');
                date_sub($fecha, date_interval_create_from_date_string('5 hours'));
                $fechaDespacho = new Carbon($fecha->format('Y-m-d') . ' ' . $hora . ':00');
                $storeReq = [
                    'unidad' => $unidad_id,
                    'conductor' => $conductor_id,
                    'ruta' => $ruta_id,
                    'fecha' => $fechaDespacho->format('Y-m-d H:i:s'),
                ];
                $existe = Despacho::where('fecha', $fecha)->where(
                    'unidad_id',
                    $unidad->_id
                )->where('ruta_id', $ruta->_id)->where('conductor_id', $conductor->_id)
                    ->where('estado', '!=', 'I')
                    ->first();
                if (!isset($existe)) {
                    $this->createDespacho($storeReq);
                    $logs[] = [
                        'linea' => $key,
                        'error' => false,
                        'mensaje' => 'Despacho creado exitosamente para la unidad, ruta y fecha seleccionada: ' . $row['unidad'] . ', ' . $row['ruta'] . ', ' . $row['fecha']
                    ];
                }
                else {
                    $logs[] = [
                        'linea' => $key,
                        'error' => true,
                        'mensaje' => 'Despacho ya existe para la unidad, ruta y fecha seleccionada: ' . $row['unidad'] . ', ' . $row['ruta'] . ', ' . $row['fecha']
                    ];
                    continue;
                }
            }
            return redirect('importar')
                ->with('logs', $logs)
                ->with('cooperativa', $cooperativa->_id);
        }
    }
    public function index()
    {
        $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

        if (Auth::user()->estado == 'A') {
            if ($tipo_usuario->valor != 4) {
                $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
                $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                date_sub($desde, date_interval_create_from_date_string('5 hours'));
                date_sub($hasta, date_interval_create_from_date_string('5 hours'));
                $user = Auth::user();
                if ($user->tipo_usuario->valor == 1) {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->where('estado', 'A')->get();
                    $despachos = collect();
                    // $despachos = Despacho::orderBy(
                    //     'fecha',
                    //     'desc'
                    // )->where('fecha', '>=', $desde)->where(
                    //     'fecha',
                    //     '<=',
                    //     $hasta
                    // )->where('estado', 'P')->paginate(60);
                } else {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->where('_id', $user->cooperativa_id)->where('estado', 'A')->get();
                    if ($user->tipo_usuario->valor != 5) {
                        $unidades = Unidad::where('cooperativa_id', $user->cooperativa_id)->where('estado', 'A')->get();
                        $ids = array();
                        foreach ($unidades as $unidad)
                            array_push($ids, $unidad->_id);
                        $despachos = Despacho::orderBy('fecha', 'desc')->where(
                            'fecha',
                            '>=',
                            $desde
                        )->where('fecha', '<=', $hasta)->where(
                            'estado',
                            'P'
                        )->whereIn(
                            'unidad_id',
                            $ids
                        )->paginate(60);
                        $array = array();

                        /* for($i=0; $i<sizeof($despachos) ;$i++)
                        {
                            date_sub($despachos[$i]["fecha"], date_interval_create_from_date_string('5 hours'));
                        }*/
                    } else {
                        //USUARIO DESPACHADOR ESPECIAL
                        $unidades_pertenecientes = $user->unidades_pertenecientes;
                        $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', $desde)->where(
                            'fecha',
                            '<=',
                            $hasta
                        )->whereIn(
                            'unidad_id',
                            $unidades_pertenecientes
                        )->where('estado', 'P')->paginate(60);
                    }
                }
                return view('panel.despachos', [
                    'cooperativas' => $cooperativas, 'despachos' =>
                    $despachos,
                    'tipo' => 'L', 'desde' => $desde, 'hasta' => $hasta
                ]);
            } else
                return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
        } else
            return view('panel.error', ['mensaje_acceso' => 'En este momento su usuario se encuentra suspendido.']);
    }
    public function frecuencias()
    {
        $user = Auth::user();
        $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

        if (Auth::user()->estado == 'A') {
            if ($tipo_usuario->valor != 4) {
                $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
                $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                date_sub($desde, date_interval_create_from_date_string('5 hours'));
                date_sub($hasta, date_interval_create_from_date_string('5 hours'));
                if ($user->tipo_usuario->valor == 1) {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->where('estado', 'A')->get();
                    $despachos = collect();
                    // $despachos = Despacho::orderBy('fecha', 'desc')->where('created_at', '>=', $desde)->where(
                    //     'created_at',
                    //     '<=',
                    //     $hasta
                    // )->where('estado', 'C')->paginate(60);
                } else {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->where('_id', $user->cooperativa_id)->where('estado', 'A')->get();
                    if ($user->tipo_usuario->valor != 5) {
                        $unidades = Unidad::where('cooperativa_id', $user->cooperativa_id)->where('estado', 'A')->get();
                        $ids = array();
                        foreach ($unidades as $unidad)
                            array_push($ids, $unidad->_id);
                        $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', $desde)->where(
                            'fecha',
                            '<=',
                            $hasta
                        )->whereIn(
                            'unidad_id',
                            $ids
                        )->where('estado', 'C')->paginate(60);

                        /*for($i=0; $i<sizeof($despachos) ;$i++)
                        {
                            date_sub($despachos[$i]["fecha"], date_interval_create_from_date_string('5 hours'));
                        }*/
                    } else {
                        //USUARIO DESPACHADOR ESPECIAL
                        $unidades_pertenecientes = $user->unidades_pertenecientes;
                        $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', $desde)->where(
                            'fecha',
                            '<=',
                            $hasta
                        )->whereIn(
                            'unidad_id',
                            $unidades_pertenecientes
                        )->where('estado', 'C')->paginate(60);
                    }
                }
                return view('panel.despachos', [
                    'cooperativas' => $cooperativas, 'despachos' => $despachos,
                    'tipo' => 'F', 'desde' => $desde, 'hasta' => $hasta
                ]);
            } else
                return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
        } else
            return view('panel.error', ['mensaje_acceso' => 'En este momento su usuario se encuentra suspendido.']);
    }

    public function canceladas()
    {
        $user = Auth::user();
        $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

        if (Auth::user()->estado == 'A') {
            if ($tipo_usuario->valor != 4) {
                $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
                $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                date_sub($desde, date_interval_create_from_date_string('5 hours'));
                date_sub($hasta, date_interval_create_from_date_string('5 hours'));
                if ($user->tipo_usuario->valor == 1) {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->get();
                    $despachos = collect();
                    // $despachos = Despacho::orderBy('fecha', 'desc')->where('created_at', '>=', $desde)->where(
                    //     'created_at',
                    //     '<=',
                    //     $hasta
                    // )->where('estado', 'I')->paginate(60);
                } else {
                    $cooperativas = Cooperativa::orderBy(
                        'descripcion',
                        'asc'
                    )->where('_id', $user->cooperativa_id)->get();
                    if ($user->tipo_usuario->valor != 5) {
                        $unidades = Unidad::where('cooperativa_id', $user->cooperativa_id)->where('estado', 'A')->get();
                        $ids = array();
                        foreach ($unidades as $unidad)
                            array_push($ids, $unidad->_id);
                        $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', $desde)->where(
                            'fecha',
                            '<=',
                            $hasta
                        )->whereIn(
                            'unidad_id',
                            $ids
                        )->where('estado', 'I')->paginate(60);

                        /*for($i=0; $i<sizeof($despachos) ;$i++)
                        {
                            date_sub($despachos[$i]["fecha"], date_interval_create_from_date_string('5 hours'));
                        }*/
                    } else {
                        //USUARIO DESPACHADOR ESPECIAL
                        $unidades_pertenecientes = $user->unidades_pertenecientes;
                        $despachos = Despacho::orderBy('fecha', 'desc')->where('fecha', '>=', $desde)->where(
                            'fecha',
                            '<=',
                            $hasta
                        )->whereIn(
                            'unidad_id',
                            $unidades_pertenecientes
                        )->where('estado', 'I')->paginate(60);
                    }
                }
                return view('panel.despachos', [
                    'cooperativas' => $cooperativas, 'despachos' => $despachos,
                    'tipo' => 'C', 'desde' => $desde, 'hasta' => $hasta
                ]);
            } else
                return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
        } else
            return view('panel.error', ['mensaje_acceso' => 'En este momento su usuario se encuentra suspendido.']);
    }

    public function find(Request $request)
    {
        $this->validate($request, [
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'tipo' => 'required|size:1',
            'cooperativa' => 'required|exists:cooperativas,_id',
            'unidades' => 'required|array'
        ]);
        $desde = new Carbon($request->input('desde') . ' 00:00:00');
        $hasta = new Carbon($request->input('hasta') . '23:59:59');
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));
        $tipo = $request->input('tipo');
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1)
            $cooperativas = Cooperativa::orderBy(
                'descripcion',
                'asc'
            )->get();
        else
            $cooperativas = Cooperativa::orderBy(
                'descripcion',
                'asc'
            )->where('_id', $user->cooperativa_id)->get();
        $despachos = Despacho::orderBy('fecha', 'desc')->whereIn(
            'unidad_id',
            $request->input('unidades')
        )->where('fecha', '>=', $desde)->where('fecha', '<=', $hasta);
        if ($tipo === 'L')
            $despachos->where('estado', 'P');
        else {
            if ($tipo === 'F')
                $despachos->where('estado', 'C');
            else
                $despachos->where('estado', 'I');
        }

        if ($request->input('errorAtm')) {
            $despachos->where('error_ATM', 'exists', true);
        }

        $despachos = $despachos->paginate(60);
        $despachos->setPath($request->fullUrl());
        return view('panel.despachos', [
            'cooperativas' => $cooperativas, 'despachos' => $despachos,
            'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'cooperativa_search' => $request->input('cooperativa'),
            'unidades_search' => $request->input('unidades'), 'filtro_fecha' => $request->input('filtro_fecha')
        ]);
    }

    public function end(Request $request, $id)
    {
        set_time_limit(0);
        $despacho = Despacho::findOrFail($id); //Obtengo el despacho
        $cooperativa = $despacho->unidad->cooperativa;
        $despacho_tmp = $despacho;
        $cooperativa_cortetubo = $despacho->unidad->cooperativa->multa_tubo;
        $fin = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
        $ini = $despacho->fecha; //fecha de inicio del despacho (salida del bus)
        $fin_corte = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
        $ini_corta = $despacho->fecha;
        $tiempoRango1 = 90;

        $total_puntoscontrol = count($despacho->puntos_control);
        $_total_pc = 1;

        $defaultMinutesIni = 570;
        $defaultMinutesFin = 700;

        if (!empty($cooperativa->tolerancia_buffer_minutos)) {
            $tolerancia = intval($cooperativa->tolerancia_buffer_minutos);
            $defaultMinutesIni = 600 - $tolerancia;
            $defaultMinutesFin = 600 + $tolerancia;
        }

        date_add($ini, date_interval_create_from_date_string($defaultMinutesIni  . ' minutes')); // Agrego 10 horas a la consulta de fecha de GPS inicial
        date_add($fin, date_interval_create_from_date_string($defaultMinutesFin . ' minutes')); // Agrego 10 horas a la fecha de fin, fecha de culminacion
        date_add($ini_corta, date_interval_create_from_date_string('10 hours')); // Agrego 10 horas a la consulta de fecha de GPS inicial
        date_add($fin_corte, date_interval_create_from_date_string('10 hours'));

        $fini = new UTCDateTime(($ini->getTimestamp() * 1000)); //Inicio
        $ffin = new UTCDateTime(($fin->getTimestamp() * 1000)); //Fin

        $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
            ->where('tipo', 'GTGEO')
            ->where('unidad_id', new ObjectID($despacho->unidad_id))
            ->where('fecha_gps', '>=', $fini)
            ->where('fecha_gps', '<=', $ffin)->get(); //Recorridos entre el inicio del despacho y el final esperado del despacho

        $array_final = array();
        $array_temp = array();
        $multa = 0.0;
        $multaMinuto = 0.0;
        $ultimo_punto = null;
        $index = 0;
        $primerpunto = true;

        if (sizeof($recorridos) > 0) {
            foreach ($despacho->puntos_control as $punto_control) {
                $puntoControlEsperado = null;
                $intervalo = null;
                // if(isset($punto_control["punto_regreso"]) && $punto_control["punto_regreso"] != null)
                //     $punto_regreso=$punto_control["punto_regreso"];
                // else
                //     $punto_regreso='N'; 

                $item = [
                    "id" => $punto_control["id"], "tiempo_esperado" =>
                    $punto_control["tiempo_esperado"], "adelanto" => $punto_control["adelanto"],
                    "atraso" => $punto_control["atraso"], "marca" => null, 'contador_marca' => null,
                    'tiempo_atraso' => null, 'tiempo_adelanto' => null, 'intervalo' => null
                ];

                $puntoControlObj = PuntoControl::find($punto_control['id']);
                if (isset($puntoControlObj)) {
                    $tiempoEsperado = $punto_control['tiempo_esperado']->toDateTime();
                    $consulta_1 = $punto_control['tiempo_esperado']->toDateTime();
                    date_add($tiempoEsperado, date_interval_create_from_date_string('10 hours'));
                    date_add($consulta_1, date_interval_create_from_date_string('10 hours'));

                    if ($index == 0) {
                        $consulta = $punto_control['tiempo_esperado']->toDateTime();
                        date_add($consulta, date_interval_create_from_date_string('10 hours'));
                    }

                    if ($primerpunto) {
                        /****VERIFICANDO PUINTOS DE CONTROL PRIMEROS POR SALIDA DEL PUNTO */
                        $puntoControlEsperado_inicio = Recorrido::orderBy('fecha_gps', 'desc')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '>=', $fini)
                            ->where('fecha_gps', '<=', $consulta_1)
                            ->where('entrada', '!=', 1)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                        if (isset($puntoControlEsperado_inicio)) {
                            $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                            $fechaLinea_ = $fechaGPS_->format('H:i');
                            $diff_ = $tiempoEsperado->diff($fechaGPS_);
                            $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                            $puntoControlEsperado_salida = Recorrido::orderBy('fecha_gps')->where(
                                'tipo',
                                'GTGEO'
                            )->where('fecha_gps', '<=', $ffin)
                                ->where('fecha_gps', '>=', $consulta)
                                ->where('entrada', '!=', 1)
                                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();

                            if (isset($puntoControlEsperado_salida)) {
                                $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                $fechaLinea = $fechaGPS->format('H:i');
                                $diff = $tiempoEsperado->diff($fechaGPS);
                                $intervalo = $diff->format('%h:%i:%s');
                                $distancia = (($diff->i) + (($diff->h) * 60));
                                if ($distancia >  $tiempoRango1) {
                                    $puntoControlEsperado = $puntoControlEsperado_inicio;
                                } else {
                                    if ($distancia_ > $distancia) {
                                        $puntoControlEsperado = $puntoControlEsperado_salida;
                                    } else {
                                        if ($distancia_ < $tiempoRango1) {
                                            $puntoControlEsperado = $puntoControlEsperado_inicio;
                                        } else {
                                            $puntoControlEsperado = null;
                                        }
                                    }
                                }
                            } else {
                                $puntoControlEsperado = $puntoControlEsperado_inicio;
                            }
                        } else {

                            $puntoControlEsperado_salida = Recorrido::orderBy('fecha_gps')->where(
                                'tipo',
                                'GTGEO'
                            )->where('fecha_gps', '<=', $ffin)
                                ->where('fecha_gps', '>=', $consulta)
                                ->where('entrada', '!=', 1)
                                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();
                            if (!isset($puntoControlEsperado_salida)) {
                                /****VERIFICANDO PUINTOS DE CONTROL PRIMEROS DEFAULT SALIDA DEL PUNTO */
                                $puntoControlEsperado = null;

                                $puntoControlEsperado_inicio = Recorrido::orderBy('fecha_gps', 'desc')->where(
                                    'tipo',
                                    'GTGEO'
                                )->where('fecha_gps', '>=', $fini)
                                    ->where('fecha_gps', '<=', $consulta_1)
                                    ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                    /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                                    ->where('pdi', (int) $puntoControlObj->pdi)->first();
                                if (isset($puntoControlEsperado_inicio)) {
                                    $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                                    $fechaLinea_ = $fechaGPS_->format('H:i');
                                    $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                    $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                                    $puntoControlEsperado_salida = Recorrido::orderBy('fecha_gps')->where(
                                        'tipo',
                                        'GTGEO'
                                    )->where('fecha_gps', '<=', $ffin)
                                        ->where('fecha_gps', '>=', $consulta)
                                        ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                        ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                    if (isset($puntoControlEsperado_salida)) {
                                        $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                        $fechaLinea = $fechaGPS->format('H:i');
                                        $diff = $tiempoEsperado->diff($fechaGPS);
                                        $intervalo = $diff->format('%h:%i:%s');
                                        $distancia = (($diff->i) + (($diff->h) * 60));
                                        if ($distancia_ < $tiempoRango1) {
                                            $puntoControlEsperado = $puntoControlEsperado_inicio;
                                        } else {
                                            if ($distancia < $tiempoRango1) {
                                                $puntoControlEsperado = $puntoControlEsperado_salida;
                                            } else {
                                                $puntoControlEsperado = null;
                                            }
                                        }
                                    } else {
                                        $puntoControlEsperado = $puntoControlEsperado_inicio;
                                    }
                                } else {
                                    $puntoControlEsperado = null;
                                }
                            } else {
                                $puntoControlEsperado = $puntoControlEsperado_salida;
                            }
                        }
                    } else {

                        $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->orderBy('fecha_gps', 'desc')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '>', $fini)
                            ->where('fecha_gps', '<=', $tiempoEsperado)
                            ->where('entrada', 1)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();
                    }

                    if (isset($puntoControlEsperado)) {
                        $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                        $fechaLinea = $fechaGPS->format('H:i');
                        $diff = $tiempoEsperado->diff($fechaGPS);
                        $intervalo = $diff->format('%h:%i:%s');
                        $distancia = (($diff->i) + (($diff->h) * 60));

                        $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '<=', $ffin)
                            ->where('fecha_gps', '>=', $tiempoEsperado)
                            ->where('entrada', 1)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                        if (isset($puntoControlEsperado_tmp)) {
                            $fechaGPS = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                            $fechaLinea = $fechaGPS->format('H:i');
                            $diff = $tiempoEsperado->diff($fechaGPS);
                            $intervalo = $diff->format('%h:%i:%s');
                            $distancia_tmp = (($diff->i) + (($diff->h) * 60));
                            if ($distancia > $distancia_tmp) {
                                $puntoControlEsperado = $puntoControlEsperado_tmp;
                                $distancia = $distancia_tmp;
                            }
                        }

                        if ($total_puntoscontrol != $_total_pc) {
                            if ($distancia > $tiempoRango1) {
                                $puntoControlEsperado = null;
                                $intervalo = null;
                            }
                        } else {
                            if ($distancia > $tiempoRango1) {
                                $puntoControlEsperado = null;
                                $intervalo = null;
                            }
                        }
                    }

                    if (!isset($puntoControlEsperado)) { //+
                        if ($punto_control['id'] == new ObjectID("599220163ebdfd2c9a4d4a12")) {
                            if ($consulta > $tiempoEsperado) {
                                $tiempoEsperado = $consulta;
                            }
                        }
                        $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '<=', $ffin)
                            ->where('fecha_gps', '>=', $tiempoEsperado)
                            ->where('entrada', 1)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                        if (!isset($puntoControlEsperado)) {
                            $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                                'tipo',
                                'GTGEO'
                            )->where('fecha_gps', '<=', $ffin)
                                ->where('fecha_gps', '>=', $tiempoEsperado)
                                ->where('entrada', 0)
                                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();
                        }

                        if (isset($puntoControlEsperado)) {
                            $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                            $fechaLinea = $fechaGPS->format('H:i');
                            $diff = $tiempoEsperado->diff($fechaGPS);
                            $intervalo = $diff->format('%h:%i:%s');
                            $distancia = (($diff->i) + (($diff->h) * 60));

                            if ($primerpunto) {
                                if ($distancia > $tiempoRango1) {
                                    $puntoControlEsperado = null;
                                    $intervalo = null;
                                }
                            } else {
                                if (
                                    $punto_control['id'] == new ObjectID("5983ce813ebdfd42792a9982") || $punto_control['id'] == new ObjectID("582a62087aea9118d059b081")
                                    || $punto_control['id'] == new ObjectID("5b23fe6a2243df528f5d4593") || $punto_control['id'] == new ObjectID("599220163ebdfd2c9a4d4a12")
                                ) { //PPUNTO FACSO CAMBERRAS  --- TERMINAL TERRESTE NUEVO ECUADOR --MALL DEL SUR CAYETANO
                                    if ($distancia > $tiempoRango1) {
                                        $puntoControlEsperado = null;
                                        $intervalo = null;
                                    }
                                } else {
                                    $item_tmp = ["fecha_tmpmen" => null, "fecha_" => null, "fecha_1" => null, "fecha_2" => null, "fecha_11" => null];
                                    if ($total_puntoscontrol != $_total_pc) {
                                        foreach ($despacho_tmp->puntos_control as $punto_control_tmp) {

                                            $puntoControlEsperado_tmp = null;
                                            $tiempoEsperado_tmp = null;

                                            $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                            date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));

                                            if ($tiempoEsperado_tmp > $tiempoEsperado) {
                                                /**PREGUJNTAR SI EL PUNTO ES DE REGRESO */
                                                // if(isset($punto_control_tmp["punto_regreso"]) && $punto_control_tmp["punto_regreso"] != null)
                                                //     $punto_regreso=$punto_control_tmp["punto_regreso"];
                                                // else
                                                //     $punto_regreso='N';            

                                                $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                                date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));
                                                $puntoControlObj_tmp = PuntoControl::find($punto_control_tmp['id']);

                                                $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps', 'desc')->where(
                                                    'tipo',
                                                    'GTGEO'
                                                )->where('fecha_gps', '>', $fini)
                                                    ->where('entrada', 1)
                                                    ->where('fecha_gps', '<=', $tiempoEsperado_tmp)
                                                    ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                    ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                // $item_tmp["fecha_"] = $fechaGPS;                                                    
                                                if (isset($puntoControlEsperado_tmp)) {
                                                    $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();

                                                    $item_tmp["fecha_11"] = $fechaGPS_tmp;
                                                    $item_tmp["fecha_2"] = $tiempoEsperado;
                                                    // if($fechaGPS > $fechaGPS_tmp && $fechaGPS >$fechaGPS_tmp_2 &&  $tiempoEsperado < $fechaGPS_tmp){
                                                    if ($fechaGPS > $fechaGPS_tmp) {
                                                        $puntoControlEsperado = null;
                                                        break;
                                                    } else {
                                                        $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                            'tipo',
                                                            'GTGEO'
                                                        )->where('fecha_gps', '<=', $ffin)
                                                            ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                            ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                            ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                        if (isset($puntoControlEsperado_tmp)) {
                                                            $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();

                                                            if ($fechaGPS > $fechaGPS_tmp) {
                                                                $puntoControlEsperado = null;
                                                                break;
                                                            }
                                                        } else {
                                                            if ($distancia > $tiempoRango1) {
                                                                $puntoControlEsperado = null;
                                                                $intervalo = null;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    //************************ 
                                                    $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                        'tipo',
                                                        'GTGEO'
                                                    )->where('fecha_gps', '<=', $ffin)
                                                        //->where('entrada',1)
                                                        ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                        ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                        ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                    // $item_tmp["fecha_"] = $fechaGPS;

                                                    if (isset($puntoControlEsperado_tmp)) {
                                                        $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                        $item_tmp["fecha_tmpmen"] = $fechaGPS_tmp;

                                                        if ($fechaGPS > $fechaGPS_tmp) {
                                                            $puntoControlEsperado = null;
                                                            break;
                                                        }
                                                    } else //************************* 
                                                    {
                                                        if ($distancia > $tiempoRango1) {
                                                            $puntoControlEsperado = null;
                                                            $intervalo = null;
                                                        }
                                                    }
                                                }
                                                break;

                                                array_push($array_temp, $item_tmp);
                                            }
                                        }
                                    } else {
                                        if ($distancia > $tiempoRango1) {
                                            $puntoControlEsperado = null;
                                            $intervalo = null;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $primerpunto = false;
                }

                if (isset($puntoControlEsperado)) {
                    $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                    $fechaGPS_ = $puntoControlEsperado->fecha_gps->toDateTime();

                    $diff = $tiempoEsperado->diff($fechaGPS);
                    $intervalo = $diff->format('%h:%i:%s');
                    $tiempo_atraso = null;
                    $tiempo_adelanto = null;

                    if ($tiempoEsperado >= $fechaGPS) {
                        $intervalo = (($diff->i) + ($diff->h * 60)) * -1;
                        $multa = $multa + (((float)$punto_control["adelanto"]) * $intervalo * -1);
                        $tiempo_adelanto = $diff->format('%h:%i:%s');
                    } else if ($tiempoEsperado <= $fechaGPS) {
                        if ($cooperativa->redondear_tiempos_atraso) {
                            $segundosDiff = $fechaGPS->getTimestamp() - $tiempoEsperado->getTimestamp();
                            $intervalo = ceil($segundosDiff / 60);
                            $multa = $multa + (((float)$punto_control["atraso"]) * $intervalo);
                            $tiempo_atraso = $diff->format('%h:%i:%s');
                        }
                        else {
                            $intervalo = (($diff->i) + ($diff->h * 60));
                            $multa = $multa + (((float)$punto_control["atraso"]) * $intervalo);
                            $tiempo_atraso = $diff->format('%h:%i:%s');
                        }
                    } else {
                        $intervalo = '0';
                    }

                    date_sub($fechaGPS, date_interval_create_from_date_string('10 hours'));
                    $fini = $fechaGPS_;
                    $fini = new UTCDateTime(($fini->getTimestamp() * 1000));

                    $consulta = $fechaGPS_;

                    $fechaLinea = $fechaGPS->format('H:i');

                    $item["marca"] = $fechaGPS->format('Y-m-d H:i:s');
                    $item["contador_marca"] = $puntoControlEsperado["contador_total"];
                    $item["tiempo_atraso"] = $tiempo_atraso;
                    $item["tiempo_adelanto"] = $tiempo_adelanto;
                    $item["intervalo"] = $intervalo;

                    $index++;
                }

                $_total_pc++;
                array_push($array_final, $item);
            }

            $paso = "si";
            $despacho->recorridos = $paso;
            $despacho->puntos_control = $array_final;

            $contador = 0;
            if (count($despacho->puntos_control) > 0) {
               // $despacho->contador_inicial = ($despacho->puntos_control[0]['contador_marca'] != null) ? $despacho->puntos_control[0]['contador_marca'] : 0;
                $contador = ($despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] != null) ? $despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] : 0;
                $despacho->salida = $despacho->puntos_control[0]['marca'];
            }

            if ($contador < 0)
                $contador = 65535 + $contador;

            $despacho->contador_final = $contador;
            if ($despacho->estado != 'C')
                $despacho->fecha_culminacion = Carbon::now();
            $despacho->estado = 'C';

            $array_aux = []; //Arreglo a valida    
            $despacho->auxiliar = $array_aux;

            /*****INICIO CORTE DE TUBO */
            $corteTubo = 'No';
            $array_recorridos_corte = array();

            // $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
            // ->whereIn('tipo', ['GTFRI'])
            // ->where('unidad_id', new ObjectID($despacho->unidad_id))
            // ->where('fecha_gps', '>=', $fini_corte)
            // ->where('fecha_gps', '<=', $ffin_corte)
            // ->select('latitud','longitud')->get();
            $latitud_corte = 0.0;
            $longitud_corte = 0.0;

            /***FIN CORTE DE TUBO***/
            if ($corteTubo == "Si") {
                if (isset($cooperativa_cortetubo) && $cooperativa_cortetubo != "") {
                    $despacho->multa = $multa + ((int)$cooperativa_cortetubo);
                } else {
                    $despacho->multa = $multa;
                }
            } else {
                $despacho->multa = $multa;
            }

            $despacho->corte_tubo = $corteTubo;
            $despacho->array_corte = $array_recorridos_corte;
            $despacho->latitud_corte = $latitud_corte;
            $despacho->longitud_corte = $longitud_corte;
            $despacho->modificador_id = Auth::user()->_id;
            $despacho->fecha_culminacion = new Carbon();
            $despacho->save();

            return response()->json([
                'error' => false, 'despacho' => $despacho, 'marcar' => $array_final,
                'paso' => $paso, 'array_temp' => $array_temp, 'recorridos' => $recorridos, 'rutarecorrido' => $despacho->ruta->recorrido
            ]);
            //***********************fin de algoritmo arreglo de marcas*************************
        } else {
            $despacho->corte_tubo = '-';
            if ($despacho->estado != 'C')
                $despacho->fecha_culminacion = Carbon::now();
            $despacho->estado = 'C';
            $despacho->modificador_id = Auth::user()->_id;
            $despacho->save();
            return response()->json(['error' => true, 'despacho' => null]);
        }
    }

    public function endtmp(Request $request, $id)
    {
        $despacho = Despacho::findOrFail($id); //Obtengo el despacho
        $cooperativa = $despacho->unidad->cooperativa->_id;
        $despacho_tmp = $despacho;
        $cooperativa_cortetubo = $despacho->unidad->cooperativa->multa_tubo;
        $fin = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
        $ini = $despacho->fecha; //fecha de inicio del despacho (salida del bus)
        $fin_corte = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
        $ini_corta = $despacho->fecha;

        $total_puntoscontrol = count($despacho->puntos_control);
        $_total_pc = 1;

        date_add($ini, date_interval_create_from_date_string('570 minutes')); // Agrego 10 horas a la consulta de fecha de GPS inicial
        date_add($fin, date_interval_create_from_date_string('12 hours')); // Agrego 10 horas a la fecha de fin, fecha de culminacion
        date_add($ini_corta, date_interval_create_from_date_string('10 hours')); // Agrego 10 horas a la consulta de fecha de GPS inicial
        date_add($fin_corte, date_interval_create_from_date_string('10 hours'));

        $fini = new UTCDateTime(($ini->getTimestamp() * 1000)); //Inicio
        $ffin = new UTCDateTime(($fin->getTimestamp() * 1000)); //Fin
        $fini_corte = new UTCDateTime(($ini_corta->getTimestamp() * 1000)); //Inicio
        $ffin_corte = new UTCDateTime(($fin_corte->getTimestamp() * 1000)); //Fin

        $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
            ->where('tipo', 'GTGEO')
            ->where('unidad_id', new ObjectID($despacho->unidad_id))
            ->where('fecha_gps', '>=', $fini)
            ->where('fecha_gps', '<=', $ffin)->get(); //Recorridos entre el inicio del despacho y el final esperado del despacho

        $array_final = array();
        $array_temp = array();
        $multa = 0.0;
        $ultimo_punto = null;
        $index = 0;
        $primerpunto = true;

        if (sizeof($recorridos) > 0) {
            foreach ($despacho->puntos_control as $punto_control) {
                $puntoControlEsperado = null;
                $intervalo = null;
                $item = [
                    "id" => $punto_control["id"], "tiempo_esperado" =>
                    $punto_control["tiempo_esperado"], "adelanto" => $punto_control["adelanto"],
                    "atraso" => $punto_control["atraso"], "marca" => null, 'contador_marca' => null,
                    'tiempo_atraso' => null, 'tiempo_adelanto' => null, 'intervalo' => null
                ];

                $puntoControlObj = PuntoControl::find($punto_control['id']);
                if (isset($puntoControlObj)) {
                    $tiempoEsperado = $punto_control['tiempo_esperado']->toDateTime();
                    $consulta_1 = $punto_control['tiempo_esperado']->toDateTime();
                    date_add($tiempoEsperado, date_interval_create_from_date_string('10 hours'));
                    date_add($consulta_1, date_interval_create_from_date_string('10 hours'));

                    if ($index == 0) {
                        $consulta = $punto_control['tiempo_esperado']->toDateTime();
                        date_add($consulta, date_interval_create_from_date_string('10 hours'));
                    }

                    if ($primerpunto) {
                        $puntoControlEsperado_inicio = Recorrido::orderBy('fecha_gps', 'desc')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '>=', $fini)
                            ->where('fecha_gps', '<=', $consulta_1)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();



                        if (isset($puntoControlEsperado_inicio)) {
                            $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                            $fechaLinea_ = $fechaGPS_->format('H:i');
                            $diff_ = $tiempoEsperado->diff($fechaGPS_);
                            $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                            $puntoControlEsperado_salida = Recorrido::orderBy('fecha_gps')->where(
                                'tipo',
                                'GTGEO'
                            )->where('fecha_gps', '<=', $ffin)
                                ->where('fecha_gps', '>=', $consulta)
                                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();

                            if (isset($puntoControlEsperado_salida)) {
                                $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                $fechaLinea = $fechaGPS->format('H:i');
                                $diff = $tiempoEsperado->diff($fechaGPS);
                                $intervalo = $diff->format('%h:%i:%s');
                                $distancia = (($diff->i) + (($diff->h) * 60));
                                if ($distancia > 35) {
                                    $puntoControlEsperado = $puntoControlEsperado_inicio;
                                } else {
                                    if ($distancia_ < 20) {
                                        $puntoControlEsperado = $puntoControlEsperado_inicio;
                                    } else {
                                        $puntoControlEsperado = null;
                                    }
                                }
                            } else {
                                $puntoControlEsperado = $puntoControlEsperado_inicio;
                            }
                        } else {
                            $puntoControlEsperado = null;
                        }
                    } else {
                        $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '>=', $fini)
                            ->where('fecha_gps', '<=', $tiempoEsperado)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();
                    }

                    if (isset($puntoControlEsperado)) {
                        $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                        $fechaLinea = $fechaGPS->format('H:i');
                        $diff = $tiempoEsperado->diff($fechaGPS);
                        $intervalo = $diff->format('%h:%i:%s');
                        $distancia = (($diff->i) + (($diff->h) * 60));
                        /* if ($distancia > 35)
                        {
                            $puntoControlEsperado = null;
                            $intervalo = null;
                        }*/

                        if ($total_puntoscontrol != $_total_pc) {
                            $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                'tipo',
                                'GTGEO'
                            )->where('fecha_gps', '<=', $ffin)
                                ->where('fecha_gps', '>=', $tiempoEsperado)
                                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();

                            if (isset($puntoControlEsperado_tmp)) {
                                $fechaGPS_ = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                                if ($distancia > $distancia_) {
                                    $puntoControlEsperado = null;
                                }
                            }
                        } else {
                            if ($distancia > 60) {
                                $puntoControlEsperado = null;
                                $intervalo = null;
                            }
                        }
                    }

                    if (!isset($puntoControlEsperado)) { //+
                        $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                            'tipo',
                            'GTGEO'
                        )->where('fecha_gps', '<=', $ffin)
                            ->where('fecha_gps', '>=', $tiempoEsperado)
                            ->where('fecha_gps', '>', $fini)
                            ->where('unidad_id', new ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                        if (isset($puntoControlEsperado)) {
                            $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                            $fechaLinea = $fechaGPS->format('H:i');
                            $diff = $tiempoEsperado->diff($fechaGPS);
                            $intervalo = $diff->format('%h:%i:%s');
                            $distancia = (($diff->i) + (($diff->h) * 60));

                            if ($primerpunto) {
                                if ($distancia > 35) {
                                    $puntoControlEsperado = null;
                                    $intervalo = null;
                                }
                            } else {
                                if ($punto_control['id'] == new ObjectID("5983ce813ebdfd42792a9982") || $punto_control['id'] == new ObjectID("582a62087aea9118d059b081")) { //PPUNTO FACSO CAMBERRAS  --- TERMINAL TERRESTE NUEVO ECUADOR
                                    if ($distancia > 40) {
                                        $puntoControlEsperado = null;
                                        $intervalo = null;
                                    }
                                } else {
                                    $item_tmp = ["fecha_tmpmen" => null, "fecha_" => null];
                                    if ($total_puntoscontrol != $_total_pc) {
                                        //if($cooperativa != "5a1351133ebdfd76eb7884b2" && $cooperativa != "5a1860f93ebdfd3ff8258572"){            
                                        foreach ($despacho_tmp->puntos_control as $punto_control_tmp) {

                                            $puntoControlEsperado_tmp = null;
                                            $tiempoEsperado_tmp = null;

                                            $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                            date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));

                                            if ($tiempoEsperado_tmp > $tiempoEsperado) {
                                                $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                                date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));
                                                $puntoControlObj_tmp = PuntoControl::find($punto_control_tmp['id']);

                                                $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps', 'desc')->where(
                                                    'tipo',
                                                    'GTGEO'
                                                )->where('fecha_gps', '>=', $fini)
                                                    ->where('fecha_gps', '<=', $tiempoEsperado_tmp)
                                                    ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                    ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();


                                                if (isset($puntoControlEsperado_tmp)) {
                                                    $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                    if ($fechaGPS > $fechaGPS_tmp &&  $tiempoEsperado < $fechaGPS_tmp) {
                                                        $puntoControlEsperado = null;
                                                        break;
                                                    } else {

                                                        $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                            'tipo',
                                                            'GTGEO'
                                                        )->where('fecha_gps', '<=', $ffin)
                                                            ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                            ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                            ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                        $item_tmp["fecha_"] = $fechaGPS;

                                                        if (isset($puntoControlEsperado_tmp)) {
                                                            $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                            $item_tmp["fecha_tmpmen"] = $cooperativa;

                                                            if ($fechaGPS > $fechaGPS_tmp) {
                                                                $puntoControlEsperado = null;
                                                                break;
                                                            }/*else{
                                                                if ($distancia > 25)
                                                                {
                                                                    $puntoControlEsperado = null;
                                                                    $intervalo = null;
                                                                }    
                                                            } */
                                                        } else {
                                                            if ($distancia > 35) {
                                                                $puntoControlEsperado = null;
                                                                $intervalo = null;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    //************************ */
                                                    $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                        'tipo',
                                                        'GTGEO'
                                                    )->where('fecha_gps', '<=', $ffin)
                                                        ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                        ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                        ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                    $item_tmp["fecha_"] = $fechaGPS;

                                                    if (isset($puntoControlEsperado_tmp)) {
                                                        $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                        $item_tmp["fecha_tmpmen"] = $cooperativa;

                                                        if ($fechaGPS > $fechaGPS_tmp) {
                                                            $puntoControlEsperado = null;
                                                            break;
                                                        }/*else{
                                                            if ($distancia > 25)
                                                            {
                                                                $puntoControlEsperado = null;
                                                                $intervalo = null;
                                                            }    
                                                        } */
                                                    } else
                                                    /************************* */
                                                    {
                                                        if ($distancia > 35) {
                                                            $puntoControlEsperado = null;
                                                            $intervalo = null;
                                                        }
                                                    }
                                                }
                                                array_push($array_temp, $item_tmp);
                                                break;
                                            }
                                        }
                                    } else {
                                        if ($distancia > 60) {
                                            $puntoControlEsperado = null;
                                            $intervalo = null;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $primerpunto = false;
                }

                if (isset($puntoControlEsperado)) {
                    $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                    $fechaGPS_ = $puntoControlEsperado->fecha_gps->toDateTime();

                    $diff = $tiempoEsperado->diff($fechaGPS);
                    $intervalo = $diff->format('%h:%i:%s');
                    $tiempo_atraso = null;
                    $tiempo_adelanto = null;


                    if ($tiempoEsperado > $fechaGPS) {
                        $intervalo = (($diff->i) + ($diff->h * 60)) * -1;
                        $multa = $multa + (((float)$punto_control["adelanto"]) * $intervalo * -1);
                        $tiempo_adelanto = $diff->format('%h:%i:%s');
                    } else if ($tiempoEsperado < $fechaGPS) {
                        $intervalo = (($diff->i) + ($diff->h * 60));
                        $multa = $multa + (((float)$punto_control["atraso"]) * $intervalo);
                        $tiempo_atraso = $diff->format('%h:%i:%s');
                    } else {
                        $intervalo = '0';
                    }

                    date_sub($fechaGPS, date_interval_create_from_date_string('10 hours'));
                    $fini = $fechaGPS_;
                    $fini = new UTCDateTime(($fini->getTimestamp() * 1000));

                    $consulta = $fechaGPS_;

                    $fechaLinea = $fechaGPS->format('H:i');

                    $item["marca"] = $fechaGPS->format('Y-m-d H:i:s');
                    $item["contador_marca"] = $puntoControlEsperado["contador_total"];
                    $item["tiempo_atraso"] = $tiempo_atraso;
                    $item["tiempo_adelanto"] = $tiempo_adelanto;
                    $item["intervalo"] = $intervalo;

                    $index++;
                }

                $_total_pc++;
                array_push($array_final, $item);
            }

            $paso = "si";
            $despacho->recorridos = $paso;
            $despacho->puntos_control = $array_final;

            $contador = 0;
            if (count($despacho->puntos_control) > 0) {
            //    $despacho->contador_inicial = ($despacho->puntos_control[0]['contador_marca'] != null) ? $despacho->puntos_control[0]['contador_marca'] : 0;
                $contador = ($despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] != null) ? $despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] : 0;
                $despacho->salida = $despacho->puntos_control[0]['marca'];
            }

            if ($contador < 0)
                $contador = 65535 + $contador;

            $despacho->contador_final = $contador;
            $despacho->estado = 'C';

            $array_aux = []; //Arreglo a valida    
            $despacho->auxiliar = $array_aux;

            /*****INICIO CORTE DE TUBO */
            $corteTubo = 'No';
            $array_recorridos_corte = array();

            $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
                ->whereIn('tipo', ['GTFRI'])
                ->where('unidad_id', new ObjectID($despacho->unidad_id))
                ->where('fecha_gps', '>=', $fini_corte)
                ->where('fecha_gps', '<=', $ffin_corte)->get();
            $latitud_corte = 0.0;
            $longitud_corte = 0.0;

            /***FIN CORTE DE TUBO***/
            if ($corteTubo == "Si") {
                if (isset($cooperativa_cortetubo) && $cooperativa_cortetubo != "") {
                    $despacho->multa = $multa + ((int)$cooperativa_cortetubo);
                } else {
                    $despacho->multa = $multa;
                }
            } else {
                $despacho->multa = $multa;
            }

            $despacho->corte_tubo = $corteTubo;
            $despacho->array_corte = $array_recorridos_corte;
            $despacho->latitud_corte = $latitud_corte;
            $despacho->longitud_corte = $longitud_corte;
            $despacho->save();

            return response()->json([
                'error' => false, 'despacho' => $despacho, 'marcar' => $array_final,
                'paso' => $paso, 'array_temp' => $array_temp, 'recorridos' => $recorridos, 'rutarecorrido' => $despacho->ruta->recorrido
            ]);
            //***********************fin de algoritmo arreglo de marcas*************************
        } else {
            $despacho->corte_tubo = '-';
            $despacho->estado = 'C';
            $despacho->save();
            return response()->json(['error' => true, 'despacho' => null]);
        }
    }

    public function reproductor(Request $request, $id) {
        $despacho = Despacho::findOrFail($id);
        return view('panel.despachos.reproductor', compact('despacho'));
    }

    public function cortetubo(Request $request)
    {
        $despacho = Despacho::findOrFail($request->input('despacho_id'));
        $cooperativa_cortetubo = $despacho->unidad->cooperativa->multa_tubo;
        $despacho->corte_tubo = 'Si';
        $multa = $despacho->multa;
        if (isset($cooperativa_cortetubo) && $cooperativa_cortetubo != "") {
            $despacho->multa = $multa + ((int)$cooperativa_cortetubo);
        }
        $despacho->coord_corte_tubo = $request->input('array_corte');
        $despacho->save();

        $fecha_flag = $despacho->fecha;
        date_add($fecha_flag, date_interval_create_from_date_string('5 hours'));
        $unidad = Unidad::findOrFail($despacho->unidad_id);
        $unidad->alerta_fecha_cortetubo = Carbon::now()->format('Y-m-d H:i:s');
        $unidad->alerta_cortetubo = 'Corte tubo despacho: ' . $fecha_flag->format('Y-m-d H:i:s') . ' Ruta: ' . $despacho->ruta->descripcion .
            ' Conductor: ' . $despacho->conductor->nombre;
        $unidad->save();

        return response()->json(['error' => false, 'despacho' => null]);
    }

    public function finalizarTodo(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidades' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'tipo' => 'required|size:1'
        ]);
        $d = new Carbon($request->input('desde') . ' 00:00:00');
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        //$d = new UTCDateTime(($desde->getTimestamp()) * 1000);
        $h = new Carbon($request->input('hasta') . ' 23:59:59');
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        //$h = new UTCDateTime(($hasta->getTimestamp()) * 1000);
        $unidad_id = $request->input('unidades');
        $tipo = $request->input('tipo');
        $reportes = array();

        if ($tipo === 'L')
            $status = 'P';
        else
            if ($tipo === 'F')
            $status = 'C';
        else
            $status = 'I';

        foreach ($unidad_id as $id) {
            $user = Auth::user();
            if ($user->tipo_usuario->valor == 5) {
                $unidades_pertenecientes = $user->unidades_pertenecientes;
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', $status)->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('unidad_id', $id)->get();
            } else {
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', $status)->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('unidad_id', $id)->get();
            }
            if ($despachos->count() > 0) {
                array_push($reportes, ['despachos' => $despachos]);
            }
        }
        /********FINALIZAR DESPACHOS */
        $array_despachos = array();
        foreach ($reportes as $reporte) {
            foreach ($reporte['despachos'] as $despacho) {
                array_push($array_despachos, $despacho->_id);
                $this->end($request, $despacho->_id);
            }
        }
        unset($reportes);
        $reportes = array();

        return response()->json(['error' => false, 'despachos' => $array_despachos]);
    }

    public function getUltimoDespacho($unidad_id, $cooperativa_id)
    {
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));

        $cooperativa = Cooperativa::findOrFail($cooperativa_id);
        //if (isset($cooperativa->ultimo_despacho_unidad) && $cooperativa->ultimo_despacho_unidad == 'S') {
        $despacho = Despacho::where('unidad_id', $unidad_id)->orderBy('fecha', 'desc')->select(['_id', 'ruta_id', 'conductor_id'])->where('fecha', '>=', $desde)
            ->where('fecha', '<=', $hasta)->first();
        if (isset($despacho)) {
            $ruta = Ruta::select(['_id', 'ruta_padre', 'tipo_ruta'])->findOrFail($despacho->ruta_id);
            $ruta_id = '';
            if (isset($ruta)) {
                if ($cooperativa->mascara == 'S') {
                    if ($ruta->tipo_ruta == 'H') {
                        $ruta_id = $ruta->ruta_padre;
                    } else {
                        $ruta_id = $ruta->_id;
                    }
                } else {
                    $ruta_id = $despacho->ruta_id;
                }
            }
            return response()->json(['error' => false, 'despacho' => $despacho, 'ruta' => $ruta_id]);
        }
        else {
            return response()->json(['error' => true, 'despacho' => null]);
        }
    }

    public function despachomasivo($id)
    {
        set_time_limit(0);
        $despacho = Despacho::findOrFail($id); //Obtengo el despacho
        $fin = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
        $ini = $despacho->fecha; //fecha de inicio del despacho (salida del bus)
        date_add($ini, date_interval_create_from_date_string('570 minutes')); // Agrego 10 horas a la consulta de fecha de GPS inicial
        date_add($fin, date_interval_create_from_date_string('667 minutes')); // Agrego 10 horas a la fecha de fin, fecha de culminacion
        $fini = new UTCDateTime(($ini->getTimestamp() * 1000)); //Inicio
        $ffin = new UTCDateTime(($fin->getTimestamp() * 1000)); //Fin

        $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
            ->where('tipo', 'GTGEO')
            ->where('unidad_id', new ObjectID($despacho->unidad_id))
            ->where('fecha_gps', '>=', $fini)
            ->where('fecha_gps', '<=', $ffin)->get(); //Recorridos entre el inicio del despacho y el final esperado del despacho

        return response()->json(['recorridos' => $recorridos, 'rutarecorrido' => $despacho->ruta->recorrido]);
    }

    private function sendMailCorteTubo($despacho)
    {
        $fecha = $despacho->fecha;
        date_add($fecha, date_interval_create_from_date_string('5 hours'));
        //$fecha=$fecha->format('Y-m-d H:i:s');
        $message = '';
        $to = array();
        $coop = $despacho->unidad->cooperativa->descripcion;
        $email_coop = $despacho->unidad->cooperativa->email;
        if (isset($email_coop)) {
            array_push($to, $email_coop);
        }

        $email_socios = User::whereIn('unidades_pertenecientes', [$despacho->unidad_id])->get();
        if (isset($email_socios) && $email_socios->count() > 0) {
            foreach ($email_socios as $socios) {
                if (isset($socios->correo) && $socios->correo != "")
                    array_push($to, $socios->correo);
            }
        }

        array_push($to, 'management.infinity.fleets@gmail.com');

        $hora = 'Hora Despacho: ' . $fecha->format('Y-m-d H:i:s');
        $conductor = 'Conductor: ' . $despacho->conductor->nombre;
        $ruta = 'Ruta: ' . $despacho->ruta->descripcion;
        $unidad = 'Unidad Disco: ' . $despacho->unidad->descripcion;
        $ubicacion = '';
        $googlemaps = '';
        if (isset($despacho->latitud_corte) && isset($despacho->longitud_corte)) {
            $client = new Client();
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $despacho->latitud_corte . "," . $despacho->longitud_corte . '&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE';
            $res = $client->request('GET', $url);

            $json = json_decode($res->getBody()->getContents());
            $index = 0;
            $ubicacion = '';

            if ($json->status == "OK") {
                foreach ($json->results as $item) {
                    if ($index == 1) {
                        $ubicacion = $item->formatted_address;
                    }
                    $index++;
                }
            } else {
                $ubicacion = 'Error al traer ubicación';
            }

            $googlemaps = 'https://www.google.com.ec/maps/dir/' . $despacho->latitud_corte . ',' . $despacho->longitud_corte . '//@-2.18997,-79.894626,16z?hl=en';
        }

        Mail::send('emails.send', [
            'title' => 'Notificacion Infinity Solutions', 'hora' => $hora,
            'conductor' => $conductor, 'ruta' => $ruta, 'unidad' => $unidad,
            'ubicacion' => $ubicacion, 'googlemaps' => $googlemaps
        ], function ($message) use ($to, $coop) {
            $message->subject('Notificacion Infinity Solutions Corte de Tubo ' . $coop);
            $message->from('notificaciones.infinity@gmail.com', 'TRACKINGSYSTEM');
            $message->to($to);
        });
    }
    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        if ($lat1 != null && $lon1 != 1 && $lat2 != null && $lon2 != null) {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            return ($miles * 1.609344);
        }
        return 0;
    }

    public function cancel(Request $request, $id)
    {
        $despacho = Despacho::findOrFail($id);
        $motivo_cancelar = $request->input('motivo_cancelar');
        $motivo_cancelar = (isset($motivo_cancelar) ? $motivo_cancelar : "");
        if ($despacho->unidad->cooperativa->despachos_atm == 'S') {
            $now = new Carbon();
            if ($now > $despacho->fecha->addHours(5))
                return response()->json(['error' => true, 'despacho' => $despacho]);
            else
                $despacho->estado = 'I';

            if ($despacho->estado_exportacion == 'P' || $despacho->estado_exportacion == 'A') {
                $despacho->estado_exportacion = 'A';
            } else {
                $despacho->estado_exportacion = 'C';
            }
            $despacho->motivo_cancelar = $motivo_cancelar;
        } else {
            $despacho->estado = 'I';
            $despacho->estado_exportacion = 'P';
            $despacho->motivo_cancelar = $motivo_cancelar;
        }
        $despacho->modificador_id = Auth::user()->_id;
        $despacho->save();
        return response()->json(['error' => false, 'despacho' => $despacho]);
    }

    public function errorATM($id)
    {
        $despacho = Despacho::findOrFail($id);
        return response()->json($despacho);
    }

    public function reenviarATM($id)
    {
        $despacho = Despacho::findOrFail($id);
        $despacho->estado_exportacion = 'P';
        $despacho->modificador_id = Auth::user()->_id;
        $despacho->error_ATM = null;
        $despacho->save();
        return response()->json(['error' => false, 'despacho' => $despacho]);
    }

    public function getUnidades($cooperativa)
    {
        $unidades_bitacoras = array();
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 5) {
            $unidades_pertenecientes = $user->unidades_pertenecientes;
            $bitacoras = Bitacora::where('estado', 'P')->whereIn('tipo_bitacora', ['M', 'R'])->get();
            foreach ($bitacoras as $bitacora) {
                array_push($unidades_bitacoras, new ObjectID($bitacora->unidad_id));
            }
            return response()->json(Unidad::with('cooperativa')->orderBy('descripcion', 'asc')->where(
                'cooperativa_id',
                $cooperativa
            )->whereIn('_id', $unidades_pertenecientes)->where('estado', 'A')
                ->whereNotIn('_id', $unidades_bitacoras)->get());
        } else {
            $bitacoras = Bitacora::where('estado', 'P')->whereIn('tipo_bitacora', ['M', 'R'])->get();
            foreach ($bitacoras as $bitacora) {
                array_push($unidades_bitacoras, new ObjectID($bitacora->unidad_id));
            }
            $unidades = Unidad::with('cooperativa')->orderBy('descripcion', 'asc')->where('cooperativa_id', $cooperativa)->whereNotIn('_id', $unidades_bitacoras)
                ->where('estado', 'A')->get();
            return response()->json($unidades);
        }
    }
    public function getConductores($cooperativa)
    {
        return response()->json(Conductor::orderBy('nombre', 'asc')->where(
            'cooperativa_id',
            $cooperativa
        )->where('estado', 'A')->get());
    }
    public function getRutas($cooperativa)
    {
        $coop = Cooperativa::findOrFail($cooperativa);

        $rutas = Ruta::where('cooperativa_id', $cooperativa)->where('estado', 'A')->orderBy('descripcion', 'asc');

        // return response()->json(Ruta::where('cooperativa_id', $cooperativa)->where('estado','A')
        // ->whereIn('tipo_ruta',['H', 'I','C'])->orderBy('descripcion','asc')->get());

        if ($coop->mascara == 'S')
            return response()->json($rutas->whereIn('tipo_ruta', ['P', 'I', 'C'])->get());
        else
            return response()->json($rutas->whereIn('tipo_ruta', ['H', 'I', 'C'])->get());
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unidad' => 'required|exists:unidads,_id',
            'conductor' => 'required|exists:conductors,_id',
            'ruta' => 'required|exists:rutas,_id',
            'fecha' => 'required|date'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else {
            $data = $request->all();
            $ruta = Ruta::findOrFail($data['ruta']);
            $request->session()->put('conductor', $data['conductor']);
            $request->session()->put('ruta', $data['ruta']);
            $request->session()->put('unidad', $data['unidad']);
            $request->session()->put('cooperativa', $ruta->cooperativa_id);
            $despacho = $this->createDespacho($data);
            return response()->json(['error' => false, 'despacho' => $despacho]);
        }
    }

    private function createDespacho($data) {
        $ruta = Ruta::findOrFail($data['ruta']);
        $fecha = new Carbon($data['fecha']);
        $cfecha = new Carbon($data['fecha']);
        date_sub($fecha, date_interval_create_from_date_string('5 hours'));
        $f = new UTCDateTime(($fecha->getTimestamp()) * 1000);
        $unidad = Unidad::findOrFail($data['unidad']);
        $coop = $unidad->cooperativa;
        $puntos_control = array();
        $fecha = $f->toDateTime();
        $indice = 0;
        $u = $data['unidad'];
        $c = $data['conductor'];

        if ($coop->mascara == 'S' && $ruta->tipo_ruta == 'P') {
            $hijaSeleccionada = null;
            foreach ($ruta->hijas()->where('estado', '!=', 'I')->get() as $hija) {
                $cron = $hija->cronogramas->where(
                    'desde',
                    '<=',
                    new Carbon('2018-01-01 ' . $cfecha->format('H:i'))
                )->where(
                    'hasta',
                    '>=',
                    new Carbon('2018-01-01 ' . $cfecha->format('H:i'))
                )->where('dia', (int) $fecha->format('w'))->first();
                if ($cron != null) {
                    $hijaSeleccionada = $hija;
                    break;
                }
            }
            if ($hijaSeleccionada == null)
                return response()->json(['error' => true, 'monitoreo' => false, 'messages' => [
                    'ruta' => [
                        'La ruta seleccionada no tiene un cronograma dentro de la fecha indicada.'
                    ]
                ]]);
            else
                $ruta = $hijaSeleccionada;
        }

        $r = $ruta->_id;

        $existe = Despacho::where('fecha', $fecha)->where(
            'unidad_id',
            $u
        )->where('ruta_id', $r)->where('conductor_id', $c)->where('estado', '!=', 'I')->first();
        if (!isset($existe)) {
            foreach ($ruta->puntos_control as $punto_control) {
                $fecha->add(new DateInterval('PT' . $punto_control["tiempo_llegada"] . 'M'));
                $fecha = new Carbon($fecha->format('Y-m-d H:i:s'));
                if ($indice == 0)
                    date_sub($fecha, date_interval_create_from_date_string('5 hours'));
                $item = [
                    'id' => $punto_control["id"],
                    "tiempo_esperado" => new UTCDateTime(($fecha->getTimestamp()) * 1000),
                    "adelanto" => $punto_control["adelanto"],
                    "atraso" => $punto_control["atraso"]
                ];
                array_push($puntos_control, $item);
                $indice++;
            }
            $contador = $unidad->contador_total;
            $despacho = Despacho::create([
                'unidad_id' => $unidad->_id,
                'conductor_id' => $data['conductor'],
                'ruta_id' => $ruta->_id,
                'fecha' => $f,
                'estado' => 'P',
                'contador_inicial' => $contador,
                'contador_final' => $contador,
                'puntos_control' => $puntos_control,
                'contador_ayer' => $unidad->contador_inicial,
                'marcas' => [],
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'estado_exportacion' => 'P'
            ]);
            return $despacho;
        } else
            return null;
    }

    public function show($id)
    {
        $despacho = Despacho::with('unidad', 'ruta.rutapadre', 'conductor')->where('_id', $id)->first();
        $fin = $despacho->fecha;
        date_add($fin, date_interval_create_from_date_string('5 hours'));
        $ini = new Carbon($fin->format('Y-m-d 00:00:00'));
        date_sub($ini, date_interval_create_from_date_string('5 hours'));
        date_sub($fin, date_interval_create_from_date_string('5 hours'));
        $array = array();
        $primero = Despacho::with('unidad')->orderBy('fecha', 'asc')->where('ruta_id', $despacho->ruta_id)->where('estado', 'C')->where('fecha', '<', $fin)->where(
            'fecha',
            '>',
            $ini
        )->first();
        $ultimo = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where('fecha', '<', $fin)->where(
            'fecha',
            '>',
            $ini
        )->where('unidad_id', $despacho->unidad_id)->first();

        $yesterday = new Carbon($despacho->fecha->format('Y-m-d 23:59:59'));
        date_sub($yesterday, date_interval_create_from_date_string('1 day'));
        date_sub($yesterday, date_interval_create_from_date_string('5 hours'));

        if (isset($yesterday)) {
            $finalayer = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where('fecha', '<=', $yesterday)
                ->where('unidad_id', $despacho->unidad_id)->first();
        }


        $indice = 0;
        foreach ($despacho->puntos_control as $punto) {
            $f = $despacho->puntos_control[$indice]["tiempo_esperado"]->toDateTime();
            date_sub($f, date_interval_create_from_date_string('10 hours'));
            $f->format('d-m-Y H:i');
            array_push($array, ['id' => $despacho->puntos_control[$indice]["id"], 'tiempo_esperado' =>  $f]);
            $indice++;
        }
        $despacho->puntos_control = $array;
        $despacho->anterior = $ultimo;
        $despacho->primero = $primero;
        $despacho->fecha_ini = $ini;
        $despacho->fecha_fin = $fin;
        $despacho->finalayer = $finalayer;
        return response()->json($despacho);
    }

    public function showAlbosau($id)
    {
        set_time_limit(0);
        $despacho = Despacho::with('unidad', 'ruta.rutapadre', 'conductor')->where('_id', $id)->first();
        if (isset($despacho)) {

            $rutas = array();
            $r = Ruta::find($despacho->ruta_id);
            if ($r->tipo_ruta != 'H') {
                array_push($rutas, $despacho->ruta_id);
            } else {
                $ruta_hijas = Ruta::where('ruta_padre', $r->rutapadre->_id)->get();
                foreach ($ruta_hijas as $hijas)
                    array_push($rutas, $hijas->_id);
            }


            if ($despacho->unidad != null) {
                $cooperativaId = $despacho->unidad->cooperativa_id;
                $fin = $despacho->fecha;
                date_add($fin, date_interval_create_from_date_string('5 hours'));
                $ini = new Carbon($fin->format('Y-m-d 00:00:00'));
                date_sub($ini, date_interval_create_from_date_string('5 hours'));
                date_sub($fin, date_interval_create_from_date_string('5 hours'));
                $array = array();
                $indice = 0;
                $despacho->puntos = $despacho->puntos_control;
                foreach ($despacho->puntos_control as $punto) {
                    $f = $despacho->puntos_control[$indice]["tiempo_esperado"]->toDateTime();
                    date_sub($f, date_interval_create_from_date_string('10 hours'));
                    $f->format('d-m-Y H:i');
                    array_push($array, ['id' => $despacho->puntos_control[$indice]["id"], 'tiempo_esperado' =>  $f]);
                    $indice++;
                }
                $primero = Despacho::with('unidad')->orderBy('fecha', 'asc')->whereIn('ruta_id', $rutas)->where('estado', 'C')->where('fecha', '<', $fin)->where(
                    'fecha',
                    '>',
                    $ini
                )->first();
                $array_ultimo = array();

                if (
                    $cooperativaId == '588d3d677aea915d897ff041' || $cooperativaId == '5d1114aa2243df71321d2682'
                    || $cooperativaId == '62d762dd2243df1cd73a79e2'
                    || $cooperativaId == '63e58b552243df4233755082'
                ) {
                    //URBANO GUAYAQUIL  & ALBOSAO & TODO POLICENTRO
                    $tomorrow = new Carbon($despacho->fecha->format('Y-m-d 23:59:59'));
                    date_sub($tomorrow, date_interval_create_from_date_string('5 hours'));
                    $siguiente = Despacho::with('unidad', 'ruta.rutapadre')->orderBy('fecha', 'desc')->where('fecha', '<', $fin)->where('fecha', '>=', $ini)->whereIn(
                        'ruta_id',
                        $rutas
                    )->where('estado', 'C')->where('unidad_id', $despacho->unidad_id)->first();
                    $ultimo = null;
                    $siguiente_bus = null;
                    if (isset($siguiente)) {
                        $ultimo = Despacho::with('unidad', 'ruta.rutapadre')->orderBy('fecha', 'desc')->whereIn('ruta_id', $rutas)->where('estado', 'C')->where('fecha', '<', $siguiente->fecha)->where(
                            'fecha',
                            '>=',
                            $ini
                        )->first();
                        $siguiente_bus = Despacho::with('unidad', 'ruta.rutapadre')->orderBy('fecha', 'asc')->where('fecha', '>', $siguiente->fecha)->where('fecha', '<=', $tomorrow)->whereIn(
                            'ruta_id',
                            $rutas
                        )->where('estado', 'C')->first();
                    }
                    $despacho->ultimo = $ultimo;
                    $despacho->siguiente_bus = $siguiente_bus;
                    $despacho->siguiente = $siguiente;
                } else {
                    $ultimo = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where('fecha', '<', $fin)->where(
                        'fecha',
                        '>',
                        $ini
                    )->where('unidad_id', $despacho->unidad_id)->first();


                    $indice = 0;
                    if (isset($ultimo) && $ultimo->puntos_control != null) {
                        foreach ($ultimo->puntos_control as $punto) {
                            $f = $ultimo->puntos_control[$indice]["tiempo_esperado"]->toDateTime();
                            date_sub($f, date_interval_create_from_date_string('10 hours'));
                            $f->format('d-m-Y H:i');
                            array_push($array_ultimo, ['id' => $ultimo->puntos_control[$indice]["id"], 'tiempo_esperado' =>  $f]);
                            $indice++;
                        }
                    }
                }

                $despacho->puntos_control = $array;
                $despacho->puntos_control_ultimo = $array_ultimo;
                $despacho->anterior = $ultimo;
                $despacho->primero = $primero;
                $despacho->fecha_ini = $ini;
                $despacho->fecha_fin = $fin;
                return response()->json($despacho);
            }
        } else
            abort(404);
    }

    public function infoPrint($id)
    {
        set_time_limit(0);
        $despacho = Despacho::with('unidad', 'ruta.rutapadre', 'conductor')->where('_id', $id)->first();
        if (isset($despacho)) {
            $indice = 0;
            $array = array();
            foreach ($despacho->puntos_control as $punto) {
                $f = $despacho->puntos_control[$indice]["tiempo_esperado"]->toDateTime();
                //date_sub($f, date_interval_create_from_date_string('10 hours'));
                $f->format('d-m-Y H:i');
                array_push($array, ['id' => $despacho->puntos_control[$indice]["id"], 'tiempo_esperado' =>  $f]);
                $indice++;
            }

            $fin = $despacho->fecha;
            date_add($fin, date_interval_create_from_date_string('5 hours'));
            $ini = new Carbon($fin->format('Y-m-d 00:00:00'));
            date_sub($ini, date_interval_create_from_date_string('5 hours'));
            date_sub($fin, date_interval_create_from_date_string('5 hours'));

            //$despacho->puntos_control = $array;
            $despacho->fecha_ini = $ini;
            $despacho->fecha_fin = $fin;
            return response()->json($despacho);
        } else
            abort(404);
    }
}
