<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Unidades</title>
    </head>
    <body>
        <h1>Reporte de unidades a la fecha {{ date('Y-m-d H:i:s') }}</h1>
        @if (Auth::user()->cooperativa != null)
            <h2>{{ Auth::user()->cooperativa->descripcion }}</h2>
        @endif
        <table>
            <thead>
                <tr>
                    @if(Auth::user()->cooperativa == null)
                        <th>Cooperativa</th>
                    @endif
                    <th>Tipo de unidad</th>
                    <th>Placa</th>
                    <th>IMEI</th>
                    <th>Descripción</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Motor</th>
                    <th>Estado</th>
                    <th>Contador diario</th>
                    <th>Contador total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unidades as $unidad)
                    <tr>
                        @if(Auth::user()->cooperativa == null)
                            <td>{{ ($unidad->cooperativa != null)?$unidad->cooperativa->descripcion:'-' }}</td>
                        @endif
                        <td>{{ ($unidad->tipo_unidad != null)?$unidad->tipo_unidad->descripcion:'-' }}</td>
                        <td>{{ $unidad->placa }}</td>
                        <td>'{{$unidad->imei }}'</td>
                        <td>{{ $unidad->descripcion }}</td>
                        <td>{{ $unidad->marca }}</td>
                        <td>{{ $unidad->modelo }}</td>
                        <td>{{ $unidad->serie }}</td>
                        <td>{{ $unidad->motor }}</td>
                        <td>{{ $unidad->estado }}</td>
                        <td>{{ $unidad->contador_diario }}</td>
                        <td>{{ $unidad->contador_total}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="(Auth::user()->cooperativa == null)?11:10"><strong>No hay unidades para mostrar.</strong></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>