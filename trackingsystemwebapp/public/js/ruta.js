/**
 * Created by José Daniel on 09/10/2016.
 */

var actual_id = null;
var array_ruta=null;
var masc = false;


function cleanFormAgregarPunto()
{
    document.getElementById('punto_control').value='';
    document.getElementById('adelanto').value='';
    document.getElementById('atraso').value='';
    document.getElementById('search').value='';
    document.getElementById('tiempo-llegada').value='';
    document.getElementById('search').value='';
    document.getElementById('div-set-punto-control').value='';
}

function llenarUnidades(url, tipo_usuario_valor, id_cooperativa)
{
    var cooperativa_id;

    if(tipo_usuario_valor=='1')
        cooperativa_id = document.getElementById('cooperativa_id').value;
    else
        cooperativa_id=id_cooperativa;

    var div_unidad=  $('#div-unidad');
    div_unidad.empty();

    var tipo_ruta_padre=  $('#div-tipo_ruta_padre');
    tipo_ruta_padre.empty();

    var tipo_ruta=  $('#div-tipo_ruta');
    tipo_ruta.empty();

    $.post(url, {
        cooperativa_id:cooperativa_id,
        opcion:'getUnidades'
    }, function( data ) {
        console.log(data);
        let coo = data.cooperativa;
        masc = (coo.mascara == 'S');
        div_unidad.append(
            '<label for="unidad_id">Unidad</label>'+
            '<select class="form-control" id="unidad_id" name="unidad_id">'+
            ' <option value="" disabled selected hidden>Seleccione...</option>'+
            ' </select>'+
            ' <span class="help-block" id="span_unidad"></span>'
        );
        var select=$('#unidad_id');
        for (var i = 0, len = data.unidades.length; i < len; i++)
            select.append('<option  value=\''+ data.unidades[i]._id + '\'> '+  data.unidades[i].descripcion +'</option>');
    }, "json");

    $.post(url, {
        cooperativa_id:cooperativa_id,
        opcion:'getRutasPadres'
    }, function( data ) {
        tipo_ruta.append(
            '<label for="tipo_ruta">Tipo ruta</label>'+
            '<select class="form-control" id="tipo_ruta" name="tipo_ruta"  onchange="verificarruta();">'+
            '<option value="" disabled selected hidden>Seleccione...</option>'+
            '<option value="I">Individual</option>'+
            '<option value="P">Padre</option>'+
            '<option value="H">Hijo</option>'+
            '<option value="C">Cooperativa</option>'+
            ' </select>'+
            ' <span class="help-block" id="span_tipo_ruta"></span>'
        );

        tipo_ruta_padre.append(
            '<label for="tipo_ruta_padre">Ruta Padre</label>'+
            '<select class="form-control" id="tipo_ruta_padre" name="tipo_ruta_padre">'+
            '<option value="" disabled selected hidden>Seleccione...</option>'+
            ' </select>'+
            ' <span class="help-block" id="span_tipo_ruta_padre"></span>'
        );

        var select=$('#tipo_ruta_padre');
        for (var i = 0, len = data.rutaspadres.length; i < len; i++)
            select.append('<option  value=\''+ data.rutaspadres[i]._id + '\'> '+  data.rutaspadres[i].descripcion +'</option>');
    }, "json");
}


function consultarRuta(url,opcion,urlRetorno,tipo_usuario_valor,id_cooperativa)
{
    cleanForm();
    var fecha_inicio =  document.getElementById('fecha_inicio');
    var fecha_fin =  document.getElementById('fecha_fin');
    var unidad_id =  document.getElementById('unidad_id');
    var descripcion = document.getElementById('descripcion');
    var tipo_ruta = document.getElementById('tipo_ruta');
    var tipo_ruta_padre = document.getElementById('tipo_ruta_padre');
    var tipo_ruta_atm = document.getElementById('tipo_ruta_atm');

    var div_unidad = document.getElementById('div-unidad');
    var div_fecha_inicio = document.getElementById('div-fecha-inicio');
    var div_fecha_fin = document.getElementById('div-fecha-fin');
    var div_descripcion = document.getElementById('div-descripcion');

    var span_unidad = document.getElementById('span_unidad');
    var span_fecha_inicio = document.getElementById('span_fecha_inicio');
    var span_fecha_fin = document.getElementById('span_fecha_fin');
    var span_descripcion = document.getElementById('span_descripcion');
    var span_tipo_ruta = document.getElementById('span_tipo_ruta');
    var span_tipo_ruta_padre = document.getElementById('span_tipo_ruta_padre');

    var list = document.getElementsByName('puntos_control[]');
    var list_json=[];

    div_fecha_inicio.classList.remove('has-error');
    div_fecha_fin.classList.remove('has-error');
    div_unidad.classList.remove('has-error');
    div_descripcion.classList.remove('has-error');

    var div_cooperativa;
    var cooperativa_id = id_cooperativa;
    var span_cooperativa;

    if(tipo_usuario_valor=='1')
    {
        div_cooperativa = document.getElementById('div-cooperativa');
        cooperativa_id = document.getElementById('cooperativa_id').value;
        span_cooperativa = document.getElementById('span_cooperativa');
        div_cooperativa.classList.remove('has-error');
    }


    if(list!=null || list.length>0) {
        for (var i = 0; i < list.length; i++) {
            aux = JSON.parse(list[i].value);
            aux.secuencia = i + 1;
            list_json.push(aux);
        }
    }

    if(opcion=='consulta')
    {
        $.post(url, {
            fecha_inicio : fecha_inicio.value, fecha_fin : fecha_fin.value, unidad_id : unidad_id.value,
                cooperativa_id:cooperativa_id, opcion:opcion},
            function( data ) {
                if(data.error==false)
                {
                    sortJsonArrayByProperty(data.array_ruta,'fecha');
                    array_ruta=data.array_ruta;

                    if(array_ruta.length==0||array_ruta==null)
                        alert('No se encontró ninguna ruta.');

                    else
                        generateRoute(array_ruta);
                }
                else
                {
                    if(tipo_usuario_valor=='1')
                        mensajesErrorConsulta(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,span_descripcion,
                            div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                            span_tipo_ruta_padre);
                   else
                        mensajesErrorConsulta(data,null,null,div_unidad,span_unidad,span_descripcion,
                            div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                            span_tipo_ruta_padre);
                }

            },"json");
    }

    if(opcion=='guardar')
    {
        var arrayDias = document.getElementsByName('dia[]');
        var arrayDesdes = document.getElementsByName('desde[]');
        var arrayHastas = document.getElementsByName('hasta[]');
        var color_ruta = document.getElementById('color_ruta');
        var aDias = [];
        var aDesdes = [];
        var aHastas = [];

        for(var i = 0; i < arrayDias.length; i++) {
            aDias.push(arrayDias[i].value);
            aDesdes.push(arrayDesdes[i].value);
            aHastas.push(arrayHastas[i].value);
        }
       if(array_ruta==null || array_ruta.length==0)
        {
            alert('No se encontró ninguna ruta, por favor realice una consulta.');
        }
        else
        {
            console.log('cooperativa_id');
            console.log(cooperativa_id);
            console.log(array_ruta);
            var array_param;
            if(list_json.length>0)
                array_param={
                    opcion:opcion,
                    descripcion:descripcion.value,
                    array_ruta:array_ruta,
                    tipo_ruta:tipo_ruta.value,
                    tipo_ruta_padre:tipo_ruta_padre.value,
                    tipo_ruta_atm:tipo_ruta_atm.value,
                    cooperativa_id:cooperativa_id,
                    puntos_control:list_json,
                    color:color_ruta.value,
                    dia : aDias,
                    desde : aDesdes,
                    hasta : aHastas
                 };
            else
                array_param={
                    opcion:opcion,
                    descripcion:descripcion.value,
                    array_ruta:array_ruta,
                    tipo_ruta:tipo_ruta.value,
                    tipo_ruta_padre:tipo_ruta_padre.value,
                    tipo_ruta_atm:tipo_ruta_atm.value,
                    cooperativa_id:cooperativa_id,
                    color:color_ruta.value,
                    puntos_control:null,
                    dia : aDias,
                    desde : aDesdes,
                    hasta : aHastas
                };
            	console.log(array_param);
               $.post(url, array_param,
                function( data ) {
                   console.log(data);
                    if(data.error==false)
                    {
                        alert('La operación se ha realizado con éxito.');
                        window.location.replace(urlRetorno);
                    }
                    else
                    {
                        switch(data.tipo_error)
                        {
                            case 'validacion':
                                if(tipo_usuario_valor=='1')
                                    mensajesErrorConsulta(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,span_descripcion,
                                        div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                                        span_tipo_ruta_padre);
                                else
                                    mensajesErrorConsulta(data,null,null,div_unidad,span_unidad,span_descripcion,
                                        div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                                        span_tipo_ruta_padre);
                                break;
                            case 'array_vacio':
                                alert('No se encontró ninguna ruta consultada previamente.');break;

                            default: break;
                        }
                    }
                },"json");
        }
        }
        if(opcion=='modificar')
        {
            var aux;
            var arrayDias = document.getElementsByName('dia[]');
            var arrayDesdes = document.getElementsByName('desde[]');
            var arrayHastas = document.getElementsByName('hasta[]');
            var color_ruta = document.getElementById('color_ruta');
            var aDias = [];
            var aDesdes = [];
            var aHastas = [];
            for(var i = 0; i < arrayDias.length; i++) {
                aDias.push(arrayDias[i].value);
                aDesdes.push(arrayDesdes[i].value);
                aHastas.push(arrayHastas[i].value);
            }

            $.post(url, {
                puntos_control : list_json,
                array_ruta:array_ruta,
                descripcion:descripcion.value,
                cooperativa_id:cooperativa_id,
                tipo_ruta:tipo_ruta.value,
                tipo_ruta_padre:tipo_ruta_padre.value,
                tipo_ruta_atm:tipo_ruta_atm.value,
                dia : aDias,
                color:color_ruta.value,
                desde : aDesdes,
                hasta : aHastas,
                _method : 'PUT'
            } ,function( data ) {
                if (data.error == true)
                {
                    if(tipo_usuario_valor=='1')
                        mensajesErrorConsulta(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,span_descripcion,
                            div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                            span_tipo_ruta_padre);
                    else
                        mensajesErrorConsulta(data,null,null,div_unidad,span_unidad,span_descripcion,
                            div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                            span_tipo_ruta_padre);
                }
                else
                {
                    alert('Los cambios han sido realizados con éxito.');
                    window.location.replace(urlRetorno);
                }
            }, "json");
        }
}

function sortJsonArrayByProperty(objArray, prop, direction){
    if (arguments.length<2) throw new Error("sortJsonArrayByProp requires 2 arguments");
    var direct = arguments.length>2 ? arguments[2] : 1; //Default to ascending

    if (objArray && objArray.constructor===Array){
        var propPath = (prop.constructor===Array) ? prop : prop.split(".");
        objArray.sort(function(a,b){
            for (var p in propPath){
                if (a[propPath[p]] && b[propPath[p]]){
                    a = a[propPath[p]];
                    b = b[propPath[p]];
                }
            }
            return ( (a < b) ? -1*direct : ((a > b) ? 1*direct : 0) );
        });
    }
}

function estadoRuta(url,check, descripcion)
{
    if(!check)
        $confirmation = confirm('¿Está seguro que desea inactivar la ruta '+ descripcion +'?');
    else
        $confirmation = confirm('¿Está seguro que desea activar la ruta '+descripcion+'?');

    if ($confirmation == true)
    {
        $.post(url, {
            _method : 'DELETE'

        } ,function(data) {
            if(!check)
            {
                if(data.estado=='I')
                    alert('la ruta '+descripcion+' ha sido inactivado con éxito.');
                else
                    alert('No se puede inactivar la ruta '+descripcion+'.');
            }
            else
                alert('La ruta '+descripcion+' ha sido activado con éxito.');

            location.reload(true);
        }, "json");
    }
    else
        location.reload(true);
}

function mensajesErrorConsulta(data,div_cooperativa,span_cooperativa,div_unidad,span_unidad,span_descripcion,
                                div_fecha_inicio,span_fecha_inicio,div_fecha_fin,span_fecha_fin, div_descripcion,span_tipo_ruta,
                                span_tipo_ruta_padre)
{
    if(div_cooperativa!=null)
    {
        if (data.messages.hasOwnProperty('cooperativa_id')){
            div_cooperativa.classList.add('has-error');
            span_cooperativa.innerHTML = '<strong>' + data.messages.cooperativa_id + '</strong>';
        }
    }

    if (data.messages.hasOwnProperty('unidad_id')){
        div_unidad.classList.add('has-error');
        span_unidad.innerHTML = '<strong>' + data.messages.unidad_id + '</strong>';
    }

    if (data.messages.hasOwnProperty('tipo_ruta')){
        span_tipo_ruta.innerHTML = '<strong>' + data.messages.tipo_ruta + '</strong>';
    }

    if (data.messages.hasOwnProperty('tipo_ruta_padre')){
        span_tipo_ruta_padre.innerHTML = '<strong>' + data.messages.tipo_ruta_padre + '</strong>';
    }

    if (data.messages.hasOwnProperty('fecha_inicio')){
        div_fecha_inicio.classList.add('has-error');
        span_fecha_inicio.innerHTML = '<strong>' + data.messages.fecha_inicio + '</strong>';
    }

    if (data.messages.hasOwnProperty('fecha_fin')){
        div_fecha_fin.classList.add('has-error');
        span_fecha_fin.innerHTML = '<strong>' + data.messages.fecha_fin + '</strong>';
    }

    if (data.messages.hasOwnProperty('descripcion')){
        div_descripcion.classList.add('has-error');
        span_descripcion.innerHTML = '<strong>' + data.messages.descripcion + '</strong>';
    }
}

function cleanForm(tipo_usuario_valor) {

    //document.getElementById('span_unidad').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_fecha_fin').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_fecha_inicio').innerHTML = '<strong>' + '' + '</strong>';
    document.getElementById('span_descripcion').innerHTML = '<strong>' + '' + '</strong>';

    document.getElementById('div-unidad').classList.remove('has-error');
    document.getElementById('div-fecha-fin').classList.remove('has-error');
    document.getElementById('div-fecha-inicio').classList.remove('has-error');
    document.getElementById('div-descripcion').classList.remove('has-error');

    if(tipo_usuario_valor=='1')
    {
        document.getElementById('span_cooperativa').innerHTML = '<strong>' + '' + '</strong>';
        document.getElementById('div-cooperativa').classList.remove('has-error');

    }
}

$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';
$("[name='chk_estado']").bootstrapSwitch();








