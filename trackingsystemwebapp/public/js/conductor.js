
var actual_id = null;

function editarConductor(url,tipo_usuario_valor)
{
    cleanForm(tipo_usuario_valor);

    var cedula = document.getElementById('cedula');
    var nombre = document.getElementById('nombre');
    var direccion = document.getElementById('direccion');
    var email = document.getElementById('email');
    var telefono = document.getElementById('telefono');
    var celular = document.getElementById('celular');
    var operadora = document.getElementById('operadora');
    var tipo_licencia = document.getElementById('tipo_licencia');

    $.get(url, function ( data ) {
        actual_id = data._id;
        nombre.value = data.nombre;
        cedula.value = data.cedula;
        direccion.value = (data.direccion != undefined)?data.direccion:null;
        email.value = (data.email != undefined)?data.email:null;
        telefono.value = (data.telefono != undefined)?data.telefono:null;
        celular.value = (data.celular != undefined)?data.celular:null;
        operadora.value = (data.operadora != undefined)?data.operadora:null;
        tipo_licencia.value= (data.tipo_licencia != undefined)?data.tipo_licencia:null;
        if(tipo_usuario_valor==1)
            document.getElementById('cooperativa_id').value=data.cooperativa_id;
    }, "json");
}
function crearConductor(url, tipo_usuario_valor, id_cooperativa)
{
    var cedula = document.getElementById('cedula');
    var nombre = document.getElementById('nombre');
    var direccion = document.getElementById('direccion');
    var email = document.getElementById('email');
    var telefono = document.getElementById('telefono');
    var celular = document.getElementById('celular');
    var operadora = document.getElementById('operadora');
    var tipo_licencia = document.getElementById('tipo_licencia');

    var div_cedula = document.getElementById('div-cedula');
    var div_nombre = document.getElementById('div-nombre');

    var span_cedula = document.getElementById('span_cedula');
    var span_nombre = document.getElementById('span_nombre');


    div_cedula.classList.remove('has-error');
    div_nombre.classList.remove('has-error');

    var cooperativa_id;
    var div_cooperativa=null;
    var span_cooperativa=null;

    if(tipo_usuario_valor=='1')
    {
        cooperativa_id = document.getElementById('cooperativa_id').value;
        div_cooperativa = document.getElementById('div-cooperativa');
        span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');
    }
    else
        cooperativa_id=id_cooperativa;

    $.post(url, {
        cedula : cedula.value,
        nombre:nombre.value,
        cooperativa_id:cooperativa_id,
        estado:"A",
        direccion : direccion.value,
        email : email.value,
        telefono : telefono.value,
        celular : celular.value,
        operadora : operadora.value,
        tipo_licencia: tipo_licencia.value
    }, function( data ) {
        if (data.error == false){
            alert('El conductor ha sido creado con éxito.');
            location.reload(true);
        }
        else
            mensajesError(data,div_cedula,span_cedula,div_nombre,span_nombre,div_cooperativa,span_cooperativa);
    }, "json");
}


function actualizarConductor(url, tipo_usuario_valor, id_cooperativa)
{
    var cedula = document.getElementById('cedula');
    var nombre = document.getElementById('nombre');
    var direccion = document.getElementById('direccion');
    var email = document.getElementById('email');
    var telefono = document.getElementById('telefono');
    var celular = document.getElementById('celular');
    var operadora = document.getElementById('operadora');
    var tipo_licencia = document.getElementById('tipo_licencia');

    var div_cedula = document.getElementById('div-cedula');
    var div_nombre = document.getElementById('div-nombre');

    var span_cedula = document.getElementById('span_cedula');
    var span_nombre = document.getElementById('span_nombre');

    div_cedula.classList.remove('has-error');
    div_nombre.classList.remove('has-error');

    var cooperativa_id;
    var div_cooperativa=null;
    var span_cooperativa=null;

    if(tipo_usuario_valor=='1')
    {
        cooperativa_id = document.getElementById('cooperativa_id').value;
        div_cooperativa = document.getElementById('div-cooperativa');
        span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');
    }
    else
        cooperativa_id=id_cooperativa;

    $.post(url, {
        cedula : cedula.value,
        nombre : nombre.value,
        cooperativa_id : cooperativa_id,
        direccion : direccion.value,
        email : email.value,
        telefono : telefono.value,
        celular : celular.value,
        operadora : operadora.value,
        tipo_licencia : tipo_licencia.value,
        _method : 'PUT' }, function( data ) {
        if (data.error == false){
            alert('El conductor ha sido actualizado con éxito.');
            location.reload(true);
        }
        else
            mensajesError(data,div_cedula,span_cedula,div_nombre,span_nombre,div_cooperativa,span_cooperativa);
    }, "json");
}

function estadoConductor(url,check)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar este conductor?');
    else
        $confirmation = confirm('¿Está seguro que desea activar este conductor?');

    if ($confirmation == true)
    {
        $.post(url, {
            _method : 'DELETE'

        } ,function(  ) {
            if(!check)
                alert('El conductor ha sido inactivado con éxito.');
            else
                alert('El conductor ha sido activado con éxito.');
            location.reload(true);
        }, "json");
    }
    else
        location.reload(true);
}

function mensajesError(data,div_cedula,span_cedula,div_nombre,span_nombre,div_cooperativa,span_cooperativa)
{
    if (data.messages.hasOwnProperty('cedula')){
        div_cedula.classList.add('has-error');
        span_cedula.innerHTML = '<strong>' + data.messages.cedula + '</strong>';
    }

    if (data.messages.hasOwnProperty('nombre')){
        div_nombre.classList.add('has-error');
        span_nombre.innerHTML = '<strong>' + data.messages.nombre + '</strong>';
    }

    if(div_cooperativa!=null)
    {
        if (data.messages.hasOwnProperty('cooperativa_id')){
            div_cooperativa.classList.add('has-error');
            span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
        }
    }

}


function cleanForm(tipo_usuario_valor) {

    document.getElementById('span_cedula').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_nombre').innerHTML = '<strong>' + '' + '</strong>';


    document.getElementById('cedula').value='';
    document.getElementById('nombre').value='';
    document.getElementById('operadora').value = '';
    document.getElementById('email').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('celular').value = '';
    document.getElementById('direccion').value = '';

    document.getElementById('div-nombre').classList.remove('has-error');
    document.getElementById('div-cedula').classList.remove('has-error');


    if(tipo_usuario_valor=='1')
    {
        document.getElementById('span_cooperativa').innerHTML = '<strong>' + '' + '</strong>';
        document.getElementById('cooperativa_id').value='';
        document.getElementById('div-cooperativa').classList.remove('has-error');
    }

    actual_id=null;
}


$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';
$("[name='chk_estado']").bootstrapSwitch();
/**
 * Created by José Daniel on 04/09/2016.
 */

