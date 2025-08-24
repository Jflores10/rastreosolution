<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Recorrido;
use App\PuntoControl;
use App\Cooperativa;
use App\Unidad;
use App\Ruta;
use App\TipoUsuario;
use MongoDB\BSON\UTCDateTime;
use Auth;
use Validator;
use MongoDB\BSON\ObjectID;
use DateTime;
use DateInterval;
use App\PuntoControlAtmOficial;
use App\RutaAtmOficial;

class RutaController extends Controller
{
    public function listar($cooperativa)
    {
        return response()->json(Ruta::orderBy('descripcion')->where('cooperativa_id', $cooperativa)
            ->where('tipo_ruta', '!=', 'H')->where('estado', 'A')->get());
    }
    public function index()
    {
        $user = Auth::user();
        return view('panel.lista-rutas', [
            'rutas' => Ruta::permitida()->orderBy('descripcion')
                ->where('estado', 'A')
                ->paginate(10),
            'cooperativa' => $user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }
    public function create()
    {
        if (Auth::user()->estado == 'A') {
            $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();

            if ($tipo_usuario->valor == "1")
                return view('panel.crear-ruta', [
                    'puntos_control' => PuntoControl::where('estado', 'A')->paginate(10),
                    'cooperativas' => Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->get(),
                    'unidades' => Unidad::orderBy('placa', 'asc')->where('estado', 'A')->get(),
                    'tipo_usuario_valor' => $tipo_usuario->valor
                ]);

            elseif ($tipo_usuario->valor == "2") {
                $cooperativa = Cooperativa::findOrFail(Auth::user()->cooperativa_id);
                return view('panel.crear-ruta', [
                    'puntos_control' =>
                    PuntoControl::where('cooperativa_id', Auth::user()->cooperativa_id)
                        ->get(),
                    'cooperativa' => $cooperativa->_id,
                    'unidades' => Unidad::orderBy('placa', 'asc')->where('estado', 'A')
                        ->where('cooperativa_id', Auth::user()->cooperativa_id)->get(),
                    'ruta_padres' => Ruta::where('cooperativa_id', Auth::user()->cooperativa_id)->where('estado', 'A')->where('tipo_ruta', 'P')->get(),
                    'tipo_usuario_valor' => $tipo_usuario->valor
                ]);
            } else
                return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
        } else
            return view('panel.error', ['mensaje_acceso' => 'En este momento su usuario se encuentra suspendido.']);
    }

    public function store(Request $request)
    {
        $cooperativa = Cooperativa::findOrFail($request->input('cooperativa_id'));
        if ($request->input('opcion') == 'getUnidades') {
            $unidades = Unidad::where('cooperativa_id', $request->input('cooperativa_id'))->where('estado', 'A')->get();
            return response()->json(['error' => false, 'unidades' => $unidades, 'cooperativa' => $cooperativa]);
        } else {
            if ($request->input('opcion') == 'getRutasPadres') {
                $rutas = Ruta::where('cooperativa_id', $request->input('cooperativa_id'))->where('estado', 'A')->where('tipo_ruta', 'P')->get();
                return response()->json(['error' => false, 'rutaspadres' => $rutas, 'cooperativa' => $cooperativa]);
            } else {
                if ($request->input('opcion') == 'getRutasATM') {
                    $rutas = RutaAtmOficial::where('cooperativa_id', $request->input('cooperativa_id'))->where('estado', 'A')->get();
                    return response()->json(['error' => false, 'rutasatm' => $rutas, 'cooperativa' => $cooperativa]);
                }
            }
        }
    }

    public function show($id)
    {
        if (Auth::user()->estado == 'A') {
            $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();
            if ($tipo_usuario->valor == "1")
                return view('panel.crear-ruta', [
                    'puntos_control' => PuntoControl::paginate(10),
                    'ruta' => Ruta::findOrFail($id),
                    'cooperativas' => Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->get(),
                    'puntos_control' => PuntoControl::where("estado", "A")->get(),
                    'unidades' => Unidad::orderBy('placa', 'asc')->where('estado', 'A')->get(),
                    'tipo_usuario_valor' => $tipo_usuario->valor
                ]);

            elseif ($tipo_usuario->valor == "2") {
                $cooperativa = Cooperativa::findOrFail(Auth::user()->cooperativa_id);
                return view('panel.crear-ruta', [
                    'puntos_control' => PuntoControl::where('cooperativa_id', Auth::user()->cooperativa_id)
                        ->get(),
                    'unidades' => Unidad::orderBy('placa', 'asc')->where('estado', 'A')
                        ->where('cooperativa_id', Auth::user()->cooperativa_id)
                        ->get(),
                    'ruta' => Ruta::findOrFail($id),
                    'cooperativas' => Cooperativa::activa()->permitida()->orderBy('descripcion', 'asc')->get(),
                    'cooperativa' => $cooperativa->_id,
                    'tipo_usuario_valor' => $tipo_usuario->valor
                ]);
            } else
                return view('panel.error', ['mensaje_acceso' => 'No posee suficientes permisos para poder ingresar a este sitio.']);
        } else
            return view('panel.error', ['mensaje_acceso' => 'En este momento su usuario se encuentra suspendido.']);
    }

    public function edit($id)
    {
    }

    public function clonar($id)
    {
        $ruta = Ruta::findOrFail($id);

        $ruta_nuevo = Ruta::create([
            'descripcion' => $ruta->descripcion . ' CLONADA',
            'cooperativa_id' => $ruta->cooperativa_id,
            'recorrido' => $ruta->recorrido,
            'puntos_control' => $ruta->puntos_control,
            'estado' => 'A',
            'todo_usuario' => $ruta->todo_usuario,
            'tipo_ruta' => $ruta->tipo_ruta,
            'ruta_padre' => $ruta->ruta_padre,
            'creador_id' => Auth::user()->_id,
            'ruta_atm' => $ruta->ruta_atm,
            'estado_exportacion' => 'P',
            'codigo' => $ruta->codigo,
            'color' => $ruta->color
        ]);
        // return response()->json($ruta_nuevo);

        $user = Auth::user();
        return view('panel.lista-rutas', [
            'rutas' => Ruta::permitida()->orderBy('descripcion')
                ->where('estado', 'A')
                ->paginate(10),
            'cooperativa' => $user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'cooperativa_id' => 'required',
            'tipo_ruta' => 'required',
            'dia' => 'nullable|array',
            'desde' => 'nullable|array',
            'hasta' => 'nullable|array',
            'dia.*' => 'required|numeric',
            'desde.*' => 'required|date_format:H:i',
            'hasta.*' => 'required|date_format:H:i'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else {
            if (sizeof($request->input('array_ruta') > 0)) {
                $ruta = Ruta::findOrFail($id);
                $ruta->descripcion = $request->input('descripcion');
                $ruta->recorrido = $request->input('array_ruta');
                $ruta->estado_exportacion = 'P';
                if ($request->input('puntos_control') != null) {
                    $ruta->puntos_control = $request->input('puntos_control');
                }
                $ruta->tipo_ruta = $request->input('tipo_ruta');
                if ($request->input('tipo_ruta_padre') != null) {
                    $ruta->ruta_padre = $request->input('tipo_ruta_padre');
                }
                if ($request->input('tipo_ruta_atm') != null) {
                    $ruta->ruta_atm = $request->input('tipo_ruta_atm');
                }
                $ruta->cooperativa_id = $request->input('cooperativa_id');
                $ruta->modificador_id = Auth::user()->_id;
                $ruta->color = $request->input('color');
                $ruta->save();
                $dias = $request->input('dia');
                $desdes = $request->input('desde');
                $hastas = $request->input('hasta');
                $ruta->cronogramas()->delete();
                if (is_array($dias)) {
                    for ($i = 0; $i < count($dias); $i++)
                        $ruta->cronogramas()->create([
                            'dia' => (int) $dias[$i],
                            'desde' => new Carbon('2018-01-01 ' . $desdes[$i]),
                            'hasta' => new Carbon('2018-01-01 ' . $hastas[$i])
                        ]);
                }
                return response()->json(['error' => false, 'ruta' => $ruta]);
            } else
                return response()->json(['error' => true, 'messages' => 'Error: No se encontró ninguna ruta.']);
        }
    }

    public function destroy($id)
    {
        $ruta = Ruta::findOrFail($id);

        if ($ruta->estado == "A")

            $ruta->estado = "I";

        else
            $ruta->estado = "A";
        $ruta->estado_exportacion = 'P';
        $ruta->save();
        return response()->json($ruta);
    }

    public function consulta(Request $request)
    {
        switch ($request->input('opcion')) {

            case 'consulta':
                $validator = Validator::make($request->all(), [
                    'fecha_inicio' => 'required',
                    'fecha_fin' => 'required',
                    'unidad_id' => 'required',
                    'cooperativa_id' => 'required',
                ]);
                if ($validator->fails())
                    return response()->json(['error' => true, 'messages' => $validator->errors()]);
                else {
                    $ini = new Carbon($request->input('fecha_inicio'));
                    $fin = new Carbon($request->input('fecha_fin'));
                    date_add($ini, date_interval_create_from_date_string('5 hours'));
                    date_add($fin, date_interval_create_from_date_string('5 hours'));
                    $ini = new UTCDateTime(($ini->getTimestamp()) * 1000);
                    $fin = new UTCDateTime(($fin->getTimestamp()) * 1000);


                    $cursor = Recorrido::whereNotNull('latitud')->whereNotNull('longitud')->where("unidad_id", new ObjectID($request->input('unidad_id')))
                        ->where('fecha_gps', '>=', $ini)
                        ->where('fecha_gps', '<=', $fin)
                        ->get();

                    $array_ruta = [];

                    foreach ($cursor as $documento) {
                        array_push($array_ruta, (object)['lat' => $documento["latitud"], 'lng' => $documento["longitud"], 'fecha' => (string)$documento["fecha_gps"]]);
                    }


                    return response()->json(['error' => false, 'array_ruta' => $array_ruta]);
                }
                break;

            case 'guardar':
                $aaa = 'no entro';
                // return response()->json($request->all());
                $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'cooperativa_id' => 'required',
                    'tipo_ruta' => 'required'
                ]);
                if ($validator->fails())
                    return response()->json(['var' => $aaa, 'error' => true, 'messages' => $validator->errors(), 'tipo_error' => 'validacion']);
                else {
                    if ($request->input('array_ruta') == null || sizeof($request->input('array_ruta')) == 0)
                        return response()->json(['var' => $aaa, 'error' => true, 'messages' => $validator->errors(), 'tipo_error' => 'array_vacio']);

                    else {
                        $aaa = 'entro....';
                        if ($request->input('puntos_control') != null)
                            $ruta = Ruta::create([
                                'descripcion' => $request->input('descripcion'),
                                'cooperativa_id' => $request->input('cooperativa_id'),
                                'recorrido' => $request->input('array_ruta'),
                                'puntos_control' => $request->input('puntos_control'),
                                'estado' => 'A',
                                'tipo_ruta' => $request->input('tipo_ruta'),
                                'color' => $request->input('color'),
                                'ruta_padre' => (($request->input('tipo_ruta_padre') != null) ? $request->input('tipo_ruta_padre') : ''),
                                'creador_id' => Auth::user()->_id,
                                'modificador_id' => Auth::user()->_id,
                                'ruta_atm' => (($request->input('tipo_ruta_atm') != null) ? $request->input('tipo_ruta_atm') : ''),
                                'estado_exportacion' => 'P'
                            ]);

                        else
                            $ruta = Ruta::create([
                                'descripcion' => $request->input('descripcion'),
                                'cooperativa_id' => $request->input('cooperativa_id'),
                                'recorrido' => $request->input('array_ruta'),
                                'estado' => 'A',
                                'tipo_ruta' => $request->input('tipo_ruta'),
                                'color' => $request->input('color'),
                                'ruta_padre' => (($request->input('tipo_ruta_padre') != null) ? $request->input('tipo_ruta_padre') : ''),
                                'creador_id' => Auth::user()->_id,
                                'modificador_id' => Auth::user()->_id,
                                'ruta_atm' => (($request->input('tipo_ruta_atm') != null) ? $request->input('tipo_ruta_atm') : ''),
                                'estado_exportacion' => 'P'
                            ]);

                        $dias = $request->input('dia');
                        $desdes = $request->input('desde');
                        $hastas = $request->input('hasta');
                        $ruta->cronogramas()->delete();
                        if (is_array($dias)) {
                            for ($i = 0; $i < count($dias); $i++)
                                $ruta->cronogramas()->create([
                                    'dia' => (int) $dias[$i],
                                    'desde' => new Carbon('2018-01-01 ' . $desdes[$i]),
                                    'hasta' => new Carbon('2018-01-01 ' . $hastas[$i])
                                ]);
                        }
                        return response()->json(['var' => $aaa, 'error' => false, 'id' => $ruta->_id, 'tipo_error' => '', 'pp' => $request->input('puntos_control')]);
                    }
                }
                break;

            default:
                break;
        }
    }

    public function search(Request $request)
    {
        $user = $request->user();
        $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();
        $search = $request->input('search');
        $cooperativa = $request->input('cooperativa');
        $rutas = Ruta::permitida($cooperativa)->orderBy('descripcion')->where(function ($query) use ($search) {
            if ($search != '')
                $query->where('descripcion', 'like', "%$search%");
        });
        $estado = $request->input('estado');
        if ($estado != 'T')
            $rutas->where('estado', $estado);
        $rutas = $rutas->paginate(10);
        $rutas->setPath($request->fullUrl());
        return view('panel.lista-rutas', [
            'rutas' => $rutas,
            'cooperativa' => $cooperativa,
            'opcion' => $estado,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get(),
            'search' => $search
        ]);
    }
}
