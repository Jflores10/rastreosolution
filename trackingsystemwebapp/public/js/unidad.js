
var actual_id = null;


function editarUnidad(url, tipo_usuario_valor)
{
    cleanForm(tipo_usuario_valor);
    $.get(url, function ( data ) {
        actual_id = data._id;
        placa.value=data.placa;
        descripcion.value = data.descripcion;
        if(tipo_usuario_valor==1)
          document.getElementById('cooperativa_id').value=data.cooperativa_id;
        imei.value = data.imei;
        tipo_unidad_id.value = data.tipo_unidad_id;
        marca.value = data.marca;
        modelo.value = data.modelo;
        serie.value = data.serie;
        motor.value = data.motor;
        email_alarma.value = data.email_alarma;
        document.getElementById('atm').checked = (data.atm == 'S');
        document.getElementById('velocidad').value = (data.velocidad != undefined)?data.velocidad:'';

        if(data.sistema_energizado)sistema_energizado.checked=true;
        if(data.contador_cero_manual)contador_cero_manual.checked=true;
        if(data.desconexion_sistema)desconexion_sistema.checked=true;
        if(data.control_velocidad)control_velocidad.checked=true;
        if(data.climatizada)climatizada.checked=true;
        if(data.rampa)rampa.checked=true;
    }, "json");
}

function setUnidadConteo(url){       
    $('#progress').modal('show');
    param={
        unidad_id : actual_id
    };
    $.post(url, param, function( data ) {
        $('#progress').modal('hide');
    },"json");
}

function crearUnidad(url, tipo_usuario_valor, id_cooperativa)
{
    var placa = document.getElementById('placa');
    var descripcion = document.getElementById('descripcion');
    var marca = document.getElementById('marca');
    var modelo = document.getElementById('modelo');
    var serie = document.getElementById('serie');
    var motor = document.getElementById('motor');
    var tipo_unidad_id = document.getElementById('tipo_unidad_id');
    var email_alarma = document.getElementById('email_alarma');
    var sistema_energizado = document.getElementById('sistema_energizado');
    var contador_cero_manual = document.getElementById('contador_cero_manual');
    var desconexion_sistema = document.getElementById('desconexion_sistema');
    var control_velocidad = document.getElementById('control_velocidad');
    var climatizada = document.getElementById('climatizada');
    var rampa = document.getElementById('rampa');
    var imei = document.getElementById('imei');
    var atm = document.getElementById('atm').checked;
    var velocidad = document.getElementById('velocidad').value;

    var div_descripcion = document.getElementById('div-descripcion');
    var div_tipo_unidad = document.getElementById('div-tipo-unidad');
    var div_marca = document.getElementById('div-marca');
    var div_modelo = document.getElementById('div-modelo');
    var div_placa = document.getElementById('div-placa');
    var div_serie = document.getElementById('div-serie');
    var div_motor = document.getElementById('div-motor');
    var div_email_alarma = document.getElementById('div-email-alarma');
    var div_imei = document.getElementById('div-imei');

    var span_descripcion = document.getElementById('span_descripcion');
    var span_tipo_unidad = document.getElementById('span_tipo_unidad');
    var span_marca = document.getElementById('span_marca');
    var span_modelo = document.getElementById('span_modelo');
    var span_placa = document.getElementById('span_placa');
    var span_serie = document.getElementById('span_serie');
    var span_motor = document.getElementById('span_motor');
    var span_email_alarma = document.getElementById('span_email_alarma');
    var span_imei = document.getElementById('span_imei');

    div_descripcion.classList.remove('has-error');
    div_tipo_unidad.classList.remove('has-error');
    div_marca.classList.remove('has-error');
    div_modelo.classList.remove('has-error');
    div_placa.classList.remove('has-error');
    div_serie.classList.remove('has-error');
    div_motor.classList.remove('has-error');
    div_email_alarma.classList.remove('has-error');
    div_imei.classList.remove('has-error');

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
            descripcion : descripcion.value,
            cooperativa_id:cooperativa_id,
            modelo:modelo.value,
            marca:marca.value,
            serie:serie.value,
            tipo_unidad_id:tipo_unidad_id.value,
            motor:motor.value,
            email_alarma:email_alarma.value,
            sistema_energizado:sistema_energizado.checked,
            contador_cero_manual:contador_cero_manual.checked,
            desconexion_sistema:desconexion_sistema.checked,
            control_velocidad:control_velocidad.checked,
            climatizada:climatizada.checked,
            rampa:rampa.checked,
            placa:placa.value,
            imei:imei.value,
            atm: atm,
            estado:"A",
            velocidad: velocidad
        }
        , function( data ) {
        if (data.error == false)
            location.reload(true);
        else
            mensajesError(data,div_descripcion,span_descripcion, div_cooperativa,span_cooperativa,
                div_tipo_unidad,span_tipo_unidad,div_motor,span_motor,div_marca,span_marca,
                div_serie,span_serie,div_modelo,span_modelo,div_placa,span_placa,
                div_email_alarma,span_email_alarma,div_imei,span_imei);
    }, "json");
}


function actualizarUnidad(url, tipo_usuario_valor, id_cooperativa)
{
    var placa = document.getElementById('placa');
    var descripcion = document.getElementById('descripcion');
    var marca = document.getElementById('marca');
    var modelo = document.getElementById('modelo');
    var serie = document.getElementById('serie');
    var motor = document.getElementById('motor');
    var tipo_unidad_id = document.getElementById('tipo_unidad_id');
    var email_alarma = document.getElementById('email_alarma');
    var sistema_energizado = document.getElementById('sistema_energizado');
    var contador_cero_manual = document.getElementById('contador_cero_manual');
    var desconexion_sistema = document.getElementById('desconexion_sistema');
    var control_velocidad = document.getElementById('control_velocidad');
    var climatizada = document.getElementById('climatizada');
    var rampa = document.getElementById('rampa');
    var imei = document.getElementById('imei');
    var atm = document.getElementById('atm').checked;
    var velocidad = document.getElementById('velocidad').value;

    var div_descripcion = document.getElementById('div-descripcion');
    var div_tipo_unidad = document.getElementById('div-tipo-unidad');
    var div_marca = document.getElementById('div-marca');
    var div_modelo = document.getElementById('div-modelo');
    var div_placa = document.getElementById('div-placa');
    var div_serie = document.getElementById('div-serie');
    var div_motor = document.getElementById('div-motor');
    var div_email_alarma = document.getElementById('div-email-alarma');
    var div_imei = document.getElementById('div-imei');

    var span_descripcion = document.getElementById('span_descripcion');
    var span_tipo_unidad = document.getElementById('span_tipo_unidad');
    var span_marca = document.getElementById('span_marca');
    var span_modelo = document.getElementById('span_modelo');
    var span_placa = document.getElementById('span_placa');
    var span_serie = document.getElementById('span_serie');
    var span_motor = document.getElementById('span_motor');
    var span_email_alarma = document.getElementById('span_email_alarma');
    var span_imei = document.getElementById('span_imei');

    div_descripcion.classList.remove('has-error');
    div_tipo_unidad.classList.remove('has-error');
    div_marca.classList.remove('has-error');
    div_modelo.classList.remove('has-error');
    div_placa.classList.remove('has-error');
    div_serie.classList.remove('has-error');
    div_motor.classList.remove('has-error');
    div_email_alarma.classList.remove('has-error');
    div_imei.classList.remove('has-error');

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
        descripcion : descripcion.value,
        cooperativa_id:cooperativa_id,
        modelo:modelo.value,
        tipo_unidad_id:tipo_unidad_id.value,
        marca:marca.value, serie:serie.value,
        motor:motor.value,email_alarma:email_alarma.value,
        sistema_energizado:sistema_energizado.checked,
        contador_cero_manual:contador_cero_manual.checked,
        desconexion_sistema:desconexion_sistema.checked,
        control_velocidad:control_velocidad.checked,
        climatizada:climatizada.checked,
        rampa:rampa.checked,
        placa:placa.value,
        imei:imei.value,
        atm: atm,
        _method : 'PUT',
        velocidad : velocidad
    } ,function( data ) {
        if (data.error == false)
            location.reload(true);
        else
            mensajesError(data,div_descripcion,span_descripcion, div_cooperativa,span_cooperativa,
                div_tipo_unidad,span_tipo_unidad,div_motor,span_motor,div_marca,span_marca,
                div_serie,span_serie,div_modelo,span_modelo,div_placa,span_placa,
                div_email_alarma,span_email_alarma,div_imei,span_imei);
       }, "json");
}

function estadoUnidad(url,check)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar esta unidad?');
    else
        $confirmation = confirm('¿Está seguro que desea activar esta unidad?');

    if ($confirmation == true)
    {
        $.post(url, {

            _method : 'DELETE'
        } ,function(  ) {
            if(!check)
                alert('La unidad ha sido inactivada con éxito.');
            else
                alert('La unidad ha sido activada con éxito.');
            location.reload(true);
        }, "json");
    }
    else
        location.reload(true);
}


function mensajesError(data,div_descripcion,span_descripcion, div_cooperativa,span_cooperativa,
                       div_tipo_unidad,span_tipo_unidad,div_motor,span_motor,div_marca,span_marca,
                       div_serie,span_serie,div_modelo,span_modelo,div_placa,span_placa,
                       div_email_alarma,span_email_alarma,div_imei,span_imei)
{

    if (data.messages.hasOwnProperty('descripcion')){
        div_descripcion.classList.add('has-error');
        span_descripcion.innerHTML = '<strong>' + data.messages.descripcion + '</strong>';
    }

    if (data.messages.hasOwnProperty('imei')){
        div_imei.classList.add('has-error');
        span_imei.innerHTML = '<strong>' + data.messages.imei + '</strong>';
    }

    if(div_cooperativa!=null)
    {
        if (data.messages.hasOwnProperty('cooperativa_id')){
            div_cooperativa.classList.add('has-error');
            span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
        }
    }

    if (data.messages.hasOwnProperty('tipo_unidad_id')){
        div_tipo_unidad.classList.add('has-error');
        span_tipo_unidad.innerHTML = '<strong>' + data.messages.tipo_unidad_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('motor')){
        div_motor.classList.add('has-error');
        span_motor.innerHTML = '<strong>' + data.messages.motor + '</strong>';
    }

    if (data.messages.hasOwnProperty('marca')){
        div_marca.classList.add('has-error');
        span_marca.innerHTML = '<strong>' + data.messages.marca + '</strong>';
    }

    if (data.messages.hasOwnProperty('serie')){
        div_serie.classList.add('has-error');
        span_serie.innerHTML = '<strong>' + data.messages.serie + '</strong>';
    }

    if (data.messages.hasOwnProperty('modelo')){
        div_modelo.classList.add('has-error');
        span_modelo.innerHTML = '<strong>' + data.messages.modelo + '</strong>';
    }

    if (data.messages.hasOwnProperty('placa')){
        div_placa.classList.add('has-error');
        span_placa.innerHTML = '<strong>' + data.messages.placa + '</strong>';
    }
    if (data.messages.hasOwnProperty('email_alarma')){
        div_email_alarma.classList.add('has-error');
        span_email_alarma.innerHTML = '<strong>' + data.messages.email_alarma + '</strong>';
    }
}

function cleanForm(tipo_usuario_valor) {

    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_placa').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_tipo_unidad').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_motor').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_modelo').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_serie').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_email_alarma').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_marca').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_imei').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('descripcion').value='';
    document.getElementById('placa').value='';
    document.getElementById('motor').value='';
    document.getElementById('modelo').value='';
    document.getElementById('serie').value='';
    document.getElementById('email_alarma').value='';
    document.getElementById('marca').value='';
    document.getElementById('imei').value='';
    document.getElementById('tipo_unidad_id').value='';
    document.getElementById('sistema_energizado').checked=false;
    document.getElementById('contador_cero_manual').checked=false;
    document.getElementById('desconexion_sistema').checked=false;
    document.getElementById('climatizada').checked=false;
    document.getElementById('rampa').checked=false;
    document.getElementById('atm').checked = false;
    document.getElementById('velocidad').value = '';

    document.getElementById('div-descripcion').classList.remove('has-error');
    document.getElementById('div-placa').classList.remove('has-error');
    document.getElementById('div-tipo-unidad').classList.remove('has-error');
    document.getElementById('div-motor').classList.remove('has-error');
    document.getElementById('div-modelo').classList.remove('has-error');
    document.getElementById('div-serie').classList.remove('has-error');
    document.getElementById('div-email-alarma').classList.remove('has-error');
    document.getElementById('div-marca').classList.remove('has-error');
    document.getElementById('div-imei').classList.remove('has-error');

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
