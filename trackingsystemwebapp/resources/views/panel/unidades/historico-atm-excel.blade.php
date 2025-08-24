<table>
    <thead>
        <tr>
            <th>Placa</th>
            @if($export_date)
                <th>Fecha</th>
            @endif
            <th>Latitud</th>
            <th>Longitud</th>
            <th>Velocidad</th>
            <th>Ubicacion</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($historico as $unidad)
            <tr>
                <td>{{ $unidad['placa'] }}</td>
                @if($export_date)
                    <td>{{ $unidad['fecha_gps'] }}</td>
                @endif
                <td>{{ $unidad['latitud'] }}</td>
                <td>{{ $unidad['longitud'] }}</td>
                <td>{{ $unidad['velocidad'] }}</td>
                <td>{{ $unidad['gps_address'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>