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
use Illuminate\Support\Facades\Cache;
use App\User;
use Excel;
use App\Helper\FunctionsHelper;


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

    /**
     * Marca de tiempo para ordenar despachos del día por hora_programada (si existe) o por fecha.
     */
    private function despachoComparableParaOrdenVuelta($d)
    {
        $fechaBase = Carbon::today();
        if (isset($d->fecha) && $d->fecha !== null) {
            try {
                if ($d->fecha instanceof \MongoDB\BSON\UTCDateTime) {
                    $fechaBase = Carbon::createFromTimestampUTC($d->fecha->toDateTime()->getTimestamp());
                } else {
                    $fechaBase = Carbon::parse($d->fecha);
                }
            } catch (\Exception $e) {
                $fechaBase = Carbon::today();
            }
        }

        if (isset($d->hora_programada)) {
            $hp = $d->hora_programada;
            try {
                if ($hp instanceof \MongoDB\BSON\UTCDateTime) {
                    return Carbon::createFromTimestampUTC($hp->toDateTime()->getTimestamp())->getTimestamp();
                }
                if ($hp instanceof \DateTimeInterface) {
                    return Carbon::parse($hp)->getTimestamp();
                }
                if (is_string($hp) && trim($hp) !== '') {
                    $hps = trim($hp);
                    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $hps, $m)) {
                        return $fechaBase->copy()->startOfDay()->setTime((int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0)->getTimestamp();
                    }

                    return Carbon::parse($hps)->getTimestamp();
                }
            } catch (\Exception $e) {
                // continuar con fecha del despacho
            }
        }

        try {
            return $fechaBase->getTimestamp();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Lista ordenada de despachos (P|C) del día: 1-based vuelta actual = posición del primer P;
     * si no hay P (todas C), devuelve la cantidad de vueltas completadas del día.
     */
    private function numVueltaDesdeDespachosOrdenados(array $ordenados)
    {
        if (count($ordenados) === 0) {
            return 0;
        }
        foreach ($ordenados as $idx => $d) {
            if (isset($d->estado) && $d->estado === 'P') {
                return $idx + 1;
            }
        }

        return count($ordenados);
    }

    // New: return metadata (ruta_actual, ruta_fecha, ruta_conductor, tipo_bitacora) for one or many unidades
    public function getUnidadesMeta(Request $request)
    {
        $this->validate($request, [
            'unidad_ids' => 'required'
        ]);

        $ids = $request->input('unidad_ids');
        if (!is_array($ids)) $ids = [$ids];

        // Tipos 4 y 5: solo meta permitida para unidades_pertenecientes (alineado con store/getUnidades).
        $tipoUsuarioMeta = Auth::user()->tipo_usuario->valor;
        if ($tipoUsuarioMeta == 4 || $tipoUsuarioMeta == 5) {
            $pertenecientes = Auth::user()->unidades_pertenecientes;
            $permitidas = [];
            if (!empty($pertenecientes)) {
                foreach ((array) $pertenecientes as $pid) {
                    $permitidas[(string) $pid] = true;
                }
            }
            $ids = array_values(array_filter($ids, function ($id) use ($permitidas) {
                return isset($permitidas[(string) $id]);
            }));
        }

        $result = [];
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));

        // ── Unidades activas por ruta (bloque aditivo) ────────────────────────
        // Si se envían rutas_ids, devuelve el array de unidades con despachos
        // pendientes hoy en esas rutas, usando la misma lógica de store/getUnidades.
        $rutasIds = $request->input('rutas_ids', []);
        if (!is_array($rutasIds)) $rutasIds = array_filter([$rutasIds]);
        $rutasIds = array_values(array_filter($rutasIds));

        if (!empty($rutasIds)) {
            $unidades_en_ruta = [];
            $array_rutas      = [];
            $despachos_pendientes = null;

            foreach ($rutasIds as $rut) {
                $r = Ruta::find($rut);
                if (!$r) continue;
                if ($r->tipo_ruta != 'P') {
                    array_push($array_rutas, $rut);
                } else {
                    $ruta_hijas = Ruta::where('ruta_padre', $r->_id)->get();
                    foreach ($ruta_hijas as $hija) {
                        array_push($array_rutas, $hija->_id);
                    }
                }
            }

            $array_aux = [];

            if (!empty($array_rutas)) {
                $tipoValor = Auth::user()->tipo_usuario->valor;

                if ($tipoValor == 4 || $tipoValor == 5) {
                    $unidades_pertenecientes = Auth::user()->unidades_pertenecientes;
                    if ($unidades_pertenecientes) {
                        // Misma query que store/getUnidades (hay_rutas)
                        $despachos_pendientes = Despacho::orderBy('fecha', 'asc')
                            ->where('estado', 'P')
                            ->whereIn('unidad_id', $unidades_pertenecientes)
                            ->whereIn('ruta_id', $array_rutas)
                            ->where('fecha', '>=', $desde)
                            ->where('fecha', '<=', $hasta)
                            ->get();

                        foreach ($unidades_pertenecientes as $unidad_id) {
                            foreach ($despachos_pendientes as $despacho) {
                                if ($unidad_id == $despacho->unidad_id) {
                                    array_push($unidades_en_ruta, $unidad_id);
                                    break;
                                }
                            }
                        }
                        $array_aux = $unidades_en_ruta;
                    }
                } else {
                    $cooperativa_id = ($tipoValor == 1)
                        ? $request->input('cooperativa_id')
                        : Auth::user()->cooperativa_id;

                    if ($cooperativa_id) {
                        $unidades_id2 = [];
                        $unidades2    = Unidad::orderBy('placa', 'asc')
                            ->where('cooperativa_id', $cooperativa_id)
                            ->where('estado', 'A')
                            ->get();

                        foreach ($unidades2 as $unidad) {
                            array_push($unidades_id2, (string) $unidad->_id);
                        }

                        // Misma query que store/getUnidades (hay_rutas)
                        $despachos_pendientes = Despacho::orderBy('fecha', 'asc')
                            ->where('estado', 'P')
                            ->whereIn('unidad_id', $unidades_id2)
                            ->whereIn('ruta_id', $array_rutas)
                            ->where('fecha', '>=', $desde)
                            ->where('fecha', '<=', $hasta)
                            ->get();

                        foreach ($despachos_pendientes as $despacho) {
                            for ($i = 0; $i < sizeof($unidades2); $i++) {
                                if ((string)$unidades2[$i]->_id == (string)$despacho->unidad_id
                                    && !in_array((string)$despacho->unidad_id, $array_aux)) {
                                    array_push($array_aux, (string)$despacho->unidad_id);
                                    break;
                                }
                            }
                        }
                    }
                }

                // Orden asc por fecha de despacho: recorrer el mismo cursor orderBy('fecha','asc') que usa store.
                if ($despachos_pendientes !== null && count($array_aux) > 0) {
                    $allow = [];
                    foreach ($array_aux as $uid) {
                        $allow[(string) $uid] = true;
                    }
                    $array_aux = [];
                    $seen = [];
                    foreach ($despachos_pendientes as $d) {
                        $uid = (string) $d->unidad_id;
                        if (isset($seen[$uid]) || !isset($allow[$uid])) {
                            continue;
                        }
                        $seen[$uid] = true;
                        $array_aux[] = $uid;
                    }
                }
            }

            $result['unidades_activas_ruta'] = $array_aux;
        }
        // ─────────────────────────────────────────────────────────────────────

        // First, try to fill from cache
        $toFetch = [];
        foreach ($ids as $uid) {
            $cached = Cache::get('unidades_meta_' . $uid);
            if ($cached) {
                $result[$uid] = $cached;
            } else {
                $toFetch[] = $uid;
            }
        }

        if (count($toFetch) === 0) {
            return response()->json($result);
        }

        try {
            // Batch fetch ignicionf from unidades (single query, only the field we need)
            $unidadesIgnicion = Unidad::whereIn('_id', $toFetch)
                ->where('estado', 'A')
                ->get(['_id', 'ignicionf', 'tiempo_power', 'tiempo_power_update']);
            $ignicionByUnidad = [];
            $tiempoPowerByUnidad = [];
            foreach ($unidadesIgnicion as $u) {
                $ignicionByUnidad[(string)$u->_id] = $u->ignicionf ?? null;
                $tiempoPowerByUnidad[(string)$u->_id] = [
                    'tiempo_power'        => $u->tiempo_power ?? 0,
                    'tiempo_power_update' => $u->tiempo_power_update ?? null,
                ];
            }

            // Batch fetch despachos for the given unidades within today range
            $despachos = Despacho::orderBy('fecha', 'asc')
                ->where('estado', 'P')
                ->whereIn('unidad_id', $toFetch)
                ->where('fecha', '>=', $desde)
                ->where('fecha', '<=', $hasta)
                ->get();

            // Despachos del día P|C (excluye I) para número de vuelta ordenado por hora_programada
            $despachosVueltas = Despacho::whereIn('unidad_id', $toFetch)
                ->where('fecha', '>=', $desde)
                ->where('fecha', '<=', $hasta)
                ->whereIn('estado', array('P', 'C'))
                ->get();

            $vueltasPorUnidad = array();
            foreach ($toFetch as $uidKey) {
                $vueltasPorUnidad[(string) $uidKey] = array();
            }
            foreach ($despachosVueltas as $dv) {
                $uidv = (string) $dv->unidad_id;
                if (!array_key_exists($uidv, $vueltasPorUnidad)) {
                    continue;
                }
                $vueltasPorUnidad[$uidv][] = $dv;
            }
            foreach ($vueltasPorUnidad as $uidv => $listaV) {
                usort($listaV, function ($a, $b) {
                    $ta = $this->despachoComparableParaOrdenVuelta($a);
                    $tb = $this->despachoComparableParaOrdenVuelta($b);
                    if ($ta === $tb) {
                        return 0;
                    }

                    return ($ta < $tb) ? -1 : 1;
                });
                $vueltasPorUnidad[$uidv] = $listaV;
            }

            // For bitacoras, apply visibility rules similar to BitacoraController
            $user = Auth::user();
            $bitacorasQuery = Bitacora::orderBy('fechaInicio', 'desc')
                ->whereIn('unidad_id', $toFetch)
                ->where('estado', 'P');

            if ($user->tipo_usuario->valor != 1) {
                $usuarios_distribuidores = User::where('tipo_usuario_id', '5827714b7b10202ff4485891')->get();
                $usr_distribuidores = [];
                foreach ($usuarios_distribuidores as $usr) array_push($usr_distribuidores, $usr->_id);

                $bitacorasQuery->where(function ($q) use ($usr_distribuidores) {
                    $q->whereNotIn('creador_id', $usr_distribuidores)
                      ->orWhere('compartido', 'S');
                });
            }

            $bitacoras = $bitacorasQuery->get();

            // Map first despacho per unidad (ordered asc, so first is earliest)
            $despByUnidad = [];
            foreach ($despachos as $d) {
                $uid = (string)$d->unidad_id;
                if (!isset($despByUnidad[$uid])) $despByUnidad[$uid] = $d;
            }

            // Map first (most recent) bitacora per unidad (ordered desc)
            $bitByUnidad = [];
            foreach ($bitacoras as $b) {
                $uid = (string)$b->unidad_id;
                if (!isset($bitByUnidad[$uid])) $bitByUnidad[$uid] = $b;
            }

            foreach ($toFetch as $uid) {
                $ruta_descr = '';
                $ruta_fecha = '';
                $ruta_conductor = '';
                $ruta_hora_final = '';
                $tipo_bitacora = '';

                if (isset($despByUnidad[$uid])) {
                    $r = $despByUnidad[$uid];
                    $ruta_descr = $r->ruta->descripcion ?? '';
                    // ruta fecha (hora despacho ajustada)
                    $rf = $r->fecha;
                    date_add($rf, date_interval_create_from_date_string('5 hours'));
                    $ruta_fecha = $rf->format('H:i');
                    $ruta_conductor = $r->conductor->nombre ?? '';

                    // calcular hora final estimada sumando los tiempos de llegada de los puntos de control
                    $tiempo_final = 0;
                    if (isset($r->ruta) && isset($r->ruta->puntos_control) && is_array($r->ruta->puntos_control)) {
                        foreach ($r->ruta->puntos_control as $punto) {
                            if (isset($punto['tiempo_llegada'])) $tiempo_final += (int)$punto['tiempo_llegada'];
                        }
                    }

                    try {
                        $rh = Carbon::parse($r->fecha);
                        $rh->addHours(5);
                        if ($tiempo_final > 0) $rh->addMinutes($tiempo_final);
                        $ruta_hora_final = $rh->format('H:i');
                    } catch (\Exception $ex) {
                        $ruta_hora_final = '';
                    }
                }

                if (isset($bitByUnidad[$uid])) {
                    $tipo_bitacora = $bitByUnidad[$uid]->tipo_bitacora ?? '';
                }

                // Siempre enviar ruta_* (vacíos si no hay despacho estado P) para que el front borre la ruta al finalizar
                $meta = [];
                $meta['ruta_actual'] = ($ruta_descr !== null && $ruta_descr !== '') ? $ruta_descr : '';
                $meta['ruta_fecha'] = ($ruta_fecha !== null && $ruta_fecha !== '') ? $ruta_fecha : '';
                $meta['ruta_conductor'] = ($ruta_conductor !== null && $ruta_conductor !== '') ? $ruta_conductor : '';
                $meta['ruta_hora_fin'] = ($ruta_hora_final !== null && $ruta_hora_final !== '') ? $ruta_hora_final : '';
                if ($tipo_bitacora !== null && $tipo_bitacora !== '') $meta['tipo_bitacora'] = $tipo_bitacora;
                // ignicionf: always include when present (on/off) so the front-end can show the icon on initial load
                $ignf = $ignicionByUnidad[$uid] ?? null;
                if ($ignf === 'on' || $ignf === 'off') $meta['ignicionf'] = $ignf;

                // tiempo_power: incluir siempre para que el front pueda evaluar si el bolt debe parpadear
                $tpData = $tiempoPowerByUnidad[$uid] ?? ['tiempo_power' => 0, 'tiempo_power_update' => null];
                $tp  = (float)($tpData['tiempo_power'] ?? 0);
                $tpu = $tpData['tiempo_power_update'];

                // Serializar tiempo_power_update a ISO 8601 legible por JS
                $tpu_iso = null;
                if ($tpu !== null) {
                    try {
                        $tpu_iso = $tpu->toDateTime()->format('Y-m-d\TH:i:s\Z');
                    } catch (\Exception $e) { $tpu_iso = null; }
                }

                // Calcular horas restantes y exponer bolt_activo para que el JS no tenga que calcular
                $bolt_activo = false;
                if ($tp > 0 && $tpu_iso !== null) {
                    $now = new DateTime('now', new \DateTimeZone('UTC'));
                    $fechaUpdate = new DateTime($tpu_iso, new \DateTimeZone('UTC'));
                    $horasTranscurridas = ($now->getTimestamp() - $fechaUpdate->getTimestamp()) / 3600;
                    $bolt_activo = ($tp - $horasTranscurridas) > 0;
                }

                $meta['tiempo_power']        = $tp;
                $meta['tiempo_power_update'] = $tpu_iso;
                $meta['bolt_activo']         = $bolt_activo;

                $listaVueltas = isset($vueltasPorUnidad[(string) $uid]) ? $vueltasPorUnidad[(string) $uid] : array();
                $meta['numvuelta'] = $this->numVueltaDesdeDespachosOrdenados($listaVueltas);

                // cache short-term (even an empty object to avoid hot loops)
                try {
                    Cache::put('unidades_meta_' . $uid, $meta, Carbon::now()->addSeconds(15));
                } catch (\Exception $e) {
                    // ignorar errores de cache
                }

                // return object ({} in JSON) when there are no keys rather than fields with empty strings
                $result[$uid] = (object)$meta;
            }
        } catch (Exception $e) {
            // on error, ensure all toFetch keys exist
            foreach ($toFetch as $uid) {
                $result[$uid] = (object)[];
            }
        }

        return response()->json($result);
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
            $punto_control=PuntoControl::where('cooperativa_id',$cooperativa_id)->where('activo',true)->orderBy('pdi', 'asc')->first();
            $bloque='';
            if(isset($punto_control) && $punto_control){
                $bloque=$punto_control->bloque;
            }
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
                    $punto_retorno=false;
                    $punto_inicio=false;
                    $sentido=false;

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
                            /*
                            if($punto['retorno']==="1"){
                                $punto_retorno = PuntoControl::where("_id",new ObjectID($punto['id']))->first();
                            }
                            if($punto['secuencia']==="1"){
                                $punto_inicio = PuntoControl::where("_id",new ObjectID($punto['id']))->first();
                            }
                                */
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
                    /*
                    if(isset($ruta_actual)){
                        $sentidoActual = $unidad['sentido'] ?? 'i';
                        $sentido = FunctionsHelper::determinar_sentido_unidad(
                            $unidad['latitud'],
                            $unidad['longitud'],
                            $punto_retorno,
                            $sentidoActual,
                            $punto_inicio
                        );
                        
                    } 
                    
                    if ($sentido && $unidad['sentido'] != $sentido) {
                       $unidad->update(['sentido' => $sentido]);
                    }
                       */


                    //$unidad['sentido']=$sentido;
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
            'notificaciones'=>$array_notificaciones,'array_rutas'=>$rutaunidad,'array_bitacora'=>$array_bitacora,"bloque"=>$bloque]);
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
                                    try {
                                        $client = new Client();
                                        $urlFinal='https://nominatim.openstreetmap.org/reverse?format=json&lat='.$documento["latitud"]. '&lon='.$documento["longitud"];
                                        $res = $client->get($urlFinal, [
                                            'verify'          => false,
                                            'timeout'         => 5,
                                            'connect_timeout' => 3,
                                        ]);

                                        $code = $res->getStatusCode();
                                        if ($code === 200) {
                                            $json = json_decode($res->getBody());
                                            if (!isset($json->error)) {
                                                $ubicacion = $json->display_name;
                                                $documento['gps_address'] = $ubicacion;
                                                $documento->save();
                                            } else {
                                                $ubicacion = 'Ubicacion no disponible';
                                            }
                                        } else {
                                            $ubicacion = 'Ubicacion no disponible';
                                        }
                                    } catch (\Exception $e) {
                                        $ubicacion = 'Ubicacion no disponible';
                                    }
                                }
                            }
                        } 
                        array_push($array_historico, (Object)[
                            '_id' => $documento["_id"],
                            'fecha_servidor' => $fecha,
                            'fecha_gps' => $fecha_gps,
                            'evento' => $evento,
                            'tipo_marcacion' => isset($documento['origen'])?$documento['origen']:"-",
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

                
                $despacho_id=$request->input('despacho_id');
                if(isset($despacho_id)){
                    $despacho = Despacho::where('_id', new ObjectID($despacho_id))->first();
                    if($despacho){
                        $puntos = $despacho->puntos_control ?? [];
                        if (!empty($puntos)){
                            $ultimo = end($puntos);
                            if (!empty($ultimo['marca'])) {
                                $marca=$ultimo['marca'];  
                                $fin = new Carbon($marca);
                                date_add($fin, date_interval_create_from_date_string('5 hours'));
                                $fin = new UTCDateTime(($fin->getTimestamp()) * 1000); 
                            }
                        }
                    }

                }
                   

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