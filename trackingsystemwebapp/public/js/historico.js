var array_historico = [];

function llenarUnidades(url, tipo_usuario_valor, id_cooperativa) {
    var cooperativa_id;

    if (tipo_usuario_valor == '1')
        cooperativa_id = document.getElementById('cooperativa_id').value;
    else
        cooperativa_id = id_cooperativa;

    var div_unidad = $('#div-unidad');
    div_unidad.empty();

    $.post(url, {
        cooperativa_id: cooperativa_id,
        opcion: 'getUnidades'
    }, function (data) {
        div_unidad.append(
            '<label for="unidad_id">Unidad</label>' +
            '<select class="form-control" id="unidad_id" name="unidad_id">' +
            ' <option value="" disabled selected hidden>Seleccione...</option>' +
            ' </select>' +
            ' <span class="help-block" id="span_unidad"></span>'
        );
        var select = $('#unidad_id');
        for (var i = 0, len = data.unidades.length; i < len; i++)
            select.append('<option  value=\'' + data.unidades[i]._id + '\'> ' + data.unidades[i].descripcion + '</option>');

        $('#unidad_id').chosen({
            width: '100%'
        });
    }, "json");
}

function llenarUnidadesSocio(unidades_id, unidades_descripcion) {
    var div_unidad = $('#div-unidad');
    div_unidad.empty();

    div_unidad.append(
        '<label for="unidad_id">Unidad</label>' +
        '<select class="form-control" id="unidad_id" name="unidad_id">' +
        ' <option value="" disabled selected hidden>Seleccione...</option>' +
        ' </select>' +
        ' <span class="help-block" id="span_unidad"></span>'
    );
    var select = $('#unidad_id');
    for (var i = 0, len = unidades_id.length; i < len; i++)
        select.append('<option  value=\'' + unidades_id[i] + '\'> ' + unidades_descripcion[i] + '</option>');

}

/* ===================== HISTÓRICO CON SCROLL VIRTUAL =====================
 * La tabla tiene el alto de TODOS los registros, pero solo se dibujan las filas visibles
 * (más un margen). Las páginas se piden al servidor a medida que se hace scroll, ya
 * ordenadas por fecha GPS. Filas de alto fijo: el texto largo se corta con "…" y se ve
 * completo al pasar el mouse.
 */
var HV_FILA_ALTO = 33;      // px, alto fijo de cada fila
var HV_POR_PAGINA = 100;    // registros por petición
var HV_MARGEN = 20;         // filas extra dibujadas arriba y abajo de lo visible
var HV_MAX_PETICIONES = 3;  // peticiones simultáneas
var HV_COLUMNAS = [
    { titulo: 'Fecha de GPS', ancho: 150 }, { titulo: 'Fecha de servidor', ancho: 150 },
    { titulo: 'Evento', ancho: 230 }, { titulo: 'Origen', ancho: 100 },
    { titulo: 'Latitud', ancho: 110 }, { titulo: 'Longitud', ancho: 110 },
    { titulo: 'Odometro', ancho: 100 }, { titulo: 'Ubicación', ancho: 320 },
    { titulo: 'Punto cardinal', ancho: 110 }, { titulo: 'Velocidad', ancho: 95 },
    { titulo: 'Voltaje', ancho: 80 }, { titulo: 'Contador', ancho: 85 },
    { titulo: 'Movimiento', ancho: 100 }
];
var hv = null;          // estado de la búsqueda mostrada
var hvSecuencia = 0;    // descarta respuestas de búsquedas anteriores
var hvRenderPendiente = false;

// Filtros del formulario (los mismos para la búsqueda y la exportación).
function parametrosHistorico(id_cooperativa, valor_usuario) {
    var unidad = document.getElementById('unidad_id');
    var origen = document.getElementById('origen');
    return {
        unidad_id: unidad ? unidad.value : '',
        fecha_inicio: document.getElementById('fecha_inicio').value,
        fecha_fin: document.getElementById('fecha_fin').value,
        cooperativa_id: valor_usuario == '1' ? document.getElementById('cooperativa_id').value : id_cooperativa,
        evento: document.getElementById('evento').value,
        origen: origen ? origen.value : 'T'
    };
}

function hvEscapar(valor) {
    return String(valor === null || valor === undefined ? '' : valor)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function cargaHistorico(url, id_cooperativa, valor_usuario) {
    var div_unidad = document.getElementById('div-unidad');
    var div_fecha_inicio = document.getElementById('div-fecha-inicio');
    var div_fecha_fin = document.getElementById('div-fecha-fin');

    var span_unidad = document.getElementById('span_unidad');
    var span_desde = document.getElementById('span_fecha_inicio');
    var span_hasta = document.getElementById('span_fecha_fin');

    div_unidad.classList.remove('has-error');
    div_fecha_inicio.classList.remove('has-error');
    div_fecha_fin.classList.remove('has-error');
    if (span_unidad) span_unidad.innerHTML = '';
    span_desde.innerHTML = '';
    span_hasta.innerHTML = '';

    var params = parametrosHistorico(id_cooperativa, valor_usuario);
    params.opcion = 'getHistorico';
    params.per_page = HV_POR_PAGINA;
    var secuencia = ++hvSecuencia;

    $('#progress').modal('show');
    $.post(url, $.extend({}, params, { page: 1 }), function (data) {
        if (secuencia !== hvSecuencia) return;
        $('#div-tabla').empty().off('scroll.hv');
        $('#hv-info').remove();
        hv = null;
        array_historico = [];

        if (data.error) {
            if (data.messages.unidad_id) {
                div_unidad.classList.add('has-error');
                if (span_unidad) span_unidad.innerHTML = '<strong>' + data.messages.unidad_id + '</strong>';
            }
            if (data.messages.fecha_inicio) {
                div_fecha_inicio.classList.add('has-error');
                span_desde.innerHTML = '<strong>' + data.messages.fecha_inicio + '</strong>';
            }
            if (data.messages.fecha_fin) {
                div_fecha_fin.classList.add('has-error');
                span_hasta.innerHTML = '<strong>' + data.messages.fecha_fin + '</strong>';
            }
        } else if (data.total == 0 || data.array_historico.length == 0) {
            alert('No se encontró ningún registro.');
        } else {
            hv = {
                url: url,
                params: params,
                total: data.total,
                paginas: { 1: data.array_historico },
                cargando: {},
                peticiones: 0,
                secuencia: secuencia
            };
            array_historico = data.array_historico;
            hvConstruirTabla();
        }
        $('#progress').modal('hide');
    }, "json").fail(function () {
        $('#progress').modal('hide');
    });
}

function hvConstruirTabla() {
    var contenedor = $('#div-tabla');
    contenedor.before('<div id="hv-info" class="text-muted" style="margin-bottom:6px;">Total: <strong>' +
        hv.total.toLocaleString('es-EC') + '</strong> registro(s)</div>');

    var anchoTotal = 0;
    var colgroup = '<colgroup>';
    var thead = '<thead><tr>';
    HV_COLUMNAS.forEach(function (col) {
        anchoTotal += col.ancho;
        colgroup += '<col style="width:' + col.ancho + 'px;" />';
        thead += '<th style="position:sticky;top:0;z-index:1;background-color:#FAFAFA;white-space:nowrap;">' +
            col.titulo + '</th>';
    });
    colgroup += '</colgroup>';
    thead += '</tr></thead>';

    contenedor.append(
        '<table class="table" id="tr-registros-historicos" style="table-layout:fixed;width:' + anchoTotal +
        'px;margin-bottom:0;">' + colgroup + thead + '<tbody id="tbody-historico"></tbody></table>'
    );
    contenedor.scrollTop(0);
    contenedor.on('scroll.hv', hvProgramarRender);
    $(window).off('resize.hv').on('resize.hv', hvProgramarRender);
    hvRender();
}

function hvProgramarRender() {
    if (hvRenderPendiente) return;
    hvRenderPendiente = true;
    (window.requestAnimationFrame || function (fn) { setTimeout(fn, 16); })(function () {
        hvRenderPendiente = false;
        hvRender();
    });
}

function hvFilaHtml(item) {
    // isbuff: 1 = trama BUFF (atrasada), 0 = tiempo real, null = registro sin el campo
    var origen = (item.isbuff === 1)
        ? '<span class="label label-warning">BUFF</span>'
        : (item.isbuff === 0)
            ? '<span class="label label-success">Tiempo real</span>'
            : '-';
    var celdas = [item.fecha_gps, item.fecha_servidor, item.evento, null, item.latitud, item.longitud,
        item.odometro, item.ubicacion, item.angulo, item.velocidad, item.voltaje, item.contador_total,
        item.tipo_marcacion];
    var html = '<tr id="' + hvEscapar(item._id) + '" style="height:' + HV_FILA_ALTO + 'px;">';
    celdas.forEach(function (valor, i) {
        var contenido = (i === 3) ? origen : hvEscapar(valor);
        html += '<td style="padding:6px 8px;line-height:20px;white-space:nowrap;overflow:hidden;' +
            'text-overflow:ellipsis;"' + (i === 3 ? '' : ' title="' + hvEscapar(valor) + '"') + '>' +
            contenido + '</td>';
    });
    return html + '</tr>';
}

function hvRender() {
    if (!hv) return;
    var contenedor = document.getElementById('div-tabla');
    var tbody = document.getElementById('tbody-historico');
    if (!contenedor || !tbody) return;

    var visibles = Math.ceil(contenedor.clientHeight / HV_FILA_ALTO);
    var primero = Math.max(0, Math.floor(contenedor.scrollTop / HV_FILA_ALTO) - HV_MARGEN);
    var ultimo = Math.min(hv.total - 1, primero + visibles + 2 * HV_MARGEN);
    var columnas = HV_COLUMNAS.length;

    var html = '';
    if (primero > 0)
        html += '<tr style="height:' + (primero * HV_FILA_ALTO) + 'px;"><td colspan="' + columnas +
            '" style="padding:0;border:0;"></td></tr>';
    for (var i = primero; i <= ultimo; i++) {
        var pagina = Math.floor(i / HV_POR_PAGINA) + 1;
        var filas = hv.paginas[pagina];
        var item = filas ? filas[i % HV_POR_PAGINA] : null;
        if (item) {
            html += hvFilaHtml(item);
        } else {
            html += '<tr style="height:' + HV_FILA_ALTO + 'px;"><td colspan="' + columnas +
                '" class="text-muted" style="padding:6px 8px;line-height:20px;">' +
                '<i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>';
        }
    }
    var restantes = hv.total - 1 - ultimo;
    if (restantes > 0)
        html += '<tr style="height:' + (restantes * HV_FILA_ALTO) + 'px;"><td colspan="' + columnas +
            '" style="padding:0;border:0;"></td></tr>';
    tbody.innerHTML = html;

    // Pide las páginas que faltan para lo que se está viendo.
    var paginaDesde = Math.floor(primero / HV_POR_PAGINA) + 1;
    var paginaHasta = Math.floor(ultimo / HV_POR_PAGINA) + 1;
    for (var p = paginaDesde; p <= paginaHasta; p++)
        hvCargarPagina(p);
}

function hvCargarPagina(pagina) {
    if (!hv || hv.paginas[pagina] || hv.cargando[pagina] || hv.peticiones >= HV_MAX_PETICIONES) return;
    var estado = hv;
    estado.cargando[pagina] = true;
    estado.peticiones++;
    $.post(estado.url, $.extend({}, estado.params, { page: pagina }), function (data) {
        if (!data.error && data.array_historico)
            estado.paginas[pagina] = data.array_historico;
    }, "json").always(function () {
        delete estado.cargando[pagina];
        estado.peticiones--;
        if (estado === hv) hvProgramarRender();
    });
}

// Exporta TODOS los registros que cumplen los filtros del formulario (no solo los visibles).
function exportarHistorico(url, id_cooperativa, valor_usuario, formato) {
    var params = parametrosHistorico(id_cooperativa, valor_usuario);
    if (!params.unidad_id || !params.fecha_inicio || !params.fecha_fin) {
        alert('Seleccione la unidad y el rango de fechas para exportar.');
        return;
    }
    params.formato = formato;

    $('#progress').modal('show');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
    xhr.responseType = 'blob';
    xhr.onload = function () {
        $('#progress').modal('hide');
        if (xhr.status !== 200) {
            alert('No se pudo exportar el histórico. Revise los filtros e intente nuevamente.');
            return;
        }
        var nombre = 'Historico.' + (formato === 'excel' ? 'xls' : 'xml');
        var disposicion = xhr.getResponseHeader('Content-Disposition') || '';
        var coincide = /filename="?([^";]+)"?/i.exec(disposicion);
        if (coincide) nombre = coincide[1];

        var enlace = document.createElement('a');
        enlace.href = URL.createObjectURL(xhr.response);
        enlace.download = nombre;
        document.body.appendChild(enlace);
        enlace.click();
        setTimeout(function () {
            URL.revokeObjectURL(enlace.href);
            enlace.parentNode.removeChild(enlace);
        }, 1000);
    };
    xhr.onerror = function () {
        $('#progress').modal('hide');
        alert('Ocurrió un error de conexión al exportar el histórico.');
    };
    xhr.send($.param(params));
}

function cargaESPuntosControl(url, pagina = 1) {

    var unidad_id = document.getElementById('unidad_id');
    var fecha_inicio = document.getElementById('fecha_inicio');
    var fecha_fin = document.getElementById('fecha_fin');

    var div_unidad = document.getElementById('div-unidad');
    var div_fecha_inicio = document.getElementById('div-fecha-inicio');
    var div_fecha_fin = document.getElementById('div-fecha-fin');

    var span_unidad = document.getElementById('span_unidad');
    var span_desde = document.getElementById('span_fecha_inicio');
    var span_hasta = document.getElementById('span_fecha_fin');

    div_unidad.classList.remove('has-error');
    div_fecha_inicio.classList.remove('has-error');
    div_fecha_fin.classList.remove('has-error');

    var id_coop = document.getElementById('cooperativa_id').value

    $('#progress').modal('show');
    $.post(url, {
        unidad_id: unidad_id.value,
        fecha_inicio: fecha_inicio.value,
        fecha_fin: fecha_fin.value,
        cooperativa_id: id_coop,
        page: pagina
    }, function (data) {
        var tr_registros_historicos = $('#div-tabla');
        tr_registros_historicos.empty();

        if (data.error) {
            if (data.messages.unidad_id) {
                div_unidad.classList.add('has-error');
                span_unidad.innerHTML = '<strong>' + data.messages.unidad_id + '</strong>';
            }
            if (data.messages.fecha_inicio) {
                div_fecha_inicio.classList.add('has-error');
                span_desde.innerHTML = '<strong>' + data.messages.fecha_inicio + '</strong>';
            }
            if (data.messages.fecha_fin) {
                div_fecha_fin.classList.add('has-error');
                span_hasta.innerHTML = '<strong>' + data.messages.fecha_fin + '</strong>';
            }
        } else {
            if (data.data.length == 0) {
                alert('No se encontró ningún registro.');
                array_historico = [];
            } else {
                array_historico = data.data;

                tr_registros_historicos.append(
                    '<table class="table" id="tr-registros-historicos">' +
                    '<thead style="background-color: #FAFAFA;">' +
                    '<th>Fecha</th><th>Evento</th><th>Latitud</th>' +
                    '<th>Longitud</th>' +
                    '</thead>' +
                    '<tbody id="tbody-historico"></tbody>' +
                    '</table>'
                );
                var tbody = $('#div-tabla').find('tbody[id="tbody-historico"]');
                array_historico.forEach(function(item) {
                    var tipo=(item.tipo=='E')?"Entrada":"Salida";
                    var ptocontrol=item.punto_control.descripcion;
                    var evento=tipo+" al punto de control "+ptocontrol;
                    const fechaStr = item.fecha; // "2025-09-18T20:39:00.000+00:00"

                    let fecha = new Date(fechaStr);
                    function pad(n) {
                    return n < 10 ? '0' + n : n;
                    }
                    let dia   = pad(fecha.getDate());
                    let mes   = pad(fecha.getMonth() + 1); // Los meses en JS empiezan en 0
                    let anio  = fecha.getFullYear().toString().slice(-2); // solo 2 dígitos
                    let hora  = pad(fecha.getHours());
                    let min   = pad(fecha.getMinutes());
                    let seg   = pad(fecha.getSeconds());

                    let fechaFormateada = `${dia}/${mes}/${anio} ${hora}:${min}:${seg}`;

                    tbody.append(
                        '<tr id="' + item._id + '">' +
                        '<td>' + fechaFormateada + '</td>' +
                        '<td>' + evento+ '</td>' +
                        '<td>' + item.latitud + '</td>' +
                        '<td>' + item.longitud + '</td>' +
                        '</tr>'
                    );
                });

                // PAGINACIÓN DINÁMICA
                var paginacion = '<ul class="pagination justify-content-center mt-2">';
                
                // Botón primero
                paginacion += (data.current_page > 1) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaESPuntosControl(\''+ url +'\',1)">&laquo;&laquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&laquo;&laquo;</span></li>';

                // Botón anterior
                paginacion += (data.prev_page_url) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaESPuntosControl(\''+ url +'\','+ (data.current_page - 1) +')">&laquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';

                // Mostrar solo 5 páginas alrededor de la actual
                var inicio = Math.max(1, data.current_page - 2);
                var fin = Math.min(data.last_page, data.current_page + 2);

                if (inicio > 1) {
                    paginacion += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for (var i = inicio; i <= fin; i++) {
                    if (i == data.current_page) {
                        paginacion += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
                    } else {
                        paginacion += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaESPuntosControl(\''+ url +'\','+ i +')">' + i + '</a></li>';
                    }
                }

                if (fin < data.last_page) {
                    paginacion += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Botón siguiente
                paginacion += (data.next_page_url) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaESPuntosControl(\''+ url +'\','+ (data.current_page + 1) +')">&raquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';

                // Botón último
                paginacion += (data.current_page < data.last_page) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaESPuntosControl(\''+ url +'\','+ data.last_page +')">&raquo;&raquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&raquo;&raquo;</span></li>';

                paginacion += '</ul>';
                $('#div-tabla').append(paginacion);
            }
        }
        $('#progress').modal('hide');
    }, "json").fail(function () {
        $('#progress').modal('hide');
    });
}




/*
function mensajesError(data,div_unidad,span_unidad,div_desde,span_desde,div_hasta,span_hasta)
{

}*/

function sortJsonArrayByProperty(objArray, prop, direction) {
    if (arguments.length < 2) throw new Error("sortJsonArrayByProp requires 2 arguments");
    var direct = arguments.length > 2 ? arguments[2] : 1; //Default to ascending

    if (objArray && objArray.constructor === Array) {
        var propPath = (prop.constructor === Array) ? prop : prop.split(".");
        objArray.sort(function (a, b) {
            for (var p in propPath) {
                if (a[propPath[p]] && b[propPath[p]]) {
                    a = a[propPath[p]];
                    b = b[propPath[p]];
                }
            }
            return ((a < b) ? -1 * direct : ((a > b) ? 1 * direct : 0));
        });
    }
}

function cleanForm() {

    document.getElementById('span_fecha_inicio').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_fecha_fin').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_unidad').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('unidad_id').value = '';
    document.getElementById('fecha_inicio').value = '';
    document.getElementById('fecha_fin').value = '';

    document.getElementById('div-fecha-inicio').classList.remove('has-error');
    document.getElementById('div-fecha-fin').classList.remove('has-error');
    document.getElementById('div-unidad').classList.remove('has-error');

}