/**
 * Created by José Daniel on 23/10/2016.
 */

function agregarPreDespacho(url)
{
    cleanForm();

    var cooperativa_id = document.getElementById('cooperativa_id');
    var unidad_id = document.getElementById('unidad_id');
    var ruta_id = document.getElementById('ruta_id');
    var conductor_id = document.getElementById('conductor_id');
    var hora_salida = document.getElementById('hora_salida');

    var div_cooperativa = document.getElementById('div-cooperativa');
    var div_unidad = document.getElementById('div-unidad');
    var div_ruta = document.getElementById('div-ruta');
    var div_conductor = document.getElementById('div-conductor');
    var div_hora_salida = document.getElementById('div-hora-salida');

    var span_cooperativa = document.getElementById('span_cooperativa');
    var span_unidad = document.getElementById('span_unidad');
    var span_ruta = document.getElementById('span_ruta');
    var span_conductor = document.getElementById('span_conductor');
    var span_hora_salida = document.getElementById('span_hora_salida');

    div_cooperativa.classList.remove('has-error');
    div_unidad.classList.remove('has-error');
    div_ruta.classList.remove('has-error');
    div_conductor.classList.remove('has-error');
    div_hora_salida.classList.remove('has-error');

    $.post(url, {
        cooperativa_id : cooperativa_id.value,
        unidad_id:unidad_id.value,
        ruta_id : ruta_id.value,
        conductor_id:conductor_id.value,
        hora_salida : hora_salida.value,
        estado:"A"
    }, function( data ) {
        if (data.error == false){
            alert('El pre-despacho ha sido agregado con éxito.');
            //location.reload(true);
        }
        else
            mensajesError(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,div_ruta,span_ruta,
                div_conductor,span_conductor,div_hora_salida,span_hora_salida);
    }, "json");
}

function mensajesError(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,div_ruta,span_ruta,
                       div_conductor,span_conductor,div_hora_salida,span_hora_salida)
{
    if (data.messages.hasOwnProperty('cooperativa_id')){
        div_cooperativa.classList.add('has-error');
        span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('unidad_id')){
        div_unidad.classList.add('has-error');
        span_unidad.innerHTML = '<strong>' + data.messages.unidad_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('ruta_id')){
        div_ruta.classList.add('has-error');
        span_ruta.innerHTML = '<strong>' + data.messages.ruta_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('conductor_id')){
        div_conductor.classList.add('has-error');
        span_conductor.innerHTML = '<strong>' + data.messages.conductor_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('hora_salida')){
        div_hora_salida.classList.add('has-error');
        span_hora_salida.innerHTML = '<strong>' + data.messages.hora_salida + '</strong>';
    }

}

function cleanForm() {

    document.getElementById('span_cooperativa').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_unidad').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_hora_salida').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_conductor').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_ruta').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('div-cooperativa').classList.remove('has-error');
    document.getElementById('div-unidad').classList.remove('has-error');
    document.getElementById('div-hora-salida').classList.remove('has-error');
    document.getElementById('div-conductor').classList.remove('has-error');
    document.getElementById('div-ruta').classList.remove('has-error');

}


