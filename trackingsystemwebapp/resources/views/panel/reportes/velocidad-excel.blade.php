<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte</title>
    <link rel="stylesheet" href="css/excel.css">
</head>
<body>
    <table class="table">
        <thead>
            <tr>
                <th>Unidad</th>
                <th>Fecha de GPS</th>
                <th>Fecha de servidor</th>
                <th>Velocidad</th>
                <th>Ubicación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recorridos as $recorrido)
                <tr>
                    <td>{{ $recorrido->recorrido->unidad->descripcion }}</td>
                    <td>{{ (new Carbon\Carbon($recorrido->recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s')))->subHours(10) }}</td>
                    <td>{{ (new Carbon\Carbon($recorrido->recorrido->fecha->toDateTime()->format('Y-m-d H:i:s')))->subHours(5) }}</td>
                    <td>{{ $recorrido->recorrido->velocidad }}</td>
                    <td>{{$recorrido->Ubicacion }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="alert alert-info">
                            <strong>La consulta no tiene registros.</strong>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>