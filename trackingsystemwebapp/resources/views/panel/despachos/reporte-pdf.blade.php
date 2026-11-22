<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Despachos {{ $titulo }}</title>
    <style>
        @page { margin: 28px 24px 36px 24px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 6px 0; color: #2a3f54; }
        .encabezado { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .encabezado td { padding: 2px 0; font-size: 9.5px; }
        .encabezado .etiqueta { font-weight: bold; width: 90px; }
        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th { background: #2a3f54; color: #fff; font-weight: bold; padding: 5px 4px; text-align: left; }
        table.datos td { padding: 4px; border-bottom: 1px solid #d9dee5; vertical-align: top; }
        table.datos tr:nth-child(even) td { background: #f4f6f9; }
        table.datos thead { display: table-header-group; }
        table.datos tr { page-break-inside: avoid; }
        .centro { text-align: center; }
        .vacio { text-align: center; padding: 14px; color: #666; }
        .pie { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 8px; color: #777; }
    </style>
</head>
<body>
    <div class="pie">Generado el {{ date('Y-m-d H:i:s') }} por {{ $usuario }}</div>

    <h1>Reporte de despachos - {{ $titulo }}</h1>
    <table class="encabezado">
        <tr>
            <td class="etiqueta">Cooperativa:</td>
            <td>{{ $cooperativa->descripcion }}</td>
            <td class="etiqueta">Total:</td>
            <td>{{ $despachos->count() }} despacho(s)</td>
        </tr>
        <tr>
            <td class="etiqueta">Desde:</td>
            <td>{{ $desde }}</td>
            <td class="etiqueta">Hasta:</td>
            <td>{{ $hasta }}</td>
        </tr>
        @if ($soloErrorAtm)
            <tr>
                <td class="etiqueta">Filtro:</td>
                <td colspan="3">Solo despachos con error ATM</td>
            </tr>
        @endif
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th class="centro">#</th>
                <th>Unidad</th>
                <th>Ruta</th>
                <th>Conductor</th>
                <th>Asignación</th>
                <th>Salida</th>
                <th>Usuario CR.</th>
                <th>Usuario FN.</th>
                <th>Usuario RE.</th>
                @if ($tipo === 'F')
                    <th>Inicio</th>
                    <th>Fin</th>
                @endif
                @if ($tipo === 'C')
                    <th>Fecha Cancelación</th>
                    <th>Motivo</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($despachos as $i => $despacho)
                <tr>
                    <td class="centro">{{ $i + 1 }}</td>
                    <td>{{ $despacho->unidad == null ? 'No disponible' : $despacho->unidad->descripcion }}</td>
                    <td>{{ $despacho->ruta == null ? 'No disponible' : $despacho->ruta->descripcion }}</td>
                    <td>{{ $despacho->conductor == null ? 'No disponible' : $despacho->conductor->nombre }}</td>
                    <td>{{ $despacho->created_at }}</td>
                    <td>{{ $despacho->fecha != null ? $despacho->fecha->copy()->addHours(5) : '' }}</td>
                    <td>{{ $despacho->creador != null ? $despacho->creador->name : '' }}</td>
                    <td>{{ $despacho->user_end != null ? $despacho->user_end->name : '' }}</td>
                    <td>{{ $despacho->user_recalculo != null ? $despacho->user_recalculo->name : '' }}</td>
                    @if ($tipo === 'F')
                        <td>{{ $despacho->contador_inicial }}</td>
                        <td>{{ $despacho->contador_final }}</td>
                    @endif
                    @if ($tipo === 'C')
                        <td>{{ $despacho->updated_at != null ? $despacho->updated_at : '' }}</td>
                        <td>{{ $despacho->motivo_cancelar != null ? $despacho->motivo_cancelar : '' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td class="vacio" colspan="{{ $tipo === 'L' ? 9 : 11 }}">No hay despachos con los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
