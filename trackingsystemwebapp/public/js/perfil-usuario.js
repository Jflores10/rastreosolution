



function actualizarUsuario(url,opcion)
{

    var name1 = document.getElementById('name');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var contraseña_actual = document.getElementById('contrasena_actual');
    var password_confirmation = document.getElementById('password_confirmation');

    var div_nombre = document.getElementById('div-nombre');
    var div_correo_electronico = document.getElementById('div-correo-electronico');
    var div_contraseña = document.getElementById('div-contraseña');
    var div_contraseña_actual = document.getElementById('div-contrasena-actual');
    var div_password_confirmation = document.getElementById('div-password-confirmation');


    var span_name1 = document.getElementById('span_name1');
    var span_correo_electronico = document.getElementById('span_correo_electronico');
    var span_contraseña_actual = document.getElementById('span_contrasena_actual');
    var span_password = document.getElementById('span_password');
    var span_password_confirmation=document.getElementById('span_password_confirmation');

    div_nombre.classList.remove('has-error');
    div_correo_electronico.classList.remove('has-error');
    div_contraseña.classList.remove('has-error');

    switch(opcion)
    {
        case "cambio_datos":
        $.post(url, {
            name : name1.value,
            email : email.value,
            tipo:"cambiar_datos",
            _method : 'PUT' }, function( data ) {
            if (data.error == false){
                alert('Los datos han sido actualizados con éxito.');
                location.reload(true);
            }
            else
            {
                if (data.messages.hasOwnProperty('name')){
                    div_nombre.classList.add('has-error');
                    span_name1.innerHTML = '<strong>' + data.messages.name + '</strong>';
                }


                if (data.messages.hasOwnProperty('email')){
                    div_correo_electronico.classList.add('has-error');
                    span_correo_electronico.innerHTML = '<strong>' + data.messages.email + '</strong>';
                }
            }
        } , "json");
        break;

        case "cambio_contraseña":

            $.post(url, {
                password : password.value,
                password_confirmation:password_confirmation.value,
                tipo:"cambiar_contraseña",
                contraseña_actual : contraseña_actual.value,
                _method : 'PUT' }, function( data ) {

                if (data.error == false){
                    alert('La contraseña ha sido cambiada con éxito.');
                    location.reload(true);
                }
                else
                {
                    switch(data.tipo_error)
                    {
                        case 'validacion':
                            mensajesError(data, div_contraseña,span_password,div_contraseña_actual,span_contraseña_actual,div_password_confirmation,
                            span_password_confirmation);
                            break;
                        case 'contraseña_actual':
                            div_contraseña_actual.classList.add('has-error');
                            span_contraseña_actual.innerHTML = '<strong>' + 'La contraseña actual no es la correcta.' + '</strong>';
                            document.getElementById('span_password').innerHTML = '<strong>' + '' + '</strong>';
                            document.getElementById('span_password_confirmation').innerHTML = '<strong>' + '' + '</strong>';

                            document.getElementById('password').value='';
                            document.getElementById('password_confirmation').value='';

                            document.getElementById('div-contraseña').classList.remove('has-error');
                            document.getElementById('div-password-confirmation').classList.remove('has-error');

                            break;
                        default:break;
                    }
                }
            } , "json");
            break;

        default: break;
    }

}

function mensajesError(data, div_contraseña,span_password,div_contraseña_actual,span_contraseña_actual,div_password_confirmation,
                       span_password_confirmation)
{

    if (data.messages.hasOwnProperty('password')){
        div_contraseña.classList.add('has-error');
        span_password.innerHTML = '<strong>' + data.messages.password + '</strong>';
    }
    if (data.messages.hasOwnProperty('contraseña_actual')){
        div_contraseña_actual.classList.add('has-error');
        span_contraseña_actual.innerHTML = '<strong>' + data.messages.contraseña_actual + '</strong>';
    }
    if (data.messages.hasOwnProperty('password_confirmation')){
        div_password_confirmation.classList.add('has-error');
        span_password_confirmation.innerHTML = '<strong>' + data.messages.password_confirmation + '</strong>';
    }
 }







/**
 * Created by José Daniel on 26/09/2016.
 */
