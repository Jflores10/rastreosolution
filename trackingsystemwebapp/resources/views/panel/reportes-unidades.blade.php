@extends('layouts.app')
@section('title')
Reportes
@endsection
@section('content')

<div class="page-title">
    <div class="title_left">
        <h3>Histórico de unidades</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Reportes</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <br/>
            <form class="form-inline">
                <div class="form-group" id="div-cooperativa">
                    <label for="cooperativa_id">Cooperativa</label>
                    <select class="form-control" id="cooperativa_id" name="cooperativa_id" onchange="llenarUnidades('{{url('/historicos')}}','1',null);">
                        <option value="" disabled selected hidden>Seleccione...</option>
                        @if(!isset($cooperativa))
                            @foreach ($cooperativas as $cooperativa_id)
                                <option value="{{ $cooperativa_id->_id }}">
                                    {{ $cooperativa_id->descripcion }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <span class="help-block" id="span_cooperativa"></span>
                </div>

                <div class="form-group">
                    <label for="evento">Evento</label>
                    <select name="evento" id="evento" class="form-control">
                        <option disabled>Seleccione un evento...</option>
                        <option selected value="T">Todos</option>
                        <option value="GTFRI">Normal (GV300)</option>
                        <option value="GPRMC">Normal (MT2500)</option>
                        <option value="GTGEO">Puntos de control</option>
                        <option value="GTDIS">Puertas</option>
                        <option value="GTSOS">Pánico</option>
                        <option value="GTIGF">Desconexion Dispositivo</option>
                        <option value="GTIGN">Conexion Dispositivo</option>
                    </select>
                </div>

                <div class="form-group" id="div-unidad">
                </div>
                <div class="form-group" id="div-fecha-inicio">
                    <label for="fecha_inicio">Desde</label>
                    <input name="fecha_inicio" id="fecha_inicio" autocomplete="off" autocorrect="off" class="form-control" type="text" />
                    <span class="help-block" id="span_fecha_inicio"></span>
                </div>

                <div class="form-group" id="div-fecha-fin">
                    <label for="fecha_fin">Hasta</label>
                    <input name="fecha_fin" id="fecha_fin" autocomplete="off" autocorrect="off" class="form-control" type="text" />
                    <span class="help-block" id="span_fecha_fin"></span>
                </div>
                <div class="btn-group">
                    @if(isset($cooperativa))
                       <button onclick="cargaHistorico('{{url('/historicos')}}','{{$cooperativa->_id}}','2');" type="button" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                    @else
                       <button onclick="cargaHistorico('{{url('/historicos')}}',null,'1');" type="button" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                    @endif
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-file"></i> Exportar <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><button id="btn-pdf" type="button"><i class="fa fa-file-pdf-o"></i> XML</button></li>
                        <li><button id="btn-excel" type="button"><i class="fa fa-file-excel-o"></i> Excel</button></li>
                    </ul>
                </div>
            </form>
            <br/>
            <div class="table-responsive" id="div-tabla"  style="height:50em;overflow: auto;"> 
                <table class="table" id="tr-registros-historicos"></table>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/historico.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/toExport/tableExport.js') }}" ></script>
    <script type="text/javascript" src="{{ asset('js/toExport/jquery.base64.js') }}" ></script>
    <script type="text/javascript" src="{{ asset('js/toExport/html2canvas.js') }}" ></script>
    <script type="text/javascript" src="{{ asset('js/toExport/jspdf/jspdf.js') }}" ></script>
    <script type="text/javascript" src="{{ asset('js/toExport/jspdf/libs/sprintf.js') }}" ></script>
    <script type="text/javascript" src="{{ asset('js/toExport/jspdf/libs/base64.js') }}" ></script>

    <script type="text/javascript">
        $(document).ready(function(e)
        {
           $("#btn-pdf").click(function(e)
           {
               $("#tr-registros-historicos").tableExport({
                   type: 'xml',
                   escape : false

               });
           });
            $("#btn-excel").click(function(e)
            {
                $("#tr-registros-historicos").tableExport({
                    type: 'excel',
                    escape : false
                });
            });
        });

    </script>
    <script>
        var id_cooperativa=null;
        @if(isset($cooperativa))
                id_cooperativa='{{$cooperativa->_id}}';
        @endif
        if('{{$tipo_usuario_valor}}'!='1')
        {
            $('#div-cooperativa').empty();
            if('{{$tipo_usuario_valor}}'=='4' || '{{$tipo_usuario_valor}}'=='5')
            {
                var array_id=[];
                var array_descripcion=[];

                @foreach($unidades as $unidad)
                      array_id.push('{{$unidad->_id}}');
                      array_descripcion.push('{{$unidad->descripcion}}');
                @endforeach

                llenarUnidadesSocio(array_id,array_descripcion);

            }
            if('{{$tipo_usuario_valor}}'=='2')
            {
                llenarUnidades('{{url('/historicos')}}','2',id_cooperativa);
            }
        }

        @if(!isset($cooperativa))
            $('#cooperativa_id').chosen({ width : '100%' });
        @endif

        $('#fecha_inicio').datetimepicker();
        $('#fecha_fin').datetimepicker();

    </script>


@endsection
