<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Conductores</title>
    </head>
    <body>
        <h1>Reporte de conductores a la fecha {{ date('Y-m-d H:i:s') }}</h1>
        @if (Auth::user()->cooperativa != null)
            <h2>{{ Auth::user()->cooperativa->descripcion }}</h2>
        @endif
        <table>
            <thead>
                <tr>
                    @if (Auth::user()->cooperativa == null)
                        <th>Cooperativa</th>
                    @endif
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Fecha de creación</th>
                    <th>Fecha de modificación</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($conductores as $conductor)
                    <tr>
                        @if (Auth::user()->cooperativa == null)
                            <td>{{ ($conductor->cooperativa != null)?$conductor->cooperativa->descripcion:'-' }}</td>
                        @endif
                        <td>{{ $conductor->nombre }}</td>
                        <td>{{ $conductor->cedula }}</td>
                        <td>{{ $conductor->created_at }}</td>
                        <td>{{ $conductor->updated_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="(Auth::user()->cooperativa == null)?5:4">
                            <strong>No hay conductores por mostrar.</strong>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>