<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Rutas</title>
        <style>
            th, td {
                white-space: nowrap;
                padding : 5px;
                text-align: center;
                vertical-align: middle;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    @if (Auth::user()->cooperativa == null)
                        <th>Cooperativa</th>
                    @endif
                    <th>Cod. Ruta</th>
                    <th>Nombre de la ruta</th>
                    <th>Puntos Control</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rutas as $ruta)
                    <tr>
                        @if (Auth::user()->cooperativa == null)
                            <td >{{ ($ruta->cooperativa != null)?$ruta->cooperativa->descripcion:'-' }}</td>
                        @endif
                        <td >{{ $ruta->codigo }}</td>
                        <td>{{ $ruta->descripcion }}</td>
                        <td>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Secuencia</th>
                                        <th>Punto de control</th>
                                        <th>Tiempo de llegada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ruta->puntos as $punto)
                                        <tr>
                                            <td>{{ $punto['secuencia'] }}</td>
                                            <td>{{ $punto['puntoControl'] !== null ?$punto['puntoControl']->descripcion:'N/A' }}</td>
                                            <td>{{ $punto['tiempo_llegada'] !== null ?$punto['tiempo_llegada']:'0' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <strong>No tiene puntos de control.</strong>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="(Auth::user()->cooperativa == null)?7:6">
                            <strong>No hay rutas por mostrar.</strong>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>