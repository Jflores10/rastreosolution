


var actual_id = null;
var aux_valor=null;

function llenarCooperativa(array_id,array_descripcion,url)
{
    var div_cooperativa=  $('#div-cooperativa');
    div_cooperativa.empty();

    div_cooperativa.append(
        '<label for="cooperativa_id">Cooperativa</label>'+
        '<select class="form-control" id="cooperativa_id" name="cooperativa_id" onchange="getUnidadesCoop(\''+url+'\');">'+
        ' <option value="" disabled selected hidden>Seleccione...</option>'+
        ' </select>'+
        ' <span class="help-block" id="span_cooperativa"></span>'
    );
    var select=$('#cooperativa_id');
    for (var i = 0, len = array_id.length; i < len; i++)
        select.append('<option  value=\''+ array_id[i] + '\'> '+ array_descripcion[i] +'</option>');
    select.val(null);
    if (array_id.length == 1)
    {
        select.val(array_id[0]);
        select.trigger('change');
        div_cooperativa.hide();
    }
    document.getElementById('div-cooperativa').classList.remove('has-error');
}


function llenarUnidad(array_id,array_descripcion)
{
    var div_unidad=  $('#div-unidad');
    div_unidad.empty();

    div_unidad.append(
         '<label for="unidades">Unidades</label>'+
         '<div style="padding-left: 1em; height: 12em;overflow: auto;">'+
             '<ul style="list-style-type: none; margin: 0; padding: 0; overflow-x: hidden;" id="ul-unidades">'+
             '</ul>'+
         '</div>'
    );

    var ul = $('#ul-unidades');
    for (var i = 0, len = array_id.length; i < len; i++)
        ul.append(
            '<li class="unidad_css">'+
                '<label for=\''+ array_id[i] + '\' class="unidad_css">'+
                    '<input type="checkbox" name=\''+ array_id[i] + '\' id=\''+ array_id[i] + '\'>'+
                    '<option style="display: inline-block;" value=\''+ array_id[i] + '\'>'+
                       array_descripcion[i] +
                    '</option>'+
                '</label>'+
             '</li>'
        );
}

function getUnidadesSeleccionadasArray()
{
    var ul_unidades = $('#ul-unidades').find('input');
    var array_unidades_id=[];

    for (var i = 0, len = ul_unidades.length; i < len; i++)
    {
        if(ul_unidades[i].checked==true)
        {
            array_unidades_id.push(ul_unidades[i].id);
        }
    }
    return array_unidades_id;
}

function editarUsuario(url,url2, tipo_usuario_valor)
{
    var name1 = document.getElementById('name');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var tipo_usuario_id = document.getElementById('tipo_usuario_id');
    var ip = document.getElementById('ip');

    $.get(url, function ( data ) {
        cleanForm(tipo_usuario_valor);
        actual_id = data._id;
        name1.value = data.name;
        email.value = data.email;
        tipo_usuario_id.value=data.tipo_usuario_id;
        document.getElementById('correo').value = (data.correo != undefined)?data.correo:'';
        document.getElementById('operadora').value = (data.operadora != undefined)?data.operadora:'';
        document.getElementById('telefono').value = (data.telefono != undefined)?data.telefono:'';
        ip.value= (data.ip != undefined)?data.ip:'';
       /* if(tipo_usuario_valor=='1')
        {
           
            mostrarEditarTipoUsuario(url2, data);
        }*/

        mostrarEditarTipoUsuario(url2, data);
          //setIfIsSocio(url2,data);
         //mostrarCooperativa();

    }, "json");
}

function setIfIsSocio(url, data_anterior)
{
    var valor=null;
    console.log(data_anterior);
    $.post(url, {
        tipo_usuario_id: data_anterior.tipo_usuario_id,
        opcion:'getTipoUsuario'
    }, function( data ) {

        if (data.error == false)
        {
            if(data.tipo_usuario_valor == '4' || data.tipo_usuario_valor == '5' )
            {
                setUnidadesPertenecientes(data_anterior.cooperativa_id,data_anterior.unidades_pertenecientes);
            }
        }

    }, "json");

    return valor;
}


/*function mostrarEditarTipoUsuario(url_tipo, data_anterior)
{
    $.post(url_tipo, {
        tipo_usuario_id:data_anterior.tipo_usuario_id,
        opcion:'getTipoUsuario'
        }, function( data ) {
        if (data.error == false){
            if(data.tipo_usuario_valor == '1')
            {
                var div_cooperativa=  $('#div-cooperativa');
                div_cooperativa.empty();
            }
            else
            {
                var cooperativa_id=document.getElementById('cooperativa_id');
                cooperativa_id.value=data_anterior.cooperativa_id;
            }
        }
        else
        {
            alert('Ocurrió un error al momento de buscar el tipo de usuario.');
            location.reload(true);
        }

    }, "json");
}*/

function estadoUsuario(url,check)
{
    /*if(!check)
        $confirmation = confirm('¿Está seguro que desea suspender a este usuario?');
    else
        $confirmation = confirm('¿Está seguro que desea activar a este usuario?');

    if ($confirmation == true)
    {*/
        //$('#progress').modal('show');
        $.post(url, {

            _method : 'DELETE'
        } ,function() {
            //$('#progress').modal('hide');  
            
            if(!check)
                alert('El usuario ha sido suspendido con éxito.');
            else
                alert('El usuario ha sido activado con éxito.');

            location.reload(true);

            
        }, "json");
    //}
}

function crearUsuario(url,id_cooperativa,tipo_usuario_valor)
{
    var name1 = document.getElementById('name');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var tipo_usuario_id = document.getElementById('tipo_usuario_id');
    var ip = document.getElementById('ip');

    var div_nombre = document.getElementById('div-nombre');
    var div_correo_electronico = document.getElementById('div-correo-electronico');
    var div_contraseña = document.getElementById('div-contraseña');
    var div_tipo_usuario = document.getElementById('div-tipo-usuario');

    var span_nombre = document.getElementById('span_nombre');
    var span_correo_electronico = document.getElementById('span_correo_electronico');
    var span_contraseña = document.getElementById('span_contraseña');
    var span_tipo_usuario = document.getElementById('span_tipo_usuario');

    div_nombre.classList.remove('has-error');
    div_correo_electronico.classList.remove('has-error');
    div_contraseña.classList.remove('has-error');
    div_tipo_usuario.classList.remove('has-error');

    var cooperativa_id;
    var div_cooperativa;
    var span_cooperativa;

    if(id_cooperativa==null || id_cooperativa == undefined || id_cooperativa == '')
    {
        cooperativa_id = document.getElementById('cooperativa_id').value;
        div_cooperativa = document.getElementById('div-cooperativa');
        span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');
    }
    else
        cooperativa_id = id_cooperativa;

    var param;
    

    if(tipo_usuario_valor=='4' || tipo_usuario_valor=='5')
         param={
            name:name1.value,
            email:email.value,
            cooperativa_id: $('#cooperativa_id').val(),
            tipo_usuario_id:tipo_usuario_id.value,
            unidades_pertenecientes:getUnidadesSeleccionadasArray(),
            password:password.value,
            password_confirmation:password_confirmation.value,
            opcion:'crear',
            estado:"A",
            ip:ip.value,
            correo : document.getElementById('correo').value,
            operadora : document.getElementById('operadora').value,
            telefono : document.getElementById('telefono').value
        };

    else
         param={
            name:name1.value,
            email:email.value,
            cooperativa_id: $('#cooperativa_id').val(),
            tipo_usuario_id:tipo_usuario_id.value,
            unidades_pertenecientes:null,
            password:password.value,
            password_confirmation:password_confirmation.value,
            opcion:'crear',
            ip:ip.value,
            estado:"A",
            correo : document.getElementById('correo').value,
            operadora : document.getElementById('operadora').value,
            telefono : document.getElementById('telefono').value
        };

            $.post(url, param, function( data ) {
                if (data.error == false){
                    alert('El usuario ha sido creado con éxito.');
                    location.reload(true);
                }
                else
                {
                    if(id_cooperativa==null)
                        mensajesError(data,div_nombre,span_nombre,div_correo_electronico,span_correo_electronico,div_contraseña,span_contraseña,
                            div_tipo_usuario,span_tipo_usuario,div_cooperativa,span_cooperativa);
                    else
                        mensajesError(data,div_nombre,span_nombre,div_correo_electronico,span_correo_electronico,div_contraseña,span_contraseña,
                            div_tipo_usuario,span_tipo_usuario,null,null);
                }
            }, "json");
}


function actualizarUsuario(url, id_cooperativa, tipo_usuario_valor)
{
    var name1 = document.getElementById('name');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var tipo_usuario_id = document.getElementById('tipo_usuario_id');
    var ip = document.getElementById('ip');

    var div_nombre = document.getElementById('div-nombre');
    var div_correo_electronico = document.getElementById('div-correo-electronico');
    var div_contraseña = document.getElementById('div-contraseña');
    var div_tipo_usuario = document.getElementById('div-tipo-usuario');


    var span_nombre = document.getElementById('span_nombre');
    var span_correo_electronico = document.getElementById('span_correo_electronico');
    var span_contraseña = document.getElementById('span_contraseña');
    var span_tipo_usuario = document.getElementById('span_tipo_usuario');


    div_nombre.classList.remove('has-error');
    div_correo_electronico.classList.remove('has-error');
    div_contraseña.classList.remove('has-error');
    div_tipo_usuario.classList.remove('has-error');


    var cooperativa_id;
    var div_cooperativa;
    var span_cooperativa;
    if(id_cooperativa==null || id_cooperativa == undefined || id_cooperativa == '')
    {
        cooperativa_id = document.getElementById('cooperativa_id').value;
        div_cooperativa = document.getElementById('div-cooperativa');
        span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');
    }
    else
    {
        cooperativa_id = id_cooperativa;
    }

    var param;

    if(tipo_usuario_valor=='4' || tipo_usuario_valor=='5')
         param={
            name:name1.value,
            email:email.value,
            cooperativa_id:$('#cooperativa_id').val(),
            tipo_usuario_id:tipo_usuario_id.value,
            unidades_pertenecientes:getUnidadesSeleccionadasArray(),
            password:password.value,
            password_confirmation:password_confirmation.value,
            _method : 'PUT',
            correo : document.getElementById('correo').value,
            operadora : document.getElementById('operadora').value,
            telefono : document.getElementById('telefono').value,
            ip:ip.value
        };
    else
        param={
            name:name1.value,
            email:email.value,
            cooperativa_id:$('#cooperativa_id').val(),
            tipo_usuario_id:tipo_usuario_id.value,
            unidades_pertenecientes:null,
            password:password.value,
            password_confirmation:password_confirmation.value,
            _method : 'PUT',
            correo : document.getElementById('correo').value,
            operadora : document.getElementById('operadora').value,
            telefono : document.getElementById('telefono').value,
            ip:ip.value
        };
    $.post(url, param
       , function( data ) {
        if (data.error == false){
            alert('El usuario ha sido actualizado con éxito.');
            location.reload(true);
        }
        else
        {
            if(id_cooperativa==null)
                mensajesError(data,div_nombre,span_nombre,div_correo_electronico,span_correo_electronico,div_contraseña,span_contraseña,
                    div_tipo_usuario,span_tipo_usuario,div_cooperativa,span_cooperativa);
            else
                mensajesError(data,div_nombre,span_nombre,div_correo_electronico,span_correo_electronico,div_contraseña,span_contraseña,
                    div_tipo_usuario,span_tipo_usuario,null,null);
        }

    }, "json");
}



function mensajesError(data,div_nombre,span_nombre,div_correo_electronico,span_correo_electronico,div_contraseña,span_contraseña,
                  div_tipo_usuario,span_tipo_usuario,div_cooperativa,span_cooperativa) {

    if (data.messages.hasOwnProperty('name')) {
        div_nombre.classList.add('has-error');
        span_nombre.innerHTML = '<strong>' + data.messages.name + '</strong>';
    }
    if (data.messages.hasOwnProperty('email')) {
        div_correo_electronico.classList.add('has-error');
        span_correo_electronico.innerHTML = '<strong>' + data.messages.email + '</strong>';
    }
    if (data.messages.hasOwnProperty('password')) {
        div_contraseña.classList.add('has-error');
        span_contraseña.innerHTML = '<strong>' + data.messages.password + '</strong>';
    }
    if (data.messages.hasOwnProperty('tipo_usuario_id')) {
        div_tipo_usuario.classList.add('has-error');
        span_tipo_usuario.innerHTML = '<strong>' + data.messages.tipo_usuario_id + '</strong>';
    }

    if(div_cooperativa!=null)
    {
        if (data.messages.hasOwnProperty('cooperativa_id')) {
            div_cooperativa.classList.add('has-error');
            span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
        }
    }
}

function cleanForm() {

    $('#div-unidad').empty();
    document.getElementById('span_nombre').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_correo_electronico').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('span_tipo_usuario').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_contraseña').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('name').value='';
    document.getElementById('email').value='';
    document.getElementById('tipo_usuario_id').value='';
    document.getElementById('password').value='';

    document.getElementById('div-nombre').classList.remove('has-error');
    document.getElementById('div-correo-electronico').classList.remove('has-error');

    document.getElementById('div-tipo-usuario').classList.remove('has-error');
    document.getElementById('div-contraseña').classList.remove('has-error');
    document.getElementById('correo').value = '';
    document.getElementById('operadora').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('ip').value = '';

    var div_cooperativa=  $('#div-cooperativa');
    div_cooperativa.empty();

    actual_id=null;
}


$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Suspendido';
$("[name='chk_estado']").bootstrapSwitch();
