<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Lista de usuarios</title>
    </head>
    <body>
        <h1>Reporte de usuarios generado a la fecha {{ date('Y-m-d H:i:s') }}</h1>
        @if (Auth::user()->cooperativa != null)
            <h2>{{ Auth::user()->cooperativa->descripcion }}</h2>
        @endif
        <table>
            <thead>
                <tr>
                    @if(Auth::user()->cooperativa == null)
                        <th>Cooperativa</th>
                    @endif
                    <th>Nombre(s) y Apellido(s)</th>
                    <th>E-mail</th>
                    <th>Tipo de usuario</th>
                    <th>Unidades pertenecientes</th>
                    <th>Estado</th>
                    <th>Fecha de creación</th>
                    <th>Fecha de modificación</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        @if(Auth::user()->cooperativa == null)
                            <td>{{ ($usuario->cooperativa != null)?$usuario->cooperativa->descripcion:'-' }}</td>
                        @endif
                        <td>{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->tipo_usuario->descripcion }}</td>
                        <td>
                            @php
                                $unidades = null;
                                if (is_array($usuario->unidades_pertenecientes))
                                    $unidades = App\Unidad::whereIn('_id', $usuario->unidades_pertenecientes)->get();
                            @endphp
                            @if (isset($unidades))
                                @forelse ($unidades as $key => $unidad)
                                    {{ (($key == 0)?'':', ') . $unidad->placa }}
                                @empty
                                    -
                                @endforelse
                            @else 
                                -
                            @endif
                        </td>
                        <td>
                            {{ ($usuario->estado == 'A')?'Activo(a)':'Inactivo(a)' }}
                        </td>
                        <td>{{ $usuario->created_at }}</td>
                        <td>{{ $usuario->updated_at }}</td>
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