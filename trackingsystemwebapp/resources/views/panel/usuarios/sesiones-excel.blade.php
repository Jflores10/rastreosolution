<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sesiones</title>
</head>
<body>
    <h1>Reporte de sesiones</h1>
    <h2>Desde: {{ $desde->format('d/m/Y') }} Hasta: {{ $hasta->format('d/m/Y') }}</h2>
    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>IP</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sesiones as $sesion)
                @if ($sesion->usuario !== null)
                    <tr>
                        <td>{{ $sesion->usuario->name }} ({{ $sesion->usuario->email }})</td>
                        <td>{{ $sesion->fecha_sesion }}</td>
                        <td>{{ $sesion->direccion_ip }}</td>
                        <td>{{ $sesion->conexion }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="4">
                        <strong>No se ha encontrado resultados.</strong>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>