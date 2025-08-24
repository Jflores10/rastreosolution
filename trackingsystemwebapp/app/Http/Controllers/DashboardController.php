<?php

namespace App\Http\Controllers;
use App\TipoUsuario;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Response;
use Carbon\Carbon;
use App\Cooperativa;
use App\Recorrido;
use MongoDB\BSON\ObjectID;
use App\Unidad;
use App\User;
use App\Despacho;
use Auth;

class DashboardController extends Controller
{
    public function index()
    {      
        set_time_limit(0);  
        if(Auth::user()->tipo_usuario->valor=='1')
        {
            $cooperativas = Cooperativa::where('estado','A')->get();
            return view('dashboards.dashboard',
            [
                'cooperativas' => $cooperativas
            ]);
        }
        else
        {            
            $id_cooperativa = Auth::user()->cooperativa_id;
            $cooperativa = Cooperativa::findOrFail($id_cooperativa)->first();
            $datos = $this->estadisticasGeneralesPorCooperativa($id_cooperativa);
            $unidades = Unidad::orderBy('placa')->where('cooperativa_id',$id_cooperativa)->get();
            $velocidad_maxima = $cooperativa->velocidad_max;
            return view('dashboards.dashboard',
            [
                'cooperativa' => $cooperativa->_id,
                'datos' => $datos[0],
                'velocidades' => $datos[1] ,
                'unidades' => $unidades,
                'velocidad_maxima' => $velocidad_maxima
            ]);            
        }            
    }
    
    public function recargarPorCooperativa(Request $request, $id)
    {       
        set_time_limit(0); 
        $cooperativas = Cooperativa::where('estado','A')->get();
        $datos = $this->estadisticasGeneralesPorCooperativa($id);
        $unidades = Unidad::orderBy('placa')->where('cooperativa_id',$id)->get();
        $cooperativa = Cooperativa::findOrFail($id);
        return view('dashboards.dashboard',
        [
            'cooperativas' => $cooperativas,
            'cooperativa' => $id,
            'datos' => $datos[0],
            'velocidades' => $datos[1] ,
            'unidades' => $unidades,
            'velocidad_maxima' => $cooperativa->velocidad_max  //(borrar el 50 y descomentar lo demas)
        ]);
    }
    public function recargarVelocidadUnidad(Request $request)
    {
        set_time_limit(0);  
        $desde = new Carbon($request->input('fecha_inicio'));
        $hasta = new Carbon($request->input('fecha_fin'));
        date_add($desde, date_interval_create_from_date_string('5 hours'));
        date_add($hasta, date_interval_create_from_date_string('5 hours'));
        $cooperativa = Cooperativa::findOrFail($request->input('cooperativa'));
        $velocidadMaxima = $cooperativa->velocidad_max;
        $unidad = Unidad::findOrFail($request->input('unidad'));
        $datos = [];
        //$velocidadMaxima = 50; //comentar esta linea------------------------------------------------------>  
        $recorridos =  Recorrido::where('fecha_gps', '>=', $desde)
            ->where('fecha_gps', '<=', $hasta)
            ->where('tipo', 'GTFRI')
            ->where('unidad_id',new ObjectID($unidad->_id))
            ->where('velocidad','>',(float) $velocidadMaxima)
            ->select('velocidad')
            ->get();  
        if(($velocidadMaxima != null) && ($velocidadMaxima > 0))
        {
            $lblVelocidad1 = $velocidadMaxima ." - ".($velocidadMaxima+20);
            $lblVelocidad2 = ($velocidadMaxima+20) ." - ".($velocidadMaxima+40);
            $lblVelocidad3 = ($velocidadMaxima+40) ." - ".($velocidadMaxima+60);
            $lblVelocidad4 = ($velocidadMaxima+60) ." - ".($velocidadMaxima+80);
            $lblVelocidad5 = ($velocidadMaxima+80) ." - ".($velocidadMaxima+100);
            $lblVelocidad6 = ">".($velocidadMaxima+100);
            $cont1 = 0;
            $cont2 = 0;
            $cont3 = 0;
            $cont4 = 0;
            $cont5 = 0;
            $cont6 = 0;
            foreach($recorridos as $recorrido)
            {   
                $velocidad = $recorrido->velocidad;
                switch($velocidad)
                {
                    case ($velocidad>$velocidadMaxima && $velocidad<($velocidadMaxima+20)):
                        $cont1++; break;
                    case($velocidad>=($velocidadMaxima+20) && $velocidad<($velocidadMaxima+40)):
                        $cont2++; break;
                    case($velocidad>=($velocidadMaxima+40) && $velocidad<($velocidadMaxima+60)):
                        $cont3++; break;
                    case($velocidad>=($velocidadMaxima+60) && $velocidad<($velocidadMaxima+80)):
                        $cont4++; break;
                    case($velocidad>=($velocidadMaxima+80) && $velocidad<($velocidadMaxima+100)):
                        $cont5++; break;
                    case($velocidad>=($velocidadMaxima+100)):
                        $cont6++; break;
                    default:break;
                }     
            }
            array_push($datos,[
                'label1' => $lblVelocidad1,
                'label2' => $lblVelocidad2,
                'label3' => $lblVelocidad3,
                'label4' => $lblVelocidad4,
                'label5' => $lblVelocidad5,
                'label6' => $lblVelocidad6,
                'velocidad1' => $cont1,
                'velocidad2' => $cont2,
                'velocidad3' => $cont3,
                'velocidad4' => $cont4,
                'velocidad5' => $cont5,
                'velocidad6' => $cont6
            ]); 
        }      
        return response()->json([
            'error' => false,
            'velocidades' => $datos
        ]);  
    }

    public function recargarVelocidadGeneral(Request $request)
    {
        set_time_limit(0);  
        $desde = new Carbon($request->input('fecha_inicio'));
        $hasta = new Carbon($request->input('fecha_fin'));
        $cooperativa_id = $request->input('cooperativa');
        $cooperativa = Cooperativa::findOrFail($cooperativa_id);
        $velocidadMaxima = $cooperativa->velocidad_max;
        date_add($desde, date_interval_create_from_date_string('5 hours'));
        date_add($hasta, date_interval_create_from_date_string('5 hours'));
        $array_velocidad = [];
        $array_unidades = [];
        $unidades = Unidad::where('cooperativa_id',$cooperativa_id)->orderBy('placa')->get();
        foreach($unidades as $unidad)
            array_push($array_unidades,new ObjectID($unidad->_id));  
        //$velocidadMaxima = 50; //comentar esta linea------------------------------------------------------>  
        $recorridos =  Recorrido::where('fecha_gps', '>=', $desde)
            ->where('fecha_gps', '<=', $hasta)
            ->where('tipo', 'GTFRI')
            ->whereIn('unidad_id',$array_unidades)
            ->where('velocidad','>',(float) $velocidadMaxima)
            >select('imei')
            ->get();   
        foreach($unidades as $unidad)
        {        
            $cantidadDeExcesos = 0;  
            if(($velocidadMaxima != null) && ($velocidadMaxima > 0))
            {
                foreach($recorridos as $recorrido)
                {
                    if($recorrido->imei == $unidad->imei)
                        $cantidadDeExcesos++;
                }
            }
            array_push($array_velocidad,["unidad"=>$unidad->placa,"cantidad_exceso"=>$cantidadDeExcesos]);    
        }
        return response()->json([
                'error' => false,
                'velocidades' => $array_velocidad
            ]);
    }

    protected function estadisticasGeneralesPorCooperativa($id)
    {     
        set_time_limit(0);   
        $unidades = Unidad::where('cooperativa_id',$id)->orderBy('placa')->get();
        $cooperativa = Cooperativa::findOrFail($id);
        $velocidadMaxima = $cooperativa->velocidad_max;
        $usuarios = User::where('cooperativa_id',$id)->get();
        $contUnidadesAtm = 0;
        $contUnidadesServidor = 0;
        $contUnidadesActivas = 0;
        $contUnidadesInactivas = 0;
        $contUsuariosActivos = 0;
        $contUsuariosInactivos = 0;
        $contUsuariosRol1= 0;
        $contUsuariosRol2= 0;
        $contUsuariosRol3= 0;
        $contUsuariosRol4= 0;
        $contDespachosPendientes = 0;
        $contDespachosCulminados = 0;
        $contDespachosInactivos = 0;
        $contCorteTuboSi = 0;
        $contCorteTuboNo = 0;
        $contEstadoExportacionSi = 0;
        $contEstadoExportacionNo = 0; 
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_add($desde, date_interval_create_from_date_string('5 hours'));
        date_add($hasta, date_interval_create_from_date_string('5 hours'));
        $desde_desp = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta_desp = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_sub($desde_desp, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta_desp, date_interval_create_from_date_string('5 hours'));

        $array = [];
        $array_velocidad = [];
        $array_return = [];
        $array_unidades = [];
        //$velocidadMaxima = 50; //comentar esta linea------------------------------------------------------>
        $recorridos = null;
        //$velocidadMaxima=null;
        if(($velocidadMaxima != null) && ($velocidadMaxima > 0))
        {
            foreach($unidades as $unidad)
                array_push($array_unidades, new ObjectID($unidad->_id)); 
            try{
                $recorridos =  Recorrido::where('fecha_gps', '>=', $desde)
                ->where('fecha_gps', '<=', $hasta)
                ->where('tipo', 'GTFRI')
                ->whereIn('unidad_id',$array_unidades)
                ->where('velocidad','>',(float) $velocidadMaxima)
                ->select('imei')
                ->get();   
            } catch(\Exception $ex)
            {
                $recorridos = null;
            }
           
        }        
        foreach($unidades as $unidad)
        {
            $despachos = $unidad->despachos()->where('fecha', '>=', $desde_desp)->where('fecha', '<=', $hasta_desp)->select('estado_exportacion','corte_tubo','estado')->get();
            foreach($despachos as $despacho)
            {
                switch($despacho->estado)
                {
                    case "P": $contDespachosPendientes++; break;
                    case "I": $contDespachosInactivos++; break;
                    case "C": $contDespachosCulminados++; break;
                    default:break;
                }
                if($despacho->corte_tubo != null)
                {
                    if($despacho->corte_tubo == "Si")
                        $contCorteTuboSi++;
                    if($despacho->corte_tubo == "No")
                        $contCorteTuboNo++;
                }
                if($despacho->estado_exportacion == "E")
                    $contEstadoExportacionSi++;
                if($despacho->estado_exportacion == "A" || $despacho->estado_exportacion == "P")
                    $contEstadoExportacionNo++;
            }
            if($unidad->is_atm == 0 || $unidad->is_atm == null)
                $contUnidadesServidor++;
            else
                $contUnidadesAtm++;
            if($unidad->estado == "A")
                $contUnidadesActivas++;
            else
                $contUnidadesInactivas++;                
            $cantidadDeExcesos = 0;
            if(($velocidadMaxima != null) && ($velocidadMaxima > 0) && ($recorridos != null))
            {
                foreach($recorridos as $recorrido)
                {
                    if($recorrido->imei == $unidad->imei)
                        $cantidadDeExcesos++;
                }
            }
            array_push($array_velocidad,["unidad"=>$unidad->placa,"cantidad_exceso"=>$cantidadDeExcesos]);        
        }
        foreach($usuarios as $usuario)
        {
            $tipo_usuario = $usuario->tipo_usuario->valor;
            switch($tipo_usuario)
            {
                case "1":$contUsuariosRol1++;break;
                case "2":
                    if($usuario->cooperativa_id == $id)
                        $contUsuariosRol2++;
                    break;
                case "3":
                    if($usuario->cooperativa_id == $id)
                        $contUsuariosRol3++;
                    break;
                case "4":
                    if($usuario->cooperativa_id == $id)
                        $contUsuariosRol4++;
                    break;
                default:break;
                    
            }
            if($usuario->estado == "A")
                $contUsuariosActivos++;
            else
                $contUsuariosInactivos++;
        }        
        array_push($array,[
            "unidades_atm" => $contUnidadesAtm,
            "unidades_servidor" => $contUnidadesServidor,
            "unidades_activas" => $contUnidadesActivas,
            "unidades_inactivas" => $contUnidadesInactivas,
            "usuarios_activos" => $contUsuariosActivos,
            "usuarios_inactivos" => $contUsuariosInactivos,
            "usuarios_rol1" => $contUsuariosRol1,
            "usuarios_rol2" => $contUsuariosRol2,
            "usuarios_rol3" => $contUsuariosRol3,
            "usuarios_rol4" => $contUsuariosRol4,
            "corte_tubo_si" => $contCorteTuboSi,
            "corte_tubo_no" => $contCorteTuboNo,
            "despachos_pendientes" => $contDespachosPendientes,
            "despachos_culminados" => $contDespachosCulminados,
            "despachos_inactivos" => $contDespachosInactivos,
            "exportacion_si" => $contEstadoExportacionSi,
            "exportacion_no" => $contEstadoExportacionNo
        ]);     
        array_push($array_return, $array);
        array_push($array_return, $array_velocidad);
        return $array_return;
    }

    protected function recargarDespachosEstados(Request $request)
    {
        set_time_limit(0);
        $desde = new Carbon($request->input('fecha_inicio'));
        $hasta = new Carbon($request->input('fecha_fin'));
        $cooperativa_id = $request->input('cooperativa');
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours')); 
        $contDespachosPendientes = 0;
        $contDespachosCulminados = 0;
        $contDespachosInactivos = 0;
        $unidades = Unidad::where('cooperativa_id',$request->input('cooperativa_id'))->get();
        foreach($unidades as $unidad)
        {
            $despachos = $unidad->despachos()->where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->get();
            foreach($despachos as $despacho)
            {
                switch($despacho->estado)
                {
                    case "P": $contDespachosPendientes++; break;
                    case "I": $contDespachosInactivos++; break;
                    case "C": $contDespachosCulminados++; break;
                    default:break;
                }
            }
        }
        return response()->json([
                'error' => false, 
                'despachos_pendientes' => $contDespachosPendientes,
                'despachos_culminados' => $contDespachosCulminados,
                'despachos_inactivos' => $contDespachosInactivos
            ]);
        
    }
    protected function recargarExportacionDespachos(Request $request)
    {     
        set_time_limit(0);  
        $desde = new Carbon($request->input('fecha_inicio'));
        $hasta = new Carbon($request->input('fecha_fin'));
        $cooperativa_id = $request->input('cooperativa');
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));
        $contEstadoExportacionSi = 0;
        $contEstadoExportacionNo = 0;
        $unidades = Unidad::where('cooperativa_id',$request->input('cooperativa_id'))->get();
        foreach($unidades as $unidad)
        {
            $despachos = $unidad->despachos()->where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->get();
            foreach($despachos as $despacho)
            {
                if($despacho->estado_exportacion == "E")
                    $contEstadoExportacionSi++;
                if($despacho->estado_exportacion == "A" || $despacho->estado_exportacion == "P")
                    $contEstadoExportacionNo++;
            }
        }
        return response()->json([
                'error' => false, 
                'exportados_si' => $contEstadoExportacionSi,
                'exportados_no' => $contEstadoExportacionNo
            ]);         
    }
    protected function recargarCortesTubo(Request $request)
    {
        set_time_limit(0);
        $desde = new Carbon($request->input('fecha_inicio'));
        $hasta = new Carbon($request->input('fecha_fin'));
        $cooperativa_id = $request->input('cooperativa');
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours')); 
        $contCorteTuboSi = 0;
        $contCorteTuboNo = 0;
        $unidades = Unidad::where('cooperativa_id',$request->input('cooperativa_id'))->get();
        foreach($unidades as $unidad)
        {
            $despachos = $unidad->despachos()->where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->get();
            foreach($despachos as $despacho)
            {
                if($despacho->corte_tubo != null)
                {
                    if($despacho->corte_tubo == "Si")
                        $contCorteTuboSi++;
                    if($despacho->corte_tubo == "No")
                        $contCorteTuboNo++;
                }
            }
        }
        return response()->json([
                'error' => false, 
                'corte_tubo_si' => $contCorteTuboSi,
                'corte_tubo_no' => $contCorteTuboNo
            ]);        
    }
    
}