@extends('layouts.app')

@section('title')
    Logs
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Logs de errores</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lista de errores</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="row">
                    <div class="col-sm-12">
                        <form action="{{ route('logs.search') }}" method="GET" class="form-inline">
                            <div class="form-group{{ ($errors->has('tipo')?' has-error':'') }}">
                                <label for="tipo">Tipo de error</label>
                                <select name="tipo" id="tipo" class="form-control">
                                    <option disabled selected>Seleccione el tipo de error</option>
                                    <option value="T">Tramas ATM</option>
                                    <option value="D">Rutas, puntos de control y despachos ATM</option>
                                </select>
                                <script>
                                    var tipo = document.getElementById('tipo');
                                    tipo.value = "{{ (isset($tipo) && old('tipo') == null)?$tipo:old('tipo') }}";
                                </script>
                                @if($errors->has('tipo'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('tipo') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ ($errors->has('desde')?' has-error':'') }}">
                                <label for="desde">Desde</label>
                                <input type="text" name="desde" id="desde" class="form-control" placeholder="Fecha inicial" value="{{ (isset($desde) && old('desde') == null)?$desde:old('desde') }}"/>
                                @if($errors->has('desde'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('desde') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ ($errors->has('hasta')?' has-error':'') }}">
                                <label for="desde">Hasta</label>
                                <input type="text" name="hasta" id="hasta" class="form-control" placeholder="Fecha final" value="{{ (isset($hasta) && old('hasta') == null)?$hasta:old('hasta') }}"/>
                                @if($errors->has('hasta'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('hasta') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <br/>
                                <button class="btn btn-primary" type="submit">
                                    Consultar
                                </button>
                                <input type="submit" class="btn btn-success" name="exportar" value="Exportar"/>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha de error</th>
                                        <th>Mensaje/Trama</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($logs))
                                        @forelse ($logs as $item)
                                            <tr>
                                                <td>
                                                    @if(isset($tipo))
                                                        @if($tipo == 'D')
                                                            {{ $item->fecha }}
                                                        @elseif($tipo == 'T'|| $tipo == 'V')
                                                            {{ $item->fecha_error }}
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(isset($tipo))
                                                        @if($tipo == 'D')
                                                            {{ $item->mensaje }}
                                                        @elseif($tipo == 'T' || $tipo == 'V')
                                                            {{ $item->Trama }}
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(isset($tipo))
                                                        @if($tipo == 'D')
                                                            {{ $item->localizacion }}
                                                        @elseif($tipo == 'T'|| $tipo == 'V')
                                                            {{ $item->Error }}
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3">
                                                    <div class="alert alert-info">
                                                        <strong>No hubo errores en el rango seleccionado</strong>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    @else 
                                        <tr>
                                            <td colspan="3">
                                                <div class="alert alert-info">
                                                    <strong>No se ha realizado la consulta.</strong>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            @if(isset($logs))
                                {{ $logs->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('#desde').datetimepicker();
        $('#hasta').datetimepicker();
    </script>
@endsection