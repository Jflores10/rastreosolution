<?php

namespace App\Http\Controllers;
use App\TipoUnidad;
use App\Unidad;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Response;
use App\TipoUsuario;
use App\Recorrido;
use Auth;
use MongoDB\BSON\ObjectID;
use phpDocumentor\Reflection\Types\Object_;
use Validator;
use App\Cooperativa;
use Excel;

class UnidadController extends Controller
{
    public function cargarPorCooperativa($cooperativa) {
        $user = Auth::user();
        return response()->json(Unidad::orderBy('descripcion')->where('cooperativa_id', 
        $cooperativa)->permitida($user->cooperativa_id)->activa()->get());
    }
    public function index(Request $request)
    {
        $unidades = Unidad::permitida()->orderBy('placa')->paginate(10);
        $user = $request->user();
        return view('panel.lista-unidades', [
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->get(),
            'unidades' => $unidades,
            'tipos_unidades'=>TipoUnidad::orderBy('descripcion', 'asc')->where('estado','A')->get(),
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'id_cooperativa' => $user->cooperativa_id
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imei' => 'required|unique:unidads',
            'placa' => 'required|unique:unidads',
            'descripcion' => 'required|max:255',
            'cooperativa_id' => 'required',
            'tipo_unidad_id' => 'required',
            'marca' => 'required|max:100',
            'modelo' => 'required|max:100',
            'serie' => 'required|max:25',
            'motor' => 'required|max:25',
            'email_alarma' => 'required|email',
            'atm' => 'required',
            'velocidad' => 'nullable|numeric'
        ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $unidad = Unidad::create([
                'placa' => $request->input('placa'),
                'imei' => $request->input('imei'),
                'descripcion' => $request->input('descripcion'),
                'cooperativa_id' => $request->input('cooperativa_id'),
                'tipo_unidad_id' => $request->input('tipo_unidad_id'),
                'marca' => $request->input('marca'),
                'modelo' => $request->input('modelo'),
                'serie' => $request->input('serie'),
                'motor' => $request->input('motor'),
                'email_alarma' => $request->input('email_alarma'),
                'sistema_energizado' => (($request->input('sistema_energizado'))=="true")?true:false,
                'contador_cero_manual' => (($request->input('contador_cero_manual'))=="true")?true:false,
                'desconexion_sistema' =>(($request->input('desconexion_sistema'))=="true")?true:false,
                'control_velocidad' =>(($request->input('control_velocidad'))=="true")?true:false,
                'climatizada' =>(($request->input('climatizada'))=="true")?true:false,
                'rampa' =>(($request->input('rampa'))=="true")?true:false,
                'estado' =>$request->input('estado'),
                'velocidad_actual'=>0,
                'contador_total'=>0,
                'contador_diario'=>0,
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id,
                'atm' => ($request->input('atm') == 'true')?'S':'N',
                'velocidad' => $request->input('velocidad')
            ]);
            return response()->json(['error' => false, 'unidad' => $request->all()]);
        }
    }

    public function show($id)
    {
        $unidad = Unidad::findOrFail($id);
        return response()->json($unidad);
    }

    public function getRecorrido($id)
    {
        $unidad = Unidad::findOrFail($id);

        if($unidad->fecha_gps!=null && $unidad->fecha!=null)
        {
            $f_gps=$unidad["fecha_gps"]->toDateTime();
            $f_servidor=$unidad["fecha"]->toDateTime();
            date_sub($f_gps, date_interval_create_from_date_string('10 hours'));
            date_sub($f_servidor, date_interval_create_from_date_string('5 hours'));
            $unidad["fecha"]=$f_servidor->format('d-m-Y H:i');
            $unidad["fecha_gps"]=$f_gps->format('d-m-Y H:i');
            $recorrido = Recorrido::orderBy('fecha_gps', 'desc')->where('tipo', 
            		'GTFRI')->where('unidad_id', new ObjectID($id))->take(3)->get();
            $unidad["recorridos"] = $recorrido;
        }

        return response()->json($unidad);
    }

    public function update(Request $request, $id)
    {
        $unidad = Unidad::findOrFail($id);

        if($request->input('imei')==$unidad->imei)
            $validator = Validator::make($request->all(), [
                'imei' => 'required',
                'placa' => 'required',
                'descripcion' => 'required|max:255',
                'cooperativa_id' => 'required',
                'marca' => 'required|max:100',
                'modelo' => 'required|max:100',
                'serie' => 'required|max:50',
                'motor' => 'required|max:50',
                'tipo_unidad_id' => 'required',
                'email_alarma' => 'required|email',
                'velocidad' => 'nullable|numeric'
            ]);
        else
            $validator = Validator::make($request->all(), [
                'imei' => 'required|unique:unidads',
                'placa' => 'required',
                'descripcion' => 'required|max:255',
                'cooperativa_id' => 'required',
                'marca' => 'required|max:100',
                'modelo' => 'required|max:100',
                'serie' => 'required|max:50',
                'motor' => 'required|max:50',
                'tipo_unidad_id' => 'required',
                'email_alarma' => 'required|email',
                'velocidad' => 'nullable|numeric'
            ]);

        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $unidad->imei = $request->input('imei');
            $unidad->placa = $request->input('placa');
            $unidad->descripcion = $request->input('descripcion');
            $unidad->cooperativa_id = $request->input('cooperativa_id');
            $unidad->tipo_unidad_id = $request->input('tipo_unidad_id');
            $unidad->marca = $request->input('marca');
            $unidad->modelo = $request->input('modelo');
            $unidad->serie = $request->input('serie');
            $unidad->motor = $request->input('motor');
            $unidad->email_alarma = $request->input('email_alarma');
            $unidad->sistema_energizado = ((($request->input('sistema_energizado'))=="true")?true:false);
            $unidad->contador_cero_manual = ((($request->input('contador_cero_manual'))=="true")?true:false);
            $unidad->desconexion_sistema = ((($request->input('desconexion_sistema'))=="true")?true:false);
            $unidad->modificador_id = Auth::user()->_id;
            $unidad->control_velocidad= ((($request->input('control_velocidad'))=="true")?true:false);
            $unidad->climatizada= ((($request->input('climatizada'))=="true")?true:false);
            $unidad->rampa= ((($request->input('rampa'))=="true")?true:false);
            $unidad->atm = ($request->input('atm') == 'true')?'S':'N';
            $unidad->velocidad = $request->input('velocidad');
            $unidad->save();
            return response()->json(['error' => false, 'unidad' => $unidad]);
        }
    }

    public function destroy($id)
    {
        $unidad = Unidad::findOrFail($id);
        if($unidad->estado=="A")
           $unidad->estado="I";
        else
            $unidad->estado="A";

        $unidad->save();
        return response()->json($unidad);
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'estado' => 'required|max:1'
        ]);
        $user = $request->user();
        $unidades = Unidad::permitida($request->input('cooperativa'))->where(function ($query) use ($request) {
            $search = $request->input('search');
            if ($search != null && $search != ''){
                $query->where('descripcion', 'like', "%$search%")
                ->orWhere('placa', 'like', "%$search%")
                ->orWhere('marca', 'like', "%$search%")
                ->orWhere('modelo', 'like', "%$search%")
                ->orWhere('motor', 'like', "%$search%")
                ->orWhere('imei', 'like', "%$search%");
            }
        })->orderBy('placa', 'asc');
        if ($request->input('estado') != 'T')
            $unidades->where('estado', $request->input('estado'));
        if ($request->input('exportar') != null)
        {
            $filename = 'unidades-' . date('Y-m-d-His');
            Excel::create($filename, function ($excel) use ($unidades) {
                $excel->sheet('Lista de unidades', function ($sheet) use ($unidades){
                    $sheet->loadView('panel.unidades.excel-unidades', [
                        'unidades' => $unidades->get()
                    ]);
                });
                $excel->download();
            });
        }
        else
        {
            $unidades = $unidades->paginate(10);
            $unidades->setPath($request->fullUrl());
            return view('panel.lista-unidades', [
                'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->get(),
                'unidades' => $unidades,
                'tipos_unidades'=>TipoUnidad::orderBy('descripcion', 'asc')->where('estado','A')->get(),
                'tipo_usuario_valor' => $request->user()->tipo_usuario->valor,
                'coop' => $request->input('cooperativa'),
                'search' => $request->input('search'),
                'estado' => $request->input('estado'),
                'id_cooperativa' => $user->cooperativa_id
            ]);
        }
    }

    public function getHistorico()
    {
        return view('panel.reportes-unidades');
    }

    public function resetConteo(Request $request){
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1)
        {
            $uId=$request->input('unidad_id');
           $unidad=Unidad::findOrFail($uId);
           $unidad->contador_inicial=0;
           $unidad->contador_total=0;
           $unidad->contador_diario=0;

           $unidad->save();

        }

        return response()->json($user); 
    }
}
