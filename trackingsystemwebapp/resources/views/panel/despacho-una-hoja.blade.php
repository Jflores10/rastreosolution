<!DOCTYPE>
<html>
    <head>
        <title>Reporte</title>
        <meta charset="utf-8" />
    </head>
    <body>
        @if ($despachos->count() > 0)
            <table>
                <tr>
                    <th>Ítem</th>
                    <th>Unidad</th>
                    <th>Conductor</th>
                    <th>Asignación</th>
                    <th>Salida</th>
                </tr>
                <?php $index = 1; ?>
                @foreach($despachos as $despacho)
                    <tr>
                        <td>{{ $index++ }}</td>
                        <td>{{ ($despacho->unidad == null)?'-':$despacho->unidad->descripcion }}</td>
                        <td>{{ ($despacho->conductor == null)?'-':$despacho->conductor->nombre }}</td>
                        <td>{{ $despacho->created_at }}</td>
                        <td>{{ $despacho->salida }}</td>
                    </tr>
                @endforeach
            </table>
        @else
        <b>No se encontró resultados.</b>
        @endif
    </body>
</html>