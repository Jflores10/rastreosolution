

var actual_id = null;



function editarPuntoControl(url, tipo_usuario_valor)
{
   cleanForm2(tipo_usuario_valor);
    var entrada = document.getElementById('entrada');
    var salida = document.getElementById('salida');
    $.get(url, function ( data ) {
        actual_id = data._id;
        latitud.value=data.latitud;
        longitud.value=data.longitud;
        pdi.value=data.pdi;
        if(tipo_usuario_valor==1)
          cooperativa_id.value=data.cooperativa_id;
        var position = { lat : parseFloat(latitud.value), lng : parseFloat(longitud.value) };
        marker.setPosition(position);
        circle.setCenter(position);
        map.setCenter(position);
        radio.value = data.radio;
        if (data.entrada && data.salida)
        {
            $('#otro').prop('checked', false);
            $('#otro').trigger('click');
            entrada.value = data.entrada;
            salida.value = data.salida;
        }
        else 
        {
            $('#otro').prop('checked', true);
            $('#otro').trigger('click');
        }
        descripcion.value=data.descripcion;
        circle.setRadius(parseFloat(radio.value));
    }, "json");
}

function crearPuntoControl(url, tipo_usuario_valor, id_cooperativa)
{
    var descripcion = document.getElementById('descripcion');
    var latitud = document.getElementById('latitud');
    var longitud = document.getElementById('longitud');
    var radio = document.getElementById('radio');
    var pdi = document.getElementById('pdi');
    var entrada = document.getElementById('entrada');
    var salida = document.getElementById('salida');
    var otro = document.getElementById('otro');


    var div_descripcion = document.getElementById('div-descripcion');
    var div_latitud = document.getElementById('div-latitud');
    var div_longitud = document.getElementById('div-longitud');
    var div_radio = document.getElementById('div-radio');
    var div_pdi = document.getElementById('div-pdi');
    var div_entrada = document.getElementById('div_entrada');
    var div_salida = document.getElementById('div_salida');

    var span_descripcion = document.getElementById('span_descripcion');
    var span_latitud = document.getElementById('span_latitud');
    var span_longitud = document.getElementById('span_longitud');
    var span_radio = document.getElementById('span_radio');
    var span_pdi = document.getElementById('span_pdi');
    var span_salida = document.getElementById('span_salida');
    var span_entrada = document.getElementById('span_entrada');

    div_descripcion.classList.remove('has-error');
    div_latitud.classList.remove('has-error');
    div_longitud.classList.remove('has-error');
    div_radio.classList.remove('has-error');
    div_pdi.classList.remove('has-error');
    div_entrada.classList.remove('has-error');
    div_salida.classList.remove('has-error');


    var param;

    if(tipo_usuario_valor=='1')
    {
        var cooperativa_id = document.getElementById('cooperativa_id');
        var div_cooperativa = document.getElementById('div-cooperativa');
        var span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');

        param = {
            pdi: pdi.value,
            descripcion: descripcion.value,
            latitud: latitud.value,
            longitud: longitud.value,
            radio: radio.value,
            cooperativa_id: cooperativa_id.value,
            estado: "A",
            entrada: entrada.value,
            salida: salida.value,
            otro: otro.checked
        }
    }
    else
      {
        param = {
              pdi: pdi.value,
              descripcion : descripcion.value,
              latitud:latitud.value,
              longitud:longitud.value,
              radio:radio.value,
              cooperativa_id:id_cooperativa,
              estado:"A",
              entrada: entrada.value,
              salida: salida.value,
              otro: otro.checked
          }
     }

    $.post(url,param,
        function( data ) {
        if (data.error == false){
            alert('El punto de control ha sido creado con éxito.');
            location.reload(true);
        }
        else
            if(tipo_usuario_valor!='1')
                mensajesError(
                    data,div_descripcion,span_descripcion,div_latitud,span_latitud,
                    div_longitud,span_longitud,div_radio,span_radio,null,null,div_pdi,span_pdi);
            else
                mensajesError(
                    data,div_descripcion,span_descripcion,div_latitud,span_latitud,
                    div_longitud,span_longitud,div_radio,span_radio,div_cooperativa,span_cooperativa,div_pdi,span_pdi);
    }, "json");
}


function actualizarPuntoControl(url, tipo_usuario_valor, id_cooperativa)
{
    var descripcion = document.getElementById('descripcion');
    var latitud = document.getElementById('latitud');
    var longitud = document.getElementById('longitud');
    var radio = document.getElementById('radio');
    var pdi = document.getElementById('pdi');
    var otro = document.getElementById('otro');
    var entrada = document.getElementById('entrada');
    var salida = document.getElementById('salida');

    var div_descripcion = document.getElementById('div-descripcion');
    var div_latitud = document.getElementById('div-latitud');
    var div_longitud = document.getElementById('div-longitud');
    var div_radio = document.getElementById('div-radio');
    var div_pdi = document.getElementById('div-pdi');
    var div_entrada = document.getElementById('div_entrada');
    var div_salida = document.getElementById('div_salida');

    var span_descripcion = document.getElementById('span_descripcion');
    var span_latitud = document.getElementById('span_latitud');
    var span_longitud = document.getElementById('span_longitud');
    var span_radio = document.getElementById('span_radio');
    var span_pdi = document.getElementById('span_pdi');
    var span_entrada = document.getElementById('span_entrada');
    var span_salida = document.getElementById('span_salida');

    div_descripcion.classList.remove('has-error');
    div_latitud.classList.remove('has-error');
    div_longitud.classList.remove('has-error');
    div_radio.classList.remove('has-error');
    div_pdi.classList.remove('has-error');
    div_entrada.classList.remove('has-error');
    div_salida.classList.remove('has-error');

    var cooperativa_id;
    var div_cooperativa;
    var span_cooperativa;

    var param;

    if(tipo_usuario_valor=='1') {

         cooperativa_id = document.getElementById('cooperativa_id');
         div_cooperativa = document.getElementById('div-cooperativa');
         span_cooperativa = document.getElementById('span_cooperativa');
         div_cooperativa.classList.remove('has-error');

        param = {
            pdi:pdi.value,
            descripcion : descripcion.value,
            latitud:latitud.value,
            longitud:longitud.value,
            radio:radio.value,
            cooperativa_id:cooperativa_id.value,
            _method : 'PUT',
            entrada: entrada.value,
            salida: salida.value,
            otro: otro.checked
        }
    }
    else
    {
        param = {
            pdi:pdi.value,
            descripcion : descripcion.value,
            latitud:latitud.value,
            longitud:longitud.value,
            radio:radio.value,
            cooperativa_id:id_cooperativa,
            _method : 'PUT',
            entrada: entrada.value,
            salida: salida.value,
            otro: otro.checked
        }
    }

    $.post(url, param
       , function( data ) {
        if (data.error == false){
            alert('El punto de control ha sido actualizado con éxito.');
            location.reload(true);
        }
        else
        {
            if(tipo_usuario_valor!='1')
                mensajesError(
                    data,div_descripcion,span_descripcion,div_latitud,span_latitud,
                    div_longitud,span_longitud,div_radio,span_radio,null,null,div_pdi,span_pdi);
            else
                mensajesError(
                    data,div_descripcion,span_descripcion,div_latitud,span_latitud,
                    div_longitud,span_longitud,div_radio,span_radio,div_cooperativa,
                    span_cooperativa,div_pdi,span_pdi);

        }

    }, "json");
}

/*function eliminarPuntoControl(url)
{
    $confirmation = confirm('¿Está seguro que desea eliminar este punto de control?');
    if ($confirmation == true)
    {
        $.post(url, { descripcion : descripcion.value, _method : 'DELETE' }, function( data ) {
            alert('El punto de control ha sido eliminado con exito.');
            location.reload(true);
        }, "json");
    }
}*/

function estadoPuntoControl(url,check,descripcion)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar el punto de control ' + descripcion + ' ?');
    else
        $confirmation = confirm('¿Está seguro que desea activar el punto de control '+ descripcion + ' ?');

    if ($confirmation == true)
    {
        $.post(url, {
            _method : 'DELETE'

        } ,function(data) {
            if(!check)
            {

                if(data.estado=='I')
                    alert('El punto de control '+ descripcion +' ha sido inactivado con éxito.');
                else
                    alert('No se puede inactivar el punto de control '+ descripcion +'.');
            }
            else
                alert('El punto de control '+ descripcion +' ha sido activado con éxito.');

        }, "json");

    }

        location.reload(true);
}

function mensajesError(data,div_descripcion,span_descripcion,div_latitud,span_latitud,
                        div_longitud,span_longitud,div_radio,span_radio,div_cooperativa,
                         span_cooperativa,div_pdi,span_pdi)
{
    if (data.messages.hasOwnProperty('descripcion')){
        div_descripcion.classList.add('has-error');
        span_descripcion.innerHTML = '<strong>' + data.messages.descripcion + '</strong>';
    }

    if (data.messages.hasOwnProperty('pdi')){
        div_pdi.classList.add('has-error');
        span_pdi.innerHTML = '<strong>' + data.messages.pdi + '</strong>';
    }

    if (data.messages.hasOwnProperty('latitud')){
        div_latitud.classList.add('has-error');
        span_latitud.innerHTML = '<strong>' + data.messages.latitud + '</strong>';
    }

    if (data.messages.hasOwnProperty('longitud')){
        div_longitud.classList.add('has-error');
        span_longitud.innerHTML = '<strong>' + data.messages.longitud + '</strong>';
    }

    if (data.messages.hasOwnProperty('radio')){
        div_radio.classList.add('has-error');
        span_radio.innerHTML = '<strong>' + data.messages.radio + '</strong>';
    }

    if(div_cooperativa!=null)
        if (data.messages.hasOwnProperty('cooperativa_id')){
            div_cooperativa.classList.add('has-error');
            span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
        }
    var div_entrada = document.getElementById('div_entrada');
    var div_salida = document.getElementById('div_salida');
    var span_entrada = document.getElementById('span_entrada');
    var span_salida = document.getElementById('span_salida');
    if (data.messages.hasOwnProperty('entrada')){
        div_entrada.classList.add('has-error');
        span_entrada.innerHTML = '<strong>' + data.messages.entrada + '</strong>';
    }
    if (data.messages.hasOwnProperty('salida')){
        div_salida.classList.add('has-error');
        span_salida.innerHTML = '<strong>' + data.messages.salida + '</strong>';
    }
}
function cleanForm2 (tipo_usuario_valor)
{
    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_latitud').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_longitud').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_radio').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_pdi').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('descripcion').value='';
    document.getElementById('longitud').value='';
    document.getElementById('latitud').value='';
    document.getElementById('radio').value='';
    document.getElementById('pdi').value='';

    document.getElementById('div-descripcion').classList.remove('has-error');
    document.getElementById('div-longitud').classList.remove('has-error');
    document.getElementById('div-latitud').classList.remove('has-error');
    document.getElementById('div-radio').classList.remove('has-error');
    document.getElementById('div-pdi').classList.remove('has-error');

    if(tipo_usuario_valor=='1')
    {
        document.getElementById('cooperativa_id').value='';
        document.getElementById('div-cooperativa').classList.remove('has-error');
        document.getElementById('span_cooperativa').innerHTML = '<strong>' + '' + '</strong>';
    }
    document.getElementById('entrada').value = '';
    document.getElementById('salida').value = '';
    document.getElementById('otro').checked = false;
    $('#entrada').prop('readonly', true);
    $('#salida').prop('readonly', true);

}

function cleanForm(tipo_usuario_valor) {

    cleanForm2(tipo_usuario_valor);



    actual_id=null;
}



$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';

$("[name='chk_estado']").bootstrapSwitch();
