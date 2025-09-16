<?php

namespace App\Http\Controllers;

use App\Ruta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use Validator;
use App\PuntoControl;
use App\Cooperativa;
use App\TipoUsuario;
use Auth;
use Carbon\Carbon;
class PuntoControlController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cooperativa= Cooperativa::where('_id', $user->cooperativa_id)->first();
        $is_bloques=false;
        if(isset($cooperativa) && $cooperativa){
            $is_bloques=$cooperativa->pto_bloques;
        }
        return view('panel.lista-puntos-control',
        [
            'puntos_control'=> PuntoControl::permitido($user->cooperativa_id)->orderBy('descripcion', 'asc')
                ->where('estado','A')
                ->with('cooperativa')
                ->paginate(10),
            'id_cooperativa' =>$user->cooperativa_id,
            'is_bloques'=>$is_bloques,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }
    public function searchJSON(Request $request)
    {
        $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
        $search = $request->input('search');
        if($tipo_usuario->valor=="1")
        {
            if($request->input('cooperativa_id')!='')
                return response()->json(PuntoControl::orderBy('descripcion', 'asc')
                    ->where('descripcion', 'like', "%$search%")
                    ->where('estado','A')
                    ->where('cooperativa_id',$request->input('cooperativa_id'))
                    ->get());
        }
        elseif($tipo_usuario->valor=="2")
        {
            return response()->json(PuntoControl::orderBy('descripcion', 'asc')
                ->where('descripcion', 'like', "%$search%")
                ->where('cooperativa_id',Auth::user()->cooperativa_id)
                ->where('estado','A')
                ->get());
        }
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'radio' => 'required|numeric',
            'cooperativa_id' => 'required',
            'pdi'=>'required',
            'otro' => 'required',
            'entrada' => 'nullable|required_if:otro,true',
            'salida' => 'nullable|required_if:otro,true'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $punto_control = PuntoControl::create([
                'descripcion' => $request->input('descripcion'),
                'cooperativa_id' => $request->input('cooperativa_id'),
                'latitud' => $request->input('latitud'),
                'longitud' => $request->input('longitud'),
                'radio' => $request->input('radio'),
                'estado' => $request->input('estado'),
                'pdi' =>  $request->input('pdi'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'entrada' => $request->input('entrada'),
                'salida' => $request->input('salida'),
                'mt' => $request->input('otro'),
                'estado_exportacion' => 'P'
            ]);
            return response()->json(['error' => false, 'punto_control' => $punto_control]);
        }
    }

    public function store_bloque(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooperativa_id' => 'required',
            'estado' => 'required',
            'puntos' => 'required|array|min:1',
            'puntos.*.descripcion' => 'required|max:255',
            'puntos.*.latitud' => 'required|numeric',
            'puntos.*.longitud' => 'required|numeric',
            'puntos.*.radio' => 'required|numeric',
            'puntos.*.pdi' => 'required',
            'puntos.*.otro' => 'required',
            'puntos.*.entrada' => 'nullable|required_if:puntos.*.otro,true',
            'puntos.*.salida' => 'nullable|required_if:puntos.*.otro,true',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        }

        $cooperativa_id = $request->input('cooperativa_id');
        $estado = $request->input('estado');
        $puntos_guardados = [];

        $diaActual = Carbon::now()->dayOfWeek; // 0=Domingo, 1=Lunes, … 6=Sábado
        $i=1;
        foreach ($request->input('puntos') as $p) {
            $activo=false;
            if($i==1){
                $activo=true;
                $pdi_final = $p['pdi'];
            }
            else{
                $pdi_final = '0'.$p['pdi'];
            }

            $punto_control = PuntoControl::create([
                'descripcion' => $p['descripcion'],
                'cooperativa_id' => $cooperativa_id,
                'latitud' => $p['latitud'],
                'longitud' => $p['longitud'],
                'radio' => $p['radio'],
                'estado' => $estado,
                'pdi' => $pdi_final, 
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'entrada' => $p['entrada'],
                'salida' => $p['salida'],
                'mt' => $p['otro'],
                'estado_exportacion' => 'P',
                'pdi_padre' => $p['pdi'], 
                'activo' => $activo, 
                'bloque' => $i, 
            ]);

            $puntos_guardados[] = $punto_control;
            $i++;
        }

        return response()->json(['error' => false, 'puntos_control' => $puntos_guardados]);
    }
    
    public function show($id)
    {

        $punto_control = PuntoControl::findOrFail($id);
        $bloques=[];
        if(isset($punto_control->pdi_padre) && $punto_control->pdi_padre!=''){
            $bloques = PuntoControl::where('pdi_padre', $punto_control->pdi_padre)->get();    
        }
       
        return response()->json([
            'punto_control' => $punto_control,
            'bloques' => $bloques
        ]);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'radio' => 'required|numeric',
            'cooperativa_id' => 'required',
            'pdi' => 'required',
            'entrada' => 'nullable',
            'salida' => 'nullable',
            'otro' => 'required',
            'entrada' => 'nullable|required_if:otro,true',
            'salida' => 'nullable|required_if:otro,true'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $punto_control = PuntoControl::findOrFail($id);
            $punto_control->descripcion = $request->input('descripcion');
            $punto_control->cooperativa_id = $request->input('cooperativa_id');
            $punto_control->latitud = $request->input('latitud');
            $punto_control->longitud = $request->input('longitud');
            $punto_control->radio = $request->input('radio');
            $punto_control->pdi = $request->input('pdi');
            $punto_control->modificador_id = Auth::user()->_id;
            $punto_control->entrada = $request->input('entrada');
            $punto_control->salida = $request->input('salida');
            $punto_control->mt = $request->input('otro');
            $punto_control->estado_exportacion = 'P';
            $punto_control->save();
            return response()->json(['error' => false, 'punto_control' => $punto_control]);
        }
    }

    public function update_bloque(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooperativa_id' => 'required',
            'estado' => 'required',
            'puntos' => 'required|array|min:1',
            'puntos.*.descripcion' => 'required|max:255',
            'puntos.*.latitud' => 'required|numeric',
            'puntos.*.longitud' => 'required|numeric',
            'puntos.*.radio' => 'required|numeric',
            'puntos.*.pdi' => 'required',
            'puntos.*.otro' => 'required',
            'puntos.*.entrada' => 'nullable|required_if:puntos.*.otro,true',
            'puntos.*.salida' => 'nullable|required_if:puntos.*.otro,true',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        }

        $cooperativa_id = $request->input('cooperativa_id');
        $estado = $request->input('estado');
        $puntos_guardados = [];

        $diaActual = Carbon::now()->dayOfWeek; // 0=Domingo, 1=Lunes, … 6=Sábado
       
        $i=1;
        foreach ($request->input('puntos') as $p) {

            $activo=false;
            if($i==1){
                $activo=true;
                $pdi_final = $p['pdi'];
            }
            else{
                $pdi_final = '0'.$p['pdi'];
            }
         
            $punto_control = PuntoControl::where('_id',$p['_id'])->first();
            if($punto_control){
                $punto_control->update([
                    'descripcion' => $p['descripcion'],
                    'cooperativa_id' => $cooperativa_id,
                    'latitud' => $p['latitud'],
                    'longitud' => $p['longitud'],
                    'radio' => $p['radio'],
                    'estado' => $estado,
                    'pdi' => $pdi_final, 
                    'creador_id' => Auth::user()->_id,
                    'modificador_id' => Auth::user()->_id,
                    'entrada' => $p['entrada'],
                    'salida' => $p['salida'],
                    'mt' => $p['otro'],
                    'estado_exportacion' => 'P',
                    'pdi_padre' => $p['pdi'], 
                    'activo' => $activo, 
                    'bloque' => $i, 

                ]);
            }
            
            $puntos_guardados[] = $punto_control;
            $i++;
        }



        return response()->json(['error' => false, 'puntos_control' => $puntos_guardados]);
    }

    public function actualizarBloques(Request $request)
    {
        $diaInicio = $request->input('diaInicio'); // 1=Lunes ... 7=Domingo

        if (!$diaInicio || $diaInicio < 1 || $diaInicio > 7) {
            return response()->json(['error' => 'El parámetro diaInicio es obligatorio y debe estar entre 1 y 7'], 400);
        }
        $diaHoy = Carbon::now()->dayOfWeekIso; // 1 (Lunes) ... 7 (Domingo)

        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];

        // Calcular diferencia de días desde el día de inicio
        $diferencia = ($diaHoy - $diaInicio + 7) % 7;

        // Bloque se alterna cada día
        // Si diferencia = 0 → Bloque 1
        // Si diferencia = 1 → Bloque 2
        // Si diferencia = 2 → Bloque 1
        // ...
        $bloqueHoy = ($diferencia % 2 == 0) ? 1 : 2;

        $diaHoyNombre = $dias[$diaHoy];

        // Buscar puntos con pdi_padre
        $puntos = PuntoControl::where('pdi_padre', '!=', '')->get();
        $agrupados = $puntos->groupBy('pdi_padre');

        foreach ($agrupados as $pdi_padre => $grupo) {
            if ($grupo->count() < 2) {
                continue;
            }
            foreach ($grupo as $punto) {
                if ($punto->bloque == $bloqueHoy) {
                    $punto->activo = true;
                    $punto->pdi = $punto->pdi_padre;
                } else {
                    $punto->activo = false;
                    $punto->pdi = '0' . $punto->pdi_padre;
                }
                $punto->save();
            }
        }

        return response()->json([
            'msj' => "Bloques actualizados para el día $diaHoyNombre (Bloque $bloqueHoy, inicio desde {$dias[$diaInicio]})"
        ]);
    }



   /* public function destroy($id)
    {
        $punto_control = PuntoControl::findOrFail($id);
        $punto_control->delete();
        return response()->json($punto_control);
    }*/

    public function destroy($id)
    {
        $ruta = Ruta::where('puntos_control.id',$id )->first();
        $punto_control = PuntoControl::findOrFail($id);

        if($punto_control->estado=="A")
        {
            if($ruta==null)
                $punto_control->estado="I";
        }
        else
            $punto_control->estado="A";

        $punto_control->estado_exportacion = 'P';
        $punto_control->save();
        return response()->json($punto_control);
    }


    public function search(Request $request)
    {
        $this->validate($request, [
            'estado' => 'required|max:1'
        ]);
        $user = $request->user();
        $id_cooperativa = $request->input('cooperativa');
        $search = $request->input('search');
        $puntosControl = PuntoControl::permitido($id_cooperativa)->orderBy('descripcion')->where(function ($query) use($search){
                if (isset($search) && $search != '')
                    $query->where('descripcion', 'like', "%$search%");
            }
        );
        $estado = $request->input('estado');
        if ($estado != 'T')
            $puntosControl->where('estado', $estado);
        $puntosControl = $puntosControl->paginate(10);
        $puntosControl->setPath($request->fullUrl());
        return view('panel.lista-puntos-control',
        [
            'puntos_control'=> $puntosControl,
            'id_cooperativa' =>$user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'opcion' =>$estado,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get(),
            'search' => $search,
            'coop' => $id_cooperativa
        ]);
    }
}

/**
 * Created by PhpStorm.
 * User: José Daniel
 * Date: 18/09/2016
 * Time: 15:33
 */