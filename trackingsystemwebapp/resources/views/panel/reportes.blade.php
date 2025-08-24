@extends('layouts.app')
@section('title')
Reportes
@endsection
@section('styles')
<style>
	.table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th
	{
		padding :3px;
	}
	.btn {
		padding : 3.5px 3px;
	}
</style>
@endsection
@section('content')

<div class="page-title">
    <div class="title_left">
        <h3>Despachos</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reportes de despachos</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br/>
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="form-group text-right">
                            <div class="btn-group" role="group" aria-label="...">
                                <button onclick="excelCompleto();" type="button" class="btn btn-primary">Generar por unidades</button>
                                <button onclick="excelUnaHoja();" type="button" class="btn btn-info">Generar por hoja</button>
                                <button onclick="excelPorRutas();" type="button" class="btn btn-default">Generar por rutas</button>
                                <button onclick="excelPorRutasSinVueltas();" type="button" class="btn btn-primary">Generar por rutas (sin vueltas)</button>
                                <button onclick="excelMultas();" type="button" class="btn btn-default">Tiempos</button>
                                <button onclick="construirImpresionMulta();" type="button" class="btn btn-default">TicketMultas</button>
                            </div>
                        </div>
                        
                        <form id="form" method="POST" action="{{ url('/reportes') }}">
                            {{ csrf_field() }}
                            <br/>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group" id="div-cooperativa">
                                    <select data-placeholder="Cooperativa" class="form-control" id="cooperativa_id" name="cooperativa_id">
                                        @foreach ($cooperativas as $cooperativa)
                                            <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                        @endforeach
                                    </select>
                                    <span class="help-block" id="span_cooperativa"></span>
                                </div>

                                <div class="form-group" id="div-unidad">
                                    <label>Unidades</label>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="seleccionar_unidades" /> Todas</label>
                                    </div>
                                    <div id="div_unidades">
                                    	<select data-placeholder="Unidades" multiple class="form-control" id="unidad_id" name="unidad_id[]">
                                    	</select>
                                    </div>
                                    <span class="help-block" id="span_unidad"></span>
                                </div>

                                <div class="form-group" id="div-ruta">
                                	<label>Rutas</label>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="seleccionar_rutas" /> Todas</label>
                                    </div>
                                    <select data-placeholder="Rutas" multiple name="ruta_id[]" class="form-control" id="ruta_id" name="ruta_id">
                                    </select>
                                    <span class="help-block" id="span_ruta"></span>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="col-lg-3 col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <div class="radio">
                                            <label for="hoy">
                                                <input type="radio" id="hoy" name="filtro_fecha" /> Hoy
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label for="ayer">
                                                <input type="radio" id="ayer" name="filtro_fecha" /> Ayer
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label for="personalizado">
                                                <input type="radio" id="personalizado" name="filtro_fecha" /> Personalizado
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-12">
                                    <div class="form-group" id="div-fecha-inicio">
                                        <input placeholder="Desde" name="desde" id="fecha_inicio" autocomplete="off" autocorrect="off" class="form-control" type="text" />
                                        <span class="help-block" id="span_fecha_inicio"></span>
                                    </div>

                                    <div class="form-group" id="div-fecha-fin">
                                        <input placeholder="Hasta" name="hasta" id="fecha_fin" autocomplete="off" autocorrect="off" class="form-control" type="text" />
                                        <span class="help-block" id="span_fecha_fin"></span>
                                    </div>
                                    <input type="hidden" id="filter" name="filter[]"/>
                                    <div class="form-group">
                                        <input onclick="search();" type="button" value="Buscar" class="btn btn-primary" />
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="modalLabel">Filtro de datos</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <label><input checked type="checkbox" name="filtros[]" value="C" /> Conductor</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="CONT" /> Contador marca</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="P" /> Pasajeros</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="R" /> Reloj</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="CI" /> Contador inicial</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="M" /> Marca</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="ATAD" /> Atrasos y adelantos</label>
                                                <label><input checked type="checkbox" name="filtros[]" value="CF" /> Contador final</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button id="generar" type="button"  class="btn btn-primary">Generar</button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <br/>
                <div id="report">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_errorATM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Error ATM</h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-lg-12">
            <div class="form-group">
            <label>Error</label>
            <textarea type="text" readonly name="errorATM" id="errorATM" class="form-control"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
    window.onload = function () { 
        $('#menu_toggle').trigger('click');
    }

	function recalcular(id)
	{
		var url = '{{ url("/despachos") }}' + '/' + id + '/finish';
        $('#progress').modal('show');
        $.get(url, function (data) {
            if(data.error == false){
                var array_path=[];
                for(var i=0; i<data.rutarecorrido.length ; i++)
                {
                    array_path.push({lat:parseFloat(data.rutarecorrido[i].lat),
                        lng:parseFloat(data.rutarecorrido[i].lng)});
                }
                
                ruta = new google.maps.Polyline({
                    geodesic: true,
                    strokeWeight: 20,
                    path:array_path
                });
                var array_corte=[];
                for(var i=0; i<data.recorridos.length ; i++)
                {
                    let latitud=data.recorridos[i].latitud;
                    let longitud=data.recorridos[i].longitud; 

                    var isLocationOnEdge = google.maps.geometry.poly.isLocationOnEdge;
                    //1e-3
                
                    let result=isLocationOnEdge(new google.maps.LatLng(parseFloat(latitud), parseFloat(longitud)), ruta, 0.0010);

                    if(!result){
                        /*console.log('ERROR');
                        console.log(latitud);
                        console.log(longitud);*/
                        array_corte.push({
                            'lat':parseFloat(latitud),
                            'lng':parseFloat(longitud)
                        });
                        
                    }
                }
                /***GUARDAR COORDENADAS Y SI CORTE TUBO */
                if(array_corte.length>0){
                    let url_corte='{{ url("/despachos")}}'+"/cortetubo";
                    var params = {
                        array_corte : array_corte,
                        despacho_id:id
                    };
                    $.post(url_corte,params, function (data) {
                        search();
                        $('#progress').modal('hide');
                    });
                }else{
                    search();
                    $('#progress').modal('hide');
                }
            }else{
                alert('Error al recalcular el despacho');
                search();
                $('#progress').modal('hide');
            }
           
        }, 'json');
	}

    function excelMultas() {
        var cooperativa_id=document.getElementById('cooperativa_id').value;
        var form = document.getElementById('form');
        if(cooperativa_id != null && cooperativa_id != undefined){
            form.action = '/reportes/multasunidades';
            form.submit(); 
        }else{
            alert('Seleccione una cooperativa');
        }
    }

    function errorAtm(id)
    {
        var url = '{{ url("/despachos/error") }}' + '/' + id;
        $('#progress').modal('show');
        $('#errorATM').val('');
        $.get(url, function (data) {
            $('#modal_errorATM').modal('show');
            $('#errorATM').val((data.error_ATM == undefined || data.error_ATM == null)?'En proceso de envio...':data.error_ATM);
            $('#progress').modal('hide');
        }, 'json');
    }

    function reenviarATM(id){
        var url = '{{ url("/despachos/reenviarATM") }}' + '/' + id;
        $('#progress').modal('show');
        $.post(url, function (data) {
            if(!data.error){
                $('#progress').modal('hide');
                location.reload(true);
            }else{
                alert('Error al actualizar el despacho');
                $('#progress').modal('hide');
                location.reload(true);
            }
        }, 'json');
    }

    function cargaDatosCooperativa(id)
    {
        $('#unidad_id').empty();
        $('#ruta_id').empty();
        var url = '{{ url("/reportes/cargar") }}' + '/' + id;
        $.get(url, function (data) {
            for (var i = 0; i < data.unidades.length; i++)
            {
                $('#unidad_id').append('<option value="' +  data.unidades[i]._id + '">' + data.unidades[i].descripcion + '</option>');
                $('#unidad_id').trigger('chosen:updated');
            }
            for (var i = 0; i < data.rutas.length; i++)
            {
                $('#ruta_id').append('<option value="' + data.rutas[i]._id + '">' + data.rutas[i].descripcion  + '</option>');
                $('#ruta_id').trigger('chosen:updated');
            }
        }, 'json');
    }

    function search()
    {
        var form = document.getElementById('form');
        form.action = "{{ url('reportes') }}";
        $('#progress').modal('show');
        $.post(form.action,
            $('#form').serialize() , function (data) {
                $('#report').empty().append(data);
                $('#progress').modal('hide');
        });
    }

    function excelUnaHoja()
    {
        var filtros = document.getElementsByName('filtros[]');
        var form = document.getElementById('form');
        var filter = document.getElementById('filter');
        var array = [];
        for (var i = 0; i < filtros.length; i++)
        {
            array.push(filtros[i].value);
        }
        filter.value = array;
        form.action = '/reportes/una-hoja';
        form.submit();
    }

    function excelCompleto()
    {
        $('#modal').modal('show');
        var generar = document.getElementById('generar');
        generar.onclick = function () {
            var form = document.getElementById('form');
            form.action = '/reportes/general';
            form.submit();
            };
    }

    function excelDiario() {
        var form = document.getElementById('form');
        form.action = '/reportes/diario';
        form.submit();
    }

    function excelPorRutas()
    {
        var form = document.getElementById('form');
        form.action = '/reportes/rutas';
        form.submit();
    } 
    
    function excelPorRutasSinVueltas()
    {
        var form = document.getElementById('form');
        form.action = '/reportes/rutasnovueltas';
        form.submit();
    }
    
    function toFloat(value) {
        return (value == '' || isNaN(value)) ? 0 : parseFloat(value);
    }

    function construirImpresionMulta(id)
    {
        var url = '{{ url("/reportes/multasrutas") }}';
        var w=window.open("", "Imprimir", "width=250,height=400");
        $.get(url,{
            unidad_id:$('#unidad_id').val(),
            ruta_id:$('#ruta_id').val(),
            desde:$('#fecha_inicio').val(),
            hasta:$('#fecha_fin').val()
        }, function(data) {
            console.log(data);
            var tabla  = [];
            tabla.push('<!DOCTYPE html>');
            tabla.push('<html>');
            tabla.push('<head>');
            tabla.push('<style>');
            tabla.push('@media print {input : { visibility:hidden !important; }}');
            tabla.push('@page {size: auto;margin: 1;padding:1;}');
            tabla.push('</style>')
            tabla.push('</head>');
            tabla.push('<body style="display:block;overflow:auto;">');
            tabla.push('<div>');
            tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
        
            tabla.push('<table style="width:100%;  border-collapse: collapse;">');
            var multaTotal=0.0;
            for (var i = 0; i < data.length; i++)
            {
                if(i==0){
                    tabla.push('<tr>');
                    tabla.push('<td colspan="4">Desde:' + data[i].inicio + '</b></td>');
                    tabla.push('</tr>');
                    tabla.push('<tr>');
                    tabla.push('<td colspan="4">Hasta:' + data[i].fin + '</b></td>');
                    tabla.push('</tr>');
                }
                if(data[i].multas.length != 0){
                    tabla.push('<tr>');
                        
                    tabla.push('<td colspan="4"><b>' + data[i].ruta + '</b></td>');
                    tabla.push('</tr>');
                }
                var multa=0;
                for (var j = 0; j < data[i].multas.length; j++)
                {
                    tabla.push('<tr>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + data[i].multas[j].unidad + '</td>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + data[i].multas[j].conductor.split(' ')[0] +" "+ data[i].multas[j].conductor.split(' ')[1] + '</td>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + data[i].multas[j].salida + '</td>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + data[i].multas[j].multa + '</td>');
                    tabla.push('</tr>');
                    multa = multa + data[i].multas[j].multa;
                }

                if(data[i].multas.length != 0){
                    tabla.push('<tr>');
                    tabla.push('<td> <br/></td>');
                    tabla.push('</tr>');

                    tabla.push('<tr style="border: 1px solid #000000; text-align: left;">');
                    tabla.push('<td colspan="2"> <b>TOTAL=$' + toFloat(multa).toFixed(2)  + '</b></td>');
                    tabla.push('</tr>');
                }


                multaTotal=multaTotal+multa;
            }

            tabla.push('<tr>');
            tabla.push('<td> <br/> <br/></td>');
            tabla.push('</tr>');

            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>TOTAL General=$' + toFloat(multaTotal).toFixed(2) + '</b></td>');
            tabla.push('</tr>');
            
            tabla.push('</tr>');
            tabla.push('</table>');
            tabla.push('</div>');
            tabla.push('</body>');
            tabla.push('</html>');
            html = tabla.join('');
            w.document.body.innerHTML = html;
        }, 'json');
    }

    function construirImpresion_(id)
    {
        var url = '{{ url("/despachos") }}' + '/' + id;
        var w=window.open("", "Imprimir", "width=250,height=400");
        $.get(url, function(data) {
            var tabla  = [];
            tabla.push('<!DOCTYPE html>');
            tabla.push('<html>');
            tabla.push('<head>');
            tabla.push('<style>');
            tabla.push('@media print {input : { visibility:hidden !important; }}');
            tabla.push('@page {size: auto;margin: 1;padding:1;}');
            tabla.push('</style>')
            tabla.push('</head>');
            tabla.push('<body style="display:block;overflow:auto;">');
            tabla.push('<div>');
            tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
            tabla.push('<table style="width:100%;">');
            tabla.push('<tr>');
            tabla.push('<td>' + data.conductor.nombre + '</td>');
            tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
            tabla.push('</tr>');
            tabla.push('<tr>');
           // tabla.push('<td>' + ((data.ruta.tipo_ruta==='H')?data.ruta.rutapadre.descripcion:data.ruta.descripcion) + '</td>');
            tabla.push('<td>' + data.ruta.descripcion + '</td>');
            tabla.push('<td>' + data.unidad.descripcion + '</td>');
            tabla.push('</tr>');
            tabla.push('<tr>');
            var fechaImpresion = new Date(data.created_at);
            tabla.push('<td>' + fechaImpresion.format('H:i') + '</td>');
            tabla.push('<td>' + ((data.unidad.contador_inicial != null)?data.unidad.contador_inicial:'-') + '/' + ((data.primero != null)?data.primero.contador_inicial:'-')  + '</td>');
            tabla.push('</tr>');
            tabla.push('</table>');
            tabla.push('<table style="width:100%; text-align:center;">');
            tabla.push('<th>M</th><th>A-|A+</th><th>H</th><th>Pt.</th>');
            for (var i = 0; i < data.puntos_control.length; i++)
            {
                tabla.push('<tr style="line-height : 0.7;">');
                if (data.anterior != null)
                {
                    if (data.anterior.puntos_control[i] != null && data.anterior.puntos_control[i].marca != null)
                    {
                        var horaAnterior = new Date(data.anterior.puntos_control[i].marca);
                        tabla.push('<td> ' + horaAnterior.format('H:i') + '</td>');
                        tabla.push('<td>' + data.anterior.puntos_control[i].intervalo + '</td>');
                    }
                    else
                    {
                        tabla.push('<td>-</td>');
                        tabla.push('<td>-</td>');
                    }
                }
                else
                {
                    tabla.push('<td>-</td>');
                    tabla.push('<td>-</td>');
                }
                var horaPeruano = new Date(data.puntos_control[i].tiempo_esperado.date);
                horaPeruano.setHours(horaPeruano.getHours() + 10);
                tabla.push('<td>' + new Date(horaPeruano).format('H:i') + '</td>');
                tabla.push('<td>' + ((data.ruta.puntos_control[i] != null)?data.ruta.puntos_control[i].secuencia:'-') + '</td>');
                tabla.push('</tr>');
            }
            tabla.push('</table><br/>');
            tabla.push('<table style="width:100%; text-align:center;">');
            tabla.push('<tr>');
            if (data.anterior != null)
            {
                tabla.push('<td>' + data.anterior.contador_inicial + '/' + data.anterior.contador_final + '</td>');
            }
            else
                tabla.push('<td>-/-</td>');
            tabla.push('<td>' + data.contador_inicial + '</td>');
            tabla.push('</tr>');
            tabla.push('</table>');
            tabla.push('<b>Descuento: $ ' + ((data.anterior == null)?'-':toFloat(data.anterior.multa).toFixed(2)) + '</b><br/>');
            tabla.push('<b>Corte de tubo: ' + ((data.anterior == null)?'-':data.anterior.corte_tubo) + '</b><br/>');
            tabla.push('</div>');
            tabla.push('</body>');
            tabla.push('</html>');
            html = tabla.join('');
            w.document.body.innerHTML = html;
        }, 'json');
    }
    
    function construirImpresion(id) {
            var desc1=0;
            var desc2=0;
            if ($('#cooperativa_id').val() == '588d3d677aea915d897ff041' || $('#cooperativa_id').val() ==
                    '62d762dd2243df1cd73a79e2' || $('#cooperativa_id').val() =='63e58b552243df4233755082') {
                    construirImpresionAlbosao(id);
                } else if ($('#cooperativa_id').val() == '646253b12243df426b1e2a82') {
                    construirImpresionOrquideas(id);
                } else {
                    var url = '{{ url('/despachos') }}' + '/' + id;
                    var w = window.open("", "Imprimir", "width=250,height=400");
                    $.get(url, function(data) {
                        var tabla = [];
                        tabla.push('<!DOCTYPE html>');
                        tabla.push('<html>');
                        tabla.push('<head>');
                        tabla.push('<style>');
                        tabla.push('@media print {input : { visibility:hidden !important; }}');
                        tabla.push('@page {size: auto;margin: 1;padding:1;}');
                        if (data.unidad.cooperativa_id == '63e58b552243df4233755082') {
                            tabla.push('td {font-size: 25px;}');
                            tabla.push('.leftborder {border-left: 1px solid; border-width: 0 0 1px 1px;}');
                            tabla.push('.topbottom {border-bottom: 1px solid; border-width: 0 0 1px 1px;}');
                        }
                        tabla.push('</style>')
                        tabla.push('</head>');
                        tabla.push('<body style="display:block;overflow:auto;">');
                        tabla.push('<div>');
                        tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
                        tabla.push('<table style="width:100%;">');
                        if (data.unidad.cooperativa_id != '63e58b552243df4233755082') { //perla pacifico
                            tabla.push('<tr>');
                            tabla.push('<td>' + data.conductor.nombre + '</td>');
                            tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
                            tabla.push('</tr>');
                        } else {
                            tabla.push('<tr>');
                            tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
                            tabla.push('</tr>');
                        }
                        tabla.push('<tr>');
                        // tabla.push('<td>' + ((data.ruta.tipo_ruta==='H')?data.ruta.rutapadre.descripcion:data.ruta.descripcion) + '</td>');
                        tabla.push('<td>' + data.ruta.descripcion + '</td>');
                        tabla.push('<td>' + data.unidad.descripcion + '</td>');
                        tabla.push('</tr>');
                        if (data.unidad.cooperativa_id != '63e58b552243df4233755082') { //perla pacifico
                            tabla.push('<tr>');
                            var fechaImpresion = new Date(data.created_at);
                            tabla.push('<td>' + fechaImpresion.format('H:i') + '</td>');
                            tabla.push('<td>' + ((data.finalayer != null) ? data.finalayer.contador_final : '-') +
                                '/' + ((data.unidad.contador_inicial != null) ? data.unidad
                                    .contador_inicial : '-') + '</td>');
                            // tabla.push('<td>' + ((data.unidad.contador_inicial != null) ? data.unidad
                            // .contador_inicial : '-') + '/' + ((data.primero != null) ? data.primero
                            // .contador_inicial : '-') + '</td>');
                            tabla.push('</tr>');
                        }
                        tabla.push('</table>');
                        tabla.push('<table style="width:100%; text-align:center;">');
                        if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') //NUEVO ECUADOR
                            tabla.push('<th>M</th><th>A-|A+</th><th>Desc</th><th>H</th><th>Pt.</th>');
                        else
                        if (data.unidad.cooperativa_id == '63e58b552243df4233755082') //perla pacifico
                            tabla.push(
                                '<th class="topbottom"></th><th class="topbottom"></th><th class="topbottom"></th>'
                            );
                        else
                            tabla.push('<th>M</th><th>A-|A+</th><th>H</th><th>Pt.</th>');

                        let rutaId = (data.ruta.rutapadre == null) ? data.ruta._id : data.ruta.rutapadre._id;
                        const ruta44 = '5edb924c2243df11d23c9e62';
                        const ruta46 = '5ec4619c2243df3c3074fd32'; 
                        for (var i = 0; i < data.puntos_control.length; i++) {
                            tabla.push('<tr style="line-height : 0.7;">');
                            if (data.anterior != null) {
                                if (data.anterior.puntos_control[i] != null && data.anterior.puntos_control[i]
                                    .marca != null) {
                                    var horaAnterior = new Date(data.anterior.puntos_control[i].marca);
                                    tabla.push('<td> ' + horaAnterior.format('H:i') + '</td>');
                                    tabla.push('<td>' + data.anterior.puntos_control[i].intervalo + '</td>');
                                    if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') { //NUEVO ECUADOR
                                        var intervalo = (data.anterior.puntos_control[i].intervalo != null && data
                                                .anterior.puntos_control[i].intervalo != undefined) ? data.anterior
                                            .puntos_control[i].intervalo : 0;
                                        var atraso = (data.anterior.puntos_control[i].atraso != null && data
                                                .anterior.puntos_control[i].atraso != undefined) ? data.anterior
                                            .puntos_control[i].atraso : 0;
                                        var adelanto = (data.anterior.puntos_control[i].adelanto != null && data
                                                .anterior.puntos_control[i].adelanto != undefined) ? data.anterior
                                            .puntos_control[i].adelanto : 0;
                                        var desc = 0;
                                        if (data.anterior.puntos_control[i].tiempo_atraso != null && data.anterior
                                            .puntos_control[i].tiempo_atraso != undefined) {
                                            desc = intervalo * atraso;
                                        } else {
                                            desc = intervalo * adelanto;
                                        }
                                        tabla.push('<td>' + toFloat(desc).toFixed(2) + '</td>');
                                        if (rutaId == ruta44) {
                                            if(i<4)
                                                desc1+=toFloat(desc);
                                            else if (i >= 4 && i < 6)
                                                desc2+=toFloat(desc);
                                        }
                                        else if (rutaId == ruta46) {
                                            if(i<4)
                                                desc1+=toFloat(desc);
                                            else if (i >= 5 && i < 9)
                                                desc2+=toFloat(desc);
                                        }
                                    }
                                } else {
                                    tabla.push('<td>-</td>');
                                    tabla.push('<td>-</td>');
                                    if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') { //NUEVO ECUADOR
                                        tabla.push('<td>-</td>');
                                    }
                                }
                            } else {
                                tabla.push('<td>-</td>');
                                tabla.push('<td>-</td>');
                                if (data.unidad.cooperativa_id != '63e58b552243df4233755082')
                                    tabla.push('<td>-</td>');
                            }
                            var horaPeruano = new Date(data.puntos_control[i].tiempo_esperado.date);
                            horaPeruano.setHours(horaPeruano.getHours() + 10);
                            tabla.push('<td class="leftborder">' + new Date(horaPeruano).format('H:i') + '</td>');
                            if (data.unidad.cooperativa_id != '63e58b552243df4233755082') { //perla pacifico
                                tabla.push('<td>' + ((data.ruta.puntos_control[i] != null) ? data.ruta
                                    .puntos_control[i].secuencia : '-') + '</td>');
                            }
                            tabla.push('</tr>');
                        }
                        tabla.push('</table><br/>');
                        if (data.unidad.cooperativa_id != '63e58b552243df4233755082') {
                            tabla.push('<table style="width:100%; text-align:center;">');
                            tabla.push('<tr>');
                            if (data.anterior != null) {
                                tabla.push('<td>' + data.anterior.contador_inicial + '/' + data.anterior
                                    .contador_final + '</td>');
                            } else
                                tabla.push('<td>-/-</td>');
                            tabla.push('<td>' + data.contador_inicial + '</td>');
                            tabla.push('</tr>');
                            tabla.push('</table>');
                        }
                        if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') {
                            tabla.push('<b>Descuento 1: $ ' + desc1.toFixed(2) + '</b><br/>');
                            tabla.push('<b>Descuento 2: $ ' + desc2.toFixed(2) + '</b><br/>');
                            let descuentoTotal = desc1 + desc2;
                            tabla.push('<b>Total: $ ' + descuentoTotal.toFixed(2) + '</b><br/>');
                        }
                        else {
                            tabla.push('<b>Descuento Total: $ ' + ((data.anterior == null) ? '-' : toFloat(data.anterior
                            .multa).toFixed(2)) + '</b><br/>');
                        }
                        tabla.push('<b>Corte de tubo: ' + ((data.anterior == null) ? '-' : data.anterior
                            .corte_tubo) + '</b><br/>');
                        tabla.push('</div>');
                        tabla.push('</body>');
                        tabla.push('</html>');
                        html = tabla.join('');
                        w.document.body.innerHTML = html;
                    }, 'json');
                }

        }

        function construirImpresionAlbosao(id) {
            var url = '{{ url('/despachos/ticketalbosao') }}' + '/' + id;
            $('#progress').modal('show');
            var w = window.open("", "Imprimir", "width=257,height=400");
            $.get(url, function(data) {
                // console.log(data);
                $('#progress').modal('hide');
                var tabla = [];
                tabla.push('<!DOCTYPE html>');
                tabla.push('<html>');
                tabla.push('<head>');
                tabla.push('<style>');
                tabla.push('@media print {input : { visibility:hidden !important; }}');
                tabla.push('@page {size: auto;margin: 0mm;padding:1;}');
                tabla.push('#info, #info tr, #info td, #info th { border: 1px solid black; }');
                tabla.push('#info, #info tr { border-left: 0px; border-right: 0px; }');
                tabla.push('#info .left { border-left: 0px; }');
                tabla.push('#info .right { border-right: 0px; }');
                tabla.push('#info { border-collapse: collapse; }');
                tabla.push('</style>')
                tabla.push('</head>');
                tabla.push('<body style="display:block;overflow:auto;">');
                tabla.push('<div>');
                tabla.push(
                    '<input type="button" id="btImprimirTicket" value="Imprimir" onclick="imprimirTicket();" />'
                );
                tabla.push('<table style="width:100%;">');
                tabla.push('<tbody>');
                tabla.push('<tr>');
                tabla.push('<td  colspan="2">' + data.conductor.nombre + '</td>');
                if (data.anterior != null) {
                    tabla.push('<td>' + data.anterior.contador_inicial + '/' + data.anterior.contador_final +
                        '</td>');
                }
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td>' + ((data.ruta.rutapadre == null) ? data.ruta.descripcion : data.ruta.rutapadre
                    .descripcion) + '</td>');
                tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
                tabla.push('<td><strong>' + data.unidad.descripcion + '</strong></td>');
                tabla.push('</tbody>');
                tabla.push('</table>');

                tabla.push('<table id="info" style="width:100%; text-align:center;">');
                if (data.hasOwnProperty('siguiente')) {
                    tabla.push('<thead>');
                    tabla.push('<tr>');
                    if (data.unidad.cooperativa_id != '63e58b552243df4233755082') {
                        tabla.push('<th class="left">P</th>');
                    }
                    tabla.push('<th colspan="2">');
                    if (data.anterior != null) {
                        tabla.push(data.anterior.unidad.descripcion + '<br/>');
                        if (data.anterior.ruta != null)
                            tabla.push((data.anterior.ruta.rutapadre == null) ? data.anterior.ruta.descripcion :
                                data.anterior.ruta.rutapadre.descripcion);
                    } else
                        tabla.push('-');
                    tabla.push('</th>');
                    tabla.push('<th colspan="2">');
                    tabla.push(data.unidad.descripcion);
                    tabla.push('</th>');
                    tabla.push('<th colspan="2">');
                    if (data.siguiente_bus != null) {
                        tabla.push(data.siguiente_bus.unidad.descripcion + '<br/>');
                        if (data.siguiente_bus.ruta != null)
                            tabla.push((data.siguiente_bus.ruta.rutapadre == null) ? data.siguiente_bus.ruta
                                .descripcion : data.siguiente_bus.ruta.rutapadre.descripcion);
                    } else
                        tabla.push('-');
                    tabla.push('</th>');
                    tabla.push('<th class="right">');
                    tabla.push('<strong>' + data.unidad.descripcion + '</strong>');
                    tabla.push('</th>');
                    tabla.push('</tr>');
                    tabla.push('</thead>');
                }
                //CUANDO LA VUELTA ANTERIOR TIENE MAS PUNTOS QUE LA QUE SE VA A REALIZAR SE DEBE RECORRER APARTE EL DATA.ANTERIOR.PUNTOS_CONTROL PARA CON ESTO LUEGO HACER UN APPEND LA TABLA, 
                let recorrido_puntos = data.puntos_control.length;
                if (data.anterior)
                    if (data.anterior.puntos_control != null && data.anterior.puntos_control != undefined) {
                        if (recorrido_puntos < data.anterior.puntos_control.length)
                            recorrido_puntos = data.anterior.puntos_control.length;
                    }

                var siguienteBusAT = 0;
                var siguienteBusAD = 0;
                var anteriorBusAT = 0;
                var anteriorBusAD = 0;
                var actualBusAD = 0;
                var actualBusAT = 0;

                for (var i = 0; i < recorrido_puntos; i++) {
                    tabla.push('<tr style="line-height : 0.7;">');
                    if (data.hasOwnProperty('siguiente'))
                        if (data.unidad.cooperativa_id != '63e58b552243df4233755082') {
                            tabla.push('<td class="left">' + ((data.puntos_control[i] != null) ? data
                                .puntos_control[i]
                                .descripcion : '-') + '</td>');
                        }
                    if (data.anterior != null) {
                        if (data.anterior.puntos_control[i] != null) {
                            if (data.anterior.puntos_control[i].tiempo_esperado != null && data.anterior
                                .puntos_control[i].tiempo_esperado != undefined) {
                                var horaAnterior = new Date(parseInt(data.anterior.puntos_control[i].tiempo_esperado
                                    .$date.$numberLong));
                                horaAnterior.setHours(horaAnterior.getHours() + 5);
                                tabla.push('<td> ' + ((i === 0 && data.hasOwnProperty('siguiente')) ? horaAnterior
                                    .format('H:i') : horaAnterior.format('i')) + '</td>');
                                if (data.anterior.puntos_control[i].marca != null) {
                                    tabla.push('<td>' + data.anterior.puntos_control[i].intervalo + '</td>');
                                    if (data.anterior.puntos_control[i].intervalo > 0)
                                        anteriorBusAT += data.anterior.puntos_control[i].intervalo;
                                    else
                                        anteriorBusAD += data.anterior.puntos_control[i].intervalo * -1;
                                } else
                                    tabla.push('<td>-</td>');
                            } else {
                                tabla.push('<td>-</td>');
                                tabla.push('<td>-</td>');
                            }
                        } else {
                            tabla.push('<td>-</td>');
                            tabla.push('<td>-</td>');
                        }
                    } else {
                        tabla.push('<td>-</td>');
                        tabla.push('<td>-</td>');
                    }

                    if (data.siguiente !== null) {
                        if (data.siguiente.puntos_control[i] != null && data.siguiente.puntos_control[i] !=
                            undefined) {
                            let sig = new Date(parseInt(data.siguiente.puntos_control[i].tiempo_esperado.$date
                                .$numberLong));
                            sig.setHours(sig.getHours() + 5);
                            tabla.push('<td>' + ((i === 0) ? sig.format('H:i') : sig.format('i')) + '</td>');
                            tabla.push('<td>' + ((data.siguiente.puntos_control[i] != null && data.siguiente
                                    .puntos_control[i].intervalo != null) ? data.siguiente.puntos_control[i]
                                .intervalo : '-') + '</td>');
                            if ((data.siguiente.puntos_control[i] != null && data.siguiente.puntos_control[i]
                                    .intervalo != null)) {
                                if (data.siguiente.puntos_control[i].intervalo > 0)
                                    actualBusAT += data.siguiente.puntos_control[i].intervalo;
                                else
                                    actualBusAD += data.siguiente.puntos_control[i].intervalo * -1;
                            }
                        } else {
                            tabla.push('<td>-</td>');
                            tabla.push('<td>-</td>');
                        }
                    } else {
                        tabla.push('<td>-</td>');
                        tabla.push('<td>-</td>');
                    }
                    if (data.siguiente_bus != null && data.siguiente_bus.puntos_control[i] != null) {
                        if (data.siguiente_bus.puntos_control[i] != null && data.siguiente_bus.puntos_control[i] !=
                            undefined) {
                            var horaUltima = new Date(parseInt(data.siguiente_bus.puntos_control[i].tiempo_esperado
                                .$date.$numberLong));
                            horaUltima.setHours(horaUltima.getHours() + 5);
                            tabla.push('<td> ' + ((i === 0) ? horaUltima.format('H:i') : horaUltima.format('i')) +
                                '</td>');
                            if (data.siguiente_bus.puntos_control[i].marca != null) {
                                tabla.push('<td>' + data.siguiente_bus.puntos_control[i].intervalo + '</td>');
                                if (data.siguiente_bus.puntos_control[i].intervalo > 0)
                                    siguienteBusAT += data.siguiente_bus.puntos_control[i].intervalo;
                                else
                                    siguienteBusAD += data.siguiente_bus.puntos_control[i].intervalo * -1;
                            } else
                                tabla.push('<td>-</td>');
                        } else {
                            tabla.push('<td>-</td>');
                            tabla.push('<td>-</td>');
                        }
                    } else if (data.hasOwnProperty('siguiente')) {
                        tabla.push('<td>-</td>');
                        tabla.push('<td>-</td>');
                    }
                    if (data.hasOwnProperty('siguiente')) {
                        if (data.puntos_control[i] != null) {
                            var hora = new Date(data.puntos_control[i].tiempo_esperado.date);
                            hora.setHours(hora.getHours() + 10);
                            tabla.push('<td class="right"><strong  style="font-size: 20px;">' + ((i === 0) ? hora
                                .format('H:i') : hora.format(
                                    'i')) + '</strong></td>');
                        } else {
                            tabla.push('<td>-</td>');
                        }
                    }
                    tabla.push('</tr>');
                }
                tabla.push('</table>');

                tabla.push('<table style="width:100%; text-align:center;">');
                tabla.push('<tr>');
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                if (data.unidad.cooperativa_id == '63e58b552243df4233755082') {
                    tabla.push('<td>' + actualBusAT + '</td>')
                } else {
                    if (data.siguiente !== null) {
                        tabla.push('<td>' + data.siguiente.contador_inicial + '/' + data.siguiente.contador_final +
                            '</td>');
                    } else {
                        tabla.push('<td>-/-</td>')
                    }
                }
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')
                tabla.push('<td></td>')

                if (data.unidad.cooperativa_id == '63e58b552243df4233755082') {
                    if (data.siguiente != null) {
                        if (data.siguiente.multa != null) {
                            tabla.push('<td><b> $ ' + toFloat(data.siguiente
                                .multa).toFixed(2) + '</b></td>');
                        } else {
                            tabla.push('<td>-</td>')
                        }
                    } else {
                        tabla.push('<td>-</td>')
                    }
                } else {
                    tabla.push('<td><b>' + data.contador_inicial + '</b></td>');
                }
                tabla.push('</tr>');
                tabla.push('</table><br/>');

                tabla.push('<table style="width:100%; text-align:center;">');
                tabla.push('<tr>');
                tabla.push('<td >' + new Date().format('d/m/Y H:i:s') + '</td>');
                tabla.push('</tr>');
                tabla.push('</table>');
                //tabla.push('<b>Descuento: $ ' + ((data.multa == null)?'-':data.multa.toFixed(2)) + '</b><br/>');
                tabla.push('<b>Corte de tubo:' + ((data.anterior == null) ? '-' : data.anterior.corte_tubo) +
                    '</b><br/>');
                if (data.unidad.cooperativa_id != '63e58b552243df4233755082') {
                    if (data.siguiente_bus != null) {
                        tabla.push('<b>Total AD ant.:' + anteriorBusAD + '</b>&nbsp&nbsp&nbsp&nbsp');
                        tabla.push('<b>Total AT ant.:' + anteriorBusAT + '</b><br/>');
                        tabla.push('<b>Total AD uni.:' + actualBusAD + '</b>&nbsp&nbsp&nbsp&nbsp');
                        tabla.push('<b>Total AT uni.:' + actualBusAT + '</b><br/>');
                        tabla.push('<b>Total AD sig.:' + siguienteBusAD + '</b>&nbsp&nbsp&nbsp&nbsp');
                        tabla.push('<b>Total AT sig.:' + siguienteBusAT + '</b><br/>');
                    }
                }
                tabla.push('</div>');
                tabla.push('</body>');
                tabla.push('<script>');
                tabla.push('function imprimirTicket()');
                tabla.push('{');
                tabla.push('var doc=document.getElementById("btImprimirTicket");');
                tabla.push('doc.setAttribute("style","display:none");');
                tabla.push('window.print();');
                tabla.push('}');
                tabla.push('<\/script>');
                tabla.push('</html>');
                html = tabla.join('');
                w.document.write(html);
                w.document.close();
            }, 'json');
        }

        function construirImpresionOrquideas(id) {
            var url = '{{ url('/despachos') }}' + '/' + id;
            var w = window.open("", "Imprimir", "width=250,height=400");
            $.get(url, function(data) {
                var tabla = [];
                tabla.push('<!DOCTYPE html>');
                tabla.push('<html>');
                tabla.push('<head>');
                tabla.push('<style>');
                tabla.push('@media print {input : { visibility:hidden !important; }}');
                tabla.push('@page {size: auto;margin: 1;padding:1;}');
                tabla.push('</style>')
                tabla.push('</head>');
                tabla.push('<body style="display:block;overflow:auto;">');
                tabla.push('<div>');
                tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
                tabla.push('<table style="width:100%;">');
                tabla.push('<tr>');
                tabla.push('<td> Conductor: ' + data.conductor.nombre + '</td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td> Fecha: ' + new Date(data.fecha).format('d/m/Y') + '</td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td> Unidad: ' + data.unidad.descripcion + '</td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td> Ruta: ' + data.ruta.descripcion + '</td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                var horainicio = new Date(data.fecha);
                horainicio.setHours(horainicio.getHours() + 5);
                tabla.push('<td> Hora inicio: ' + new Date(horainicio).format('H:i') + '</td>');
                tabla.push('</tr>');
                // tabla.push('<tr>');
                // tabla.push('<td>Contador: ' + ((data.unidad.contador_inicial != null) ? data.unidad
                //     .contador_inicial : '-') + '/' + ((data.primero != null) ? data.primero
                //     .contador_inicial : '-') + '</td>');
                // tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('</tr>');
                tabla.push('</table>');
                tabla.push('<table style="width:100%; text-align:center;">');
                tabla.push('<th></th><th></th><th></th>');
                for (var i = 0; i < data.puntos_control.length; i++) {
                    tabla.push('<tr style="line-height : 0.7;">');
                    tabla.push('<td>' + (i + 1) + '</td>');
                    var horaPeruano = new Date(data.puntos_control[i].tiempo_esperado.date);
                    horaPeruano.setHours(horaPeruano.getHours() + 10);
                    tabla.push('<td style="font-size: 14px;">' + data.ruta.puntos[i].puntoControl.descripcion +
                        '</td>');
                    tabla.push('<td class="leftborder">' + new Date(horaPeruano).format('H:i') + '</td>');
                    tabla.push('</tr>');
                    tabla.push('<tr>');
                    tabla.push('</tr>');
                    tabla.push('<tr>');
                    tabla.push('</tr>');
                }
                tabla.push('</table><br/>');
                tabla.push('</div>');
                tabla.push('</body>');
                tabla.push('</html>');
                html = tabla.join('');
                w.document.body.innerHTML = html;
            }, 'json');
        }

    $(function () {
        $('#cooperativa_id').chosen({
            width : '100%'
        }).change(function () {
            cargaDatosCooperativa($(this).val());
        });
        $('#unidad_id').chosen({
            width : '100%'
        });
        $('#ruta_id').chosen({
            width : '100%'
        });
        $('#fecha_inicio').datetimepicker();
        $('#fecha_fin').datetimepicker(
        );
        $('#hoy').click(function () {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours =  "00";//String(date.getHours()).padStart(2, '0');
                const minutes = "00";//String(date.getMinutes()).padStart(2, '0');

                const hours2 =  "23";//String(date.getHours()).padStart(2, '0');
                const minutes2 = "59";//String(date.getMinutes()).padStart(2, '0');

                const formattedDate = `${year}/${month}/${day} ${hours}:${minutes}`;
                const formattedDate2 = `${year}/${month}/${day} ${hours2}:${minutes2}`;
                $('#fecha_inicio').val(formattedDate);
                $('#fecha_fin').val(formattedDate2);
                $('#fecha_inicio').prop('readonly', true);
                $('#fecha_fin').prop('readonly', true);
            });
            $('#ayer').click(function () {
                const date = new Date();
                date.setDate(date.getDate() - 1);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = "00";//String(date.getHours()).padStart(2, '0');
                const minutes = "00";//String(date.getMinutes()).padStart(2, '0');

                const hours2 =  "23";//String(date.getHours()).padStart(2, '0');
                const minutes2 = "59";//String(date.getMinutes()).padStart(2, '0');

                const formattedDate = `${year}/${month}/${day} ${hours}:${minutes}`;
                const formattedDate2 = `${year}/${month}/${day} ${hours2}:${minutes2}`;
                $('#fecha_inicio').val(formattedDate);
                $('#fecha_fin').val(formattedDate2);
                $('#fecha_inicio').prop('readonly', true);
                $('#fecha_fin').prop('readonly', true);
            });
            $('#personalizado').click(function () {
                $('#fecha_inicio').prop('readonly', false);
                $('#fecha_fin').prop('readonly', false);
            });
            $('#hoy').trigger('click');
            $('#seleccionar_unidades').click(function () {
                var checked = $('#seleccionar_unidades').is(':checked');
                $("#unidad_id").find("option").each(function() {
                    $(this).prop('selected', checked);
                    $('#unidad_id').trigger('chosen:updated');
                });
                if (checked)
                    $('#div_unidades').hide();
                else
                    $('#div_unidades').show();
            });
            $('#seleccionar_rutas').click(function () {
                $("#ruta_id").find("option").each(function() {
                    $(this).prop('selected', $('#seleccionar_rutas').is(':checked'));
                    $('#ruta_id').trigger('chosen:updated');
                });
            });
    });
    
    @if (isset($rutas) && isset($unidades) && isset($cooperativa_id) && isset($desde) && isset($hasta))
        $('#cooperativa_id').val('{{ $cooperativa_id }}');
        $('#cooperativa_id').trigger('chosen:updated');
        cargaDatosCooperativa($('#cooperativa_id').val());
        var rutas = [];
        var unidades = [];
        document.getElementById('fecha_inicio').value = '{{ $desde }}';
        document.getElementById('fecha_fin').value = '{{ $hasta }}';
        @foreach ($rutas as $ruta)
            rutas.push('{{ $ruta }}');
        @endforeach
        @foreach ($unidades as $unidad)
            unidades.push('{{ $unidad }}');
        @endforeach
        $('#ruta_id').val(rutas).trigger('chosen:updated');
        $('#unidad_id').val(unidades).trigger('chosen:updated');
        if ({{ $unidades->count() }} == unidades.length){
            $('#seleccionar_unidades').prop('checked', true);
            $('#div_unidades').hide();
        }
    @else 
        cargaDatosCooperativa($('#cooperativa_id').val());
    @endif
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry"
    async defer></script>
@endsection
