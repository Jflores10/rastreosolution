@extends('layouts.app')
@section('title')
    Despachos
@endsection
@section('styles')
    <style>
        .table>tbody>tr>td,
        .table>tbody>tr>th,
        .table>tfoot>tr>td,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>thead>tr>th {
            padding: 0px;
        }

        .btn {
            padding: 3.5px 3px;
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
                    <h2>Lista de despachos</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    <button type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i
                            class="fa fa-plus"></i> Crear nuevo</button>
                    <br />
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <form id="formDespacho" class="form-inline" method="GET" action="/despachos/search">
                            <input type="hidden" name="tipo" value="{{ $tipo }}" />
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
                                        <input type="radio" id="personalizado" name="filtro_fecha" value="P" />
                                        Personalizado
                                    </label>
                                </div>
                            </div>
                            <div class="form-group{{ $errors->has('desde') ? ' has-error' : '' }}">
                                <label for="desde">Desde</label><br />
                                <input type="text" class="form-control" autocomplete="off" autocorrect="off"
                                    id="desde" placeholder="aaaa-mm-dd" name="desde"
                                    value="{{ isset($desde) ? $desde->format('Y-m-d') : old('desde') }}">
                                @if ($errors->has('desde'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('desde') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('hasta') ? ' has-error' : '' }}">
                                <label for="hasta">Hasta</label></br>
                                <input type="text" class="form-control" autocomplete="off" autocorrect="off"
                                    id="hasta" placeholder="aaaa-mm-dd" name="hasta"
                                    value="{{ isset($hasta) ? $hasta->format('Y-m-d') : old('hasta') }}">
                                @if ($errors->has('hasta'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('hasta') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div id="div_cooperativa"
                                class="form-group{{ $errors->has('cooperativa') ? ' has-error' : '' }}">
                                <label for="cooperativa">Cooperativa</label>
                                <select name="cooperativa" id="cooperativa_search" class="form-control"
                                    data-placeholder="Cooperativa">
                                    @foreach ($cooperativas as $cooperativa)
                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('cooperativa'))
                                    <span><strong>{{ $errors->first('cooperativa') }}</strong></span>
                                @endif
                            </div>

                            <div id="div_errorAtm" class="form-group">
                                <label for="errorAtm">Mostrar solo Error ATM</label>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="errorAtm" id="errorAtm" /> Si</label>
                                </div>
                            </div>
                            <div class="form-group{{ $errors->has('unidades') ? ' has-error' : '' }}">
                                <label for="unidades">Unidades</label>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="seleccionar_unidades" /> Todas</label>
                                </div>
                                <div id="unidades_div">
                                    <select multiple name="unidades[]" id="unidades_search" class="form-control"
                                        data-placeholder="Unidades">
                                    </select>
                                </div>
                                @if ($errors->has('unidades'))
                                    <span><strong>{{ $errors->first('unidades') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group">

                            </div>
                            <div class="form-group">
                                <br />
                                <input onclick="buscar();" type="button" value="Buscar"
                                    class="btn btn-primary search-options__button" />
                                {{-- <button type="submit" class="btn btn-default">Buscar</button> --}}
                                @if ($tipo === 'L')
                                    <input onclick="finalizarTodos();" id="btnRecalculoAll" type="button"
                                        value="Finalizar los Despachos" class="btn btn-success" />
                                @else
                                    <input onclick="finalizarTodos();" id="btnRecalculoAll" type="button"
                                        value="Recalcular los Despachos" class="btn btn-success" />
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="col-xs-12">
                        <ul class="nav nav-tabs nav-justified">
                            <li {{ $tipo === 'L' ? 'class=active; style=background-color:#AECCFC;' : '' }}><a
                                    href="{{ url('/despachos') }}">Liquidación</a></li>
                            <li {{ $tipo === 'F' ? 'class=active; style=background-color:#AECCFC;' : '' }}><a
                                    href="{{ url('/despachos/frecuencias') }}">Frecuencias</a></li>
                            <li {{ $tipo === 'C' ? 'class=active; style=background-color:#AECCFC;' : '' }}><a
                                    href="{{ url('/despachos/cancelados') }}">Cancelados</a></li>
                        </ul>
                    </div>
                    <div class="col-xs-12">
                        @if ($despachos->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <th>Unidad</th>
                                    <th>Ruta</th>
                                    <th>Conductor</th>
                                    <th>Asignación</th>
                                    <th>Salida</th>
                                    <th>Usuario</th>
                                    @if ($tipo === 'F')
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                    @endif
                                    @if ($tipo === 'C')
                                        <th>Fecha Cancelación</th>
                                        <th>Motivo</th>
                                    @endif
                                    @if ($tipo === 'F')
                                        <th></th>
                                    @endif
                                    <th></th>
                                    @if ($tipo != 'C')
                                        <th></th>
                                        <th></th>
                                    @endif
                                    <th>Exportado a ATM</th>
                                    <th>Fecha Exportado</th>
                                    <th>Error ATM</th>
                                    @foreach ($despachos as $despacho)
                                        <tr>
                                            <td>
                                                {{ $despacho->unidad == null ? 'No disponible' : $despacho->unidad->descripcion }}
                                            </td>
                                            <td>{{ $despacho->ruta == null ? 'No disponible' : $despacho->ruta->descripcion }}
                                            </td>
                                            <td>{{ $despacho->conductor == null ? 'No disponible' : $despacho->conductor->nombre }}
                                            </td>
                                            <td>{{ $despacho->created_at }}</td>
                                            <td>{{ $despacho->fecha->addHours(5) }}</td>
                                            <td>{{ $despacho->modificador != null ? $despacho->modificador->name : '' }}
                                            </td>
                                            @if ($tipo === 'F')
                                                <td>{{ $despacho->contador_inicial }}</td>
                                                <td>{{ $despacho->contador_final }} </td>
                                            @endif
                                            @if ($tipo === 'C')
                                                <td>{{ $despacho->updated_at != null ? $despacho->updated_at : '' }}</td>
                                                <td>{{ $despacho->motivo_cancelar != null ? $despacho->motivo_cancelar : '' }}
                                                </td>
                                            @endif
                                            @if ($tipo === 'F')
                                                <td><button onclick="construirImpresionInfo('{{ $despacho->_id }}');"
                                                        type="button" class="btn btn-default"><i
                                                            class="fa fa-print"></i> Info</button></td>
                                            @endif
                                            <td><button onclick="construirImpresion('{{ $despacho->_id }}');"
                                                    type="button" class="btn btn-default"><i class="fa fa-print"></i>
                                                    Imprimir</button></td>
                                            @if ($tipo != 'C')
                                                @if ($despacho->ruta->ruta_padre == '5b4528f9f544150ac01cecc6')
                                                    <td><button onclick="finish('{{ $despacho->_id }}');" type="button"
                                                            class="btn btn-info"><i class="fa fa-check"></i>
                                                            {{ $despacho->estado === 'C' ? 'Recalcular' : 'Culminar' }}</button>
                                                    </td>
                                                @else
                                                    <td><button onclick="finish('{{ $despacho->_id }}');" type="button"
                                                            class="btn btn-primary"><i class="fa fa-check"></i>
                                                            {{ $despacho->estado === 'C' ? 'Recalcular' : 'Culminar' }}</button>
                                                    </td>
                                                @endif                                            
                                                @if ($despacho->unidad->cooperativa_id != '59e8ddc83ebdfd11696c11f2')
                                                    <td>
                                                    <button onclick="modalCancelar('{{ $despacho->_id }}');"
                                                        type="button" class="btn btn-danger"><i class="fa fa-close"></i>
                                                        Cancelar</button>
                                                    
                                                    </td>
                                                @else
                                                    <td> </td>
                                                @endif

                                            @endif
                                            <td>{{ $despacho->estado_exportacion == 'E' ? 'Si' : 'No' }}</td>
                                            {{-- <td>{{ $despacho->fecha_exportado }}</td> --}}
                                            @if ($despacho->estado_exportacion == 'E')
                                                <td></td>
                                            @else
                                                @if (isset($despacho->error_ATM))
                                                    <td>
                                                        <button onclick="errorAtm('{{ $despacho->_id }}');"
                                                            type="button" class="btn btn-info"><i
                                                                class="fa fa-info"></i> Ver</button>
                                                        <button onclick="reenviarATM('{{ $despacho->_id }}');"
                                                            type="button" class="btn btn-warning"><i
                                                                class="fa fa-upload"></i> Reenviar</button>
                                                    </td>
                                                @else
                                                    <td></td>
                                                @endif
                                            @endif
                                        </tr>
                                    @endforeach
                                </table>
                                {{ $despachos->links() }}
                            </div>
                        @else
                            <div class="alert alert-danger">No hay registros que mostrar.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="culminar" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabel">Culminación de despacho</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label>Fecha de culminación</label>
                                <input type="text" name="fecha_culminacion" id="fecha_culminacion"
                                    class="form-control" value="{{ date('Y-m-d') }}" />
                            </div>
                            <div class="form-group">
                                <label>Hora de culminación</label>
                                <input type="text" name="hora_culminacion" id="hora_culminacion" class="form-control"
                                    value="{{ date('H:i') }}" />
                            </div>
                            <input type="hidden" id="id_despacho" name="id_despacho" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnCulminar">Culminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i>
                        Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabel">Despacho</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>Cooperativa</label>
                                <select autofocus class="form-control" id="cooperativa" name="cooperativa">
                                    @foreach ($cooperativas as $cooperativa)
                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="div_unidad" class="form-group">
                                <label>Unidad</label>
                                <select data-placeholder="Seleccione la unidad" class="form-control chosen-select"
                                    id="unidad" name="unidad">
                                    <option disabled></option>
                                </select>
                                <span class="help-block" id="span_unidad"></span>
                            </div>
                            <div id="div_ruta" class="form-group">
                                <label>Ruta</label>
                                <select data-placeholder="Seleccione la ruta" class="form-control chosen-select"
                                    id="ruta" name="ruta">
                                    <option disabled></option>
                                </select>
                                <span class="help-block" id="span_ruta"></span>
                            </div>
                            <div id="div_conductor" class="form-group">
                                <label>Conductor</label>
                                <select data-placeholder="Seleccione el conductor" class="form-control chosen-select"
                                    class="form-control" id="conductor" name="conductor">
                                    <option disabled></option>
                                </select>
                                <span class="help-block" id="span_conductor"></span>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div id="div_fecha" class="form-group">
                                <label>Fecha</label>
                                <input class="form-control" autocomplete="off" autocorrect="off" name="fecha"
                                    id="fecha" placeholder="aaaa-mm-dd" />
                                <span class="help-block" id="span_fecha"></span>
                            </div>
                            <div id="div_hora" class="form-group">
                                <label>Hora</label>
                                <input placeholder="HH:mm" autocomplete="off" autocorrect="off" class="form-control"
                                    type="time" id="hora" />
                                <span class="help-block" id="span_hora"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="alerta_save();">Aceptar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i>
                        Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" id="despacho_notificacion" tabindex="-1" role="dialog"
        aria-labelledby="modalLabel">
        <div class="modal-dialog  modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabel">
                        <font color="red" size="6">Esta seguro que desea crear el siguiente despacho?</font>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div id="div_conductor_warning" class="form-group">
                                <label>
                                    <font size="5">Cooperativa</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="cooperativa_warning" id="cooperativa_warning" readonly />
                            </div>
                            <div class="form-group">
                                <label>
                                    <font size="5">Unidad</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="unidad_warning" id="unidad_warning" readonly />
                            </div>
                            <div id="div_ruta_warning" class="form-group">
                                <label>
                                    <font size="5">Ruta</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="ruta_warning" id="ruta_warning" readonly />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div id="div_conductor_warning" class="form-group">
                                <label>
                                    <font size="5">Conductor</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="conductor_warning" id="conductor_warning" readonly />
                            </div>
                            <div id="div_fecha_warning" class="form-group">
                                <label>
                                    <font size="5">Fecha</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="fecha_warning" id="fecha_warning" readonly />
                            </div>
                            <div id="div_hora_warning" class="form-group">
                                <label>
                                    <font size="5">Hora</font>
                                </label>
                                <input class="form-control" style="font-size:25px;" autocomplete="off" autocorrect="off"
                                    name="hora_warning" id="hora_warning" readonly />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="save();">
                        <font size="5">Si</font>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i>
                        <font size="5"> Cerrar</font>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_errorATM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
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

    <div class="modal fade" id="modal_cancelar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Cancelar Despacho</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>Motivo</label>
                                <textarea type="text" name="motivo_cancelar" id="motivo_cancelar" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="cancel();">
                        <font size="5">Si</font>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i>
                        <font size="5"> Cerrar</font>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script>
        var despachoIdTMP = "";
        window.onload = function() {
            $('#menu_toggle').trigger('click');
        }

        function buscar() {
            var form = document.getElementById('formDespacho');
            form.action = "{{ url('/despachos/search') }}";
            form.submit();
        }

        function finalizarTodos() {
            if (confirm("¿Seguro que desea " + document.getElementById("btnRecalculoAll").value + " ?")) {
                var form = document.getElementById('formDespacho');
                form.action = "{{ url('despachos/despachosfinalizar/despacho') }}";
                $('#progress').modal('show');
                $.post(form.action, $('#formDespacho').serialize(), function(data) {
                    console.log(data);
                    // $('#report').empty().append(data);
                    arrayDespachos = data.despachos;
                    validarCortetuboMasivo();
                }).always(function() {
                    // $('#progress').modal('hide');
                    // location.reload(true);
                });
            }
        }

        function validarCortetuboMasivo() {
            if (arrayDespachos.length > 0) {
                let url = '{{ url('/despachos') }}' + '/' + arrayDespachos[0] + '/despachomasivo';
                $.post(url, function(data) {
                    var array_path = [];
                    for (var i = 0; i < data.rutarecorrido.length; i++) {
                        array_path.push({
                            lat: parseFloat(data.rutarecorrido[i].lat),
                            lng: parseFloat(data.rutarecorrido[i].lng)
                        });
                    }

                    ruta = new google.maps.Polyline({
                        geodesic: true,
                        strokeWeight: 22,
                        path: array_path
                    });
                    var array_corte = [];
                    for (var i = 0; i < data.recorridos.length; i++) {
                        let latitud = data.recorridos[i].latitud;
                        let longitud = data.recorridos[i].longitud;

                        var isLocationOnEdge = google.maps.geometry.poly.isLocationOnEdge;
                        //1e-3

                        let result = isLocationOnEdge(new google.maps.LatLng(parseFloat(latitud), parseFloat(
                            longitud)), ruta, 0.0010);

                        if (!result) {
                            array_corte.push({
                                'lat': parseFloat(latitud),
                                'lng': parseFloat(longitud)
                            });
                        }
                    }

                    if (array_corte.length > 0) {
                        let url_corte = '{{ url('/despachos') }}' + "/cortetubo";
                        var params = {
                            array_corte: array_corte,
                            despacho_id: arrayDespachos[0]
                        };
                        $.post(url_corte, params, function(data) {

                        }).always(function() {
                            arrayDespachos.shift();
                            validarCortetuboMasivo();
                        });
                    } else {
                        arrayDespachos.shift();
                        validarCortetuboMasivo();
                    }
                }).always(function() {
                    // arrayDespachos.shift();
                    // validarCortetuboMasivo();
                });
            } else {
                $('#progress').modal('hide');
                location.reload(true);
            }
        }

        function getUnidades(coop) {
            $.get('{{ url('/despachos') }}' + '/' + coop + '/unidades', function(data) {
                $('#unidad').empty();
                for (var i = 0; i < data.length; i++) {
                    $('#unidad').append('<option value="' + data[i]._id + '">' + data[i].descripcion + '</option>');
                    $('#unidad').trigger("chosen:updated");
                }

                @if (Session::has('unidad'))
                    $('#unidad').val("{{ Session::get('unidad') }}");
                    $('#unidad').trigger('chosen:updated');
                    $('#unidad').trigger('change');
                @endif
            }, 'json');

        }

        function getConductores(coop) {
            $.get('{{ url('/despachos') }}' + '/' + coop + '/conductores', function(data) {
                $('#conductor').empty();
                for (var i = 0; i < data.length; i++) {
                    $('#conductor').append('<option value="' + data[i]._id + '">' + data[i].nombre + '</option>');
                    $('#conductor').trigger("chosen:updated");
                }
                @if (Session::has('conductor'))
                    $('#conductor').val("{{ Session::get('conductor') }}");
                    $('#conductor').trigger('chosen:updated');
                    $('#conductor').trigger('change');
                @endif
            }, 'json');

        }

        function getRutas(coop) {
            $.get('{{ url('/despachos') }}' + '/' + coop + '/rutas', function(data) {
                $('#ruta').empty();
                for (var i = 0; i < data.length; i++) {
                    $('#ruta').append('<option value="' + data[i]._id + '">' + data[i].descripcion + '</option>');
                    $('#ruta').trigger("chosen:updated");
                }
                @if (Session::has('ruta'))
                    var a = "{{ Session::get('ruta') }}";
                    $('#ruta').val("{{ Session::get('ruta') }}");
                    $('#ruta').trigger('chosen:updated');
                    $('#ruta').trigger('change');
                @endif
            }, 'json');

        }

        function alerta_save() {
            $('#hora_warning').val($('#hora').val());
            $('#fecha_warning').val($('#fecha').val());
            $('#conductor_warning').val($('#conductor option:selected').text());
            $('#ruta_warning').val($('#ruta option:selected').text());
            $('#unidad_warning').val($('#unidad option:selected').text());
            $('#cooperativa_warning').val($('#cooperativa option:selected').text());
            $('#despacho_notificacion').modal('show');
        }

        function save() {
            var url = '{{ url('/despachos') }}';
            var unidad = document.getElementById('unidad');
            var ruta = document.getElementById('ruta');
            var conductor = document.getElementById('conductor');
            var fecha = document.getElementById('fecha');
            var span_unidad = document.getElementById('span_unidad');
            var span_ruta = document.getElementById('span_ruta');
            var span_conductor = document.getElementById('span_conductor');
            var span_fecha = document.getElementById('span_fecha');
            var div_unidad = document.getElementById('div_unidad');
            var div_conductor = document.getElementById('div_conductor');
            var div_fecha = document.getElementById('div_fecha');
            var div_ruta = document.getElementById('div_ruta');
            div_fecha.classList.remove('has-error');
            div_conductor.classList.remove('has-error');
            div_unidad.classList.remove('has-error');
            div_ruta.classList.remove('has-error');
            $('#span_fecha').empty();
            $('#span_conductor').empty();
            $('#span_unidad').empty();
            $('#span_ruta').empty();
            $.post(url, {
                unidad: unidad.value,
                conductor: conductor.value,
                ruta: ruta.value,
                fecha: fecha.value + ' ' + $('#hora').val()
            }, function(data) {
                if (data.error == true) {
                    if (data.messages.hasOwnProperty('fecha')) {
                        div_fecha.classList.add('has-error');
                        span_fecha.appendChild(document.createTextNode(data.messages.fecha[0]));
                    }
                    if (data.messages.hasOwnProperty('conductor')) {
                        div_conductor.classList.add('has-error');
                        span_conductor.appendChild(document.createTextNode(data.messages.conductor[0]));
                    }
                    if (data.messages.hasOwnProperty('unidad')) {
                        div_unidad.classList.add('has-error');
                        span_unidad.appendChild(document.createTextNode(data.messages.unidad[0]));
                    }
                    if (data.messages.hasOwnProperty('ruta')) {
                        div_ruta.classList.add('has-error');
                        span_ruta.appendChild(document.createTextNode(data.messages.ruta[0]));
                    }
                } else if (data.despacho != null) {
                    alert('El despacho fue creado con éxito.');
                    fecha.value = null;
                    conductor.value = null;
                    ruta.value = null;
                    unidad.value = null;
                    /*if ($('#cooperativa_search').val() == '588d3d677aea915d897ff041' || $('#cooperativa_search')
                        .val() == '62d762dd2243df1cd73a79e2') {
                        construirImpresionAlbosao(data.despacho._id);
                    } else if ($('#cooperativa_search').val() == '646253b12243df426b1e2a82') {
                        construirImpresionOrquideas(data.despacho._id);
                    } else {
                        construirImpresion(data.despacho._id);
                    }*/
                    location.reload(true);
                } else
                    alert('El despacho ya ha sido creado con anterioridad.');
            }, 'json');
        }

        getUnidades(document.getElementById('cooperativa').value);
        getConductores(document.getElementById('cooperativa').value);
        getRutas(document.getElementById('cooperativa').value);
        var ruta = null
        var localpunto = null
        var localpuntocorte = null;

        function finish(id) {
            var url = '{{ url('/despachos') }}' + '/' + id + '/finish';
            $('#progress').modal('show');
            $.get(url, function(data) {
                if (data.error == false) {
                    var array_path = [];
                    for (var i = 0; i < data.rutarecorrido.length; i++) {
                        // array_path.push({lat:parseFloat(data.rutarecorrido[i].lat),
                        //     lng:parseFloat(data.rutarecorrido[i].lng)});
                        array_path.push(new google.maps.LatLng(parseFloat(data.rutarecorrido[i].lat),
                            parseFloat(data.rutarecorrido[i].lng)));
                    }

                    ruta = new google.maps.Polyline({
                        strokeWeight: 100,
                        path: array_path
                    });
                    // console.log('ruta');
                    // console.log(ruta);
                    var array_corte = [];
                    for (var i = 0; i < data.recorridos.length; i++) {
                        let latitud = data.recorridos[i].latitud;
                        let longitud = data.recorridos[i].longitud;

                        var isLocationOnEdge = google.maps.geometry.poly.isLocationOnEdge;
                        //1e-3

                        localpunto = new google.maps.LatLng(parseFloat(latitud), parseFloat(longitud))
                        // console.log(localpunto);
                        let toleranceInMeters = 10

                        //let resp=isLocationOnEdge(location,ruta,toleranceInMeters)00100

                        let result = isLocationOnEdge(localpunto, ruta, 0.00100);

                        // console.log(result);
                        if (!result) {
                            localpuntocorte = localpunto;
                            /*console.log('ERROR');
                            console.log(latitud);
                            console.log(longitud);*/
                            array_corte.push({
                                'lat': parseFloat(latitud),
                                'lng': parseFloat(longitud)
                            });

                        }
                    }
                    /***GUARDAR COORDENADAS Y SI CORTE TUBO */
                    if (array_corte.length > 0) {
                        console.log('CORTE')
                        let url_corte = '{{ url('/despachos') }}' + "/cortetubo";
                        var params = {
                            array_corte: array_corte,
                            despacho_id: id
                        };
                        $.post(url_corte, params, function(data) {
                            $('#progress').modal('hide');
                            location.reload(true);
                        });
                    } else {
                        $('#progress').modal('hide');
                        location.reload(true);
                    }
                } else {
                    $('#progress').modal('hide');
                    location.reload(true);
                }
            }, 'json');
        }

        // function isLocationOnEdge (location,ruta,toleranceInMeters) {
        //     for(var leg of ruta.getPath().b) {
        //         if(google.maps.geometry.spherical.computeDistanceBetween(location,leg) <= toleranceInMeters){
        //             return 'verdad';
        //         }
        //     } 
        //     return 'false';
        // };

        function modalCancelar(id) {
            $('#modal_cancelar').modal('show');
            despachoIdTMP = id;
        }


        function cancel() {
            var motivo_cancelar = document.getElementById('motivo_cancelar');
            var url = '{{ url('/despachos') }}' + '/' + despachoIdTMP + '/cancel';
            if (confirm('¿Estás seguro de cancelar este despacho?')) {
                $('#progress').modal('show');
                $.post(url, {
                        motivo_cancelar: motivo_cancelar.value
                    },
                    function(data) {
                        if (data.error) {
                            alert('No se puede cancelar un despacho despues de su hora de salida... ORDEN ATM');
                            $('#progress').modal('hide');
                        } else {
                            alert('El despacho fue cancelado con éxito.');
                            location.reload(true);
                        }
                    }, 'json');
            } else {
                $('#modal_cancelar').modal('hide');
            }
        }

        function errorAtm(id) {
            var url = '{{ url('/despachos/error') }}' + '/' + id;
            $('#progress').modal('show');
            $('#errorATM').val('');
            $.get(url, function(data) {
                $('#modal_errorATM').modal('show');
                $('#errorATM').val((data.error_ATM == undefined || data.error_ATM == null) ?
                    'En proceso de envio...' : data.error_ATM);
                $('#progress').modal('hide');
            }, 'json');
        }

        function reenviarATM(id) {
            var url = '{{ url('/despachos/reenviarATM') }}' + '/' + id;
            $('#progress').modal('show');
            $.post(url, function(data) {
                if (!data.error) {
                    $('#progress').modal('hide');
                    location.reload(true);
                } else {
                    alert('Error al actualizar el despacho');
                    $('#progress').modal('hide');
                    location.reload(true);
                }
            }, 'json');
        }

        function toFloat(value) {
            return (value == '' || isNaN(value)) ? 0 : parseFloat(value);
        }

        function construirImpresionInfo(id) {
            var desc1=0;
            var desc2=0;
            var url = '{{ url('/despachos/info') }}' + '/' + id;
            $('#progress').modal('show');
            var w = window.open("", "Imprimir", "width=257,height=400");
            $.get(url, function(data) {
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
                tabla.push('#info .left { border-left: 1px solid black;}');
                tabla.push('#info .right { border-right: 0px; }');
                tabla.push('#info { border-collapse: collapse; }');

                tabla.push('</style>')
                tabla.push('</head>');
                tabla.push('<body style="display:block;overflow:auto;">');
                tabla.push('<div>');
                tabla.push(
                    '<input type="button" id="btImprimirTicket" value="Imprimir" onclick="imprimirTicket();" />'
                );

                let rutaId = (data.ruta.rutapadre == null) ? data.ruta._id : data.ruta.rutapadre._id;
                const ruta44 = '5edb924c2243df11d23c9e62';
                const ruta46 = '5ec4619c2243df3c3074fd32'; 

                tabla.push('<table style="width:100%;">');
                tabla.push('<tbody>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3" align="center"><strong>TICKET INFO.</strong></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                if (data.unidad.cooperativa_id != '5cc725bc2243df3e4d365362' &&
                    data.unidad.cooperativa_id != '5e0778022243df6cb03a0943' &&
                    data.unidad.cooperativa_id != '5ac3032b2243df3f4d02f243') // RAMOVAL 10AGOSTO           
                    tabla.push('<td  colspan="2">' + data.conductor.nombre + '</td>');
                tabla.push('<td>' + data.contador_inicial + '/' + data.contador_final + '</td>');

                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td>' + ((data.ruta.rutapadre == null) ? data.ruta.descripcion : data.ruta.rutapadre
                    .descripcion) + '</td>');
                tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
                tabla.push('<td><strong>' + data.unidad.descripcion + '</strong></td>');
                tabla.push('</tbody>');
                tabla.push('</table>');

                tabla.push('<table id="info" style="width:100%; text-align:center;">');
                tabla.push('<thead><th>PT.</th><th>M</th><th>A-|A+</th></thead>');
                //CUANDO LA VUELTA ANTERIOR TIENE MAS PUNTOS QUE LA QUE SE VA A REALIZAR SE DEBE RECORRER APARTE EL DATA.ANTERIOR.PUNTOS_CONTROL PARA CON ESTO LUEGO HACER UN APPEND LA TABLA, 
                let recorrido_puntos = data.puntos_control.length;
                var anteriorBusAT = 0;
                var anteriorBusAD = 0;

                for (var i = 0; i < recorrido_puntos; i++) {
                    tabla.push('<tr style="line-height : 0.7;">');
                    let descripcionReal = data.unidad.cooperativa_id == '68012ee36d838a101b60fb13';
                    tabla.push('<td class="left">' + ((data.puntos_control[i] != null) ? (
                        descripcionReal?data.puntos_control[i].original_descripcion:data.puntos_control[i].descripcion
                    ) : '-') + '</td>');

                    if (data.puntos_control[i] != null) {
                        if (data.puntos_control[i].tiempo_esperado != null &&
                            data.puntos_control[i].tiempo_esperado != undefined) {
                            console.log(data.puntos_control[i]);
                            var horaAnterior = new Date(parseInt(data.puntos_control[i].tiempo_esperado.$date
                                .$numberLong));
                            horaAnterior.setHours(horaAnterior.getHours() + 5);

                            tabla.push('<td> ' + horaAnterior.format('H:i') + '</td>');
                            if (data.puntos_control[i].marca != null) {
                                var intervalo= (data.puntos_control[i].intervalo!=null &&
                                    data.puntos_control[i].intervalo!=undefined)?data.puntos_control[i].intervalo:0;
                                var atraso = (data.puntos_control[i].atraso != null && data.puntos_control[i]
                                    .atraso != undefined) ? data.puntos_control[i].atraso : 0;
                                var adelanto = (data.puntos_control[i].adelanto != null && 
                                data.puntos_control[i].adelanto != undefined) ? data.puntos_control[i].adelanto : 0;
                            
                                tabla.push('<td>' + data.puntos_control[i].intervalo + '</td>');
                                if (data.puntos_control[i].intervalo > 0)
                                    anteriorBusAT += data.puntos_control[i].intervalo;
                                else
                                    anteriorBusAD += data.puntos_control[i].intervalo * -1;

                                var desc = 0;
                                if (data.puntos_control[i].tiempo_atraso != null && data.puntos_control[i].tiempo_atraso != undefined) {
                                    desc = intervalo * atraso;
                                } else {
                                    desc = intervalo * adelanto;
                                }

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
                    tabla.push('</tr>');
                }

                tabla.push('</table><br/>');
                tabla.push('<table style="width:100%; text-align:center;">');
                tabla.push('<tr>');

                tabla.push('<td >' + new Date().format('d/m/Y H:i:s') + '</td>');
                tabla.push('</tr>');
                tabla.push('</table>');
                if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') {
                    tabla.push('<b>Descuento 1: $ ' + desc1.toFixed(2) + '</b><br/>');
                    tabla.push('<b>Descuento 2: $ ' + desc2.toFixed(2) + '</b><br/>');
                    let descuentoTotal = desc1 + desc2;
                    tabla.push('<b>Total: $ ' + descuentoTotal.toFixed(2) + '</b><br/>'); 
                }
                tabla.push('<b>Cobro Total: $ ' + ((data.multa == null) ? '-' : data.multa.toFixed(2)) + '</b><br/>');
                //tabla.push('<b>Descuento: $ ' + ((data.multa == null)?'-':data.multa.toFixed(2)) + '</b><br/>');
                tabla.push('<b>Corte de tubo:' + ((data == null) ? '-' : data.corte_tubo) + '</b><br/>');
                tabla.push('<b>Total AD ant.:' + anteriorBusAD + '</b>&nbsp&nbsp&nbsp&nbsp');
                tabla.push('<b>Total AT ant.:' + anteriorBusAT + '</b><br/>');
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

        function construirImpresion(id) {
            var desc1=0;
            var desc2=0;
            if ($('#cooperativa_search').val() == null || $('#cooperativa_search').val() == undefined) {
                alert('Seleccione una cooperativa a buscar');
            } else {
                if ($('#cooperativa_search').val() == '588d3d677aea915d897ff041' || $('#cooperativa_search').val() ==
                    '62d762dd2243df1cd73a79e2' || $('#cooperativa_search').val() ==
                    '63e58b552243df4233755082') {
                    construirImpresionAlbosao(id);
                } else if ($('#cooperativa_search').val() == '646253b12243df426b1e2a82') {
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
                            tabla.push('<b>Corte de tubo: ' + ((data.anterior == null) ? '-' : data.anterior
                                .corte_tubo) + '</b><br/>');
                        }
                        tabla.push('</div>');
                        tabla.push('</body>');
                        tabla.push('</html>');
                        html = tabla.join('');
                        w.document.body.innerHTML = html;
                    }, 'json');
                }
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


        function getUltimoDespacho(unidad, coop) {
            $.get('{{ url('/despachos') }}' + '/' + unidad + '/' + coop + '/ultimo_despacho', function(data) {
                //console.log('ultimo despacho'+data);
                // $('#conductor').val("{{ Session::get('conductor') }}");
                if (!data.error) {
                    $('#conductor').val(data.despacho.conductor_id);
                    $('#conductor').trigger('chosen:updated');
                    $('#conductor').trigger('change');
                    $('#ruta').val(data.ruta);
                    $('#ruta').trigger('chosen:updated');
                    $('#ruta').trigger('change');
                }
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

        $(function() {
            $('#cooperativa').chosen({
                width: '100%'
            }).change(function() {
                getUnidades($(this).val());
                getRutas($(this).val());
                getConductores($(this).val());
            });
            $('#unidad').chosen({
                width: '100%'
            }).change(function() {
                getUltimoDespacho($(this).val(), $('#cooperativa').val());
            });
            $('#conductor').chosen({
                width: '100%'
            });
            $('#ruta').chosen({
                width: '100%'
            });
            $('#fecha').datepicker({
                dateFormat: 'yy-mm-dd'
            });
            $('#fecha_culminacion').datepicker({
                dateFormat: 'yy-mm-dd'
            });
            $('#hora_culminacion').bootstrapMaterialDatePicker({
                format: 'HH:mm',
                date: false,
                time: true
            });
            $('#btnCulminar').click(function() {
                culminar();
            });
            $('#cooperativa_search').chosen({
                width: '100%'
            }).change(function() {
                $.get('/despachos/' + $(this).val() + '/unidades', function(data) {
                    $('#unidades_search').empty();
                    $("#div_errorAtm").hide();
                    for (var i = 0; i < data.length; i++) {
                        $('#unidades_search').append('<option value="' + data[i]._id + '">' + data[
                            i].descripcion + '</option>');
                        $('#unidades_search').trigger('chosen:updated');

                        if (data[i].cooperativa.despachos_atm == 'S')
                            $("#div_errorAtm").show();
                    }
                    @if (isset($unidades_search))
                        var unidades = [];
                        @foreach ($unidades_search as $unidad)
                            unidades.push('{{ $unidad }}');
                        @endforeach
                        $('#unidades_search').val(unidades);
                        $('#unidades_search').trigger('chosen:updated');
                        if ({{ count($unidades_search) }} == data.length) {
                            $('#seleccionar_unidades').prop('checked', true);
                            $('#unidades_div').hide();
                        }
                    @endif
                }, 'json');
            });
            $('#unidades_search').chosen({
                width: '100%'
            });
            $('#cooperativa_search').val(null);
            $('#cooperativa_search').trigger('chosen:updated');
            @if (isset($cooperativa_search))
                $('#cooperativa_search').val('{{ $cooperativa_search }}');
                $('#cooperativa_search').trigger('chosen:updated');
                $('#cooperativa_search').trigger('change');
            @endif

            @if (Session::has('cooperativa'))
                $('#cooperativa').val("{{ Session::get('cooperativa') }}");
                $('#cooperativa').trigger('chosen:updated');
                $('#cooperativa').trigger('change');
            @endif
        });

        $('#unidad').val("{{ Session::get('unidad') }}");
        $('#unidad').trigger('chosen:updated');
        $('#unidad').trigger('change');
    </script>
    <script>
        var today = new Date();
        var todayTime = new Date();
        document.getElementById('fecha').value = today.format('Y-m-d');
        document.getElementById('hora').value = todayTime.format('H:i');
        $(function() {
            $('#desde').datepicker({
                dateFormat: 'yy-mm-dd'
            });
            $('#hasta').datepicker({
                dateFormat: 'yy-mm-dd'
            });
            $('#hoy').click(function() {
                $('#desde').val(new Date().format('Y-m-d'));
                $('#hasta').val(new Date().format('Y-m-d'));
                $('#desde').prop('readonly', true);
                $('#hasta').prop('readonly', true);
            });
            $('#ayer').click(function() {
                var date = new Date();
                date.setDate(date.getDate() - 1);
                $('#desde').val(date.format('Y-m-d'));
                $('#hasta').val(date.format('Y-m-d'));
                $('#desde').prop('readonly', true);
                $('#hasta').prop('readonly', true);
            });
            $('#personalizado').click(function() {
                $('#desde').prop('readonly', false);
                $('#hasta').prop('readonly', false);
            });
            $('#seleccionar_unidades').click(function() {
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

            $("#div_errorAtm").hide();

            @if (isset($filtro_fecha))
                $("input[name=filtro_fecha][value='{{ $filtro_fecha }}']").prop("checked", true);
            @else
                $('#hoy').trigger('click');
            @endif
            @if (isset($errorAtm))
                @if ($errorAtm)
                    $('#errorAtm').prop('checked', true);
                @endif
            @endif
            @if (Auth::user()->tipo_usuario->valor != 1)
                $('#cooperativa_search').val('{{ Auth::user()->cooperativa_id }}');
                $('#cooperativa_search').trigger('chosen:updated');
                $('#cooperativa_search').trigger('change');
                $('#div_cooperativa').hide();
            @endif
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry" async defer></script>
@endsection
