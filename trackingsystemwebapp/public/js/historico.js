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

function cargaHistorico(url, id_cooperativa, valor_usuario, pagina = 1) {

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

    var id_coop = valor_usuario == '1' ? document.getElementById('cooperativa_id').value : id_cooperativa;

    $('#progress').modal('show');
    $.post(url, {
        unidad_id: unidad_id.value,
        fecha_inicio: fecha_inicio.value,
        fecha_fin: fecha_fin.value,
        opcion: 'getHistorico',
        cooperativa_id: id_coop,
        evento: document.getElementById('evento').value,
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
            if (data.array_historico.length == 0) {
                alert('No se encontró ningún registro.');
                array_historico = [];
            } else {
                sortJsonArrayByProperty(data.array_historico, 'fecha_gps');
                array_historico = data.array_historico;

                tr_registros_historicos.append(
                    '<table class="table" id="tr-registros-historicos">' +
                    '<thead style="background-color: #FAFAFA;">' +
                    '<th>Fecha de GPS</th><th>Fecha de servidor</th><th>Evento</th><th>Latitud</th>' +
                    '<th>Longitud</th><th>Mileage</th><th>Ubicación</th><th>Punto cardinal</th>' +
                    '<th>Velocidad</th><th>Voltaje</th><th>Contador</th>' +
                    '</thead>' +
                    '<tbody id="tbody-historico"></tbody>' +
                    '</table>'
                );
                var tbody = $('#div-tabla').find('tbody[id="tbody-historico"]');
                data.array_historico.forEach(function(item) {
                    tbody.append(
                        '<tr id="' + item._id + '">' +
                        '<td>' + item.fecha_gps + '</td>' +
                        '<td>' + item.fecha_servidor + '</td>' +
                        '<td>' + item.evento + '</td>' +
                        '<td>' + item.latitud + '</td>' +
                        '<td>' + item.longitud + '</td>' +
                        '<td>' + item.mileage + '</td>' +
                        '<td>' + item.ubicacion + '</td>' +
                        '<td>' + item.angulo + '</td>' +
                        '<td>' + item.velocidad + '</td>' +
                        '<td>' + item.voltaje + '</td>' +
                        '<td>' + item.contador_total + '</td>' +
                        '</tr>'
                    );
                });

                // PAGINACIÓN DINÁMICA
                var paginacion = '<ul class="pagination justify-content-center mt-2">';
                
                // Botón primero
                paginacion += (data.current_page > 1) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaHistorico(\''+ url +'\',\''+ id_cooperativa +'\','+ valor_usuario +',1)">&laquo;&laquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&laquo;&laquo;</span></li>';

                // Botón anterior
                paginacion += (data.prev_page_url) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaHistorico(\''+ url +'\',\''+ id_cooperativa +'\','+ valor_usuario +','+ (data.current_page - 1) +')">&laquo;</a></li>'
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
                        paginacion += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaHistorico(\''+ url +'\',\''+ id_cooperativa +'\','+ valor_usuario +','+ i +')">' + i + '</a></li>';
                    }
                }

                if (fin < data.last_page) {
                    paginacion += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Botón siguiente
                paginacion += (data.next_page_url) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaHistorico(\''+ url +'\',\''+ id_cooperativa +'\','+ valor_usuario +','+ (data.current_page + 1) +')">&raquo;</a></li>'
                    : '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';

                // Botón último
                paginacion += (data.current_page < data.last_page) 
                    ? '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="cargaHistorico(\''+ url +'\',\''+ id_cooperativa +'\','+ valor_usuario +','+ data.last_page +')">&raquo;&raquo;</a></li>'
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