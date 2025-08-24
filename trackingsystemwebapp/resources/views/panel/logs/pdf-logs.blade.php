<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Logs</title>
</head>
<body>
    <h1>Registro de logs desde {{ $desde }} hasta {{ $hasta }}</h1>
    <h2>Generado a la fecha: {{ date('Y-m-d H:i:s') }}</h2>
    <h3>Categoría de los errores: {{ ($tipo == 'T')?'Geolocalización ATM':(($tipo == 'D')?'Despachos, rutas y puntos de control ATM':'Puntos virtuales') }}</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Fecha de error</th>
                <th>Mensaje/Trama</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $item)
                <tr>
                    <td>
                        @if($tipo == 'D')
                            {{ $item->fecha }}
                        @elseif($tipo == 'T' || $tipo == 'V')
                            {{ $item->fecha_error }}
                        @endif
                    </td>
                    <td>
                        @if($tipo == 'D')
                            {{ $item->mensaje }}
                        @elseif($tipo == 'T' || $tipo == 'V')
                            {{ $item->Trama }}
                        @endif
                    </td>
                    <td>
                        @if($tipo == 'D')
                            {{ $item->localizacion }}
                        @elseif($tipo == 'T' || $tipo == 'V')
                            {{ $item->Error }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        <div class="alert alert-info">
                            <strong>No hubo errores en el rango seleccionado</strong>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>