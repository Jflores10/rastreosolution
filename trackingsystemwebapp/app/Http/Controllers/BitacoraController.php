<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\Unidad;
use App\User;
use App\Conductor;
use App\Ruta;
use Validator;
use Auth;
use DateInterval;
use App\Despacho;
use MongoDB\BSON\UTCDateTime;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use App\TipoUsuario;
use App\Bitacora;


class BitacoraController extends Controller
{
    public function index()
    {
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1)
        {
            $cooperativas = Cooperativa::orderBy('descripcion','asc')->get();
            $bitacoras = Bitacora::orderBy('fechaInicio','desc')->where('fechaInicio', '>=', $desde)
            ->where('fechaInicio', '<=',$hasta)->where('estado', 'P')->paginate(60);
        }
        else
        {
            $usuarios_distribuidores=User::where('tipo_usuario_id','5827714b7b10202ff4485891')->get();
            $usr_distribuidores=array();
            foreach ($usuarios_distribuidores as $usr)
                array_push($usr_distribuidores, $usr->_id);

            $cooperativas = Cooperativa::orderBy('descripcion','asc')->where('_id', $user->cooperativa_id)->get();
            $unidades = Unidad::where('cooperativa_id', $user->cooperativa_id)->get();
            $ids = array();
            foreach ($unidades as $unidad)
                array_push($ids, $unidad->_id);

            $bitacoras = Bitacora::orderBy('fechaInicio','desc')->where('fechaInicio', '>=', $desde)
            ->where('fechaInicio', '<=',$hasta)->whereIn('unidad_id',$ids)->where('estado', 'P')
            ->where(function ($query) use($usr_distribuidores){
                $query->whereNotIn('creador_id',$usr_distribuidores)
                ->orWhere('compartido','S');
            })->paginate(60);
        }
        return view('panel.bitacoras.bitacora',['tipo' => 'P','cooperativas' => $cooperativas, 
        'desde' => $desde, 'hasta' => $hasta,'bitacoras'=>$bitacoras]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unidad' => 'required|exists:unidads,_id',
            'fechaInicio' => 'required|date',
            'tipo_bitacora'=>'required',
            'descripcion'=>'required'
        ]);

        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $fechaInicio = new Carbon($request->input('fechaInicio'));
            date_sub($fechaInicio, date_interval_create_from_date_string('5 hours'));

            $fechaFin=$request->input('fechaFin');
            if(isset($fechaFin) && $fechaFin != ''){
                $fechaFin = new Carbon($request->input('fechaFin'));
                date_sub($fechaFin, date_interval_create_from_date_string('5 hours'));
            }else{
                $fechaFin=null;
            }
            if($request->input('accion') == 'C'){
                $bitacora = Bitacora::create([
                    'unidad_id' => $request->input('unidad'),
                    'fechaInicio' => $fechaInicio,
                    'fechaFin'=>$fechaFin,
                    'tipo_bitacora'=>$request->input('tipo_bitacora'),
                    'descripcion'=>$request->input('descripcion'),
                    'compartido'=> $request->input('compartido'),
                    'estado' => ($fechaFin == null)?'P':'F',
                    'creador_id' => Auth::user()->_id
                ]);
            }else{
                $bitacora = Bitacora::findOrFail($request->input('id_bitacora'));
                $bitacora->unidad_id = $request->input('unidad');
                $bitacora->fechaInicio = $fechaInicio;
                $bitacora->fechaFin = $fechaFin;
                $bitacora->tipo_bitacora = $request->input('tipo_bitacora');
                $bitacora->descripcion = $request->input('descripcion');
                $bitacora->compartido = $request->input('compartido');
                $bitacora->estado = ($fechaFin == null)?'P':'F';
                $bitacora->modificador_id = Auth::user()->_id;
                $bitacora->save();
            }

            return response()->json(['error' => false, 'bitacora' => $bitacora]);
        }
    }

    public function finalizados(){
        $user = Auth::user();
        $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();

        if(Auth::user()->estado=='A')
        {   
            if($tipo_usuario->valor!=4 && $tipo_usuario->valor!=5)
            {
                $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
                $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
                date_sub($desde, date_interval_create_from_date_string('5 hours'));
                date_sub($hasta, date_interval_create_from_date_string('5 hours'));
                if ($user->tipo_usuario->valor == 1)
                {
                    $cooperativas = Cooperativa::orderBy('descripcion','asc')->get();
                    $bitacoras = Bitacora::orderBy('fechaInicio','desc')->where('fechaFin', '>=', $desde)
                    ->where('fechaFin', '<=',$hasta)->where('estado', 'F')->paginate(60);
                }
                else
                {
                    $usuarios_distribuidores=User::where('tipo_usuario_id','5827714b7b10202ff4485891')->get();
                    $usr_distribuidores=array();
                    foreach ($usuarios_distribuidores as $usr)
                        array_push($usr_distribuidores, $usr->_id);

                    $cooperativas = Cooperativa::orderBy('descripcion','asc')->where('_id', $user->cooperativa_id)->get();
                    $unidades = Unidad::where('cooperativa_id', $user->cooperativa_id)->get();
                    $ids = array();
                    foreach ($unidades as $unidad)
                        array_push($ids, $unidad->_id);

                    $bitacoras = Bitacora::orderBy('fechaInicio','desc')->where('fechaFin', '>=', $desde)
                    ->where('fechaFin', '<=',$hasta)->whereIn('unidad_id',$ids)->where('estado', 'F')
                    ->where(function ($query) use($usr_distribuidores){
                        $query->whereNotIn('creador_id',$usr_distribuidores)
                        ->orWhere('compartido','S');
                    })->paginate(60);
                }
                return view('panel.bitacoras.bitacora',['tipo' => 'F','cooperativas' => $cooperativas, 
                            'desde' => $desde, 'hasta' => $hasta,'bitacoras'=>$bitacoras]);

            }
            else
                return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
        }        
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);
         
    }

    public function find(Request $request){
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

        if ($user->tipo_usuario->valor == 1){
            $cooperativas = Cooperativa::orderBy('descripcion','asc')->get();

            $bitacoras = Bitacora::orderBy('fechaInicio', 'desc')->whereIn('unidad_id',$request->input('unidades'));
            if ($tipo === 'P'){
                $bitacoras->where('estado' , 'P')->where('fechaInicio', '>=', $desde)->where('fechaInicio', '<=', $hasta);
            }
            else{
                $bitacoras->where('estado' , 'F')->where('fechaFin', '>=', $desde)->where('fechaFin', '<=', $hasta);
            }
            $bitacoras = $bitacoras->paginate(60);
            $bitacoras->setPath($request->fullUrl());

            return view('panel.bitacoras.bitacora', ['cooperativas' => $cooperativas, 'bitacoras' => $bitacoras,
            'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'cooperativa_search' => $request->input('cooperativa_search'),
            'unidades_search' => $request->input('unidades'), 'filtro_fecha' => $request->input('filtro_fecha')]);
        }
        else{            
            $usuarios_distribuidores=User::where('tipo_usuario_id','5827714b7b10202ff4485891')->get();
            $usr_distribuidores=array();
            foreach ($usuarios_distribuidores as $usr)
                array_push($usr_distribuidores, $usr->_id);

            $cooperativas = Cooperativa::orderBy('descripcion','asc')->where('_id', $user->cooperativa_id)->get();

            $bitacoras = Bitacora::orderBy('fechaInicio', 'desc')->whereIn('unidad_id',$request->input('unidades'))
            ->where(function ($query) use($usr_distribuidores){
                $query->whereNotIn('creador_id',$usr_distribuidores)
                ->orWhere('compartido','S');
            });
            
            if ($tipo === 'P'){
                $bitacoras->where('estado' , 'P')->where('fechaInicio', '>=', $desde)->where('fechaInicio', '<=', $hasta);
            }
            else{
                $bitacoras->where('estado' , 'F')->where('fechaFin', '>=', $desde)->where('fechaFin', '<=', $hasta);
            }
            $bitacoras = $bitacoras->paginate(60);
            $bitacoras->setPath($request->fullUrl());

            return view('panel.bitacoras.bitacora', ['cooperativas' => $cooperativas, 'bitacoras' => $bitacoras,
            'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'cooperativa_search' => $request->input('cooperativa_search'),
            'unidades_search' => $request->input('unidades'), 'filtro_fecha' => $request->input('filtro_fecha')]);
        }

        
    }

    public function show($id)
    {
        $bitacora = Bitacora::with('unidad')->where('_id', $id)->first();
        $fechaInicio = $bitacora->fechaInicio;
        date_add($fechaInicio, date_interval_create_from_date_string('5 hours'));
        $bitacora->fechaInicio=$fechaInicio;

        return response()->json($bitacora);
    }

    public function getBitacorasUnidades(Request $request){
        $user = Auth::user();
        if ($user->tipo_usuario->valor != 1)
        {
            $usuarios_distribuidores=User::where('tipo_usuario_id','5827714b7b10202ff4485891')->get();
            $usr_distribuidores=array();
            foreach ($usuarios_distribuidores as $usr)
                array_push($usr_distribuidores, $usr->_id);
            $bitacoras=Bitacora::with('unidad','creador','modificador')->orderBy('fechaInicio','desc')->where('unidad_id',$request->input('unidad_id'))
                ->where('estado','P')->where(function ($query) use($usr_distribuidores){
                    $query->whereNotIn('creador_id',$usr_distribuidores)
                    ->orWhere('compartido','S');
                })->get();
        }else{
            $bitacoras=Bitacora::with('unidad','creador','modificador')->orderBy('fechaInicio','desc')->where('unidad_id',$request->input('unidad_id'))
            ->where('estado','P')->get();
        }

        return response()->json($bitacoras); 
    }

    public function getUnidades($cooperativa)
    {
        $unidades=Unidad::orderBy('descripcion', 'asc')->where('cooperativa_id',$cooperativa)
        ->where('estado','A')->get();
        
        return response()->json($unidades);
    }
}
