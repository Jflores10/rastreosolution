
var actual_id = null;

function editarTipoUnidad(url)
{
    cleanForm();
    $.get(url, function ( data ) {
        actual_id = data._id;
        descripcion.value = data.descripcion;
    }, "json");
}
function crearTipoUnidad(url)
{
    var descripcion = document.getElementById('descripcion');
    var div_descripcion = document.getElementById('div-descripcion');
    var span_descripcion = document.getElementById('span_descripcion');
    div_descripcion.classList.remove('has-error');

    $.post(url, {
        descripcion : descripcion.value,
        estado:"A"
    }, function( data ) {
        if (data.error == false){
            alert('El tipo de unidad ha sido creado con exito.');
            location.reload(true);
        }
        else
            mensajesErrores(data,div_descripcion,span_descripcion);
    }, "json");
}
function actualizarTipoUnidad(url)
{
    var descripcion = document.getElementById('descripcion');
    var div_descripcion = document.getElementById('div-descripcion');
    var span_descripcion = document.getElementById('span_descripcion');
    div_descripcion.classList.remove('has-error');

    $.post(url, { descripcion : descripcion.value, _method : 'PUT' }, function( data ) {
        if (data.error == false){
            alert('El tipo de unidad ha sido actualizado con exito.');
            location.reload(true);
        }
        else
            mensajesErrores(data,div_descripcion,span_descripcion);
        }, "json");
}

function estadoTipoUnidad(url,check)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar este tipo de unidad?');
    else
        $confirmation = confirm('¿Está seguro que desea activar este tipo de unidad?');

    if ($confirmation == true)
    {
        $.post(url, {
            _method : 'DELETE'

        } ,function(data) {
            if(!check)
            {

                if(data.estado=='I')
                    alert('El tipo de unidad ha sido inactivado con éxito.');
                else
                    alert('No se puede inactivar el tipo de unidad seleccionado.');
            }
            else
                alert('El tipo de unidad ha sido activado con éxito.');

            location.reload(true);
        }, "json");
    }
    else
        location.reload(true);
}

function mensajesErrores(data,div_descripcion,span_descripcion)
{
    if (data.messages.hasOwnProperty('descripcion')){
        div_descripcion.classList.add('has-error');
        span_descripcion.innerHTML = '<strong>' + data.messages.descripcion + '</strong>';
    }
}

function cleanForm() {

    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('descripcion').value='';
    document.getElementById('div-descripcion').classList.remove('has-error');
    actual_id=null;
}

/*function eliminarTipoUnidad(url)
{
    $confirmation = confirm('¿Está seguro que desea eliminar este tipo de unidad?');
    if ($confirmation == true)
    {
        $.post(url, { descripcion : descripcion.value, _method : 'DELETE' }, function( data ) {
            alert('El tipo de unidad ha sido eliminado con exito.');
            location.reload(true);
        }, "json");
    }
}*/


$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';
$("[name='chk_estado']").bootstrapSwitch();
