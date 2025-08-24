<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de sorteos</title>
</head>
<body>
    @php
        $dias =  $desde->diffInDays($hasta);
        $columnas = $dias + 3;
        $horas = array();
        foreach ($sorteos as $sorteo) 
            foreach ($sorteo->sorteos as $s)
                $horas = array_merge($horas, collect($s->unidades)->pluck('hora')->all());
        $horas = collect($horas)->unique()->values()->all();
        $horas = collect($horas)->sortBy(function ($hora, $key) {
            $carbon = new Carbon\Carbon(date('Y-m-d ' . $hora));
            return intval($carbon->format('YmdHi'));
        })->values()->all();
        $fechas = array();
        $fecha = clone $desde;
        for ($i = 0 ; $i <= $dias; $i++)
        {
            array_push($fechas, $fecha);
            $fecha = (clone $fecha)->addDay();
        }
    @endphp
    <table>
        <tbody>
            @if (isset($cooperativa))
                <tr>
                    <td colspan="{{ $columnas }}"><h2>{{ $cooperativa->descripcion }}</h2></td>
                </tr>
            @endif
            @if(isset($cabecera))
                <tr>
                    <td colspan="{{ $columnas }}"><h3>{{ $cabecera }}</h3></td>
                </tr>
            @endif
            <tr>
                <td colspan="{{ $columnas }}">SEMANA DEL {{ $desde->format('d/m/Y') }} HASTA {{ $hasta->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Puesto</strong></td>
                <td><strong>Hora</strong></td>
                @foreach ($fechas as $fecha)
                    <td><strong>{{ $fecha->format('d/m/Y') }}</strong></td>
                @endforeach
            </tr>
            @php
                $puesto = 1;
            @endphp
            @foreach ($horas as $hora)
                <tr>
                    <td>{{ $puesto++ }}</td>
                    <td>{{ $hora }}</td>
                    @foreach ($fechas as $fecha)
                        <td>
                            <ul>
                                @php
                                    $horaMarcada = false;
                                @endphp
                                @foreach ($sorteos as $s)
                                    @foreach ($s->sorteos as $sorteo)
                                        @if ($s->fecha->equalTo($fecha))
                                            @foreach ($sorteo->unidades as $unidad)
                                                @if ($unidad['hora'] === $hora)
                                                    <li>{{ $horaMarcada?', ':'' }} {{ $unidad['descripcion'] }}</li>
                                                    @php
                                                        $horaMarcada = true;
                                                    @endphp
                                                @endif
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endforeach
                                @if (!$horaMarcada)
                                    <li>-</li>
                                @endif
                            </ul>
                        </td>
                        
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ $columnas }}">
                    <strong>LLEGAR 15 MINUTOS ANTES DE LA HORA DE SALIDA</strong>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>