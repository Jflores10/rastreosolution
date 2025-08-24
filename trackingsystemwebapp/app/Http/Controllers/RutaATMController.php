<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\PuntoControlAtmOficial;
use App\Cooperativa;
use App\Unidad;
use App\RutaAtmOficial;
use App\User;
use App\TipoUsuario;
use MongoDB\BSON\UTCDateTime;
use Auth;
use Validator;
use MongoDB\BSON\ObjectID;
use DateTime;
use DateInterval;
use Excel;

class RutaATMController extends Controller
{    
    public function index()
    {
        $user = Auth::user();
        return view('panel.lista-rutas-atm',[
            'rutas'=> RutaAtmOficial::permitida()->orderBy('descripcion')
                ->where('estado','A')
                ->paginate(10),
            'cooperativa' => $user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }
    
    public function show($id)
    {
        if(Auth::user()->estado=='A')
        {
            $ruta=RutaAtmOficial::findOrFail($id);
            $coops=array();
            array_push($coops,$ruta->cooperativa_id);
            $usuarios=User::where('tipo_usuario_id','5a7239227524c31fadb5043c')->whereIn('cooperativa_id',$coops)->where('estado','A')
            ->orderBy('name','asc')->get();

            $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
            if($tipo_usuario->valor=="1")
                return view('panel.crear-ruta-atm',['puntos_control' => PuntoControlAtmOficial::paginate(10),
                    'ruta'=> $ruta,
                    'cooperativas' => Cooperativa::orderBy('descripcion', 'asc')->where('estado','A')->get(),
                    'puntos_control' => PuntoControlAtmOficial::all(),
                    'unidades' => Unidad::orderBy('placa', 'asc')->where('estado','A')->get(),
                    'tipo_usuario_valor' => $tipo_usuario->valor,
                    'usuarios' =>$usuarios
                ]);

            elseif($tipo_usuario->valor=="2")
            {
                return view('panel.crear-ruta-atm',[
                    'puntos_control' => PuntoControlAtmOficial::
                        whereIn('cooperativa_id',Auth::user()->cooperativa_id)
                        ->get(),
                    'cooperativas' => Cooperativa::activa()->permitida()->orderBy('descripcion', 'asc')->get(),
                    'unidades' => Unidad::orderBy('placa', 'asc')
                        ->whereIn('cooperativa_id',Auth::user()->cooperativa_id)
                        ->get(),
                    'ruta'=> $ruta,
                    'tipo_usuario_valor' => $tipo_usuario->valor,
                    'usuarios' =>$usuarios
                ]);
            }

            else
                return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
        }
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);

    }

    public function search(Request $request)
    {
        $user = $request->user();
        $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
        $search = $request->input('search');
        $cooperativa = $request->input('cooperativa');
        $rutas = RutaAtmOficial::permitida($cooperativa)->orderBy('descripcion')->where(function ($query) use($search) {
            if ($search != ''){
                $query->where('descripcion', 'like', "%$search%")->orWhere('codigo', 'like', "%$search%");
            }
        });

        if (!$request->input('reportar')) {
            $rutas = $rutas->paginate(10);
            $rutas->setPath($request->fullUrl());
            return view('panel.lista-rutas-atm',[
                'rutas'=> $rutas,
                'cooperativa' => $cooperativa,
                'tipo_usuario_valor' => $user->tipo_usuario->valor,
                'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get(),
                'search' => $search
            ]);
        }
        else 
        {
            set_time_limit(0);
            $rutas = $rutas->select('puntos', 'codigo', '_id', 'descripcion', 'cooperativa_id','puntos_control', 
            'cooperativa')->get();
            Excel::create('Rutas ATM', function ($excel) use($rutas){
                $excel->sheet('Consulta de rutas', function ($sheet) use($rutas) {
                    $sheet->loadView('panel.rutas.excel-consulta-rutas-atm', [
                        'rutas' => $rutas
                    ]);
                });
            })->export('xlsx');
        }
    }

    public function update(Request $request, $id)
    {
        $ruta = RutaAtmOficial::findOrFail($id);
        if($request->input('puntos_control')!=null) {
            $ruta->puntos_control = $request->input('puntos_control');
        }           
        $ruta->save();
        return response()->json(['error' => false, 'ruta' => $ruta]);
        
    }
}
