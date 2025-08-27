<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Response;
use App\Conductor;
use App\Cooperativa;
use App\TipoUsuario;
use Auth;
use Validator;
use Excel;
//
class ConductorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('panel.lista-conductores',[
            'conductores'=> Conductor::permitido()->orderBy('nombre', 'asc')->where('estado','A')->paginate(10),
            'cooperativas' => Cooperativa::permitida()->orderBy('nombre', 'asc')->where('estado','A')->get(),
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 'asc')->where('estado', 'A')->get(),
            'id_cooperativa' => $user->cooperativa_id
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => 'required|numeric|digits:10',
            'nombre' => 'required|max:50',
            'cooperativa_id' => 'required',
            'email' => 'nullable|max:255',
            'direccion' => 'nullable|max:255',
            'telefono' => 'nullable|max:20',
            'tipo_licencia' => 'nullable|max:20',
            'celular' => 'nullable',
            'operadora' => 'nullable|max:5'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $conductor = Conductor::create([
                'cedula' => $request->input('cedula'),
                'nombre' => $request->input('nombre'),
                'cooperativa_id' => $request->input('cooperativa_id'),
                'estado' =>$request->input('estado'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'direccion' => $request->input('direccion'),
                'email' => $request->input('email'),
                'operadora' => $request->input('operadora'),
                'telefono' => $request->input('telefono'),
                'tipo_licencia' => $request->input('tipo_licencia'),
                'celular' => $request->input('celular'),
                'exportado_atm' => 'P'
            ]);
            return response()->json(['error' => false, 'conductor' => $conductor]);
        }
    }

    public function show($id)
    {
        $conductor = Conductor::findOrFail($id);
        return response()->json($conductor);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => 'required|numeric|digits:10',
            'nombre' => 'required|max:50',
            'cooperativa_id' => 'required',
            'email' => 'nullable|max:255',
            'direccion' => 'nullable|max:255',
            'telefono' => 'nullable|max:20',
            'tipo_licencia' => 'nullable|max:20',
            'celular' => 'nullable',
            'operadora' => 'nullable|max:5'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $conductor = Conductor::findOrFail($id);
            $conductor->cedula = $request->input('cedula');
            $conductor->nombre = $request->input('nombre');
            $conductor->cooperativa_id = $request->input('cooperativa_id');
            $conductor->modificador_id = Auth::user()->_id;
            $conductor->email = $request->input('email');
            $conductor->direccion = $request->input('direccion');
            $conductor->operadora = $request->input('operadora');
            $conductor->telefono = $request->input('telefono');
            $conductor->celular = $request->input('celular');
            $conductor->tipo_licencia=$request->input('tipo_licencia');
            $conductor->exportado_atm='P';
            $conductor->save();
            return response()->json(['error' => false, 'conductor' => $conductor]);
        }
    }

    public function destroy($id)
    {
        $conductor = Conductor::findOrFail($id);
        if($conductor->estado=="A")
            $conductor->estado="I";
        else
            $conductor->estado="A";

        if(isset($conductor->exportado_atm) && $conductor->exportado_atm=="E")
            $conductor->exportado_atm='P';
        else
            $conductor->exportado_atm='A';

        $conductor->save();
        return response()->json($conductor);
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'cooperativa' => 'nullable|exists:cooperativas,_id',
            'estado' => 'required|max:1'
        ]);
        $user = Auth::user();
        $search = $request->input('search');
        $estado = $request->input('estado');
        $conductores = Conductor::orderBy('nombre')->permitido($request->input('cooperativa'))->where(function ($query) use ($search) {
            if ($search != null && $search != '')
                $query->where('nombre', 'like', "%$search%")
                    ->orWhere('cedula', 'like', "%$search%");
        });
        if ($estado != 'T')
            $conductores->where('estado', $estado);
        if ($request->input('exportar') != null) {
            $filename = 'Conductores-' . date('YmdHis');
            Excel::create($filename, function ($excel) use ($conductores) {
                $excel->sheet('Unidades', function ($sheet) use ($conductores) {
                    $sheet->loadView('panel.conductores.excel-conductores', [
                        'conductores' => $conductores->get()
                    ]);
                });
                $excel->download();
            });
        }
        else 
        {
            $conductores = $conductores->paginate(10);
            $conductores->setPath($request->fullUrl());
            return view('panel.lista-conductores',[
                'conductores' => $conductores,
                'tipo_usuario_valor' => Auth::user()->tipo_usuario->valor,
                'opcion' =>$request->input('estado'),
                'coop' => $request->input('cooperativa'),
                'search' => $search,
                'cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 'asc')->where('estado', 'A')->get(),
                'id_cooperativa' => $user->cooperativa_id
            ]);
        }    
    }

}