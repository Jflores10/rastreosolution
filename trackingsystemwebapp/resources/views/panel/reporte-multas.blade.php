<!DOCTYPE>
<html>
    <head>
        <meta charset="utf-8" />
    </head>
    <body>
        @if (isset($reportes) && count($reportes) > 0)
            @foreach ($reportes as $reporte)
                <h4>Unidad: {{ $reporte['unidad']['descripcion'] . ' | Ruta: ' . $reporte['ruta']['descripcion'] }}</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <td><b>Vehículo</b></td>
                            <td>{{ $reporte['unidad']['descripcion'] }}</td>
                            <td><b>Fecha de impresión</b></td>
                            <td>{{ date('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><b>Propietario</b></td>
                            <td>{{ (isset($reporte['unidad']['user']))?$reporte['unidad']['user']['name']:'-' }}</td>
                        </tr>
                    </table>
                    <table class="table table-bordered">
                        <tr>
                            <th></th>
                            @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                <th colspan="5">{{ App\PuntoControl::findOrFail($punto_control['id'])->descripcion }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <td><strong>Fecha</strong></td>
                            @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                <td><strong>Reloj</strong></td>
                                <td><strong>Marca</strong></td>
                                <td><strong>AT</strong></td>
                                <td><strong>AD</strong></td>
                                <td><strong>DESC.</strong></td>
                            @endforeach
                        </tr>
                        @php
                            $atrasos = 0;
                            $adelantos = 0;
                            $arrayAtrasos = array();
                            $arrayAdelantos = array();
                            $multaAtrasos = 0;
                            $multaAdelantos = 0;
                        @endphp
                        @foreach ($reporte['despachos'] as $despacho)
                            <tr>
                                <td>{{ $despacho->fecha->addHours(5) }}</td>
                                @foreach ($despacho->puntos_control as $key => $punto_control)
                                    @php 
                                        $tiempoAtraso = (isset($punto_control['tiempo_atraso']))?intval(explode(':', $punto_control['tiempo_atraso'])[1]):0;
                                        $tiempoAdelanto = (isset($punto_control['tiempo_adelanto']))?intval(explode(':', $punto_control['tiempo_adelanto'])[1]):0;
                                        $atrasos+= $tiempoAtraso;
                                        $adelantos+= $tiempoAdelanto;
                                        if (!array_key_exists($key, $arrayAdelantos))
                                            $arrayAdelantos[$key] = $tiempoAdelanto;
                                        else 
                                            $arrayAdelantos[$key]+= $tiempoAdelanto;
                                        if (!array_key_exists($key, $arrayAtrasos))
                                            $arrayAtrasos[$key] = $tiempoAtraso;
                                        else 
                                            $arrayAtrasos[$key] += $tiempoAtraso;
                                        $multaAtrasos += (isset($punto_control['tiempo_atraso']))?floatval($punto_control['atraso']):0;
                                        $multaAdelantos += (isset($punto_control['tiempo_adelanto']))?floatval($punto_control['adelanto']):0;
                                    @endphp
                                    <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
                                    <td>{{ (isset($punto_control['marca']))?DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i'):'-' }}</td>
                                    <td>{{ $tiempoAtraso }}</td>
                                    <td>{{ $tiempoAdelanto }}</td>
                                    @php 
                                        $intervalo=isset($punto_control['intervalo'])?$punto_control['intervalo']:0;
                                        $atraso=isset($punto_control['atraso'])?$punto_control['atraso']:0;
                                        $adelanto=isset($punto_control['adelanto'])?$punto_control['adelanto']:0;
                                        $desc=0;
                                        if(isset($punto_control['tiempo_atraso'])){
                                            $desc=$intervalo*$atraso;
                                        }else{
                                            $desc=$intervalo*$adelanto;
                                        }        
                                    @endphp
                                    <td>{{ $desc }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td><b>Subtotal</b></td>
                            @foreach ($arrayAdelantos as $key => $adelanto)
                                <td colspan="2"></td>
                                <td><b>{{ $arrayAtrasos[$key] }}</b></td>
                                <td><b>{{ $arrayAdelantos[$key] }}</b></td>
                            @endforeach
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td><b>Totales</b></td>
                            <td>Total de atrasos: {{ $atrasos }}</td>
                            <td>Total de adelantos: {{ $adelantos }}</td>
                            <td>Total: {{ ($atrasos + $adelantos) }}</td>
                            <td>Multa de atrasos: ${{ $multaAtrasos }}</td>
                            <td>Multa de adelantos: ${{ $multaAdelantos }}</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td colspan="2">
                                <center><b>{{ ($cooperativa != null)?$cooperativa->descripcion:'-' }}</b></center>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>{{ $multaAdelantos + $multaAtrasos }}</td>
                        </tr>
                        <tr>
                            <td><b>Recibí del Sr(a):</b></td>
                            <td>{{ (isset($reporte['unidad']['user']))?$reporte['unidad']['user']['name']:'-' }}</td>
                        </tr>
                        <tr>
                            <td><b>La suma de:</b></td>
                            <td>{{ $multaAdelantos + $multaAtrasos }}</td>
                        </tr>
                        <tr>
                            <td><b>Por concepto de:</b></td>
                            <td>Multas y desvíos</td>
                        </tr>
                        <tr>
                            <td><b>Disco:</b></td>
                            <td>{{ $reporte['unidad']['descripcion'] }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>Guayaquil, {{ date('d/m/Y') }}</td>
                        </tr>
                    </table>
                </div>
            @endforeach
        @else 
            <strong>No hay resultados que mostrar.</strong>
        @endif
    </body>
</html>