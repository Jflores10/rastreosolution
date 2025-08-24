<thead>
    <tr>
        <th rowspan="2"><strong>Unidad</strong></th>
        @foreach ($puntosControl as $puntoControl)
            <th colspan="3"><strong>{{ $puntoControl->descripcion }}</strong></th>
        @endforeach
    </tr>
    <tr>
        @foreach ($puntosControl as $puntoControl)
            <th>Reloj</th>
            <th>Marca</th>
            <th>AT/AD</th>
        @endforeach
    </tr>
</thead>
<tbody>
@if (isset($despachos) && $despachos->count() > 0)
    @foreach ($despachos as $despacho)
        <tr>
            @php
                $index=0; 
            @endphp
            <td>{{ $despacho->unidad->descripcion }}</td>
            @php
                $primerpunto=true;
                $despacho_tmp = $despacho;
                $hasta = $despacho->puntos_control[count($despacho->puntos_control) - 1]['tiempo_esperado']->toDateTime();//Tiempo de finalizacion esperada
                $desde = $despacho->fecha; //fecha de inicio del despacho (salida del bus)
                date_add($desde, date_interval_create_from_date_string('570 minutes'));// Agrego 10 horas a la consulta de fecha de GPS inicial
                date_add($hasta, date_interval_create_from_date_string('700 minutes'));// Agrego 10 horas a la fecha de fin, fecha de culminacion
                $desde = new MongoDB\BSON\UTCDateTime(($desde->getTimestamp() * 1000));//Inicio
                $hasta = new MongoDB\BSON\UTCDateTime(($hasta->getTimestamp() * 1000));//Fin

                $total_puntoscontrol=count($despacho->puntos_control);
                $_total_pc=1;

            @endphp
            
            @foreach ($despacho->puntos_control as $punto_control)
                @php
                    $puntoControlEsperado = null;
                    $intervalo = null;
                    $tiempoRango1 = 90;
                    $puntoControlObj = App\PuntoControl::find($punto_control['id']);

                    if (isset($puntoControlObj)){
                        $tiempoEsperado = $punto_control['tiempo_esperado']->toDateTime();
                        $consulta_1 = $punto_control['tiempo_esperado']->toDateTime();                    
                        date_add($tiempoEsperado, date_interval_create_from_date_string('10 hours'));
                        date_add($consulta_1, date_interval_create_from_date_string('10 hours'));

                        if($index==0){
                            $consulta = $punto_control['tiempo_esperado']->toDateTime();
                            date_add($consulta, date_interval_create_from_date_string('10 hours'));
                        }

                        if($primerpunto){
                            $puntoControlEsperado_inicio = App\Recorrido::orderBy('fecha_gps', 'desc')->where('tipo', 
                            'GTGEO')->where('fecha_gps', '>=', $desde)
                            ->where('fecha_gps','<=', $consulta_1)
                            ->where('unidad_id', new MongoDB\BSON\ObjectID($despacho->unidad_id))/****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();                            

                            if(isset($puntoControlEsperado_inicio))
                            {
                                $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                                $fechaLinea_ = $fechaGPS_->format('H:i');
                                $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                $distancia_ = (($diff_->i) + (($diff_->h) * 60));

                                $puntoControlEsperado_salida = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                'GTGEO')->where('fecha_gps', '<=', $hasta)
                                ->where('fecha_gps','>=', $consulta)
                                ->where('entrada','!=',1)
                                ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                if(isset($puntoControlEsperado_salida)){
                                    $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                    $fechaLinea = $fechaGPS->format('H:i');
                                    $diff = $tiempoEsperado->diff($fechaGPS);
                                    $intervalo = $diff->format('%h:%i:%s'); 
                                    $distancia = (($diff->i) + (($diff->h) * 60));
                                    if ($distancia > $tiempoRango1)
                                    {
                                        $puntoControlEsperado = $puntoControlEsperado_inicio;
                                    }else{
                                        if($distancia_ > $distancia){
                                            $puntoControlEsperado = $puntoControlEsperado_salida;
                                        }else{
                                            if($distancia_<$tiempoRango1){
                                                $puntoControlEsperado = $puntoControlEsperado_inicio;
                                            }else{
                                                $puntoControlEsperado=null; 
                                            }      
                                        }                                
                                    }
                                }else{
                                    $puntoControlEsperado=$puntoControlEsperado_inicio;
                                }

                            }else{
                                $puntoControlEsperado_salida = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                'GTGEO')->where('fecha_gps', '<=', $hasta)
                                ->where('fecha_gps','>=', $consulta)
                                ->where('entrada','!=',1)
                                ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                if(!isset($puntoControlEsperado_salida)){
                                    /****VERIFICANDO PUINTOS DE CONTROL PRIMEROS DEFAULT SALIDA DEL PUNTO */
                                    $puntoControlEsperado=null;
                                    
                                    $puntoControlEsperado_inicio = App\Recorrido::orderBy('fecha_gps', 'desc')->where('tipo', 
                                    'GTGEO')->where('fecha_gps', '>=', $desde)
                                    ->where('fecha_gps','<=', $consulta_1)
                                    ->where('unidad_id', new MongoDB\BSON\ObjectID($despacho->unidad_id))/****SI SE DESEA HACER UN CAMBIO SOBRE PUNTO ENTRADA O SALIDA AGREGAR LOS FILTROS EN TODOS LAS BUSQUEDAD DE RECORRIDOS Y MAS IF */
                                    ->where('pdi', (int) $puntoControlObj->pdi)->first();
                                    if(isset($puntoControlEsperado_inicio))
                                    {
                                        $fechaGPS_ = $puntoControlEsperado_inicio->fecha_gps->toDateTime();
                                        $fechaLinea_ = $fechaGPS_->format('H:i');
                                        $diff_ = $tiempoEsperado->diff($fechaGPS_);
                                        $distancia_ = (($diff_->i) + (($diff_->h) * 60));
            
                                        $puntoControlEsperado_salida = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                        'GTGEO')->where('fecha_gps', '<=', $hasta)
                                        ->where('fecha_gps','>=', $consulta)
                                        ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                                        ->where('pdi', (int) $puntoControlObj->pdi)->first();

                                        if(isset($puntoControlEsperado_salida)){
                                            $fechaGPS = $puntoControlEsperado_salida->fecha_gps->toDateTime();
                                            $fechaLinea = $fechaGPS->format('H:i');
                                            $diff = $tiempoEsperado->diff($fechaGPS);
                                            $intervalo = $diff->format('%h:%i:%s'); 
                                            $distancia = (($diff->i) + (($diff->h) * 60));
                                            if ($distancia_ < $tiempoRango1)
                                            {
                                                $puntoControlEsperado = $puntoControlEsperado_inicio;
                                            }else{
                                                if($distancia<$tiempoRango1){
                                                    $puntoControlEsperado = $puntoControlEsperado_salida;
                                                }else{
                                                    $puntoControlEsperado=null; 
                                                }                                     
                                            }
                                        }else{
                                            $puntoControlEsperado=$puntoControlEsperado_inicio;
                                        }
                                    }else{
                                        $puntoControlEsperado=null;
                                    }
                                }else{
                                    $puntoControlEsperado=$puntoControlEsperado_salida;
                                }
                            }

                        }else{
                            $puntoControlEsperado = App\Recorrido::orderBy('fecha_gps')->orderBy('fecha_gps', 'desc')->where('tipo', 
                            'GTGEO')->where('fecha_gps', '>', $desde)
                            ->where('fecha_gps','<=', $tiempoEsperado) 
                            ->where('entrada',1)                        
                            ->where('unidad_id', new MongoDB\BSON\ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();
                        }

                        if (isset($puntoControlEsperado)) {
                            $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                            // date_add($fechaGPS, date_interval_create_from_date_string('1 hours'));
                            $fechaLinea = $fechaGPS->format('H:i');
                            $diff = $tiempoEsperado->diff($fechaGPS);
                            $intervalo = $diff->format('%h:%i:%s'); 
                            $distancia = (($diff->i) + (($diff->h) * 60));

                            $puntoControlEsperado_tmp = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                            'GTGEO')->where('fecha_gps', '<=', $hasta)
                            ->where('fecha_gps','>=', $tiempoEsperado)
                            ->where('entrada',1)    
                            ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                            if (isset($puntoControlEsperado_tmp)) {
                                $fechaGPS = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                $fechaLinea = $fechaGPS->format('H:i');
                                $diff = $tiempoEsperado->diff($fechaGPS);
                                $intervalo = $diff->format('%h:%i:%s'); 
                                $distancia_tmp = (($diff->i) + (($diff->h) * 60));
                                if($distancia>$distancia_tmp){
                                    $puntoControlEsperado=$puntoControlEsperado_tmp;
                                    $distancia=$distancia_tmp;
                                }
                            }

                            if($total_puntoscontrol != $_total_pc){  
                                if ($distancia > $tiempoRango1)
                                {
                                    $puntoControlEsperado = null;
                                    $intervalo = null;
                                }
                            }else{
                                if ($distancia > $tiempoRango1)
                                {
                                    $puntoControlEsperado = null;
                                    $intervalo = null;
                                }
                            }
                        }

                        if (!isset($puntoControlEsperado)) {//+
                            //   if($punto_control['id']==new MongoDB\BSON\ObjectID("599220163ebdfd2c9a4d4a12")){
                            //         if ($distancia > $tiempoEsperado)
                            //         {
                            //             $puntoControlEsperado = null;
                            //             $intervalo = null;
                            //         }      
                            //     }
                            $puntoControlEsperado = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                            'GTGEO')->where('fecha_gps', '<=', $hasta)
                            ->where('fecha_gps','>=', $tiempoEsperado)
                            ->where('entrada',1)    
                            ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                            ->where('pdi', (int) $puntoControlObj->pdi)->first();

                            if(!isset($puntoControlEsperado)){
                                $puntoControlEsperado = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                'GTGEO')->where('fecha_gps', '<=', $hasta)
                                ->where('fecha_gps','>=', $tiempoEsperado)
                                ->where('entrada',0)    
                                ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho->unidad_id))
                                ->where('pdi', (int) $puntoControlObj->pdi)->first();
                            }
                            
                            if (isset($puntoControlEsperado)) {
                                $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                                //date_add($fechaGPS, date_interval_create_from_date_string('1 hours'));
                                $fechaLinea = $fechaGPS->format('H:i');
                                $diff = $tiempoEsperado->diff($fechaGPS);
                                $intervalo = $diff->format('%h:%i:%s'); 
                                $distancia = (($diff->i) + (($diff->h) * 60));

                                if($primerpunto){
                                    if ($distancia > $tiempoRango1)
                                    {
                                        $puntoControlEsperado = null;
                                        $intervalo = null;
                                    }                            
                                }else{    
                                    if($punto_control['id']==new MongoDB\BSON\ObjectID("5983ce813ebdfd42792a9982") 
                                    || $punto_control['id']==new MongoDB\BSON\ObjectID("582a62087aea9118d059b081")){
                                        if ($distancia > $tiempoRango1)
                                        {
                                            $puntoControlEsperado = null;
                                            $intervalo = null;
                                        }      
                                    }else{             
                                        if($total_puntoscontrol != $_total_pc){        
                                            foreach ($despacho_tmp->puntos_control as $punto_control_tmp){
                                                
                                                $puntoControlEsperado_tmp = null;
                                                $tiempoEsperado_tmp=null;

                                                $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime(); 
                                                date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));
                                                
                                                if($tiempoEsperado_tmp > $tiempoEsperado){                                            
                                                    $tiempoEsperado_tmp = $punto_control_tmp['tiempo_esperado']->toDateTime();             
                                                    date_add($tiempoEsperado_tmp, date_interval_create_from_date_string('10 hours'));
                                                    $puntoControlObj_tmp = App\PuntoControl::find($punto_control_tmp['id']);
                                                    
                                                    $puntoControlEsperado_tmp = App\Recorrido::orderBy('fecha_gps','desc')->where('tipo', 
                                                    'GTGEO')->where('fecha_gps', '>', $desde)
                                                    ->where('entrada',1)
                                                    ->where('fecha_gps','<=', $tiempoEsperado_tmp)
                                                    ->where('unidad_id', new MongoDB\BSON\ObjectID($despacho_tmp->unidad_id))
                                                    ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();                                                

                                                    if(isset($puntoControlEsperado_tmp)){
                                                        $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                        if ($fechaGPS > $fechaGPS_tmp) {
                                                            $puntoControlEsperado = null;
                                                            break;
                                                        }else{
                                                        /******INICIO BLOQUE NUEVO VALIDACION SI YA PASO POR UN PUNTO ANTERIOR*/
                                                            $puntoControlEsperado_tmp = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                                            'GTGEO')->where('fecha_gps', '<=', $hasta)
                                                            ->where('fecha_gps','>=', $tiempoEsperado_tmp)
                                                            ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho_tmp->unidad_id))
                                                            ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();                                                        

                                                            if(isset($puntoControlEsperado_tmp)){
                                                                $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();
                                                            
                                                                if($fechaGPS > $fechaGPS_tmp){
                                                                    $puntoControlEsperado=null;
                                                                    break;
                                                                }                                         
                                                                
                                                            }else{
                                                                if ($distancia > $tiempoRango1)
                                                                {
                                                                    $puntoControlEsperado = null;
                                                                    $intervalo = null;
                                                                }    
                                                            }
                                                        }                                           
                                                        
                                                    }else{
                                                        //************************ 
                                                        $puntoControlEsperado_tmp = App\Recorrido::orderBy('fecha_gps')->where('tipo', 
                                                        'GTGEO')->where('fecha_gps', '<=', $hasta)
                                                        //->where('entrada',1)
                                                        ->where('fecha_gps','>=', $tiempoEsperado_tmp)
                                                        ->where('unidad_id',new MongoDB\BSON\ObjectID($despacho_tmp->unidad_id))
                                                        ->where('pdi', (int) $puntoControlObj_tmp->pdi)->first();

                                                        if(isset($puntoControlEsperado_tmp)){
                                                            $fechaGPS_tmp = $puntoControlEsperado_tmp->fecha_gps->toDateTime();

                                                            if($fechaGPS > $fechaGPS_tmp){
                                                                $puntoControlEsperado=null;
                                                                break;
                                                            }                                      
                                                        }else //************************* 
                                                        {
                                                            if ($distancia > $tiempoRango1)
                                                            {
                                                                $puntoControlEsperado = null;
                                                                $intervalo = null;
                                                            } 
                                                        }  
                                                    }
                                                    break;                                                
                                                }
                                            }                                 
                                                // TAB
                                        }else{
                                            if ($distancia > $tiempoRango1)
                                            {
                                                $puntoControlEsperado = null;
                                                $intervalo = null;
                                            } 
                                        }
                                    }
                                }

                            }
                        }

                        $primerpunto=false;
                    }

                    if (isset($puntoControlEsperado))
                    {
                        $fechaGPS = $puntoControlEsperado->fecha_gps->toDateTime();
                        $fechaGPS_ = $puntoControlEsperado->fecha_gps->toDateTime();
                        
                        //date_add($fechaGPS, date_interval_create_from_date_string('1 hours'));

                        $diff = $tiempoEsperado->diff($fechaGPS);
                        $intervalo = $diff->format('%h:%i:%s');
                        
                        if ($tiempoEsperado > $fechaGPS) {                                     
                            $intervalo = '-' . $intervalo;
                        }
                        else if ($tiempoEsperado < $fechaGPS) {   
                            $intervalo = '+' . $intervalo;
                        }
                        else {
                            $intervalo = '0';
                        }

                        date_sub($fechaGPS, date_interval_create_from_date_string('10 hours')); 
                        $desde = $fechaGPS_;
                        $desde = new MongoDB\BSON\UTCDateTime(($desde->getTimestamp() * 1000));                        
                        $consulta = $fechaGPS_;

                        //date_sub($fechaGPS, date_interval_create_from_date_string('1 hours'));
                        $fechaLinea = $fechaGPS->format('H:i');

                        $index++;

                    }

                     $_total_pc++;
                @endphp
                <td>
                    {{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}
                </td>
                <td>{{ (!isset($punto_control['marca']))?(!isset($puntoControlEsperado)?'-':$fechaLinea):DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i') }}</td>
                <td>{{ (!isset($punto_control['tiempo_atraso']))?(!isset($punto_control['tiempo_adelanto'])?(!isset($intervalo)?'-':$intervalo): '-' . $punto_control['tiempo_adelanto']):'+' . $punto_control['tiempo_atraso'] }}</td>
            @endforeach
        </tr>
    @endforeach
@else 
    <tr>
        <td colspan="{{ $puntosControl->count() * 3 + 1 }}">
            <div class="alert alert-info">
                No hay unidades disponibles
            </div>
        </td>
    </tr>
@endif
</tbody>