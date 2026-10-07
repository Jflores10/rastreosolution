
var actual_id = null;

// =========================
// ÍCONOS (listado y mapa)
// =========================
var CAMPOS_ICONO = ['icono_lista', 'icono_mapa'];
var ICONO_MAX_BYTES = 2 * 1024 * 1024;
var ICONO_TIPOS = ['image/png', 'image/jpeg', 'image/webp'];
var iconos_actuales = {};      // campo -> url actual guardada
var iconos_objurl = {};        // campo -> object URL de la vista previa

function setPreviewIcono(campo, src) {
    var img = document.getElementById(campo + '_preview');
    if (!img) return;
    if (iconos_objurl[campo] && src !== iconos_objurl[campo]) {
        URL.revokeObjectURL(iconos_objurl[campo]);
        iconos_objurl[campo] = null;
    }
    // Si el campo tiene un ícono por defecto propio (glifo fa-bus del listado), se muestra ese en lugar de la imagen.
    var porDefecto = document.getElementById(campo + '_default');
    var mostrarDefecto = function (mostrar) {
        if (!porDefecto) return;
        porDefecto.style.display = mostrar ? 'inline-flex' : 'none';
        img.style.display = mostrar ? 'none' : '';
    };
    img.onerror = function () {
        img.onerror = null;
        img.src = img.getAttribute('data-default');
        mostrarDefecto(true);
    };
    mostrarDefecto(!src);
    img.src = src || img.getAttribute('data-default');
}

function setErrorIcono(campo, msg) {
    var div = document.getElementById('div-' + campo);
    var span = document.getElementById('span_' + campo);
    if (!div || !span) return;
    if (msg) {
        div.classList.add('has-error');
        span.innerHTML = '<strong>' + msg + '</strong>';
    } else {
        div.classList.remove('has-error');
        span.innerHTML = '';
    }
}

function archivoIcono(campo) {
    var input = document.getElementById(campo);
    return (input && input.files && input.files.length > 0) ? input.files[0] : null;
}

function validarIconoCliente(file) {
    if (!file) return null;
    if (ICONO_TIPOS.indexOf(file.type) === -1)
        return 'El ícono debe ser una imagen PNG, JPG o WEBP.';
    if (file.size > ICONO_MAX_BYTES)
        return 'El ícono no debe superar los 2 MB.';
    return null;
}

function previsualizarIcono(campo) {
    var file = archivoIcono(campo);
    var error = validarIconoCliente(file);
    setErrorIcono(campo, error);
    if (!file || error) {
        document.getElementById(campo).value = '';
        setPreviewIcono(campo, document.getElementById('quitar_' + campo).checked ? null : iconos_actuales[campo]);
        return;
    }
    document.getElementById('quitar_' + campo).checked = false;
    var objUrl = URL.createObjectURL(file);
    setPreviewIcono(campo, objUrl);
    iconos_objurl[campo] = objUrl;
}

function toggleQuitarIcono(campo) {
    if (document.getElementById('quitar_' + campo).checked) {
        document.getElementById(campo).value = '';
        setErrorIcono(campo, null);
        setPreviewIcono(campo, null);
    } else {
        setPreviewIcono(campo, iconos_actuales[campo]);
    }
}

function mostrarIconosActuales(data) {
    CAMPOS_ICONO.forEach(function (campo) {
        iconos_actuales[campo] = data[campo + '_url'] || null;
        document.getElementById('div-quitar_' + campo).style.display = iconos_actuales[campo] ? '' : 'none';
        setPreviewIcono(campo, iconos_actuales[campo]);
    });
}

function limpiarIconos() {
    CAMPOS_ICONO.forEach(function (campo) {
        document.getElementById(campo).value = '';
        document.getElementById('quitar_' + campo).checked = false;
        document.getElementById('div-quitar_' + campo).style.display = 'none';
        iconos_actuales[campo] = null;
        setErrorIcono(campo, null);
        setPreviewIcono(campo, null);
    });
}

// Devuelve false (y muestra el error) si algún archivo seleccionado no es válido.
function iconosValidosAntesDeEnviar() {
    var ok = true;
    CAMPOS_ICONO.forEach(function (campo) {
        var error = validarIconoCliente(archivoIcono(campo));
        setErrorIcono(campo, error);
        if (error) ok = false;
    });
    return ok;
}

function construirFormDataTipoUnidad(campos) {
    var fd = new FormData();
    for (var k in campos) {
        if (campos.hasOwnProperty(k))
            fd.append(k, (campos[k] === null || campos[k] === undefined) ? '' : campos[k]);
    }
    CAMPOS_ICONO.forEach(function (campo) {
        var file = archivoIcono(campo);
        if (file)
            fd.append(campo, file);
        else if (document.getElementById('quitar_' + campo).checked)
            fd.append('quitar_' + campo, 'true');
    });
    return fd;
}

function enviarTipoUnidad(url, formData, mensajeExito)
{
    var div_descripcion = document.getElementById('div-descripcion');
    var span_descripcion = document.getElementById('span_descripcion');
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function( data ) {
            if (data.error == false){
                alert(mensajeExito);
                location.reload(true);
            }
            else
                mensajesErrores(data,div_descripcion,span_descripcion);
        }
    }).fail(function () {
        alert('Error al guardar el tipo de unidad. Revise los datos e intente de nuevo.');
    });
}

function editarTipoUnidad(url)
{
    cleanForm();
    $.get(url, function ( data ) {
        actual_id = data._id;
        descripcion.value = data.descripcion;
        mostrarIconosActuales(data);
    }, "json");
}
function crearTipoUnidad(url)
{
    var descripcion = document.getElementById('descripcion');
    document.getElementById('div-descripcion').classList.remove('has-error');
    if (!iconosValidosAntesDeEnviar())
        return;

    enviarTipoUnidad(url, construirFormDataTipoUnidad({
        descripcion : descripcion.value,
        estado:"A"
    }), 'El tipo de unidad ha sido creado con exito.');
}
function actualizarTipoUnidad(url)
{
    var descripcion = document.getElementById('descripcion');
    document.getElementById('div-descripcion').classList.remove('has-error');
    if (!iconosValidosAntesDeEnviar())
        return;

    // PHP no procesa archivos en PUT: se envía POST con _method=PUT (method spoofing).
    enviarTipoUnidad(url, construirFormDataTipoUnidad({
        descripcion : descripcion.value,
        _method : 'PUT'
    }), 'El tipo de unidad ha sido actualizado con exito.');
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
    CAMPOS_ICONO.forEach(function (campo) {
        if (data.messages.hasOwnProperty(campo)) {
            var msg = data.messages[campo];
            setErrorIcono(campo, Array.isArray(msg) ? msg[0] : msg);
        }
    });
}

function cleanForm() {

    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('descripcion').value='';
    document.getElementById('div-descripcion').classList.remove('has-error');
    limpiarIconos();
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
