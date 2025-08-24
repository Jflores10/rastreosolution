<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use Validator;
use App\TipoUnidad;
use App\TipoUsuario;
use App\Unidad;
use Auth;

class TipoUnidadController extends Controller
{
    public function index()
    {
        if(Auth::user()->estado=='A')
        {
            $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
            if($tipo_usuario->valor==1)
                return view('panel.lista-tipos-de-unidades', ['tipos_de_unidades' => TipoUnidad::where('estado','A')->paginate(10)]);
            else
                return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
        }
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else 
        {
            $tipoUnidad = TipoUnidad::create([
                'descripcion' => $request->input('descripcion'),
                'estado' =>$request->input('estado'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id
            ]);
            return response()->json(['error' => false, 'tipo_unidad' => $tipoUnidad]);
        }
    }
    public function show($id)
    {
        $tipoUnidad = TipoUnidad::findOrFail($id);
        return response()->json($tipoUnidad);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else 
        {
            $tipoUnidad = TipoUnidad::findOrFail($id);
            $tipoUnidad->descripcion = $request->input('descripcion');
            $tipoUnidad->modificador_id = Auth::user()->_id;
            $tipoUnidad->save();
            return response()->json(['error' => false, 'tipo_unidad' => $tipoUnidad]);
        }
    }
    public function destroy($id)
    {
       $unidad = Unidad::findOrFail($id);
        $tipo_unidad = TipoUnidad::findOrFail($id);

        if($tipo_unidad->estado=="A")
        {
            if($unidad==null)
                $tipo_unidad->estado="I";
        }
        else
            $tipo_unidad->estado="A";

        $tipo_unidad->save();
        return response()->json($tipo_unidad);
    }


    public function search(Request $request)
    {
        $search = $request->input('search');


        switch($request->input('mostrar_modo'))
        {
            case "inactivos":

                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::
                        where('estado', 'I')
                        ->paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->where('estado', 'I')
                        ->paginate(10);
                }


                break;

            case "todos":
                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::

                        paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->paginate(10);
                }

                break;

            default:
                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::
                    where('estado', 'A')
                        ->paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->where('estado', 'A')
                        ->paginate(10);
                }
                break;
        }
        $tipo_unidad->setPath($request->fullUrl());
        return view('panel.lista-tipos-de-unidades', ['tipos_de_unidades' => $tipo_unidad,'opcion'=> $request->input('mostrar_modo')
        ,'sss'=>$search]);
    }

}
