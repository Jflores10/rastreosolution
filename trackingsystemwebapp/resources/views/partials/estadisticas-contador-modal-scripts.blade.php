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
var statsUiInicializado = false;
var statsContextoHome = { cooperativa_id: null, unidades: [] };
var STATS_COLOR_PALETTE = [
    '#2A62BC', '#00AA88', '#F39C12', '#8E44AD', '#E74C3C',
    '#16A085', '#D35400', '#2C3E50', '#27AE60', '#C0392B',
    '#2980B9', '#7F8C8D', '#1ABC9C', '#9B59B6', '#E67E22'
];

function contadorDiarioDispDesdeUnidad(unidad) { var v = Number(unidad && unidad.contador_diario); return isNaN(v) ? 0 : v; }
function statsHexToRgba(hex, alpha) {
    var h = String(hex || '').replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    var r = parseInt(h.substring(0, 2), 16);
    var g = parseInt(h.substring(2, 4), 16);
    var b = parseInt(h.substring(4, 6), 16);
    if (!isFinite(r) || !isFinite(g) || !isFinite(b)) return 'rgba(42,98,188,' + alpha + ')';
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}
function statsColorPorIndice(i, alpha) {
    var c = STATS_COLOR_PALETTE[i % STATS_COLOR_PALETTE.length];
    return statsHexToRgba(c, alpha);
}
function statsDestroyChart(chart) { if (chart) chart.destroy(); return null; }
function statsResetParticipacionWrapHeight(barCount) { var wrap = document.getElementById('stats-participacion-wrap'); if (wrap) wrap.style.height = Math.max(280, (barCount || 0) * 32) + 'px'; }
function statsClearCanvasAfterDestroy(canvas) { if (!canvas) return null; var parent = canvas.parentNode; if (!parent) return canvas; var nuevoCanvas = document.createElement('canvas'); nuevoCanvas.id = canvas.id; parent.replaceChild(nuevoCanvas, canvas); return nuevoCanvas; }
function statsGetCooperativaId() { var el = document.getElementById('stats_cooperativa'); return el ? String(el.value || '').trim() : ''; }
function statsFormatoFechaRelativa(diasOffset, inicioDelDia) { var d = new Date(); d.setDate(d.getDate() + (diasOffset || 0)); var y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0'); return y + '/' + m + '/' + day + (inicioDelDia ? ' 00:00' : ' 23:59'); }
function statsFormatoFechaHoy(inicioDelDia) { return statsFormatoFechaRelativa(0, inicioDelDia); }
function statsEstablecerFechaHoy() { $('#stats_fecha_inicio').val(statsFormatoFechaRelativa(0, true)); $('#stats_fecha_fin').val(statsFormatoFechaRelativa(0, false)); }
function statsEstablecerFechaAyer() { $('#stats_fecha_inicio').val(statsFormatoFechaRelativa(-1, true)); $('#stats_fecha_fin').val(statsFormatoFechaRelativa(-1, false)); }
function statsEsRangoHoy() { var hi = statsFormatoFechaHoy(true).substring(0, 10), hf = statsFormatoFechaHoy(false).substring(0, 10); var ini = ($('#stats_fecha_inicio').val() || '').substring(0, 10).replace(/-/g, '/'); var fin = ($('#stats_fecha_fin').val() || '').substring(0, 10).replace(/-/g, '/'); return ini === hi && fin === hf; }
function statsObtenerContadorDiario(uid) { uid = String(uid); return statsUnidadesCatalogo[uid] ? contadorDiarioDispDesdeUnidad(statsUnidadesCatalogo[uid]) : 0; }
function statsOnCooperativaChange() { statsDetenerRefresco(); statsCargarUnidadesCooperativa(statsGetCooperativaId()); }
function statsCargarUnidadesCooperativa(cooperativaId, callback) {
    if (!cooperativaId) { $('#stats_unidades').empty().trigger('change'); if (typeof callback === 'function') callback(); return; }
    $.post('{{ url('/historico/unidades-estadisticas') }}', { cooperativa_id: cooperativaId }, function (data) {
        statsUnidadesCatalogo = {};
        var $sel = $('#stats_unidades'); $sel.empty();
        if (data && data.unidades && data.unidades.length) {
            for (var i = 0; i < data.unidades.length; i++) { var u = data.unidades[i], uid = String(u._id); statsUnidadesCatalogo[uid] = { id: uid, descripcion: u.descripcion || uid, contador_diario: 0 }; $sel.append($('<option></option>').attr('value', uid).text(u.descripcion || uid)); }
        }
        $sel.trigger('change'); if (typeof callback === 'function') callback();
    }, 'json').fail(function () { $('#stats_unidades').empty().trigger('change'); if (typeof callback === 'function') callback(); });
}
function statsSeleccionarTodas() { $('#stats_unidades').val(Object.keys(statsUnidadesCatalogo)).trigger('change'); }
function statsLimpiarSeleccion() { $('#stats_unidades').val(null).trigger('change'); }
function statsDetenerRefresco() { if (statsRefreshTimer) { clearInterval(statsRefreshTimer); statsRefreshTimer = null; } }
function statsIniciarRefresco() { if (statsRefreshTimer || !statsEsRangoHoy() || !$('#estadisticasContadorModal').hasClass('in')) return; statsRefreshTimer = setInterval(function () { if (!$('#estadisticasContadorModal').hasClass('in')) return; statsRefrescarDatos(true); }, STATS_REFRESH_MS); }
function statsAsegurarRefresco() { if (!statsEsRangoHoy()) { statsDetenerRefresco(); return; } statsIniciarRefresco(); }
function statsAplicarGrafico() { var ids = $('#stats_unidades').val(); if (!ids || !ids.length) { document.getElementById('statsUltimaActualizacion').textContent = 'Seleccione al menos una unidad.'; chartContadorDiario = statsDestroyChart(chartContadorDiario); chartParticipacion = statsDestroyChart(chartParticipacion); statsParticipacionBarCount = 0; statsResetParticipacionWrapHeight(0); statsDetenerRefresco(); return; } statsRefrescarDatos(false); }
function statsConstruirItemsSeleccion(ids) { var items = []; for (var i = 0; i < ids.length; i++) { var uid = String(ids[i]); items.push({ label: statsUnidadesCatalogo[uid] ? statsUnidadesCatalogo[uid].descripcion : uid, value: statsObtenerContadorDiario(uid) }); } return items; }
function statsRefrescarDatos(esPolling) {
    var ids = $('#stats_unidades').val(); if (!ids || !ids.length) return;
    if (esPolling && statsRefreshEnCurso) return;
    var coopId = statsGetCooperativaId(), fechaInicio = $('#stats_fecha_inicio').val(), fechaFin = $('#stats_fecha_fin').val();
    if (!fechaInicio || !fechaFin || !coopId) return;
    if (!esPolling && statsRefreshXhr) { statsRefreshXhr.abort(); statsRefreshXhr = null; statsRefreshEnCurso = false; }
    statsRefreshEnCurso = true;
    statsRefreshXhr = $.ajax({ url: '{{ url('/historico/estadisticas-contador-diario') }}', type: 'POST', dataType: 'json', data: { cooperativa_id: coopId, fecha_inicio: fechaInicio, fecha_fin: fechaFin, unidad_ids: ids } })
        .done(function (data) {
            if (data && data.unidades) {
                for (var i = 0; i < data.unidades.length; i++) {
                    var u = data.unidades[i], uid = String(u._id);
                    if (!statsUnidadesCatalogo[uid]) statsUnidadesCatalogo[uid] = { id: uid, descripcion: u.descripcion || uid, contador_diario: 0 };
                    statsUnidadesCatalogo[uid].contador_diario = contadorDiarioDispDesdeUnidad(u);
                    statsUnidadesCatalogo[uid].descripcion = u.descripcion || uid;
                }
            }
            statsUltimoRango = { un_solo_dia: data ? !!data.un_solo_dia : true, es_hoy: data ? !!data.es_hoy : statsEsRangoHoy() };
            var items = statsConstruirItemsSeleccion(ids);
            statsRenderChartContador(items, !!esPolling);
            statsRenderChartParticipacion(items, !!esPolling);
            if (!esPolling) document.getElementById('statsUltimaActualizacion').textContent = 'Última actualización: ' + new Date().toLocaleTimeString('es-EC') + ' | ' + fechaInicio + ' — ' + fechaFin;
            if ($('#stats_unidades').val() && $('#stats_unidades').val().length) statsAsegurarRefresco();
        }).always(function () { statsRefreshEnCurso = false; statsRefreshXhr = null; });
}
function statsRenderChartContador(items, esPolling) {
    var labels = [], values = []; for (var i = 0; i < items.length; i++) { labels.push(items[i].label); values.push(items[i].value); }
    var bgColors = [], borderColors = [];
    for (var c = 0; c < labels.length; c++) {
        bgColors.push(statsColorPorIndice(c, 0.75));
        borderColors.push(statsColorPorIndice(c, 1));
    }
    var canvas = document.getElementById('statsChartContadorDiario'); if (!canvas) return; var ctx = canvas.getContext('2d');
    if (chartContadorDiario) {
        chartContadorDiario.data.labels = labels;
        chartContadorDiario.data.datasets[0].data = values;
        chartContadorDiario.data.datasets[0].backgroundColor = bgColors;
        chartContadorDiario.data.datasets[0].borderColor = borderColors;
        chartContadorDiario.update(esPolling ? { duration: 0 } : undefined);
        return;
    }
    chartContadorDiario = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Contador diario',
                data: values,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: true },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        min: 0,
                        precision: 0
                    }
                }]
            }
        }
    });
}
function statsRenderChartParticipacion(items, esPolling) {
    var total = 0; for (var i = 0; i < items.length; i++) total += items[i].value;
    var pctRows = []; for (var j = 0; j < items.length; j++) pctRows.push({ label: items[j].label, percent: total > 0 ? (items[j].value / total) * 100 : 0, raw: items[j].value });
    pctRows.sort(function (a, b) { return b.percent - a.percent; });
    var labels = [], percents = []; for (var k = 0; k < pctRows.length; k++) { labels.push(pctRows[k].label); percents.push(Math.round(pctRows[k].percent * 10) / 10); }
    var bgColors = [], borderColors = [];
    for (var pc = 0; pc < labels.length; pc++) {
        bgColors.push(statsColorPorIndice(pc, 0.75));
        borderColors.push(statsColorPorIndice(pc, 1));
    }
    var canvas = document.getElementById('statsChartParticipacion'); if (!canvas) return;
    var barCount = pctRows.length, rebuild = !chartParticipacion || barCount !== statsParticipacionBarCount || !esPolling;
    statsResetParticipacionWrapHeight(barCount);
    if (!rebuild) {
        chartParticipacion.data.labels = labels;
        chartParticipacion.data.datasets[0].data = percents;
        chartParticipacion.data.datasets[0].backgroundColor = bgColors;
        chartParticipacion.data.datasets[0].borderColor = borderColors;
        chartParticipacion.update(esPolling ? { duration: 0 } : undefined);
        return;
    }
    statsParticipacionBarCount = barCount; chartParticipacion = statsDestroyChart(chartParticipacion); canvas = statsClearCanvasAfterDestroy(canvas); if (!canvas) return;
    chartParticipacion = new Chart(canvas.getContext('2d'), {
        type: 'horizontalBar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Participación %',
                data: percents,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        min: 0,
                        max: 100
                    }
                }]
            }
        }
    });
}
function statsInicializarUIEnHome() {
    if (statsUiInicializado) return;
    var $modal = $('#estadisticasContadorModal');
    if ($('#stats_cooperativa').is('select')) {
        var $coop = $('#stats_cooperativa');
        if ($coop.hasClass('select2-hidden-accessible')) $coop.select2('destroy');
        $coop.select2({
            width: '100%',
            placeholder: 'Seleccione cooperativa...',
            allowClear: true,
            dropdownParent: $modal
        }).on('change', statsOnCooperativaChange);
    }
    var $unidades = $('#stats_unidades');
    if ($unidades.hasClass('select2-hidden-accessible')) $unidades.select2('destroy');
    $unidades.select2({
        width: '100%',
        dropdownParent: $modal
    });
    $('#stats_fecha_inicio').datetimepicker();
    $('#stats_fecha_fin').datetimepicker();
    statsEstablecerFechaHoy();
    statsUiInicializado = true;
}
function statsAbrirContextoHome() {
    statsInicializarUIEnHome();
    var coopId = statsContextoHome.cooperativa_id || statsGetCooperativaId();
    var aplicarUnidades = function () {
        var ids = statsContextoHome.unidades || [];
        if (ids.length) { $('#stats_unidades').val(ids).trigger('change'); statsAplicarGrafico(); }
    };
    if (coopId && $('#stats_cooperativa').is('select')) {
        var $coop = $('#stats_cooperativa');
        $coop.off('change', statsOnCooperativaChange).val(coopId).trigger('change.select2').on('change', statsOnCooperativaChange);
    }
    if (coopId) statsCargarUnidadesCooperativa(coopId, aplicarUnidades);
}
$('#estadisticasContadorModal').on('shown.bs.modal', function () { statsAbrirContextoHome(); });
$('#estadisticasContadorModal').on('hidden.bs.modal', function () { statsDetenerRefresco(); });
</script>
