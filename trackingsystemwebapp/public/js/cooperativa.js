

var actual_id = null;

function editarCooperativa(url)
{
    cleanForm();
    var descripcion = document.getElementById('descripcion');
    var multa_tubo = document.getElementById('multa_tubo');
    var tolerancia_buffer_minutos = document.getElementById('tolerancia_buffer_minutos');
    var taxis = document.getElementById('taxis');
    var ruc = document.getElementById('ruc');
    var despachos_atm = document.getElementById('despachos_atm');
    var despachos_job = document.getElementById('despachos_job');
    var mascara = document.getElementById('mascara');
    let importador_despachos = document.getElementById('importador_despachos');
    let finalizacion_automatica = document.getElementById('finalizacion_automatica');
    let redondear_tiempos_atraso = document.getElementById('redondear_tiempos_atraso');
    $.get(url, function ( data ) {
        actual_id = data._id;
        descripcion.value = data.descripcion;
        multa_tubo.value = data.multa_tubo;
        tolerancia_buffer_minutos.value = data.tolerancia_buffer_minutos??'';
        mascara.checked = (data.mascara == 'S');
        taxis.checked = data.taxis;
        ruc.value = data.ruc;
        despachos_atm.checked = (data.despachos_atm == 'S');
        despachos_job.checked=(data.despachos_job == 'S');
        importador_despachos.checked = data.importador_despachos??false;
        finalizacion_automatica.checked = data.finalizacion_automatica??false;
        redondear_tiempos_atraso.checked = data.redondear_tiempos_atraso??false;
        document.getElementById('email').value = (data.email != undefined)?data.email:'';
    }, "json");
}
function crearCooperativa(url)
{
    var descripcion = document.getElementById('descripcion');
    var ruc = document.getElementById('ruc');
    var div_ruc = document.getElementById('div_ruc');
    var despachos_atm = document.getElementById('despachos_atm');
    var despachos_job = document.getElementById('despachos_job');
    var mascara = document.getElementById('mascara');
    var span_ruc = document.getElementById('span_ruc');
    var multa_tubo = document.getElementById('multa_tubo');
    var tolerancia_buffer_minutos = document.getElementById('tolerancia_buffer_minutos');
    var taxis = document.getElementById('taxis');
    var div_descripcion = document.getElementById('div-descripcion');
    var div_multa_tubo = document.getElementById('div-multa-tubo');
    var span_descripcion = document.getElementById('span_descripcion');
    var span_multa_tubo = document.getElementById('span_multa_tubo');
    var email = document.getElementById('email').value;
    let importador_despachos = document.getElementById('importador_despachos');
    let finalizacion_automatica = document.getElementById('finalizacion_automatica');
    let redondear_tiempos_atraso = document.getElementById('redondear_tiempos_atraso');

    div_descripcion.classList.remove('has-error');
    div_multa_tubo.classList.remove('has-error');

    var params = {
        descripcion : descripcion.value,
        multa_tubo : multa_tubo.value,
        tolerancia_buffer_minutos : tolerancia_buffer_minutos.value,
        taxis : (taxis.checked)?1:0,
        estado : "A",
        mascara : (mascara.checked)?'S':'N',
        ruc : ruc.value,
        despachos_atm : (despachos_atm.checked)?'S':'N',
        despachos_job : (despachos_job.checked)?'S':'N',
        email : email,
        importador_despachos: importador_despachos.checked,
        finalizacion_automatica: finalizacion_automatica.checked,
        redondear_tiempos_atraso: redondear_tiempos_atraso.checked
    };

    $.post(url , params ,function( data ) {
        if (data.error == false)
            location.reload(true);
        else
            mensajesError(data,div_descripcion,span_descripcion,div_multa_tubo,span_multa_tubo, div_ruc, span_ruc);

    }, "json");
}


function actualizarCooperativa(url)
{
    var descripcion = document.getElementById('descripcion');
    var multa_tubo = document.getElementById('multa_tubo');
    var tolerancia_buffer_minutos = document.getElementById('tolerancia_buffer_minutos');
    var taxis = document.getElementById('taxis');
    var div_descripcion = document.getElementById('div-descripcion');
    var div_multa_tubo = document.getElementById('div-multa-tubo');
    var span_descripcion = document.getElementById('span_descripcion');
    var span_multa_tubo = document.getElementById('span_multa_tubo');
    var ruc = document.getElementById('ruc');
    var mascara = document.getElementById('mascara');
    var div_ruc = document.getElementById('div_ruc');
    var despachos_job = document.getElementById('despachos_job');
    var despachos_atm = document.getElementById('despachos_atm');
    var span_ruc = document.getElementById('span_ruc');
    var email = document.getElementById('email').value;
    let importador_despachos = document.getElementById('importador_despachos');
    let finalizacion_automatica = document.getElementById('finalizacion_automatica');
    let redondear_tiempos_atraso = document.getElementById('redondear_tiempos_atraso');
    div_descripcion.classList.remove('has-error');
    div_multa_tubo.classList.remove('has-error');

    $.post(url, { 
        descripcion : descripcion.value, 
        multa_tubo : multa_tubo.value, 
        tolerancia_buffer_minutos : tolerancia_buffer_minutos.value,
        taxis : (taxis.checked)?1:0,
        _method : 'PUT', ruc : ruc.value, 
        despachos_atm : (despachos_atm.checked)?'S':'N',
        mascara : (mascara.checked)?'S':'N',
        despachos_job: (despachos_job.checked)?'S':'N', 
        email : email,
        importador_despachos: importador_despachos.checked,
        finalizacion_automatica: finalizacion_automatica.checked,
        redondear_tiempos_atraso: redondear_tiempos_atraso.checked
    }, function( data ) {
        if (data.error == false)
            location.reload(true);
        else
            mensajesError(data,div_descripcion,span_descripcion,div_multa_tubo,span_multa_tubo, div_ruc, span_ruc);

    }, "json");
}


function estadoCooperativa(url,check)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar esta cooperativa?');
    else
        $confirmation = confirm('¿Está seguro que desea activar esta cooperativa?');

    if ($confirmation == true)
    {
        $.post(url, {
            _method : 'DELETE'

        } ,function(data) {
            if(!check)
            {

                if(data.estado=='I')
                    alert('La cooperativa ha sido inactivada con éxito.');
                else
                    alert('No se puede inactivar esta cooperativa.');
            }
            else
                alert('La cooperativa ha sido activada con éxito.');

            location.reload(true);
        }, "json");
    }
    else
        location.reload(true);
}

function mensajesError(data,div_descripcion,span_descripcion,div_multa_tubo,span_multa_tubo, div_ruc, span_ruc)
{

    if (data.messages.hasOwnProperty('descripcion')){
        div_descripcion.classList.add('has-error');
        span_descripcion.innerHTML = '<strong>' + data.messages.descripcion + '</strong>';
    }

    if (data.messages.hasOwnProperty('multa_tubo')){
        div_multa_tubo.classList.add('has-error');
        span_multa_tubo.innerHTML = '<strong>' + data.messages.multa_tubo + '</strong>';
    }

    if (data.messages.hasOwnProperty('ruc')) {
        div_ruc.classList.add('has-error');
        span_ruc.innerHTML = '<strong>' + data.messages.ruc + '</strong>';
    }
}

function cleanForm() {

    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_multa_tubo').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_ruc').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('descripcion').value='';
    document.getElementById('multa_tubo').value='';
    document.getElementById('tolerancia_buffer_minutos').value='';
    document.getElementById('taxis').checked = false;
    document.getElementById('despachos_atm').checked = false;
    document.getElementById('ruc').value = '';

    document.getElementById('div-descripcion').classList.remove('has-error');
    document.getElementById('div-multa-tubo').classList.remove('has-error');
    document.getElementById('div_ruc').classList.remove('has-error');
    document.getElementById('email').value = '';

    document.getElementById('despachos_job').checked = false;
    document.getElementById('mascara').checked = false;
    document.getElementById('importador_despachos').checked = false;
    document.getElementById('finalizacion_automatica').checked = false;
    document.getElementById('redondear_tiempos_atraso').checked = false;
    
    actual_id=null;
}

$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';
$("[name='chk_estado']").bootstrapSwitch();

/**
 * Created by José Daniel on 04/09/2016.
 */
