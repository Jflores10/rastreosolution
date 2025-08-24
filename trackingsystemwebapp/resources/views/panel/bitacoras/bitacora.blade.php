@extends('layouts.app')
@section('title')
Bitacoras
@endsection
@section('styles')
<style>
	.table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th
	{
		padding : 0px;
	}
	.btn {
		padding : 3.5px 3px;
	}
</style>
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Bitacora</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lista de Bitacoras</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <button type="button"  onclick="nuevo();" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
                <br />
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <form class="form-inline" method="GET" action="/bitacora/search">
                        <input type="hidden" name="tipo" value="{{ $tipo }}" />
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <div class="radio">
                                    <label for="hoy">
                                        <input type="radio" id="hoy" name="filtro_fecha" value="H" /> Hoy
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="radio">
                                    <label for="ayer">
                                        <input type="radio" id="ayer" name="filtro_fecha" value="A" /> Ayer
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="radio">
                                    <label for="personalizado">
                                        <input type="radio" id="personalizado" name="filtro_fecha" value="P" /> Personalizado
                                    </label>
                                </div>
                            </div>
                            <div class="form-group{{ ($errors->has('desde'))?' has-error':'' }}">
                                <label for="desde">Desde</label><br/>
                                <input type="text" class="form-control" autocomplete="off" autocorrect="off" id="desde" placeholder="aaaa-mm-dd" name="desde" value="{{ (isset($desde))?$desde->format('Y-m-d'):old('desde') }}">
                                @if ($errors->has('desde'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('desde') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ ($errors->has('hasta'))?' has-error':'' }}">
                                <label for="hasta">Hasta</label></br>
                                <input type="text" class="form-control" id="hasta" autocomplete="off" autocorrect="off" placeholder="aaaa-mm-dd" name="hasta" value="{{ (isset($hasta))?$hasta->format('Y-m-d'):old('hasta') }}">
                                @if ($errors->has('hasta'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('hasta') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div id="div_cooperativa" class="form-group{{ ($errors->has('cooperativa'))?' has-error':'' }}">
                                <label for="cooperativa">Cooperativa</label>
                                <select name="cooperativa_search" id="cooperativa_search" class="form-control" data-placeholder="Cooperativa">
                                    @foreach ($cooperativas as $cooperativa)
                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('cooperativa'))
                                    <span><strong>{{ $errors->first('cooperativa') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group{{ ($errors->has('unidades'))?' has-error':'' }}">
                                <label for="unidades">Unidades</label>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="seleccionar_unidades" /> Todas</label>
                                </div>
                                <div id="unidades_div">
                                    <select multiple name="unidades[]" id="unidades_search" class="form-control" data-placeholder="Unidades">
                                    </select>
                                </div>
                                @if ($errors->has('unidades'))
                                    <span><strong>{{ $errors->first('unidades') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group">
                                <br/>
                                <button type="submit" class="btn btn-default">Buscar</button>
                            </div>
                        </div>
                    </from>
                </div>
                <ul class="nav nav-tabs nav-justified">
                    <li {{ (($tipo === 'P')?"class=active; style=background-color:#AECCFC;":'') }} ><a href="{{ url('/bitacora') }}">Pendientes</a></li>
                    <li {{ (($tipo === 'F')?"class=active; style=background-color:#AECCFC;":'') }}><a href="{{ url('/bitacora/finalizados') }}">Finalizadas</a></li>
                </ul>
                @if ($bitacoras->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <th>Unidad</th>
                            <th>Descripción</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Tipo</th>
                            <th>Usuario Creador</th>
                            <th>Usuario Modificador </th>
                            <th></th>
                            @foreach ($bitacoras as $bitacora)
                                <tr>
                                    <td>{{ ($bitacora->unidad == null)?'No disponible':$bitacora->unidad->descripcion }}</td>
                                    <td>{{$bitacora->descripcion}}</td>
                                    <td>{{ $bitacora->fechaInicio->addHours(5) }}</td>
                                    <td>{{($bitacora->fechaFin != null)? $bitacora->fechaFin->addHours(5):'--' }}</td>
                                    <td><img width="35" height="35" src="{{(($bitacora->tipo_bitacora == 'R')?'/images/police.png':(($bitacora->tipo_bitacora == 'M')?'/images/mantenimiento.png':'/images/other.png'))}}"/></td>
                                    <td>{{ ($bitacora->creador!=null)?$bitacora->creador->name:""}}</td>
                                    <td>{{ ($bitacora->modificador!=null && $bitacora->modificador != '')?$bitacora->modificador->name:""}}</td>
                                    @if($bitacora->estado=='P')
                                        <td><button onclick="editBitacora('{{ $bitacora->_id }}');" type="button" class="btn btn-info"><i class="fa fa-info"></i> Editar</button></td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                        {{ $bitacoras->links() }}
                    </div>
                    </div>
                @else
                    <div class="alert alert-danger">No hay registros que mostrar.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="formBitacora" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalLabel">Bitacora</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label>Cooperativa</label>
                            <select autofocus class="form-control" id="cooperativa" name="cooperativa">
                                @foreach ($cooperativas as $cooperativa)
                                    <option  value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="div_unidad" class="form-group">
                            <label>Unidad</label>
                            <select data-placeholder="Seleccione la unidad" class="form-control chosen-select" id="unidad" name="unidad">
                                <option disabled></option>
                            </select>
                            <span class="help-block" id="span_unidad"></span>
                        </div>
                        </br>
                        <div id="div_fechaInicio" class="form-group">
                            <label>Fecha Inicio</label>
                            <input class="form-control" name="fechaInicio" id="fechaInicio" autocomplete="off" autocorrect="off"  type="text"  placeholder="aaaa-mm-dd hh:mm"/>
                            <span class="help-block" id="span_fechaInicio"></span>
                        </div>                       
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select autofocus class="form-control" id="tipo_bitacora" name="tipo_bitacora">                        
                                <option  value="M">Mantenimiento</option>
                                <option  value="R">Retenido</option>
                                <option  value="O">Otros</option>
                            </select>
                        </div>
                        <div class ="form-group">
                            <img id="imageBitacora" src="/images/mantenimiento.png">
                        </div>                       
                        <div id="div_fechaFin" class="form-group">
                            <label>Fecha Fin</label>
                            <input class="form-control" name="fechaFin" id="fechaFin" autocomplete="off" autocorrect="off" type="text"  placeholder="aaaa-mm-dd hh:mm"/>
                            <span class="help-block" id="span_fechaFin"></span>
                        </div>
                    </div>
                </div>
                <div class="checkbox" id="div-compartir">
                    <label><input type="checkbox" name="compartir" id="compartir"/> Compartir con Coop.</label>
                </div>
                <div id="div_descripcion" class="form-group">
                    <label>Descripción</label>
                    <textarea  class="form-control" name="descripcion" id="descripcion"  type="text"></textarea>
                    <span class="help-block" id="span_descripcion"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="save();">Aceptar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
    var id_bitacora=null;

    window.onload = function () { 
        $('#menu_toggle').trigger('click');
    }

    $('#unidades_search').chosen({
            width : '100%'
    });
    $('#unidad').chosen({
            width : '100%'
    });
    $('#cooperativa').chosen({
            width : '100%'
    });
    $('#tipo_bitacora').chosen({
            width : '100%'
    });

    $('#fechaFin').datetimepicker();
    $('#fechaInicio').datetimepicker();

    function nuevo(){
        $('#span_fechaInicio').empty();
        $('#span_fechaFin').empty();
        $('#span_unidad').empty();
        $('#span_descripcion').empty(); 
        $('#fechaInicio').val(''); 
        $('#fechaFin').val(''); 
        $('#descripcion').val(''); 

        $("#compartir").prop('checked', false);

        id_bitacora=null;
        $('#formBitacora').modal('show');
    }

    function save()
    {
        var url = '{{ url("/bitacora") }}';
        var unidad = document.getElementById('unidad');
        var fechaInicio = document.getElementById('fechaInicio');
        var fechaFin = document.getElementById('fechaFin');
        var tipo_bitacora = document.getElementById('tipo_bitacora');
        var descripcion = document.getElementById('descripcion');
        var span_unidad = document.getElementById('span_unidad');
        var span_fechaInicio = document.getElementById('span_fechaInicio');
        var span_descripcion = document.getElementById('span_descripcion');
        var div_unidad = document.getElementById('div_unidad');
        var div_descripcion = document.getElementById('div_descripcion');
        var div_fechaInicio = document.getElementById('div_fechaInicio');
        div_fechaInicio.classList.remove('has-error');
        div_unidad.classList.remove('has-error');
        div_descripcion.classList.remove('has-error');

        $('#span_fechaInicio').empty();
        $('#span_fechaFin').empty();
        $('#span_unidad').empty();
        $('#span_descripcion').empty(); 
        var save=true;

        if(fechaFin.value != null && fechaFin.value != undefined && fechaInicio.value != null && fechaInicio.value != undefined){
            var d1=new Date(fechaInicio.value);
            var d2=new Date(fechaFin.value);

            if(d1 > d2){
                save=false;
                alert('Fechas rangos mal ingresados');
            }
        }

        var compartido='N';
        if($("#compartir").is(':checked'))
            compartido='S';
        else
            compartido='N';
    

        if(save){
            $.post(url, {
                unidad : unidad.value,
                fechaInicio : fechaInicio.value,
                fechaFin : fechaFin.value,
                descripcion : descripcion.value,
                tipo_bitacora : tipo_bitacora.value,
                accion : (id_bitacora != null)?'M':'C',
                id_bitacora : id_bitacora,
                compartido : compartido
            }, function (data) {
                if (data.error == true)
                {
                    if (data.messages.hasOwnProperty('fechaInicio'))
                    {
                        div_fechaInicio.classList.add('has-error');
                        span_fechaInicio.appendChild(document.createTextNode(data.messages.fechaInicio[0]));
                    }
                    if (data.messages.hasOwnProperty('unidad'))
                    {
                        div_unidad.classList.add('has-error');
                        span_unidad.appendChild(document.createTextNode(data.messages.unidad[0]));
                    }
                    if (data.messages.hasOwnProperty('descripcion'))
                    {
                        div_descripcion.classList.add('has-error');
                        span_descripcion.appendChild(document.createTextNode(data.messages.descripcion[0]));
                    }
                }
                else if (data.bitacora != null)
                {
                    alert('Operación ejecutada con éxito.');
                    location.reload(true);
                }

            }, 'json');
        }
    }
    
    function editBitacora(id){
        var url = '{{ url("/bitacora") }}' + '/' + id;
        $.get(url, function(data) {
            var fechaInicio = document.getElementById('fechaInicio');
            var fechaFin = document.getElementById('fechaFin');
            var descripcion = document.getElementById('descripcion');
            var span_unidad = document.getElementById('span_unidad');
            var span_fechaInicio = document.getElementById('span_fechaInicio');
            var span_descripcion = document.getElementById('span_descripcion');
            var div_unidad = document.getElementById('div_unidad');
            var div_descripcion = document.getElementById('div_descripcion');
            var div_fechaInicio = document.getElementById('div_fechaInicio');
            div_fechaInicio.classList.remove('has-error');
            div_unidad.classList.remove('has-error');
            div_descripcion.classList.remove('has-error');

            $('#span_fechaInicio').empty();
            $('#span_fechaFin').empty();
            $('#span_unidad').empty();
            $('#span_descripcion').empty(); 
            $('#fechaInicio').val(''); 
            $('#fechaFin').val(''); 
            $('#descripcion').val(''); 

            //console.log(data);
            $('#cooperativa').val(data.unidad.cooperativa_id);
            $('#cooperativa').trigger('chosen:updated');

            $('#progress').modal('show');
            $.get('{{ url("/bitacora") }}' + '/' + data.unidad.cooperativa_id + '/unidades', function (unidades) {
                $('#unidad').empty();
                for (var i = 0; i < unidades.length; i++)
                {
                    $('#unidad').append('<option value="' + unidades[i]._id + '">' + unidades[i].descripcion + '</option>');
                    $('#unidad').trigger("chosen:updated");
                }

                $('#unidad').val(data.unidad_id);
                $('#unidad').trigger('chosen:updated');

                $('#progress').modal('hide');
            }, 'json');
                   
            
            $('#tipo_bitacora').val(data.tipo_bitacora);
            $('#tipo_bitacora').trigger('chosen:updated');

            if(data.tipo_bitacora == 'M'){
                $("#imageBitacora").attr("src", "/images/mantenimiento.png");
            }else{
                if(data.tipo_bitacora == 'R'){
                    $("#imageBitacora").attr("src", "/images/police.png");
                }else{
                    $("#imageBitacora").attr("src", "/images/other.png");
                }
            }

            id_bitacora=data._id;
            descripcion.value=data.descripcion;
            fechaInicio.value=data.fechaInicio;

            if(data.compartido != null && data.compartido != undefined)
                if(data.compartido == 'S')
                    $("#compartir").prop('checked', true);
                else
                    $("#compartir").prop('checked', false);

            if(data.estado=='F')
                fechaFin.value=data.fechaFin;
            
            $('#formBitacora').modal('show');
        }, 'json');
    }
    
    function getUnidades(coop)
    {
        $('#progress').modal('show');
        $.get('{{ url("/bitacora") }}' + '/' + coop + '/unidades', function (data) {
            $('#unidad').empty();
            for (var i = 0; i < data.length; i++)
            {
                $('#unidad').append('<option value="' + data[i]._id + '">' + data[i].descripcion + '</option>');
                $('#unidad').trigger("chosen:updated");
            }

            $('#progress').modal('hide');
        }, 'json');

    }
    getUnidades(document.getElementById('cooperativa').value);

    $('#cooperativa').chosen({ width : '100%' }).change(function () {
        getUnidades($(this).val());
    });

    $('#tipo_bitacora').chosen({ width : '100%' }).change(function () {
        if($(this).val() == 'M'){
            $("#imageBitacora").attr("src", "/images/mantenimiento.png");
        }else{
            if($(this).val() == 'R'){
                $("#imageBitacora").attr("src", "/images/police.png");
            }else{
                $("#imageBitacora").attr("src", "/images/other.png");
            }
        }
    });

    $('#cooperativa_search').chosen({
            width : '100%'
    }).change(function () {
        $('#progress').modal('show');
        $.get('{{ url("/bitacora") }}' + '/' + $(this).val()  + '/unidades', function (data) {
                $('#unidades_search').empty();
                for(var i=0; i<data.length; i++)
                {
                    $('#unidades_search').append('<option value="' + data[i]._id + '">' + data[i].descripcion + '</option>');
                    $('#unidades_search').trigger('chosen:updated');
                }
                @if (isset($unidades_search))
                    var unidades = [];
                    @foreach ($unidades_search as $unidad)
                        unidades.push('{{ $unidad }}');
                    @endforeach
                    $('#unidades_search').val(unidades);
                    $('#unidades_search').trigger('chosen:updated');
                    if ({{ count($unidades_search) }} == data.length){
                        $('#seleccionar_unidades').prop('checked', true);
                        $('#unidades_div').hide();
                    }
                @endif

                $('#progress').modal('hide');
            }, 'json');
    });
    $('#cooperativa_search').val(null);
    $('#cooperativa_search').trigger('chosen:updated');
    $("#compartir").prop('checked', false);

    $(function () {
        $('#desde').datepicker({
            dateFormat : 'yy-mm-dd'
        });
        $('#hasta').datepicker({
            dateFormat : 'yy-mm-dd'
        });
        $('#hoy').click(function () {
            $('#desde').val(new Date().format('Y-m-d'));
            $('#hasta').val(new Date().format('Y-m-d'));
            $('#desde').prop('readonly', true);
            $('#hasta').prop('readonly', true);
        });
        $('#ayer').click(function () {
            var date = new Date();
            date.setDate(date.getDate() - 1);
            $('#desde').val(date.format('Y-m-d'));
            $('#hasta').val(date.format('Y-m-d'));
            $('#desde').prop('readonly', true);
            $('#hasta').prop('readonly', true);
        });
        $('#personalizado').click(function () {
            $('#desde').prop('readonly', false);
            $('#hasta').prop('readonly', false);
        });
        @if (isset($filtro_fecha))
            	$("input[name=filtro_fecha][value='{{ $filtro_fecha }}']").prop("checked",true);
        @else
            $('#hoy').trigger('click');
        @endif

        @if (Auth::user()->tipo_usuario->valor != 1)
            $('#div-compartir').hide();
            $('#cooperativa_search').val('{{ Auth::user()->cooperativa_id }}');
            $('#cooperativa_search').trigger('chosen:updated');
            $('#cooperativa_search').trigger('change');
            $('#div_cooperativa').hide();
        @endif
        
        @if (isset($cooperativa_search))
            $('#cooperativa_search').val('{{ $cooperativa_search }}');
            $('#cooperativa_search').trigger('chosen:updated');
            $('#cooperativa_search').trigger('change');
        @endif

         $('#seleccionar_unidades').click(function () {
            var checked = $('#seleccionar_unidades').is(':checked');
            $("#unidades_search").find("option").each(function() {
                $(this).prop('selected', checked);
                    $('#unidades_search').trigger('chosen:updated');
            });
            if (checked)
                $("#unidades_div").hide();
            else 
                $("#unidades_div").show();
        });
    });

</script>
@endsection