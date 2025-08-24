<?php

namespace App\Http\Controllers;


use App\TipoUsuario;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Cooperativa;
use App\unidad;
use Auth;
use Validator;
use  Moloquent;

class CooperativaController extends Controller
{

    public function index()
    {
        if(Auth::user()->estado=='A')
        {
            $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
            if($tipo_usuario->valor=="1")
                return view('panel.lista-cooperativas',['cooperativas'=> Cooperativa::orderBy('descripcion', 'asc')->where('estado','A')->paginate(10)]);
            else
                return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
        }
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'multa_tubo'=>'numeric',
            'tolerancia_buffer_minutos' => 'nullable|numeric|min:0',
            'taxis' => 'required|boolean',
            'ruc' => 'nullable|required_if:despachos_atm,S|digits:13|unique:cooperativas,ruc',
            'despachos_atm' => 'nullable|max:1',
            'email' => 'nullable|max:255|email',
            'mascara' => 'required|max:1',
            'importador_despachos' => 'nullable',
            'finalizacion_automatica' => 'nullable',
            'redondear_tiempos_atraso' => 'nullable'

        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $taxis = $request->input('taxis');
            $cooperativa = Cooperativa::create([
                'descripcion' => $request->input('descripcion'),
                'multa_tubo' => $request->input('multa_tubo'),
                'taxis' => ($taxis == 1 || $taxis === 'true'),
                'estado' =>$request->input('estado'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'ruc' => $request->input('ruc'),
                'mascara' => $request->input('mascara'),
                'despachos_job'=>($request->input('despachos_job') == 'S')?'S':'N',
                'despachos_atm' => ($request->input('despachos_atm') == 'S')?'S':'N',
                'email' => $request->input('email'),
                'importador_despachos' => $request->input('importador_despachos') == 'true'?true:false,
                'finalizacion_automatica' => $request->input('finalizacion_automatica')  == 'true'?true:false,
                'redondear_tiempos_atraso' => $request->input('redondear_tiempos_atraso') == 'true'?true:false,
                'tolerancia_buffer_minutos' => $request->input('tolerancia_buffer_minutos')
            ]);

            return response()->json(['error' => false, 'cooperativa' => $cooperativa]);
        }
    }

    public function show($id)
    {
        $cooperativa = Cooperativa::findOrFail($id);
        return response()->json($cooperativa);
    }

    public function update(Request $request, $id)
    {
        $ruc = $request->input('ruc');
        $cooperativa = Cooperativa::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'multa_tubo' => 'numeric',
            'tolerancia_buffer_minutos' => 'nullable|numeric|min:0',
            'taxis' => 'required|boolean',
            'ruc' => 'nullable|required_if:despachos_atm,S|digits:13' . ($ruc == $cooperativa->ruc)?'':'|unique:cooperativas,ruc',
            'despachos_atm' => 'nullable|max:1',
            'email' => 'nullable|max:255|email',
            'mascara' => 'required|max:1',
            'importador_despachos' => 'nullable',
            'finalizacion_automatica' => 'nullable',
            'redondear_tiempos_atraso' => 'nullable'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $taxis = $request->input('taxis');
            $cooperativa->descripcion = $request->input('descripcion');
            $cooperativa->multa_tubo = $request->input('multa_tubo');
            $cooperativa->tolerancia_buffer_minutos = $request->input('tolerancia_buffer_minutos');
            $cooperativa->modificador_id = Auth::user()->_id;
            $cooperativa->taxis = ($taxis == 1 || $taxis === 'true');
            $cooperativa->ruc = $ruc;
            $cooperativa->despachos_job=($request->input('despachos_job') == 'S')?'S':'N';
            $cooperativa->email = $request->input('email');
            $cooperativa->despachos_atm = ($request->input('despachos_atm') == 'S')?'S':'N';
            $cooperativa->mascara = $request->input('mascara');
            $cooperativa->importador_despachos = $request->input('importador_despachos')  == 'true'?true:false;
            $cooperativa->finalizacion_automatica = $request->input('finalizacion_automatica')  == 'true'?true:false;
            $cooperativa->redondear_tiempos_atraso = $request->input('redondear_tiempos_atraso') == 'true'?true:false;
            $cooperativa->save();
            return response()->json(['error' => false, 'cooperativa' => $cooperativa]);
        }
    }

    public function destroy($id)
    {
        $unidad = Unidad::where('cooperativa_id',$id )->first();
        $cooperativa = Cooperativa::findOrFail($id);

        if($cooperativa->estado=="A")
        {
            if($unidad==null)
                $cooperativa->estado="I";
        }
        else
            $cooperativa->estado="A";


        $cooperativa->save();
        return response()->json($cooperativa);
    }

    public function search(Request $request)
    {
        $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
        if($tipo_usuario->valor==1)
        {
            $search = $request->input('search');

            switch($request->input('mostrar_modo'))
            {
                case "inactivos":
                    $cooperativa = Cooperativa::orderBy('descripcion', 'asc')->where('descripcion', 'like', '%'. $search . '%')
                        -> orWhere('multa_tubo', 'like',  $search . '%')
                        -> where('estado', 'I')
                        ->paginate(10);
                    break;

                case "todos":
                    $cooperativa = Cooperativa::orderBy('descripcion', 'asc')->where('descripcion', 'like','%' . $search . '%')
                        -> orWhere('multa_tubo', 'like',  $search . '%')
                        ->paginate(10);
                    break;

                default:
                    $cooperativa = Cooperativa::orderBy('descripcion', 'asc')->where('descripcion', 'like','%' . $search . '%')
                        -> orWhere('multa_tubo', 'like',  $search . '%')
                        -> where('estado', 'A')
                        ->paginate(10);
                    break;
            }
            $cooperativa->setPath($request->fullUrl());
            return view('panel.lista-cooperativas', ['cooperativas' => $cooperativa,'opcion'=> $request->input('mostrar_modo')]);
        }
        else
            return view('panel.reportes-unidades');

    }

    public function getCooperativas()
    {

    }
}
