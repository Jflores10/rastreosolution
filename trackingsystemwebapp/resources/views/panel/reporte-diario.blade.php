<!DOCTYPE>
<html>
    <head>
        <title>{{ $title }}</title>
        <meta charset="utf-8" />
    </head>
    <body>
        <table>
            <tr>
                <td colspan="{{ (3 + (count($puntos_control) * 2)) }}">
                    <b>{{ $ruta->cooperativa->descripcion }}</b>
                </td>
                <td><b>Fecha de impresión</b></td>
                <td>{{ date('Y-m-d') }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <td><b>Fecha</b></td>
                <td><b>Disco</b></td>
                @foreach ($puntos_control as $punto)
                    <td colspan="2">
                        <b>{{ $punto->descripcion }}</b>
                    </td>
                @endforeach
                <td><b>Atraso</b></td>
                <td><b>Adelanto</b></td>
                <td><b>Cobro</b></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                @foreach ($puntos_control as $punto)
                    <td><b>AT</b></td>
                    <td><b>AD</b></td>
                @endforeach
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @php
                $totalAtrasos = 0;
                $totalAdelantos = 0;
                $total = 0;
                $totalMulta = 0;
            @endphp
            @foreach ($despachos as $despacho)
                <tr>
                    <td>{{ $despacho->fecha->addHours(5) }}</td>
                    <td>{{ ($despacho->unidad != null)?$despacho->unidad->descripcion:'-' }}</td>
                    @foreach ($despacho->puntos_control as $punto_control)
                        <td>{{ (isset($punto_control['tiempo_atraso']))?explode(':', $punto_control['tiempo_atraso'])[1]:'0' }}</td>
                        <td>{{ (isset($punto_control['tiempo_adelanto']))?explode(':', $punto_control['tiempo_adelanto'])[1]:'0' }}</td>
                    @endforeach
                    @php 
                        $atrasos = 0;
                        $adelantos = 0;
                        foreach ($despacho->puntos_control as $punto_control)
                        {
                            if (isset($punto_control['tiempo_atraso']))
                                $atrasos+= ((float) explode(':', $punto_control['tiempo_atraso'])[1]);
                            else if (isset($punto_control['tiempo_adelanto']))
                                $adelantos+= ((float) explode(':', $punto_control['tiempo_adelanto'])[1]);
                        }
                        $totalAdelantos += $adelantos;
                        $totalAtrasos += $atrasos;
                        $totalMulta+= $despacho->multa;
                        $total += ($adelantos + $atrasos);
                    @endphp
                    <td>{{ $atrasos }}</td>
                    <td>{{ $adelantos }}</td>
                    <td>{{ (isset($despacho->multa))?$despacho->multa:0 }}</td>
                </tr>
            @endforeach
            @if ($despachos->count() === 0)
                <tr>
                    <td colspan="{{ (5 + (count($puntos_control) * 2)) }}">
                        No hay despachos.
                    </td>
                </tr>
            @endif
        </table>
        <table>
            <tr>
                <td><b>Totales</b></td>
                <td><b>Atrasos: </b>{{ $totalAtrasos }}</td>
                <td><b>Adelantos: </b>{{ $totalAdelantos }}</td>
                <td><b>Total: </b>{{ $total }}</td>
                <td><b>Total de multa: </b>{{ round($totalMulta, 2) }}</td>
            </tr>
        </table>
    </body>
</html>