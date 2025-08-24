@extends('layouts.app')

@section('title')
Historico ATM
@endsection

@section('styles')
<style>
    .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {
        padding: 3px;
    }
    .btn {
        padding: 3.5px 3px;
    }
</style>
@endsection

@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Historico ATM</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Exportar Historico ATM</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form id="formHistoricoAtm" method="POST" action="{{ url('/historico-atm') }}">
                    {{ csrf_field() }}
                    <div class="row">
                        <div id="div_cooperativa" class="col-md-3">
                            <div class="form-group">
                                <label>Cooperativa</label>
                                <select class="form-control" name="cooperativa_id" id="cooperativa_id" required>
                                    <option value="">Seleccione...</option>
                                    @foreach ($cooperativas as $cooperativa)
                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="export_date" id="export_date" value="1" {{ old('export_date') ? 'checked' : '' }}>
                                        Exportar columna de fechas
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="col-md-3">
                            <div class="form-group">
                                <label>Unidad</label>
                                <select class="form-control" name="unidad" id="unidad" required>
                                    <option value="">Seleccione...</option>
                                </select>
                                @if ($errors->has('unidad'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('unidad') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> --}}

                        <div id="div_from" class="col-md-3">
                            <div class="form-group{{ $errors->has('from') ? ' has-error' : '' }}">
                                <label>Desde</label>
                                <input type="text" class="form-control datetimepicker" name="from" id="from" placeholder="YYYY-MM-DD HH:mm:ss" autocomplete="off" required value="{{ old('from') }}">
                                @if ($errors->has('from'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('from') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div id="div_to" class="col-md-3">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="text" class="form-control datetimepicker" name="to" id="to" placeholder="YYYY-MM-DD HH:mm:ss" autocomplete="off" required>
                                @if ($errors->has('to'))
                                    <span class="help-block
                                        <strong>{{ $errors->first('to') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2" style="margin-top:25px;">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fa fa-file-excel-o"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                </form>
                <div id="historico-atm-result"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    $('.datetimepicker').datetimepicker({
        format: 'Y-m-d H:i:s'
    });

    $('#cooperativa_id').chosen({
        allow_single_deselect: true,
        no_results_text: "No se encontraron resultados",
        width: "100%"
    });

    // $('#unidad').chosen({
    //     allow_single_deselect: true,
    //     no_results_text: "No se encontraron resultados",
    //     width: "100%"
    // });

    let fromHardCoded = '2025-06-16 00:00:00';
    let toHardCoded = '2025-06-18 23:59:59';
    $('#from').val(fromHardCoded);
    $('#to').val(toHardCoded);

    $('#div_from').hide();
    $('#div_to').hide();



    @if($cooperativas->count() == 1)
        $('#cooperativa_id').val('{{ $cooperativas->first()->_id }}');
        $('#div_cooperativa').hide();
        getUnidades('{{ $cooperativas->first()->_id }}');
    @endif

    // $('#cooperativa_id').on('change', function() {
    //     var coop = $(this).val();
    //     if (coop) {
    //         getUnidades(coop);
    //     } else {
    //         $('#unidad').empty();
    //         $('#unidad').trigger("chosen:updated");
    //     }
    // });
});

function getUnidades(coop) {
    $.get('{{ url('/despachos') }}' + '/' + coop + '/unidades', function(data) {
        $('#unidad').empty();
        for (var i = 0; i < data.length; i++) {
            $('#unidad').append('<option value="' + data[i]._id + '">' + data[i].descripcion + '</option>');
            $('#unidad').trigger("chosen:updated");
        }
    }, 'json');
}
</script>
@endsection