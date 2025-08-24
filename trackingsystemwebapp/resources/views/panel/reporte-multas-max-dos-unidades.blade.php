<!DOCTYPE>
<html>
    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="css/multas.css">
    </head>
    <body>
        @if (isset($reportes) && count($reportes) > 0)
            @foreach ($reportes as $reporte)
                <div class="table-responsive">
                    <table id="header">
                        <tr>
                            <td colspan="21">
                                {{ $cooperativa  != null?$cooperativa->descripcion:'-' }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7">
                                Disco: {{ $reporte['unidad']['descripcion'] }}
                            </td>
                            <td colspan="7">
                                Fecha de despacho: {{ $desde->format('d/m/Y') }}
                            </td>
                            <td colspan="7">Fecha de impresión: {{ date('d/m/Y') }}</td>
                        </tr>
                    </table>
                    <table id="marcas">
                        <tr>
                            @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                <td colspan="5"><strong>{{ App\PuntoControl::findOrFail($punto_control['id'])->descripcion }}</strong></td>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                <td>Reloj</td>
                                <td>Marca</td>
                                <td>AT</td>
                                <td>AD</td>
                                <td>DESC.</td>
                            @endforeach
                        </tr>
                        @php
                            $atrasos = 0;
                            $adelantos = 0;
                            $atrasosV=0;
                            $adelantosV=0;
                            $arrayAtrasos = array();
                            $arrayAdelantos = array();
                            $multaAtrasos = 0;
                            $multaAdelantos = 0;
                        @endphp
                        @foreach ($reporte['despachos'] as $despacho)
                            @php
                                $atrasosV=0;
                                $adelantosV=0; 
                            @endphp
                            <tr>
                                @foreach ($despacho->puntos_control as $key => $punto_control)
                                    @php 
                                        if (isset($punto_control['tiempo_atraso']))
                                            $at = explode(':', $punto_control['tiempo_atraso']);
                                        $tiempoAtraso = (isset($punto_control['tiempo_atraso']) && array_key_exists(1, $at))?$at[1]:0;
                                        if (isset($punto_control['tiempo_adelanto']))
                                            $ad = explode(':', $punto_control['tiempo_adelanto']);
                                        $tiempoAdelanto = (isset($punto_control['tiempo_adelanto']) && array_key_exists(1, $ad))?intval($ad[1]):0;
                                        $atrasos+= $tiempoAtraso;
                                        $adelantos+= $tiempoAdelanto;
                                        $atrasosV+= $tiempoAtraso;
                                        $adelantosV+= $tiempoAdelanto;
                                        if (!array_key_exists($key, $arrayAdelantos))
                                            $arrayAdelantos[$key] = $tiempoAdelanto;
                                        else 
                                            $arrayAdelantos[$key]+= $tiempoAdelanto;
                                        if (!array_key_exists($key, $arrayAtrasos))
                                            $arrayAtrasos[$key] = $tiempoAtraso;
                                        else 
                                            $arrayAtrasos[$key] += $tiempoAtraso;
                                        $multaAtrasos += (isset($punto_control['tiempo_atraso']))?floatval($punto_control['intervalo'] * $punto_control['atraso']):0;
                                        $multaAdelantos += (isset($punto_control['tiempo_adelanto']))?floatval($punto_control['intervalo'] * $punto_control['adelanto']):0;
                                    @endphp
                                    <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
                                    <td>{{ (isset($punto_control['marca']))?DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i:s'):'-' }}</td>
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
                                <td></td>
                                <td>{{ $atrasosV }}</td>
                                <td>{{ $adelantosV }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            @foreach ($arrayAdelantos as $key => $adelanto)
                                <td colspan="2"></td>
                                <td>{{ $arrayAtrasos[$key] }}</td>
                                <td>{{ $arrayAdelantos[$key] }}</td>
                            @endforeach
                        </tr>
                    </table>
                    <table id="totales">
                        <tr>
                            <td colspan="20">Total de atrasos: {{ $atrasos }} | Total de adelantos: {{ $adelantos }} | Total: {{ ($atrasos + $adelantos) }} | Multa de atrasos: ${{ number_format ( $multaAtrasos, 2 ) }} | Multa de adelantos: ${{ number_format ( $multaAdelantos, 2 ) }}</td>
                        </tr>
                    </table> 
                    <table></table>
                    <table></table>
                </div>
            @endforeach
        @else 
            <strong>No hay resultados que mostrar.</strong>
        @endif
    </body>
</html>