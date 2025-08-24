<!DOCTYPE>
<html>
    <head>
        <title>{{ $title }}</title>
        <meta charset="utf-8" />
    </head>
    <body>
        @php
            $columnNumber = 3;
            $puntosColumn = 5;
        @endphp
        <h1>{{ $ruta->cooperativa->descripcion }}</h1>
        @foreach ($fechas as $fecha)
            <table>
                <tr>
                    <td colspan="{{ $columnNumber }}">
                        <b>{{ $fecha }}</b>
                    </td>
                    @foreach ($puntos_control as $punto)
                        <td colspan="5">
                            <b>{{ $punto->descripcion }}</b>
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td><b>Unidad</b></td>
                    <td><b>Placa</b></td>
                    <td><b>Conductor</b></td>
                    @foreach ($puntos_control as $punto)
                        <td><b>Reloj</b></td>
                        <td><b>Marca</b></td>
                        <td><b>Cont.</b></td>
                        <td><b>AT/AD</b></td>
                        <td><b>Desc.</b></td>
                    @endforeach
                    <td><b>Multa</b></td>
                    <td><b>Cont.Ini</b></td>
                    <td><b>Cont.Fin</b></td>
                    <td><b>P</b></td>
                </tr>
                    @for($i = 0; $i <= $vueltas; $i++)
                    @php
                        $existeVuelta = false;
                        foreach ($despachos as $despacho) {
                            if ($despacho->fecha->format('Y-m-d') === $fecha && $despacho->vuelta == $i) {
                                $existeVuelta = true;
                                break;
                            }
                        }
                    @endphp
                        @if ($existeVuelta)
                            <tr>
                                <td colspan="{{ (count($puntos_control) * $puntosColumn) + $columnNumber }}"><b>Vuelta {{ $i+1 }}</b></td>
                            </tr>
                        @endif
                        @foreach ($despachos as $despacho)
                            @php
                                $fec=$despacho->fecha;
                                date_add($fec, date_interval_create_from_date_string('5 hours'));
                            @endphp
	                        @if ($fec->format('Y-m-d') === $fecha && $despacho->vuelta == $i)
	                            <tr>
	                                <td>{{ ($despacho->unidad == null)?'-':$despacho->unidad->descripcion }}</td>
                                    <td>{{ ($despacho->unidad == null)?'-':$despacho->unidad->placa }}</td>
	                                <td>{{ ($despacho->conductor == null)?'-':$despacho->conductor->nombre }}</td>
	                                @foreach ($despacho->puntos_control as $punto_control)
	                                    <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
	                                    <td>{{ (isset($punto_control['marca']))?DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i'):'-' }}</td>
                                        <td>{{ (isset($punto_control['contador_marca']))?$punto_control['contador_marca']:'-' }}</td>
	                                    <td>{{ (!isset($punto_control['tiempo_atraso']))?((isset($punto_control['tiempo_adelanto']))? '(-)' . (explode(':', $punto_control['tiempo_adelanto'])[1]):'-') : '(+)' . (explode(':', $punto_control['tiempo_atraso'])[1]) }}</td>
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
                                    <td>$ {{number_format($despacho->multa,2)}}</td>
                                    <td>{{ ($despacho->contador_inicial == null)?'-':$despacho->contador_inicial }}</td>
                                    <td>{{ ($despacho->contador_final == null)?'-':$despacho->contador_final }}</td>
                                    @php
                                        $diff = intval($despacho->contador_final) - intval($despacho->contador_inicial);
                                    @endphp
                                    <td>{{ ($diff <= 0)?'-':$diff }}</td>
	                            </tr>
	                        @endif
	                    @endforeach
                    @endfor
            </table>
            <br/>
        @endforeach
    </body>
</html>