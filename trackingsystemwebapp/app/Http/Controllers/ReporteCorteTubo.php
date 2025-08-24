<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use App\Recorrido;
use App\Despacho;
use App\Unidad;
use App\Ruta;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Excel;

class ReporteCorteTubo extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        set_time_limit(0);
        $search = $request->input('search');
        $exportar = $request->input('exportar');
        if (isset($search) || isset($exportar)) {
            $this->validate($request, [
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date',
                'unidad' => 'required|array'
            ]);
            $unidad = $request->input('unidad');
            $desde = $request->input('fecha_desde');
            $hasta = $request->input('fecha_hasta');
            $fechaDesde = new Carbon($desde);
            $fechaHasta = new Carbon($hasta);
            
            date_sub($fechaDesde, date_interval_create_from_date_string('5 hours'));
            date_sub($fechaHasta, date_interval_create_from_date_string('5 hours'));
            for($i = 0; $i < count($unidad); $i++){
                $unidad[$i] =$unidad[$i];
            }

            $despachos = Despacho::orderBy('fecha', 'desc')->whereIn('unidad_id', $unidad)->where('fecha', '>=', $fechaDesde)->where('fecha', '<=', $fechaHasta)
            ->where('corte_tubo','=','Si');

            if (isset($search)) {
                $despachos = $despachos->paginate(50);
                $despachos->setPath($request->fullUrl());
                return back()->withInput()->with('recorridos', $despachos);
            }
            else 
            {
                $arrayUbicaciones = array();
                $despachos = $despachos->get();

                foreach ($despachos as $documento) {
                    $ubicacion= '';
                    if(isset($documento["latitud_corte"]) && isset($documento["longitud_corte"])){
                        if($documento["latitud_corte"] != null && $documento["longitud_corte"] != null){
                            $client = new Client();
                            $url='https://maps.googleapis.com/maps/api/geocode/json?latlng='.$documento["latitud_corte"]. ",".$documento["longitud_corte"].'&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE';
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

                            array_push($arrayUbicaciones,(Object)['Ubicacion'=>$ubicacion,
                            'despacho'=>$documento]);
                        }
                    }
                }

                return Excel::create('Reporte de Corte Tubo ' . date('YmdHis'), function ($excel) use ($arrayUbicaciones){
                    $excel->sheet('Reporte', function ($sheet) use ($arrayUbicaciones) {
                        $sheet->loadView('panel.reportes.cortetubo-excel', [
                            'recorridos'=>$arrayUbicaciones
                        ]);
                    });
                })->download();
            }
        }
        else 
        {
            $cooperativas = Cooperativa::orderBy('descripcion', 
            'asc')->activa()->permitida()->get();
            return view('panel.reportes.cortetubo', ['cooperativas' => $cooperativas]);
        }
    }

}
