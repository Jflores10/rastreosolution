

var actual_id = null;


/*
function editarPuntoControl(url, tipo_usuario_valor, is_bloque=false)
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
*/


function drawPolygonOnMap(bloque, poligono) {
    // limpiar polígono anterior si existe
    if (polygon[bloque]) {
        polygon[bloque].setMap(null);
    }

    // normalizar coordenadas: convertir string → float y quitar inválidas
    var coords = poligono
        .map(function (p) {
            return {
                lat: parseFloat(p.lat),
                lng: parseFloat(p.lng)
            };
        })
        .filter(function (p) {
            return !isNaN(p.lat) && !isNaN(p.lng);
        });

    if (coords.length === 0) {
        console.warn("No hay coordenadas válidas para dibujar el polígono.");
        return;
    }

    // crear y dibujar polígono
    polygon[bloque] = new google.maps.Polygon({
        paths: coords,
        strokeColor: "#FF0000",
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: "#FF0000",
        fillOpacity: 0.35,
        editable: false,
        map: map[bloque]
    });

    // calcular bounds del polígono
    var bounds = new google.maps.LatLngBounds();
    coords.forEach(function (p) {
        bounds.extend(new google.maps.LatLng(p.lat, p.lng));
    });

    // 🔑 Esperar un poco para que el modal termine de abrir
    setTimeout(function () {
        google.maps.event.trigger(map[bloque], "resize");
        map[bloque].fitBounds(bounds);
        map[bloque].panTo(bounds.getCenter());
    }, 300);
}



function editarPuntoControl(url, tipo_usuario_valor, is_bloque = false) {
    cleanForm2(tipo_usuario_valor, is_bloque);

    $.get(url, function (data) {
        console.log(is_bloque);

        // Limpiamos eventos anteriores para evitar duplicados
        $('#form').off('shown.bs.modal').on('shown.bs.modal', function () {

            if (is_bloque === false || is_bloque === "false" || is_bloque == 0) {
                actual_id = data.punto_control._id;

                $("#pdi").val(data.punto_control.pdi);
                $("#descripcion").val(data.punto_control.descripcion);
                if (tipo_usuario_valor == 1) {
                    $("#cooperativa_id").val(data.punto_control.cooperativa_id);
                }

                if (data.punto_control.entrada && data.punto_control.salida) {
                    $("#otro").prop("checked", false).trigger("click");
                    $("#entrada").val(data.punto_control.entrada);
                    $("#salida").val(data.punto_control.salida);
                } else {
                    $("#otro").prop("checked", true).trigger("click");
                }

                if(data.punto_control.pto_imaginario){
                    $("#pto_imaginario").prop("checked", true);
                }
                if (data.punto_control.tipo_mar == 2) {
                    $("input[name='tipo_mar'][value='2']").prop("checked", true).trigger("change");

                    data.punto_control.poligono.forEach(function (p, idx) {
                        if (idx < 4) {
                            $("#lat" + (idx + 1)).val(p.lat);
                            $("#lng" + (idx + 1)).val(p.lng);
                        }
                    });

                    drawPolygonOnMap(1, data.punto_control.poligono);

                } else {
                    $("input[name='tipo_mar'][value='1']").prop("checked", true).trigger("change");
                    let pos = { lat: parseFloat(data.punto_control.latitud), lng: parseFloat(data.punto_control.longitud) };

                    marker[1].setPosition(pos);
                    circle[1].setCenter(pos);
                    map[1].setCenter(pos);
                    circle[1].setRadius(parseFloat(data.punto_control.radio));
                    $("#radio").val(data.punto_control.radio);
                }

            } else {
                // ---------- BLOQUES ----------
                actual_id = data.bloques[0].pdi_padre;

                if (data.bloques && data.bloques[0]) {
                    $("#pdi").val(data.bloques[0].pdi_padre);
                    $("#descripcion").val(data.bloques[0].descripcion);
                    $("#_id").val(data.bloques[0]._id);

                    if (data.bloques[0].tipo_mar == 2) {
                        $("input[name='tipo_mar'][value='2']").prop("checked", true).trigger("change");

                        data.bloques[0].poligono.forEach(function (p, idx) {
                            if (idx < 4) {
                                $("#lat" + (idx + 1)).val(p.lat);
                                $("#lng" + (idx + 1)).val(p.lng);
                            }
                        });

                        drawPolygonOnMap(1, data.bloques[0].poligono);

                    } else {
                        $("input[name='tipo_mar'][value='1']").prop("checked", true).trigger("change");

                        let pos1 = { lat: parseFloat(data.bloques[0].latitud), lng: parseFloat(data.bloques[0].longitud) };
                        marker[1].setPosition(pos1);
                        circle[1].setCenter(pos1);
                        map[1].setCenter(pos1);
                        circle[1].setRadius(parseFloat(data.bloques[0].radio));
                        $("#radio").val(data.bloques[0].radio);
                    }
                }

                if (data.bloques && data.bloques[1]) {
                    $("#descripcion1").val(data.bloques[1].descripcion);
                    $("#_id1").val(data.bloques[1]._id);

                    if (data.bloques[1].tipo_mar == 2) {
                        $("input[name='tipo_mar'][value='2']").prop("checked", true).trigger("change");

                        data.bloques[1].poligono.forEach(function (p, idx) {
                            if (idx < 4) {
                                $("#lat" + (idx + 1) + "_b2").val(p.lat);
                                $("#lng" + (idx + 1) + "_b2").val(p.lng);
                            }
                        });

                        drawPolygonOnMap(2, data.bloques[1].poligono);

                    } else {
                        $("input[name='tipo_mar'][value='1']").prop("checked", true).trigger("change");
                        let pos2 = { lat: parseFloat(data.bloques[1].latitud), lng: parseFloat(data.bloques[1].longitud) };

                        marker[2].setPosition(pos2);
                        circle[2].setCenter(pos2);
                        map[2].setCenter(pos2);
                        circle[2].setRadius(parseFloat(data.bloques[1].radio));
                        $("#radio1").val(data.bloques[1].radio);
                    }
                }
            }

        }); // fin del on('shown.bs.modal')
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
    var pto_imaginario = document.getElementById('pto_imaginario');



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

    var tipo_mar = $("input[name='tipo_mar']:checked").val(); // "1" = Radio, "2" = Polígono

    var poligono = [];
    if (tipo_mar === "2") {
        for (var i = 1; i <= 4; i++) {
            var lat = $("#lat" + i).val();
            var lng = $("#lng" + i).val();
            if (lat && lng) {
                poligono.push({ lat: lat, lng: lng });
            }
        }
    }



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
            otro: otro.checked,
            tipo_mar: tipo_mar,
            poligono: poligono,
            pto_imaginario: pto_imaginario.checked,

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
              otro: otro.checked,
              tipo_mar: tipo_mar,
              poligono: poligono,
              pto_imaginario: pto_imaginario.checked,

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


function guardarPuntosControl(url, tipo_usuario_valor, id_cooperativa) {
    // ----- BLOQUE 1 -----
    var pdi = document.getElementById('pdi'); // único
    var descripcion = document.getElementById('descripcion');
    var latitud = document.getElementById('latitud');
    var longitud = document.getElementById('longitud');
    var radio = document.getElementById('radio');
    var entrada = document.getElementById('entrada');
    var salida = document.getElementById('salida');
    var otro = document.getElementById('otro');
     var tipo_mar = $("input[name='tipo_mar']:checked").val(); // 1 = radio, 2 = polígono

    // Si es polígono, capturamos los 4 vértices
    var poligono1 = [];
    if (tipo_mar === "2") {
        for (var i = 1; i <= 4; i++) {
            var lat = $("#lat" + i).val();
            var lng = $("#lng" + i).val();
            if (lat && lng) {
                poligono1.push({ lat: lat, lng: lng });
            }
        }
    }

    /*
    // Días bloque 1
    var dias1 = [];
    $("input[name='dias[]']:checked").each(function () {
        dias1.push($(this).val());
    });
    */

    var punto1 = {
        pdi: pdi.value,
        descripcion: descripcion.value,
        latitud: latitud.value,
        longitud: longitud.value,
        radio: radio.value,
        entrada: entrada.value,
        salida: salida.value,
        otro: otro.checked,
        tipo_mar: tipo_mar,
        poligono: poligono1,
        //dias: dias1
    };

    // ----- BLOQUE 2 -----
    var descripcion1 = document.getElementById('descripcion1');
    var latitud1 = document.getElementById('latitud1');
    var longitud1 = document.getElementById('longitud1');
    var radio1 = document.getElementById('radio1');
    var entrada1 = document.getElementById('entrada1');
    var salida1 = document.getElementById('salida1');
    var otro1 = document.getElementById('otro1');

    var poligono2 = [];
    if (tipo_mar === "2") {
        for (var j = 1; j <= 4; j++) {
            var lat2 = $("#lat" + j + "_b2").val();
            var lng2 = $("#lng" + j + "_b2").val();
            if (lat2 && lng2) {
                poligono2.push({ lat: lat2, lng: lng2 });
            }
        }
    }

    /*
    // Días bloque 2
    var dias2 = [];
    $("input[name='dias1[]']:checked").each(function () {
        dias2.push($(this).val());
    });
    */

    var punto2 = {
        pdi: pdi.value, // si cada bloque debe tener su PDI, crea <input id="pdi1"> y cámbialo aquí
        descripcion: descripcion1.value,
        latitud: latitud1.value,
        longitud: longitud1.value,
        radio: radio1.value,
        entrada: entrada1.value,
        salida: salida1.value,
        otro: otro1.checked,
        tipo_mar: tipo_mar,
        poligono: poligono2
        //dias: dias2
    };

    // ----- COOPERATIVA -----
    var cooperativa_id;
    if (tipo_usuario_valor == '1') {
        cooperativa_id = document.getElementById('cooperativa_id').value;
    } else {
        cooperativa_id = id_cooperativa;
    }

    // ----- PARAMETROS A ENVIAR -----
    var param = {
        cooperativa_id: cooperativa_id,
        estado: "A",
        puntos: [punto1, punto2] // enviamos los dos bloques en un array
    };

    // ----- POST -----
    $.post(url, param, function (data) {
        if (data.error == false) {
            alert('Los puntos de control han sido creados con éxito.');
            location.reload(true);
        } else {
            alert('Error al guardar: ' + JSON.stringify(data));
        }
    }, "json");
}

function actualizarPuntoControlBloq(url, tipo_usuario_valor, id_cooperativa) {
    // ----- BLOQUE 1 -----
    var pdi = document.getElementById('pdi'); // único
    var _id = document.getElementById('_id'); 

    var descripcion = document.getElementById('descripcion');
    var latitud = document.getElementById('latitud');
    var longitud = document.getElementById('longitud');
    var radio = document.getElementById('radio');
    var entrada = document.getElementById('entrada');
    var salida = document.getElementById('salida');
    var otro = document.getElementById('otro');
    var tipo_mar = $("input[name='tipo_mar']:checked").val(); // 1 = radio, 2 = polígono
    // Si es polígono, capturamos los 4 vértices
    var poligono1 = [];
    if (tipo_mar === "2") {
        for (var i = 1; i <= 4; i++) {
            var lat = $("#lat" + i).val();
            var lng = $("#lng" + i).val();
            if (lat && lng) {
                poligono1.push({ lat: lat, lng: lng });
            }
        }
    }


    /*
    // Días bloque 1
    var dias1 = [];
    $("input[name='dias[]']:checked").each(function () {
        dias1.push($(this).val());
    });
    */

    var punto1 = {
        pdi: pdi.value,
        _id: _id.value,
        descripcion: descripcion.value,
        latitud: latitud.value,
        longitud: longitud.value,
        radio: radio.value,
        entrada: entrada.value,
        salida: salida.value,
        otro: otro.checked,
        tipo_mar: tipo_mar,
        poligono: poligono1
        //dias: dias1
    };

    // ----- BLOQUE 2 -----
    var _id1 = document.getElementById('_id1'); 
    var descripcion1 = document.getElementById('descripcion1');
    var latitud1 = document.getElementById('latitud1');
    var longitud1 = document.getElementById('longitud1');
    var radio1 = document.getElementById('radio1');
    var entrada1 = document.getElementById('entrada1');
    var salida1 = document.getElementById('salida1');
    var otro1 = document.getElementById('otro1');

    
    var poligono2 = [];
    if (tipo_mar === "2") {
        for (var j = 1; j <= 4; j++) {
            var lat2 = $("#lat" + j + "_b2").val();
            var lng2 = $("#lng" + j + "_b2").val();
            if (lat2 && lng2) {
                poligono2.push({ lat: lat2, lng: lng2 });
            }
        }
    }

    /*
    // Días bloque 2
    var dias2 = [];
    $("input[name='dias1[]']:checked").each(function () {
        dias2.push($(this).val());
    });
    */

    var punto2 = {
        pdi: pdi.value, // si cada bloque debe tener su PDI, crea <input id="pdi1"> y cámbialo aquí
        _id: _id1.value,
        descripcion: descripcion1.value,
        latitud: latitud1.value,
        longitud: longitud1.value,
        radio: radio1.value,
        entrada: entrada1.value,
        salida: salida1.value,
        otro: otro1.checked,
        tipo_mar: tipo_mar,
        poligono: poligono2
       // dias: dias2
    };

    // ----- COOPERATIVA -----
    var cooperativa_id;
    if (tipo_usuario_valor == '1') {
        cooperativa_id = document.getElementById('cooperativa_id').value;
    } else {
        cooperativa_id = id_cooperativa;
    }

    // ----- PARAMETROS A ENVIAR -----
    var param = {
        cooperativa_id: cooperativa_id,
        _method : 'PUT',
        estado: "A",
        puntos: [punto1, punto2] // enviamos los dos bloques en un array
    };

    // ----- POST -----
    $.post(url, param, function (data) {
        if (data.error == false) {
            alert('Los puntos de control han sido creados con éxito.');
            location.reload(true);
        } else {
            alert('Error al guardar: ' + JSON.stringify(data));
        }
    }, "json");
}




function actualizarPuntoControl(url, tipo_usuario_valor, id_cooperativa)
{
    console.log(url)
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
    var pto_imaginario = document.getElementById('pto_imaginario');

    var cooperativa_id;
    var div_cooperativa;
    var span_cooperativa;

    var param;

    var tipo_mar = $("input[name='tipo_mar']:checked").val(); // "1" = Radio, "2" = Polígono
    var poligono = [];
    if (tipo_mar === "2") {
        for (var i = 1; i <= 4; i++) {
            var lat = $("#lat" + i).val();
            var lng = $("#lng" + i).val();
            if (lat && lng) {
                poligono.push({ lat: lat, lng: lng });
            }
        }
    }



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
            otro: otro.checked,
            tipo_mar: tipo_mar,
            poligono: poligono,
            pto_imaginario: pto_imaginario.checked,

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
            otro: otro.checked,
            tipo_mar: tipo_mar,
            poligono: poligono,
            pto_imaginario: pto_imaginario.checked,

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
/*
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
*/

function limpiarInputsPoligono(bloque) {
  for (var i = 1; i <= 4; i++) {
    // caso normal con sufijo _b{bloque}
    var $lat = $("#lat" + i + "_b" + bloque);
    var $lng = $("#lng" + i + "_b" + bloque);

    if ($lat.length && $lng.length) {
      $lat.val('');
      $lng.val('');
    } else {
      // fallback a los inputs que no tienen sufijo _b
      $("#lat" + i).val('');
      $("#lng" + i).val('');
    }
  }
}

function cleanForm2(tipo_usuario_valor, is_bloque=false) {
    limpiarInputsPoligono();
    // --- Bloque 1 ---
    document.getElementById('span_descripcion').innerHTML = '<strong></strong>';
    document.getElementById('span_latitud').innerHTML = '<strong></strong>';
    document.getElementById('span_longitud').innerHTML = '<strong></strong>';
    document.getElementById('span_radio').innerHTML = '<strong></strong>';
    document.getElementById('span_pdi').innerHTML = '<strong></strong>';

    document.getElementById('descripcion').value = '';
    document.getElementById('longitud').value = '';
    document.getElementById('latitud').value = '';
    document.getElementById('radio').value = '';
    document.getElementById('pdi').value = '';

    document.getElementById('div-descripcion').classList.remove('has-error');
    document.getElementById('div-longitud').classList.remove('has-error');
    document.getElementById('div-latitud').classList.remove('has-error');
    document.getElementById('div-radio').classList.remove('has-error');
    document.getElementById('div-pdi').classList.remove('has-error');

    if (tipo_usuario_valor == '1') {
        document.getElementById('cooperativa_id').value = '';
        document.getElementById('div-cooperativa').classList.remove('has-error');
        document.getElementById('span_cooperativa').innerHTML = '<strong></strong>';
    }
    document.getElementById('entrada').value = '';
    document.getElementById('salida').value = '';
    document.getElementById('otro').checked = false;
    $('#entrada').prop('readonly', true);
    $('#salida').prop('readonly', true);

    // --- Comportamiento según is_bloque ---
    if (is_bloque === true || is_bloque === "true" || is_bloque == 1) {
        // --- Bloque 2 ---
        document.getElementById('span_descripcion1').innerHTML = '<strong></strong>';
        document.getElementById('span_latitud1').innerHTML = '<strong></strong>';
        document.getElementById('span_longitud1').innerHTML = '<strong></strong>';
        document.getElementById('span_radio1').innerHTML = '<strong></strong>';

        document.getElementById('descripcion1').value = '';
        document.getElementById('longitud1').value = '';
        document.getElementById('latitud1').value = '';
        document.getElementById('radio1').value = '';

        document.getElementById('div-descripcion').classList.remove('has-error');
        document.getElementById('div-longitud1').classList.remove('has-error');
        document.getElementById('div-latitud1').classList.remove('has-error');
        document.getElementById('div-radio1').classList.remove('has-error');

        document.getElementById('entrada1').value = '';
        document.getElementById('salida1').value = '';
        document.getElementById('otro1').checked = false;
        $('#entrada1').prop('readonly', true);
        $('#salida1').prop('readonly', true);
        /*
        // Mostrar días habilitados
        $('#div-dias-semana').show();
        $('#div-dias-semana1').show();
        */
    } else {
        $('ul.nav-tabs li:eq(1)').hide(); // pestaña
        $('ul.nav-tabs li:eq(0) a').tab('show');
        /*
        // Ocultar días habilitados
        $('#div-dias-semana').hide();
        $('#div-dias-semana1').hide();
        */
    }
}

function cleanForm(tipo_usuario_valor) {
    cleanForm2(tipo_usuario_valor);



    actual_id=null;
}



$.fn.bootstrapSwitch.defaults.onText = 'Activo';
$.fn.bootstrapSwitch.defaults.offText = 'Inactivo';

$("[name='chk_estado']").bootstrapSwitch();
