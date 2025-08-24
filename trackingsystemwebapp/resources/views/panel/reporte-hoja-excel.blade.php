<!DOCTYPE>
<html>
    <head>
        <meta charset="utf-8" />
    </head>
    <body>
        @if (isset($reportes) && count($reportes) > 0)
            @foreach ($reportes as $reporte)
                <h4>Unidad: {{ $reporte['unidad']['descripcion'] . '| Ruta: ' . $reporte['ruta']['descripcion'] }}</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th></th>
                            @php 
                                $colspan = 0;
                                if (in_array('R', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                                if (in_array('M', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                                if (in_array('ATAD', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                                if (in_array('CONT', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                            @endphp
                            @if ($colspan > 0)
                                @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                    <th colspan="{{ $colspan }}">{{ App\PuntoControl::findOrFail($punto_control['id'])->descripcion }}</th>
                                @endforeach
                            @endif
                            @php 
                                $colspan = 0;
                                if (in_array('CI', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                                if (in_array('CF', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                                if (in_array('P', $reporte['filtros']))
                                    $colspan = $colspan + 1;
                            @endphp
                            @if ($colspan > 0)
                                <th colspan="{{ $colspan }}">Contador</th>
                            @endif
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <td><strong>Fecha</strong></td>
                            @if(in_array('C', $reporte['filtros']))
                                <td><strong>Conductor</strong></td>
                            @endif
                            @foreach ($reporte['ruta']->puntos_control as $punto_control)
                                @if (in_array('R', $reporte['filtros']))
                                    <td><strong>Reloj</strong></td>
                                @endif
                                @if (in_array('M', $reporte['filtros']))
                                    <td><strong>Marca</strong></td>
                                @endif
                                @if (in_array('ATAD', $reporte['filtros']))
                                    <td><strong>AT/AD</strong></td>
                                @endif
                                @if (in_array('CONT', $reporte['filtros']))
                                    <td><strong>CONT</strong></td>
                                @endif
                            @endforeach
                            @if (in_array('CI', $reporte['filtros']))
                                <td><strong>I</strong></td>
                            @endif
                            @if (in_array('CF', $reporte['filtros']))
                                <td><strong>F</strong></td>
                            @endif
                            @if (in_array('P', $reporte['filtros']))
                                <td><strong>P</strong></td>
                            @endif
                            <td><strong>C. Tubo</strong></td>
                            <td><strong>T. AT</strong></td>
                            <td><strong>T. AD</strong></td>
                            <td><strong>Multa</strong></td>
                        </tr>
                        @foreach ($reporte['despachos'] as $despacho)
                            <tr>
                                <td>{{ $despacho->fecha->addHours(5) }}</td>
                                @if(in_array('C', $reporte['filtros']))
                                    <td>{{ ($despacho->conductor != null)?$despacho->conductor->nombre:'-' }}</td>
                                @endif
                                @foreach ($despacho->puntos_control as $punto_control)
                                    @if (in_array('R', $reporte['filtros']))
                                        <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
                                    @endif
                                    @if (in_array('M', $reporte['filtros']))
                                        <td>{{ (isset($punto_control['marca']))?DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i'):'-' }}</td>
                                    @endif
                                    @if (in_array('ATAD', $reporte['filtros']))
                                        <td>{{ (!isset($punto_control['tiempo_atraso']))? '-' . (!isset($punto_control['tiempo_adelanto'])?'':$punto_control['tiempo_adelanto']):'+' . $punto_control['tiempo_atraso'] }}</td>
                                    @endif
                                    @if (in_array('CONT', $reporte['filtros']))
                                        <td>{{ (!isset($punto_control['contador_marca']))?'-':$punto_control['contador_marca'] }}</td>
                                    @endif
                                @endforeach
                                @if (in_array('CI', $reporte['filtros']))
                                    <td>{{ $despacho->contador_inicial }}</td>
                                @endif
                                @if (in_array('CF', $reporte['filtros']))
                                    <td>{{ $despacho->contador_final }}</td>
                                @endif
                                @if (in_array('P', $reporte['filtros']))
                                    <td>{{ intval($despacho->contador_final - $despacho->contador_inicial) }}</td>
                                @endif
                                <td>{{ $despacho->corte_tubo }}</td>
                                @php
                                    $atrasos = 0;
                                    $adelantos = 0;
                                    foreach ($despacho->puntos_control as $punto_control) {
                                        if (isset($punto_control['tiempo_atraso'])) 
                                            $atrasos += $punto_control['intervalo'] * floatval($punto_control['atraso']);
                                        else if (isset($punto_control['tiempo_adelanto']))
                                            $adelantos += ($punto_control['intervalo'] * -1) * floatval($punto_control['adelanto']);
                                    }
                                @endphp
                                <td>{{ $atrasos }}</td>
                                <td>{{ $adelantos }}</td>
                                <td>{{ $despacho->multa }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        @else 
            <strong>No hay resultados que mostrar.</strong>
        @endif
    </body>
</html>