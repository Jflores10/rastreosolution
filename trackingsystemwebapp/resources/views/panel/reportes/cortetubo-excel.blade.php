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
                <th>Conductor</th>
                <th>Fecha Despacho</th>
                <th>Ruta</th>
                <th>Ubicación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recorridos as $recorrido)
                <tr>
                <td>{{$recorrido->despacho->unidad->descripcion }}</td>
                <td>{{$recorrido->despacho->conductor->nombre }}</td>
                <td>{{ (new Carbon\Carbon($recorrido->despacho->fecha->format('Y-m-d H:i:s')))->addHours(5) }}</td>
                <td>{{$recorrido->despacho->ruta->descripcion }}</td>
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