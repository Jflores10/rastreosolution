@extends('layouts.app')
@section('title')
Reportes
@endsection
@section('content')

<div class="page-title">
    <div class="title_left">
        <h3> Entrada/Salida Puntos Control</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        
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
                    <button onclick="cargaESPuntosControl('{{url('/es-puntoscontrol/listar')}}','1');" type="button" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
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
    <script>
        var id_cooperativa=null;
        @if(isset($cooperativa))
                id_cooperativa='{{$cooperativa->_id}}';
        @endif
       

        @if(!isset($cooperativa))
            $('#cooperativa_id').chosen({ width : '100%' });
        @endif

        $('#fecha_inicio').datetimepicker();
        $('#fecha_fin').datetimepicker();

    </script>


@endsection
