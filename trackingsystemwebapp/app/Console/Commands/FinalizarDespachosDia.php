<?php

namespace App\Console\Commands;

use App\Despacho;
use App\PuntoControl;
use App\Recorrido;
use App\Ruta;
use Carbon\Carbon;
use App\LOGATMDESPACHOS;
use Illuminate\Console\Command;
use MongoDB\BSON\ObjectID;
use App\SchedulerLog;
use MongoDB\BSON\UTCDateTime;


class FinalizarDespachosDia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:despacho';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finalizacion de despachos del dia';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        try {

            $hoy_desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
            $hoy_hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
            date_sub($hoy_desde, date_interval_create_from_date_string('5 hours'));
            date_sub($hoy_hasta, date_interval_create_from_date_string('5 hours'));

            /* $hoy_desde = Carbon::today();
            date_sub($hoy_desde, date_interval_create_from_date_string('5 hours'));
            $hoy_hasta = Carbon::tomorrow()->subSecond();
            date_sub($hoy_hasta, date_interval_create_from_date_string('5 hours'));*/

            $despachos = Despacho::with('ruta')->where('estado', 'P')->where('fecha', '>=', $hoy_desde)
                ->where('fecha', '<=', $hoy_hasta)->get();
            $this->info('Despachos a procesar ' . $despachos->count());
            foreach ($despachos as $despacho) {

                /***************ALGORITMO FIN DESPACHO*************/

                $cooperativa = $despacho->unidad->cooperativa->_id;
                $despacho_tmp = $despacho;
                $cooperativa_cortetubo = $despacho->unidad->cooperativa->multa_tubo;
                $fin = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
                $ini = $despacho->fecha; //fecha de inicio del despacho (salida del bus)
                $fin_corte = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime(); //Tiempo de finalizacion esperada
                $ini_corta = $despacho->fecha;

                $total_puntoscontrol = count($despacho->puntos_control);
                $_total_pc = 1;
                //$this->info('P1 ');
                date_add($ini, date_interval_create_from_date_string('570 minutes')); // Agrego 10 horas a la consulta de fecha de GPS inicial
                date_add($fin, date_interval_create_from_date_string('12 hours')); // Agrego 10 horas a la fecha de fin, fecha de culminacion
                date_add($ini_corta, date_interval_create_from_date_string('10 hours')); // Agrego 10 horas a la consulta de fecha de GPS inicial
                date_add($fin_corte, date_interval_create_from_date_string('10 hours'));

                $fini = new UTCDateTime(($ini->getTimestamp() * 1000)); //Inicio
                $ffin = new UTCDateTime(($fin->getTimestamp() * 1000)); //Fin
                $fini_corte = new UTCDateTime(($ini_corta->getTimestamp() * 1000)); //Inicio
                $ffin_corte = new UTCDateTime(($fin_corte->getTimestamp() * 1000)); //Fin

                $recorridos = Recorrido::orderBy('fecha_gps', 'asc')
                    ->where('tipo', 'GTGEO')
                    ->where('unidad_id', new ObjectID($despacho->unidad_id))
                    ->where('fecha_gps', '>=', $fini)
                    ->where('fecha_gps', '<=', $ffin)->get(); //Recorridos entre el inicio del despacho y el final esperado del despacho

                $array_final = array();
                $array_temp = array();
                $multa = 0.0;
                $ultimo_punto = null;
                $index = 0;
                $primerpunto = true;
                //$this->info('P2 ');
                if (sizeof($recorridos) > 0) {
                    foreach ($despacho->puntos_control as $punto_control) {
                        $puntoControlEsperado = null;
                        $intervalo = null;
                        $item = [
                            "id" => $punto_control["id"], "tiempo_esperado" =>
                            $punto_control["tiempo_esperado"], "adelanto" => $punto_control["adelanto"],
                            "atraso" => $punto_control["atraso"], "marca" => null, 'contador_marca' => null,
                            'tiempo_atraso' => null, 'tiempo_adelanto' => null, 'intervalo' => null
                        ];

                        $puntoControlObj = PuntoControl::find($punto_control['id']);
                        if (isset($puntoControlObj)) {
                            $tiempoEsperado = $punto_control['tiempo_esperado']->toDateTime();
                            $consulta_1 = $punto_control['tiempo_esperado']->toDateTime();
                            date_add($tiempoEsperado, date_interval_create_from_date_string('10 hours'));
                            date_add($consulta_1, date_interval_create_from_date_string('10 hours'));

                            if ($index == 0) {
                                $consulta = $punto_control['tiempo_esperado']->toDateTime();
                                date_add($consulta, date_interval_create_from_date_string('10 hours'));
                            }

                            if ($primerpunto) {
                                $puntoControlEsperado_inicio = Recorrido::orderBy('fecha_gps', 'desc')->where(
                                    'tipo',
                                    'GTGEO'
                                )->where('fecha_gps', '>=', $fini)
                                    ->where('fecha_gps', '<=', $consulta_1)
                                    ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                    /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                                    ->where('pdi', (int) $puntoControlObj->pdi)->first();



                                if (isset($puntoControlEsperado_inicio)) {
                                    $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                                    $fechaLinea_ = $fechaGPS_->format('H:i');
                                    $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                    $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                                    $puntoControlEsperado_salida = Recorrido::orderBy('fecha_gps')->where(
                                        'tipo',
                                        'GTGEO'
                                    )->where('fecha_gps', '<=', $ffin)
                                        ->where('fecha_gps', '>=', $consulta)
                                        ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                        ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                    if (isset($puntoControlEsperado_salida)) {
                                        $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                        $fechaLinea = $fechaGPS->format('H:i');
                                        $diff = $tiempoEsperado->diff($fechaGPS);
                                        $intervalo = $diff->format('%h:%i:%s');
                                        $distancia = (($diff->i) + (($diff->h) * 60));
                                        if ($distancia > 35) {
                                            $puntoControlEsperado = $puntoControlEsperado_inicio;
                                        } else {
                                            if ($distancia_ < 20) {
                                                $puntoControlEsperado = $puntoControlEsperado_inicio;
                                            } else {
                                                $puntoControlEsperado = null;
                                            }
                                        }
                                    } else {
                                        $puntoControlEsperado = $puntoControlEsperado_inicio;
                                    }
                                } else {
                                    $puntoControlEsperado = null;
                                }
                            } else {
                                $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                                    'tipo',
                                    'GTGEO'
                                )->where('fecha_gps', '>=', $fini)
                                    ->where('fecha_gps', '<=', $tiempoEsperado)
                                    ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                    /****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                                    ->where('pdi', (int) $puntoControlObj->pdi)->first();
                            }

                            if (isset($puntoControlEsperado)) {
                                $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                                $fechaLinea = $fechaGPS->format('H:i');
                                $diff = $tiempoEsperado->diff($fechaGPS);
                                $intervalo = $diff->format('%h:%i:%s');
                                $distancia = (($diff->i) + (($diff->h) * 60));
                                /* if ($distancia > 35)
                                {
                                    $puntoControlEsperado = null;
                                    $intervalo = null;
                                }*/

                                if ($total_puntoscontrol != $_total_pc) {
                                    $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                        'tipo',
                                        'GTGEO'
                                    )->where('fecha_gps', '<=', $ffin)
                                        ->where('fecha_gps', '>=', $tiempoEsperado)
                                        ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                        ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                    if (isset($puntoControlEsperado_tmp)) {
                                        $fechaGPS_ = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                        $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                        $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                                        if ($distancia > $distancia_) {
                                            $puntoControlEsperado = null;
                                        }
                                    }
                                } else {
                                    if ($distancia > 60) {
                                        $puntoControlEsperado = null;
                                        $intervalo = null;
                                    }
                                }
                            }

                            if (!isset($puntoControlEsperado)) { //+
                                $puntoControlEsperado = Recorrido::orderBy('fecha_gps')->where(
                                    'tipo',
                                    'GTGEO'
                                )->where('fecha_gps', '<=', $ffin)
                                    ->where('fecha_gps', '>=', $tiempoEsperado)
                                    ->where('fecha_gps', '>', $fini)
                                    ->where('unidad_id', new ObjectID($despacho->unidad_id))
                                    ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                if (isset($puntoControlEsperado)) {
                                    $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                                    $fechaLinea = $fechaGPS->format('H:i');
                                    $diff = $tiempoEsperado->diff($fechaGPS);
                                    $intervalo = $diff->format('%h:%i:%s');
                                    $distancia = (($diff->i) + (($diff->h) * 60));

                                    if ($primerpunto) {
                                        if ($distancia > 35) {
                                            $puntoControlEsperado = null;
                                            $intervalo = null;
                                        }
                                    } else {
                                        if ($punto_control['id'] == new ObjectID("5983ce813ebdfd42792a9982")) { //PPUNTO FACSO CAMBERRAS
                                            if ($distancia > 40) {
                                                $puntoControlEsperado = null;
                                                $intervalo = null;
                                            }
                                        } else {
                                            $item_tmp = ["fecha_tmpmen" => null, "fecha_" => null];
                                            if ($total_puntoscontrol != $_total_pc) {
                                                //if($cooperativa != "5a1351133ebdfd76eb7884b2" && $cooperativa != "5a1860f93ebdfd3ff8258572"){            
                                                foreach ($despacho_tmp->puntos_control as $punto_control_tmp) {

                                                    $puntoControlEsperado_tmp = null;
                                                    $tiempoEsperado_tmp = null;

                                                    $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                                    date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));

                                                    if ($tiempoEsperado_tmp > $tiempoEsperado) {
                                                        $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();
                                                        date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));
                                                        $puntoControlObj_tmp = PuntoControl::find($punto_control_tmp['id']);

                                                        $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps', 'desc')->where(
                                                            'tipo',
                                                            'GTGEO'
                                                        )->where('fecha_gps', '>=', $fini)
                                                            ->where('fecha_gps', '<=', $tiempoEsperado_tmp)
                                                            ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                            ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();


                                                        if (isset($puntoControlEsperado_tmp)) {
                                                            $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                            if ($fechaGPS > $fechaGPS_tmp &&  $tiempoEsperado < $fechaGPS_tmp) {
                                                                $puntoControlEsperado = null;
                                                                break;
                                                            } else {

                                                                $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                                    'tipo',
                                                                    'GTGEO'
                                                                )->where('fecha_gps', '<=', $ffin)
                                                                    ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                                    ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                                    ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                                $item_tmp["fecha_"] = $fechaGPS;

                                                                if (isset($puntoControlEsperado_tmp)) {
                                                                    $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                                    $item_tmp["fecha_tmpmen"] = $cooperativa;

                                                                    if ($fechaGPS > $fechaGPS_tmp) {
                                                                        $puntoControlEsperado = null;
                                                                        break;
                                                                    }/*else{
                                                                        if ($distancia > 25)
                                                                        {
                                                                            $puntoControlEsperado = null;
                                                                            $intervalo = null;
                                                                        }    
                                                                    } */
                                                                } else {
                                                                    if ($distancia > 35) {
                                                                        $puntoControlEsperado = null;
                                                                        $intervalo = null;
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            //************************ */
                                                            $puntoControlEsperado_tmp = Recorrido::orderBy('fecha_gps')->where(
                                                                'tipo',
                                                                'GTGEO'
                                                            )->where('fecha_gps', '<=', $ffin)
                                                                ->where('fecha_gps', '>=', $tiempoEsperado_tmp)
                                                                ->where('unidad_id', new ObjectID($despacho_tmp->unidad_id))
                                                                ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                            $item_tmp["fecha_"] = $fechaGPS;

                                                            if (isset($puntoControlEsperado_tmp)) {
                                                                $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                                $item_tmp["fecha_tmpmen"] = $cooperativa;

                                                                if ($fechaGPS > $fechaGPS_tmp) {
                                                                    $puntoControlEsperado = null;
                                                                    break;
                                                                }/*else{
                                                                    if ($distancia > 25)
                                                                    {
                                                                        $puntoControlEsperado = null;
                                                                        $intervalo = null;
                                                                    }    
                                                                } */
                                                            } else
                                                            /************************* */
                                                            {
                                                                if ($distancia > 35) {
                                                                    $puntoControlEsperado = null;
                                                                    $intervalo = null;
                                                                }
                                                            }
                                                        }
                                                        array_push($array_temp, $item_tmp);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                if ($distancia > 60) {
                                                    $puntoControlEsperado = null;
                                                    $intervalo = null;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $primerpunto = false;
                        }

                        if (isset($puntoControlEsperado)) {
                            $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                            $fechaGPS_ = $puntoControlEsperado->fecha_gps->toDateTime();

                            $diff = $tiempoEsperado->diff($fechaGPS);
                            $intervalo = $diff->format('%h:%i:%s');
                            $tiempo_atraso = null;
                            $tiempo_adelanto = null;


                            if ($tiempoEsperado > $fechaGPS) {
                                $intervalo = (($diff->i) + ($diff->h * 60)) * -1;
                                $multa = $multa + (((float)$punto_control["adelanto"]) * $intervalo * -1);
                                $tiempo_adelanto = $diff->format('%h:%i:%s');
                            } else if ($tiempoEsperado < $fechaGPS) {
                                $intervalo = (($diff->i) + ($diff->h * 60));
                                $multa = $multa + (((float)$punto_control["atraso"]) * $intervalo);
                                $tiempo_atraso = $diff->format('%h:%i:%s');
                            } else {
                                $intervalo = '0';
                            }

                            date_sub($fechaGPS, date_interval_create_from_date_string('10 hours'));
                            $fini = $fechaGPS_;
                            $fini = new UTCDateTime(($fini->getTimestamp() * 1000));

                            $consulta = $fechaGPS_;

                            $fechaLinea = $fechaGPS->format('H:i');

                            $item["marca"] = $fechaGPS->format('Y-m-d H:i:s');
                            $item["contador_marca"] = $puntoControlEsperado["contador_total"];
                            $item["tiempo_atraso"] = $tiempo_atraso;
                            $item["tiempo_adelanto"] = $tiempo_adelanto;
                            $item["intervalo"] = $intervalo;

                            $index++;
                        }

                        $_total_pc++;
                        array_push($array_final, $item);
                    }

                    //$this->info('P3 ');
                    $paso = "si";
                    $despacho->recorridos = $paso;
                    $despacho->puntos_control = $array_final;

                    $contador = 0;
                    if (count($despacho->puntos_control) > 0) {
                        $despacho->contador_inicial = ($despacho->puntos_control[0]['contador_marca'] != null) ? $despacho->puntos_control[0]['contador_marca'] : 0;
                        $contador = ($despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] != null) ? $despacho->puntos_control[count($despacho->puntos_control) - 1]['contador_marca'] : 0;
                        $despacho->salida = $despacho->puntos_control[0]['marca'];
                    }

                    if ($contador < 0)
                        $contador = 65535 + $contador;

                    $despacho->contador_final = $contador;
                    $despacho->estado = 'C';

                    $array_aux = []; //Arreglo a valida    
                    $despacho->auxiliar = $array_aux;

                    /*****INICIO CORTE DE TUBO */
                    $corteTubo = 'No';
                    $array_recorridos_corte = array();

                    $latitud_corte = 0.0;
                    $longitud_corte = 0.0;
                    //$this->info('P4 ');

                    $corteTubo = 'Si';
                    /***FIN CORTE DE TUBO***/
                    if ($corteTubo == "Si") {
                        if (isset($cooperativa_cortetubo) && $cooperativa_cortetubo != "") {
                            $despacho->multa = $multa + ((int)$cooperativa_cortetubo);
                        } else {
                            $despacho->multa = $multa;
                        }
                    } else {
                        $despacho->multa = $multa;
                    }

                    $despacho->corte_tubo = $corteTubo;
                    $despacho->array_corte = $array_recorridos_corte;
                    $despacho->latitud_corte = $latitud_corte;
                    $despacho->longitud_corte = $longitud_corte;
                    $despacho->save();

                    // return response()->json(['error' => false, 'despacho' => $despacho,'marcar'=>$array_final,
                    //  'paso'=>$paso,'array_temp'=>$array_temp]);
                    //***********************fin de algoritmo arreglo de marcas*************************
                } else {
                    $despacho->corte_tubo = '-';
                    $despacho->estado = 'C';
                    $despacho->save();
                    // return response()->json(['error' => true, 'despacho' => null]);
                }
            }
        } catch (Exception $ex) {
            $errorMessage = $ex->getMessage();
            LOGATMDESPACHOS::create([
                'mensaje' => "TASK DESPACHO FIN SERVER : " . $errorMessage,
                'fecha' => Carbon::now(),
                'localizacion' => 'PN'
            ]);
        }
    }

    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        if ($lat1 != null && $lon1 != 1 && $lat2 != null && $lon2 != null) {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            return ($miles * 1.609344);
        }
        return 0;
    }
}
