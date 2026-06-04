@extends('layouts.app')
@section('title')
Estadísticas contador diario
@endsection
@section('styles')
<style>
    .stats-chart-wrap {
        position: relative;
        width: 100%;
        height: 420px;
        overflow: hidden;
    }
    .stats-chart-wrap--participacion {
        height: 280px;
        min-height: 280px;
        overflow: hidden;
    }
    .stats-chart-wrap canvas {
        display: block;
        max-width: 100%;
    }
    .stats-meta-info {
        font-size: 12px;
        color: #73879C;
        margin-top: 8px;
    }
    .stats-filtros-row .form-group {
        margin-bottom: 12px;
    }
    .stats-filtros-row .stats-filtros-col {
        padding-left: 15px;
        padding-right: 15px;
    }
    @media (min-width: 992px) {
        .stats-filtros-row .stats-filtros-col--fechas {
            border-left: 1px solid #e8e8e8;
        }
    }
    .stats-filtros-row .stats-filtros-botones {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    .stats-filtros-row .stats-filtros-botones .btn {
        margin: 0;
    }
    .stats-filtros-row .stats-filtros-seccion {
        margin-bottom: 16px;
    }
    .stats-filtros-row .stats-filtros-seccion-titulo {
        font-weight: 600;
        margin-bottom: 8px;
        color: #555;
    }
    .stats-filtros-row .select2-container {
        width: 100% !important;
        max-width: 100%;
    }
    .stats-filtros-row .stats-filtros-acciones-derecha {
        text-align: right;
        margin-top: 16px;
    }
    #reloj {
        display: none !important;
    }
</style>
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-bar-chart"></i> Estadísticas — contador diario</h3>
    </div>
</div>
<div class="clearfix"></div>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Filtros</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content stats-filtros-row">
                <div class="row">
                    <div class="col-md-6 col-sm-12 stats-filtros-col">
                        <div class="form-group">
                            <label>Cooperativa</label>
                            @if(Auth::user()->tipo_usuario->valor == 1)
                                <select class="form-control select2" id="stats_cooperativa" data-placeholder="Seleccione cooperativa...">
                                    <option value=""></option>
                                    @foreach ($cooperativas as $coop)
                                        <option value="{{ $coop->_id }}">{{ $coop->descripcion }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" id="stats_cooperativa" value="{{ Auth::user()->cooperativa_id }}">
                                <p class="form-control-static" style="margin:0; padding-top:6px;"><strong>{{ $cooperativa ? $cooperativa->descripcion : '' }}</strong></p>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Unidades</label>
                            <select class="form-control select2" id="stats_unidades" multiple data-placeholder="Seleccione una o más unidades..."></select>
                        </div>

                        <div class="stats-filtros-botones">
                            <button type="button" class="btn btn-default btn-sm" onclick="statsSeleccionarTodas();">Todas</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="statsLimpiarSeleccion();">Ninguna</button>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-12 stats-filtros-col stats-filtros-col--fechas">
                        <div class="stats-filtros-seccion" style="margin-bottom:0;">
                            <div class="stats-filtros-seccion-titulo">Rango de fechas</div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group" style="margin-bottom:8px;">
                                        <label>Desde</label>
                                        <input type="text" class="form-control" id="stats_fecha_inicio" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group" style="margin-bottom:8px;">
                                        <label>Hasta</label>
                                        <input type="text" class="form-control" id="stats_fecha_fin" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                            <div class="stats-filtros-botones" style="margin-bottom:0;">
                                <button type="button" class="btn btn-default btn-sm" onclick="statsEstablecerFechaHoy();">Hoy</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="statsEstablecerFechaAyer();">Ayer</button>
                            </div>
                            <div class="stats-filtros-acciones-derecha">
                                <button type="button" class="btn btn-primary" onclick="statsAplicarGrafico();">
                                    <i class="fa fa-refresh"></i> Actualizar gráficos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Contador diario por unidad</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="stats-chart-wrap">
                    <canvas id="statsChartContadorDiario"></canvas>
                </div>
                <p class="stats-meta-info" id="statsUltimaActualizacion">Seleccione cooperativa y unidades para ver los gráficos.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Participación por unidad (%)</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <p class="text-muted" style="margin-bottom:12px;">Porcentaje del contador diario de cada unidad .</p>
                <div class="stats-chart-wrap stats-chart-wrap--participacion" id="stats-participacion-wrap">
                    <canvas id="statsChartParticipacion"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var chartContadorDiario = null;
var chartParticipacion = null;
var statsUnidadesCatalogo = {};
var statsRefreshTimer = null;
var statsRefreshEnCurso = false;
var statsRefreshXhr = null;
var statsParticipacionBarCount = 0;
var STATS_REFRESH_MS = 30000;
var statsUltimoRango = { un_solo_dia: true, es_hoy: true };

function contadorDiarioDispDesdeUnidad(unidad) {
    var contador_diario_disp = Number(unidad && unidad.contador_diario);
    if (isNaN(contador_diario_disp)) contador_diario_disp = 0;
    return contador_diario_disp;
}

function statsDestroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
    return null;
}

function statsResetParticipacionWrapHeight(barCount) {
    var wrap = document.getElementById('stats-participacion-wrap');
    if (!wrap) return;
    var h = Math.max(280, (barCount || 0) * 32);
    wrap.style.height = h + 'px';
}

function statsClearCanvasAfterDestroy(canvas) {
    if (!canvas) return null;
    var parent = canvas.parentNode;
    if (!parent) return canvas;
    var nuevoCanvas = document.createElement('canvas');
    nuevoCanvas.id = canvas.id;
    parent.replaceChild(nuevoCanvas, canvas);
    return nuevoCanvas;
}

function statsOrdenarItemsMayorMenor(items) {
    return items.slice().sort(function (a, b) {
        return (b.value || 0) - (a.value || 0);
    });
}

function statsGetCooperativaId() {
    var el = document.getElementById('stats_cooperativa');
    return el ? String(el.value || '').trim() : '';
}

function statsFormatoFechaRelativa(diasOffset, inicioDelDia) {
    var date = new Date();
    date.setDate(date.getDate() + (diasOffset || 0));
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    if (inicioDelDia) {
        return y + '/' + m + '/' + d + ' 00:00';
    }
    return y + '/' + m + '/' + d + ' 23:59';
}

function statsFormatoFechaHoy(inicioDelDia) {
    return statsFormatoFechaRelativa(0, inicioDelDia);
}

function statsEstablecerFechaHoy() {
    $('#stats_fecha_inicio').val(statsFormatoFechaRelativa(0, true));
    $('#stats_fecha_fin').val(statsFormatoFechaRelativa(0, false));
}

function statsEstablecerFechaAyer() {
    $('#stats_fecha_inicio').val(statsFormatoFechaRelativa(-1, true));
    $('#stats_fecha_fin').val(statsFormatoFechaRelativa(-1, false));
}

function statsEsRangoHoy() {
    var hoyIni = statsFormatoFechaHoy(true).substring(0, 10);
    var hoyFin = statsFormatoFechaHoy(false).substring(0, 10);
    var ini = ($('#stats_fecha_inicio').val() || '').substring(0, 10).replace(/-/g, '/');
    var fin = ($('#stats_fecha_fin').val() || '').substring(0, 10).replace(/-/g, '/');
    return ini === hoyIni && fin === hoyFin;
}

function statsObtenerContadorDiario(uid) {
    uid = String(uid);
    var cat = statsUnidadesCatalogo[uid];
    if (cat) return contadorDiarioDispDesdeUnidad(cat);
    return 0;
}

function statsOnCooperativaChange() {
    statsDetenerRefresco();
    statsCargarUnidadesCooperativa(statsGetCooperativaId());
}

function statsCargarUnidadesCooperativa(cooperativaId, callback) {
    if (!cooperativaId) {
        $('#stats_unidades').empty().trigger('change');
        if (typeof callback === 'function') callback();
        return;
    }
    $.post('{{ url('/historico/unidades-estadisticas') }}', {
        cooperativa_id: cooperativaId
    }, function (data) {
        statsUnidadesCatalogo = {};
        var $sel = $('#stats_unidades');
        $sel.empty();
        if (data && data.unidades && data.unidades.length) {
            var i, u, uid;
            for (i = 0; i < data.unidades.length; i++) {
                u = data.unidades[i];
                uid = String(u._id);
                statsUnidadesCatalogo[uid] = {
                    id: uid,
                    descripcion: u.descripcion || uid,
                    contador_diario: 0
                };
                $sel.append($('<option></option>').attr('value', uid).text(u.descripcion || uid));
            }
        }
        $sel.trigger('change');
        if (typeof callback === 'function') callback();
    }, 'json').fail(function () {
        $('#stats_unidades').empty().trigger('change');
        if (typeof callback === 'function') callback();
    });
}

function statsSeleccionarTodas() {
    $('#stats_unidades').val(Object.keys(statsUnidadesCatalogo)).trigger('change');
}

function statsLimpiarSeleccion() {
    $('#stats_unidades').val(null).trigger('change');
}

function statsDetenerRefresco() {
    if (statsRefreshTimer) {
        clearInterval(statsRefreshTimer);
        statsRefreshTimer = null;
    }
}

function statsIniciarRefresco() {
    if (statsRefreshTimer || !statsEsRangoHoy()) {
        return;
    }
    if (document.visibilityState === 'hidden') {
        return;
    }
    statsRefreshTimer = setInterval(function () {
        if (document.visibilityState === 'hidden') {
            return;
        }
        statsRefrescarDatos(true);
    }, STATS_REFRESH_MS);
}

function statsAsegurarRefresco() {
    if (!statsEsRangoHoy()) {
        statsDetenerRefresco();
        return;
    }
    statsIniciarRefresco();
}

function statsAplicarGrafico() {
    var ids = $('#stats_unidades').val();
    if (!ids || !ids.length) {
        document.getElementById('statsUltimaActualizacion').textContent = 'Seleccione al menos una unidad.';
        chartContadorDiario = statsDestroyChart(chartContadorDiario);
        chartParticipacion = statsDestroyChart(chartParticipacion);
        statsParticipacionBarCount = 0;
        statsResetParticipacionWrapHeight(0);
        statsDetenerRefresco();
        return;
    }
    statsRefrescarDatos(false);
}

function statsConstruirItemsSeleccion(ids) {
    var items = [];
    var i, uid, cat, val;
    for (i = 0; i < ids.length; i++) {
        uid = String(ids[i]);
        cat = statsUnidadesCatalogo[uid];
        val = statsObtenerContadorDiario(uid);
        items.push({
            label: cat ? cat.descripcion : uid,
            value: val
        });
    }
    return items;
}

function statsRefrescarDatos(esPolling) {
    var ids = $('#stats_unidades').val();
    if (!ids || !ids.length) return;

    if (esPolling) {
        if (!statsEsRangoHoy()) {
            statsDetenerRefresco();
            return;
        }
        if (statsRefreshEnCurso) {
            return;
        }
    }

    var coopId = statsGetCooperativaId();
    var fechaInicio = $('#stats_fecha_inicio').val();
    var fechaFin = $('#stats_fecha_fin').val();

    if (!fechaInicio || !fechaFin) {
        if (!esPolling) {
            document.getElementById('statsUltimaActualizacion').textContent = 'Indique el rango de fechas.';
        }
        return;
    }

    if (!coopId) {
        if (!esPolling) {
            document.getElementById('statsUltimaActualizacion').textContent = 'Seleccione una cooperativa.';
        }
        return;
    }

    if (!esPolling && statsRefreshXhr) {
        statsRefreshXhr.abort();
        statsRefreshXhr = null;
        statsRefreshEnCurso = false;
    }

    statsRefreshEnCurso = true;
    statsRefreshXhr = $.ajax({
        url: '{{ url('/historico/estadisticas-contador-diario') }}',
        type: 'POST',
        dataType: 'json',
        data: {
            cooperativa_id: coopId,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            unidad_ids: ids
        }
    }).done(function (data) {
        if (data && data.unidades) {
            var i, u, uid;
            for (i = 0; i < data.unidades.length; i++) {
                u = data.unidades[i];
                uid = String(u._id);
                if (!statsUnidadesCatalogo[uid]) {
                    statsUnidadesCatalogo[uid] = { id: uid, descripcion: u.descripcion || uid, contador_diario: 0 };
                }
                statsUnidadesCatalogo[uid].contador_diario = contadorDiarioDispDesdeUnidad(u);
                statsUnidadesCatalogo[uid].descripcion = u.descripcion || uid;
            }
        }
        statsUltimoRango = {
            un_solo_dia: data ? !!data.un_solo_dia : true,
            es_hoy: data ? !!data.es_hoy : statsEsRangoHoy()
        };
        var items = statsConstruirItemsSeleccion(ids);
        statsRenderChartContador(items, !!esPolling);
        statsRenderChartParticipacion(items, !!esPolling);
        if (!esPolling) {
            var msg = 'Última actualización: ' + new Date().toLocaleTimeString('es-EC');
            msg += ' | ' + fechaInicio + ' — ' + fechaFin;
            if (statsUltimoRango.un_solo_dia) {
                msg += statsUltimoRango.es_hoy ? ' (hoy, en vivo)' : ' (máx. del día)';
            } else {
                msg += ' (suma máx. diario del período)';
            }
            if (statsEsRangoHoy()) {
                msg += ' · refresco cada ' + (STATS_REFRESH_MS / 1000) + ' s (solo con la pestaña visible)';
            }
            document.getElementById('statsUltimaActualizacion').textContent = msg;
        }
        if ($('#stats_unidades').val() && $('#stats_unidades').val().length) {
            statsAsegurarRefresco();
        }
    }).fail(function (jqXHR, textStatus) {
        if (textStatus === 'abort') {
            return;
        }
        if (!esPolling) {
            document.getElementById('statsUltimaActualizacion').textContent = 'Error al consultar contadores.';
        }
    }).always(function () {
        statsRefreshEnCurso = false;
        statsRefreshXhr = null;
    });
}

function statsRenderChartContador(items, esPolling) {
    items = statsOrdenarItemsMenorMayor(items);
    var labels = [];
    var values = [];
    var i;
    for (i = 0; i < items.length; i++) {
        labels.push(items[i].label);
        values.push(items[i].value);
    }

    var canvas = document.getElementById('statsChartContadorDiario');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');

    var labelY = 'Contador diario';
    if (statsUltimoRango && !statsUltimoRango.un_solo_dia) {
        labelY = 'Contador (suma del período)';
    }

    if (chartContadorDiario) {
        chartContadorDiario.data.labels = labels;
        chartContadorDiario.data.datasets[0].data = values;
        chartContadorDiario.data.datasets[0].label = labelY;
        chartContadorDiario.options.scales.yAxes[0].scaleLabel.labelString = labelY;
        chartContadorDiario.update(esPolling ? { duration: 0 } : undefined);
        if (!esPolling) {
            chartContadorDiario.resize();
        }
        return;
    }

    chartContadorDiario = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: labelY,
                data: values,
                backgroundColor: 'rgba(42, 98, 188, 0.75)',
                borderColor: 'rgba(42, 98, 188, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: true },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem) {
                        return ' Contador diario: ' + tooltipItem.yLabel;
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true, precision: 0 },
                    scaleLabel: { display: true, labelString: 'Contador diario' }
                }],
                xAxes: [{
                    ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 },
                    scaleLabel: { display: true, labelString: 'Unidades' }
                }]
            }
        }
    });
}

function statsRenderChartParticipacion(items, esPolling) {
    items = statsOrdenarItemsMenorMayor(items);
    var total = 0;
    var i;
    for (i = 0; i < items.length; i++) {
        total += items[i].value;
    }

    var pctRows = [];
    for (i = 0; i < items.length; i++) {
        pctRows.push({
            label: items[i].label,
            percent: total > 0 ? (items[i].value / total) * 100 : 0,
            raw: items[i].value
        });
    }
    pctRows.sort(function (a, b) {
        return b.raw - a.raw;
    });

    var labels = [];
    var percents = [];
    for (i = 0; i < pctRows.length; i++) {
        labels.push(pctRows[i].label);
        percents.push(Math.round(pctRows[i].percent * 10) / 10);
    }

    var canvas = document.getElementById('statsChartParticipacion');
    if (!canvas) return;

    var barCount = pctRows.length;
    // Rebuild en interacción manual para evitar artefactos de dibujo al cambiar selección.
    var rebuild = !chartParticipacion || barCount !== statsParticipacionBarCount || !esPolling;

    statsResetParticipacionWrapHeight(barCount);

    if (!rebuild) {
        chartParticipacion.data.labels = labels;
        chartParticipacion.data.datasets[0].data = percents;
        chartParticipacion._statsPctRows = pctRows;
        chartParticipacion.update(esPolling ? { duration: 0 } : undefined);
        return;
    }

    statsParticipacionBarCount = barCount;
    chartParticipacion = statsDestroyChart(chartParticipacion);
    canvas = statsClearCanvasAfterDestroy(canvas);
    if (!canvas) return;
    var ctx = canvas.getContext('2d');

    chartParticipacion = new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Participación %',
                data: percents,
                backgroundColor: 'rgba(0, 170, 136, 0.75)',
                borderColor: 'rgba(0, 170, 136, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem) {
                        var idx = tooltipItem.index;
                        var pct = tooltipItem.xLabel;
                        var rows = chartParticipacion._statsPctRows || [];
                        var raw = rows[idx] ? rows[idx].raw : 0;
                        return ' ' + pct + '% (contador: ' + raw + ')';
                    }
                }
            },
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 100,
                        callback: function (v) { return v + '%'; }
                    },
                    scaleLabel: { display: true, labelString: 'Participación %' }
                }],
                yAxes: [{
                    ticks: { autoSkip: false }
                }]
            }
        }
    });
    chartParticipacion._statsPctRows = pctRows;
}

$(document).ready(function () {
    if ($('#stats_cooperativa').is('select')) {
        $('#stats_cooperativa').select2({
            width: '100%',
            placeholder: 'Seleccione cooperativa...',
            allowClear: true
        }).on('change', statsOnCooperativaChange);
    }
    $('#stats_unidades').select2({ width: '100%' });
    $('#stats_fecha_inicio').datetimepicker();
    $('#stats_fecha_fin').datetimepicker();
    statsEstablecerFechaHoy();

    var params = new URLSearchParams(window.location.search);
    var coopParam = params.get('cooperativa_id');
    if (coopParam && $('#stats_cooperativa').is('select')) {
        var $coopSelect = $('#stats_cooperativa');
        $coopSelect.off('change', statsOnCooperativaChange).val(coopParam).trigger('change');
        $coopSelect.on('change', statsOnCooperativaChange);
    }

    var coopId = statsGetCooperativaId();
    if (coopId) {
        statsCargarUnidadesCooperativa(coopId, function () {
            var unidadesParam = params.get('unidades');
            if (unidadesParam) {
                var ids = unidadesParam.split(',').filter(function (x) { return x; });
                if (ids.length) {
                    $('#stats_unidades').val(ids).trigger('change');
                    statsAplicarGrafico();
                }
            }
        });
    }
});

document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
        statsDetenerRefresco();
        return;
    }
    if (statsEsRangoHoy() && $('#stats_unidades').val() && $('#stats_unidades').val().length) {
        statsRefrescarDatos(true);
        statsAsegurarRefresco();
    }
});

$(window).on('beforeunload', function () {
    statsDetenerRefresco();
});
</script>
@endsection
