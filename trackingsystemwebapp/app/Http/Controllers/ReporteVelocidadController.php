<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\Recorrido;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use Excel;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
class ReporteVelocidadController extends Controller
{
    public function index(Request $request) 
    {
        set_time_limit(0);
        $search = $request->input('search');
        $exportar = $request->input('exportar');
        if (isset($search) || isset($exportar)) {
            $this->validate($request, [
                'desde' => 'required|numeric',
                'hasta' => 'required|numeric',
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date',
                'unidad' => 'required|array'
            ]);
            $unidad = $request->input('unidad');
            $desde = $request->input('fecha_desde');
            $hasta = $request->input('fecha_hasta');
            $fechaDesde = new Carbon($desde);
            $fechaHasta = new Carbon($hasta);
            date_add($fechaDesde, date_interval_create_from_date_string('10 hours'));
            date_add($fechaHasta, date_interval_create_from_date_string('10 hours'));

            $d = floatval($request->input('desde'));
            $h = floatval($request->input('hasta'));
            for($i = 0; $i < count($unidad); $i++)
                $unidad[$i] = new ObjectID($unidad[$i]);
            $recorridos = Recorrido::orderBy('fecha_gps', 'desc')->whereIn('unidad_id', $unidad)->where('fecha_gps', 
            '>=', $fechaDesde)->where('fecha_gps', '<=', 
            $fechaHasta)->whereBetween('velocidad', [$d,
            $h]);

            $recorridos_count=0;
            $recorridos_count = Recorrido::orderBy('fecha_gps', 'desc')->whereIn('unidad_id', $unidad)->where('fecha_gps', 
            '>=', $fechaDesde)->where('fecha_gps', '<=', 
            $fechaHasta)->where('velocidad','>' , $h)->count();

            if (isset($search)) {
                $recorridos = $recorridos->paginate(50);
                $recorridos->setPath($request->fullUrl());
                return back()->withInput()->with('recorridos', $recorridos)->with('recorridos_count',$recorridos_count);
            }
            else 
            {

                $arrayUbicaciones = array();
                $recorridos = $recorridos->get();

                foreach ($recorridos as $documento) {
                    $ubicacion= '';
                    if(isset($documento["latitud"]) && isset($documento["longitud"])){
                        if($documento["latitud"] != null && $documento["longitud"] != null){
                            $client = new Client();
                            $url='https://maps.googleapis.com/maps/api/geocode/json?latlng='.$documento["latitud"]. ",".$documento["longitud"].'&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE';
                            // $res = $client->get($url);
                            
                            $res = $client->request('GET',$url );
        
                            //if($res->status)
                            $json =json_decode($res->getBody()->getContents());
                            $index=0;
        
                            if($json->status=="OK"){
                                foreach($json->results as $item)
                                {
                                    if($index ==1)
                                    {
                                        $ubicacion=$item->formatted_address;
                                    }
                                    $index ++;
                                }
                            }else{
                                $ubicacion='Error al traer ubicación';
                            }
                            
                        }
                    }
                    array_push($arrayUbicaciones,(Object)['Ubicacion'=>$ubicacion,
                            'recorrido'=>$documento]);
                }


                return Excel::create('Reporte de velocidad ' . date('YmdHis'), function ($excel) use ($arrayUbicaciones){
                    $excel->sheet('Reporte', function ($sheet) use ($arrayUbicaciones) {
                        $sheet->loadView('panel.reportes.velocidad-excel', [
                            'recorridos' => $arrayUbicaciones
                        ]);
                    });
                })->download();
            }
        }
        else 
        {
            $cooperativas = Cooperativa::orderBy('descripcion', 
            'asc')->activa()->permitida()->get();
            return view('panel.reportes.velocidad', ['cooperativas' => $cooperativas]);
        }
    }
}
