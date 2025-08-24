<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use App\Recorrido;
use App\Unidad;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Excel;
class ReportePuertasController extends Controller
{
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
            date_sub($fechaDesde, date_interval_create_from_date_string('10 hours'));
            date_sub($fechaHasta, date_interval_create_from_date_string('10 hours'));
            for($i = 0; $i < count($unidad); $i++)
                $unidad[$i] = new ObjectID($unidad[$i]);
            $recorridos = Recorrido::where('tipo', 'GTDIS')->orderBy('fecha_gps', 'desc')->whereIn('unidad_id', $unidad)->where('fecha_gps', 
            '>=', $fechaDesde)->where('fecha_gps', '<=', $fechaHasta);
            $listaRecorridos = $recorridos->get();
            $unidades = Unidad::whereIn('_id', $unidad)->get();
            $reportes = array();
            $arrayRecorridos = array();
            foreach ($unidades as $u) {
                $mAbiertas = 0;
                $mCerradas = 0;
                $cantidadAbiertas = 0;
                $cantidadCerradas = 0;
                $puertaAbierta = false;
                $puertaCerrada = false;
                $ultimaFecha = null;
                
                foreach ($listaRecorridos as $key => $recorrido) {
                    if ($recorrido->unidad_id == $u->_id) {
                        if ($ultimaFecha === null) 
                            $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                        if ('Puerta abierta' === $recorrido->evento) {
                            if ($puertaCerrada) {
                                $f = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                $tiempo = round($ultimaFecha->diffInSeconds($f) / 60, 2);
                                $mCerradas+= $tiempo;
                                $tf = $ultimaFecha->diff($f)->format('%h:%i:%s');
                                array_push($arrayRecorridos, ['recorrido' => $recorrido, 'tiempo' => $tf, 'desde' => $f, 'hasta' => $ultimaFecha]);
                                $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                            }
                            $cantidadAbiertas++;
                            $puertaAbierta = true;
                            $puertaCerrada = false;
                        }
                        else {
                            if ($puertaAbierta) {
                                $f = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                $tiempo = round($ultimaFecha->diffInSeconds($f) / 60, 2);
                                $mAbiertas+= $tiempo;
                                $tf = $ultimaFecha->diff($f)->format('%h:%i:%s');
                                array_push($arrayRecorridos, ['recorrido' => $recorrido, 'tiempo' => $tf, 'desde' => $f, 'hasta' => $ultimaFecha]);
                                $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                            }
                            $puertaCerrada = true;
                            $puertaAbierta = false;
                            $cantidadCerradas++; 
                        }
                    }
                }
                array_push($reportes, ['unidad' => $u, 'cantidadAbiertas' => $cantidadAbiertas,
                    'cantidadCerradas' => $cantidadCerradas, 'mAbiertas' => $mAbiertas,
                    'mCerradas' => $mCerradas]);
            }
            if (isset($search)) {
                $recorridos = $recorridos->paginate(50);
                $recorridos->setPath($request->fullUrl());
                return back()->withInput()->with('recorridos', $arrayRecorridos)->with('reportes', 
                $reportes);
            }
            else 
            {

                $reportes = array();
                $arrayRecorridos = array();
                foreach ($unidades as $u) {
                    $mAbiertas = 0;
                    $mCerradas = 0;
                    $cantidadAbiertas = 0;
                    $cantidadCerradas = 0;
                    $puertaAbierta = false;
                    $puertaCerrada = false;
                    $ultimaFecha = null;
                    
                    foreach ($listaRecorridos as $key => $recorrido) {
                        if ($recorrido->unidad_id == $u->_id) {
                            if ($ultimaFecha === null) 
                                $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                
                                $ubicacion= '';
                                if(isset($recorrido->latitud) && isset($recorrido->longitud)){
                                    if($recorrido->latitud != null && $recorrido->longitud != null){
                                        $client = new Client();
                                        $url='https://maps.googleapis.com/maps/api/geocode/json?latlng='.$recorrido->latitud. ",".$recorrido->longitud.'&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE';
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

                            if ('Puerta abierta' === $recorrido->evento) {
                                if ($puertaCerrada) {
                                    $f = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                    $tiempo = round($ultimaFecha->diffInSeconds($f) / 60, 2);
                                    $mCerradas+= $tiempo;
                                    $tf = $ultimaFecha->diff($f)->format('%h:%i:%s');
                                    array_push($arrayRecorridos, ['recorrido' => $recorrido, 'tiempo' => $tf, 'desde' => $f, 'hasta' => $ultimaFecha,
                                    'ubicacion'=>$ubicacion]);
                                    $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                }
                                $cantidadAbiertas++;
                                $puertaAbierta = true;
                                $puertaCerrada = false;
                            }
                            else {
                                if ($puertaAbierta) {
                                    $f = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                    $tiempo = round($ultimaFecha->diffInSeconds($f) / 60, 2);
                                    $mAbiertas+= $tiempo;
                                    $tf = $ultimaFecha->diff($f)->format('%h:%i:%s');
                                    array_push($arrayRecorridos, ['recorrido' => $recorrido, 'tiempo' => $tf, 'desde' => $f, 'hasta' => $ultimaFecha,
                                    'ubicacion'=>$ubicacion]);
                                    $ultimaFecha = new Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s'));
                                }
                                $puertaCerrada = true;
                                $puertaAbierta = false;
                                $cantidadCerradas++; 
                            }                            

                        }
                    }
                    array_push($reportes, ['unidad' => $u, 'cantidadAbiertas' => $cantidadAbiertas,
                        'cantidadCerradas' => $cantidadCerradas, 'mAbiertas' => $mAbiertas,
                        'mCerradas' => $mCerradas]);
                }

                return Excel::create('Reporte de puertas ' . date('YmdHis'), function ($excel) use ($arrayRecorridos, $reportes){
                    $excel->sheet('Reporte', function ($sheet) use ($arrayRecorridos, $reportes) {
                        $sheet->loadView('panel.reportes.puertas-excel', [
                            'recorridos' => $arrayRecorridos, 'reportes' => $reportes
                        ]);
                    });
                })->download();
            }
        }
        else 
        {
            $cooperativas = Cooperativa::orderBy('descripcion', 
            'asc')->activa()->permitida()->get();
            return view('panel.reportes.puertas', ['cooperativas' => $cooperativas]);
        }
    }
}
