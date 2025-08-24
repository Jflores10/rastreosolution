<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de puertas</title>
    <link rel="stylesheet" href="css/excel.css">
</head>
<body>
    <div class="row">
        <div class="col-sm-12">
            <h3>Reporte por tiempos</h3>
            @if(count($recorridos) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Tiempo</th>
                                <th>Evento</th>
                                <th>Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recorridos as $recorrido)
                                <tr class="{{ ($recorrido['recorrido']->evento == 'Puerta abierta')?'info':'danger' }}">
                                    <td>{{ $recorrido['recorrido']->unidad->descripcion }}</td>
                                    <td>{{ $recorrido['desde'] }}</td>
                                    <td>{{ $recorrido['hasta'] }}</td>
                                    <td>{{ $recorrido['tiempo'] }}</td>
                                    <td>{{ $recorrido['recorrido']->evento }}</td>
                                    <td>{{ $recorrido['ubicacion'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="alert alert-info">
                                            <strong>La consulta no tiene registros.</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                </div>
            @else 
                <div class="alert alert-info">
                    <strong>No hubo recorridos en la consulta.</strong>
                </div>
            @endif
        </div>
        <div class="col-sm-12">
            <h3>Reporte por unidad</h3>
            <div class="table-responsive">
                @if (count($reportes) > 0)
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>TA Total</th>
                                <th>TC Total</th>
                                <th>CA Total</th>
                                <th>CC Total</th>
                                <th>Prom. Apertura</th>
                                <th>Prom. Cierre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportes as $reporte)
                                <tr>
                                    <td>{{ $reporte['unidad']->descripcion }}</td>
                                    <td>{{ $reporte['mAbiertas'] }} min.</td>
                                    <td>{{ $reporte['mCerradas'] }} min.</td>
                                    <td>{{ $reporte['cantidadAbiertas'] }}</td>
                                    <td>{{ $reporte['cantidadCerradas'] }}</td>
                                    <td>{{ ($reporte['cantidadAbiertas'] === 0)?0:(round($reporte['mAbiertas']/$reporte['cantidadAbiertas'], 2)) }} min.</td>
                                    <td>{{ ($reporte['cantidadCerradas'] === 0)?0:(round($reporte['mCerradas']/$reporte['cantidadCerradas'], 2)) }} min.</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="alert alert-info">
                                            <strong>No hay reportes con la consulta realizada.</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else 
                    <div class="alert alert-info">
                        <strong>No hay reportes que mostrar.</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>