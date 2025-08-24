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

function cargaHistorico(url, id_cooperativa, valor_usuario) {
    //cleanForm();
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

    var id_coop = "";
    if (valor_usuario == '1')
        id_coop = document.getElementById('cooperativa_id').value;
    else
        id_coop = id_cooperativa;

    $('#progress').modal('show');
    $.post(url, {
        unidad_id: unidad_id.value,
        fecha_inicio: fecha_inicio.value,
        fecha_fin: fecha_fin.value,
        opcion: 'getHistorico',
        cooperativa_id: id_coop,
        evento: document.getElementById('evento').value
    }, function (data) {
        if (data.error == true) {
            if (data.messages.hasOwnProperty('unidad_id')) {
                div_unidad.classList.add('has-error');
                span_unidad.innerHTML = '<strong>' + data.messages.unidad_id + '</strong>';
            }

            if (data.messages.hasOwnProperty('fecha_inicio')) {
                div_fecha_inicio.classList.add('has-error');
                span_desde.innerHTML = '<strong>' + data.messages.fecha_inicio + '</strong>';
            }

            if (data.messages.hasOwnProperty('fecha_fin')) {
                div_fecha_fin.classList.add('has-error');
                span_hasta.innerHTML = '<strong>' + data.messages.fecha_fin + '</strong>';
            }
        } else {
            var tr_registros_historicos = $('#div-tabla');
            tr_registros_historicos.empty();

            if (data.array_historico.length == 0) {
                alert('No se encontró ningún registro.');
                array_historico = [];
            } else {
                sortJsonArrayByProperty(data.array_historico, 'fecha_gps');
                array_historico = data.array_historico;
               // if (data.tipo != 'GTFRI' || data.ev == 'T') {
                   /* tr_registros_historicos.append(
                        '<table class="table" id="tr-registros-historicos">' +
                        '<thead style="background-color: #FAFAFA;">' +
                        '<th >Fecha de GPS</th>' +
                        '<th>Fecha de servidor</th>' +
                        '<th>Evento</th>' +
                        '<th>Punto cardinal</th>' +
                        '<th>Velocidad</th>' +
                        '<th>Voltaje</th>' +
                        '<th>Contador</th>' +
                        '</thead>' +
                        '<tbody id="tbody-historico"></tbody>' +
                        '</table>'
                    );
                    tr_registros_historicos = $('#div-tabla').find('tbody[id="tbody-historico"]');

                    for (var i = 0, len = data.array_historico.length; i < len; i++)
                        tr_registros_historicos.append(
                            '<tr>' +
                            '<td>' + data.array_historico[i].fecha_gps + '</td>' +
                            '<td>' + data.array_historico[i].fecha_servidor + '</td>' +
                            '<td>' + data.array_historico[i].evento + '</td>' +
                            '<td>' + data.array_historico[i].angulo + '</td>' +
                            '<td>' + data.array_historico[i].velocidad + '</td>' +
                            '<td>' + data.array_historico[i].voltaje + '</td>' +
                            '<td>' + data.array_historico[i].contador_total + '</td>' +
                            '</tr>'
                        );*/
                    //$('#tr-registros-historicos').paginate();
              //  } else {
                   // if (data.ev != 'T') {
                        tr_registros_historicos.append(
                            '<table class="table" id="tr-registros-historicos">' +
                            '<thead style="background-color: #FAFAFA;">' +
                            '<th >Fecha de GPS</th>' +
                            '<th>Fecha de servidor</th>' +
                            '<th>Evento</th>' +
                            '<th>Latitud</th>' +
                            '<th>Longitud</th>' +
                            '<th>Mileage</th>' +
                            '<th>Ubicación</th>' +
                            '<th>Punto cardinal</th>' +
                            '<th>Velocidad</th>' +
                            '<th>Voltaje</th>' +
                            '<th>Contador</th>' +
                            '</thead>' +
                            '<tbody id="tbody-historico"></tbody>' +
                            '</table>'
                        );
                        tr_registros_historicos = $('#div-tabla').find('tbody[id="tbody-historico"]');

                        for (var i = 0, len = data.array_historico.length; i < len; i++)
                            tr_registros_historicos.append(
                                '<tr id="'+data.array_historico[i]._id+'">' +
                                '<td>' + data.array_historico[i].fecha_gps + '</td>' +
                                '<td>' + data.array_historico[i].fecha_servidor + '</td>' +
                                '<td>' + data.array_historico[i].evento + '</td>' +
                                '<td>' + data.array_historico[i].latitud + '</td>' +
                                '<td>' + data.array_historico[i].longitud + '</td>' +
                                '<td>' + data.array_historico[i].mileage + '</td>' +
                                '<td>' + data.array_historico[i].ubicacion + '</td>' +
                                '<td>' + data.array_historico[i].angulo + '</td>' +
                                '<td>' + data.array_historico[i].velocidad + '</td>' +
                                '<td>' + data.array_historico[i].voltaje + '</td>' +
                                '<td>' + data.array_historico[i].contador_total + '</td>' +
                                '</tr>'
                            );
                    //}else{
                       /* tr_registros_historicos.append(
                            '<table class="table" id="tr-registros-historicos">' +
                            '<thead style="background-color: #FAFAFA;">' +
                            '<th >Fecha de GPS</th>' +
                            '<th>Fecha de servidor</th>' +
                            '<th>Evento</th>' +
                            '<th>Punto cardinal</th>' +
                            '<th>Velocidad</th>' +
                            '<th>Voltaje</th>' +
                            '<th>Contador</th>' +
                            '</thead>' +
                            '<tbody id="tbody-historico"></tbody>' +
                            '</table>'
                        );
                        tr_registros_historicos = $('#div-tabla').find('tbody[id="tbody-historico"]');
    
                        for (var i = 0, len = data.array_historico.length; i < len; i++)
                            tr_registros_historicos.append(
                                '<tr>' +
                                '<td>' + data.array_historico[i].fecha_gps + '</td>' +
                                '<td>' + data.array_historico[i].fecha_servidor + '</td>' +
                                '<td>' + data.array_historico[i].evento + '</td>' +
                                '<td>' + data.array_historico[i].angulo + '</td>' +
                                '<td>' + data.array_historico[i].velocidad + '</td>' +
                                '<td>' + data.array_historico[i].voltaje + '</td>' +
                                '<td>' + data.array_historico[i].contador_total + '</td>' +
                                '</tr>'
                            );*/
                   // }
               // }
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