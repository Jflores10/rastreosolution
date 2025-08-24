<h1>{{ $ruta->cooperativa->descripcion }}</h1>
@foreach ($fechas as $fecha)
    <table>
        <tr>
            <td colspan="2">
                <b>{{ $fecha }}</b>
            </td>
            @foreach ($puntos_control as $punto)
                <td colspan="4">
                    <b>{{ $punto->descripcion }}</b>
                </td>
            @endforeach
        </tr>
        <tr>
            <td><b>Unidad</b></td>
            <td><b>Conductor</b></td>
            @foreach ($puntos_control as $punto)
                <td><b>Reloj</b></td>
                <td><b>Marca</b></td>
                <td><b>AT/AD</b></td>
                <td><b>Desc.</b></td>
            @endforeach
            <td><b>Multa</b></td>
        </tr>
        @foreach ($despachos as $despacho)
            @php
                $fec=$despacho->fecha;
                date_add($fec, date_interval_create_from_date_string('5 hours'));
            @endphp
            @if ($fec->format('Y-m-d') === $fecha)
                <tr>
                    <td>{{ ($despacho->unidad == null)?'-':$despacho->unidad->descripcion }}</td>
                    <td>{{ ($despacho->conductor == null)?'-':$despacho->conductor->nombre }}</td>
                    @foreach ($despacho->puntos_control as $punto_control)
                        <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
                        <td>{{ (isset($punto_control['marca']))?DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i:s'):'-' }}</td>
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
                </tr>
            @endif
        @endforeach
    </table>
    <br/>
@endforeach