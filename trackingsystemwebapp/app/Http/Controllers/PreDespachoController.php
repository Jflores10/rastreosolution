<?php

namespace App\Http\Controllers;

use App\PreDespacho;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Conductor;
use App\Cooperativa;
use App\Unidad;
use App\Ruta;
use Validator;
use Auth;

class PreDespachoController extends Controller
{
    public function index()
    {
        return view('panel.pre-despachos', ['unidades' => Unidad::where('estado','A')->get(),
            'cooperativas' => Cooperativa::where('estado','A')->get(),'rutas' => Ruta::where('estado','A')->get(),
            'conductores' => Conductor::where('estado','A')->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooperativa_id' => 'required',
            'unidad_id' => 'required',
            'ruta_id' => 'required',
            'conductor_id' => 'required',
            'hora_salida' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $pre_despacho = PreDespacho::create([
                'oooperativa_id' => $request->input('cooperativa_id'),
                'unidad_id' => $request->input('unidad_id'),
                'ruta_id' => $request->input('ruta_id'),
                'conductor_id' => $request->input('conductor_id'),
                'hora_salida' => $request->input('hora_salida'),
                'estado' =>$request->input('estado'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id
            ]);
            return response()->json(['error' => false, 'pre_despacho' => $pre_despacho]);
        }
    }

    public function destroy($id)
    {
        $pre_despacho = PreDespacho::findOrFail($id);
        if($pre_despacho->estado=="A")
            $pre_despacho->estado="I";
        else
            $pre_despacho->estado="A";

        $pre_despacho->save();
        return response()->json($pre_despacho);
    }


}
