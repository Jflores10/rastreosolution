@extends('layouts.app')
@section('title')
Liquidacion
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
        <h3>Liquidación</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lista de liquidación</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <form class="form-inline" method="POST" action="/liquidacion/search">
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
                                <input type="text" class="form-control" id="desde" placeholder="aaaa-mm-dd" name="desde" value="{{ (isset($desde))?$desde->format('Y-m-d'):old('desde') }}">
                                @if ($errors->has('desde'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('desde') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ ($errors->has('hasta'))?' has-error':'' }}">
                                <label for="hasta">Hasta</label></br>
                                <input type="text" class="form-control" id="hasta" placeholder="aaaa-mm-dd" name="hasta" value="{{ (isset($hasta))?$hasta->format('Y-m-d'):old('hasta') }}">
                                @if ($errors->has('hasta'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('hasta') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div id="div_cooperativa" class="form-group{{ ($errors->has('cooperativa'))?' has-error':'' }}">
                                <label for="cooperativa">Cooperativa</label>
                                <select name="cooperativa" id="cooperativa_search" class="form-control" data-placeholder="Cooperativa">
                                    @foreach ($cooperativas as $cooperativa)
                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('cooperativa'))
                                    <span><strong>{{ $errors->first('cooperativa') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label><input type="checkbox" id="primera_guardia" /> Primera Guardia</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="segunda_guardia" /> Segunda Guardia </label>
                                </div>
                                <div class="form-group">
                                    </br>
                                    <button type="submit" class="btn btn-success">Buscar</button>
                                </div>
                            </div>
                            
                        </div>
                    </from>
                </div>
            </div>
</div>
@endsection
@section('scripts')
<script>
    window.onload = function () { 
        $('#menu_toggle').trigger('click');
    }

    $('#cooperativa_search').chosen({
            width : '100%'
    }).change(function () {
        
    });
    $('#cooperativa_search').val(null);
    $('#cooperativa_search').trigger('chosen:updated');

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
    });
</script>
@endsection