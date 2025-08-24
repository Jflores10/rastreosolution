@extends('layouts.app')
@section('title')
Importar
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="clearfix" style="margin-bottom: 20px;">
            <h3 class="pull-left" style="margin-top: 0;">Importar despachos</h3>
            <a href="{{ asset('templates/despachos.xlsx') }}" class="btn btn-success btn-sm pull-right" target="_blank">
            <i class="fa fa-file-excel-o"></i> Descargar plantilla
            </a>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Seleccione una cooperativa y cargue el archivo</strong>
            </div>
            <div class="panel-body">
                <form action="{{ route('importar') }}" method="POST" enctype="multipart/form-data" class="form-horizontal">
                    {{ csrf_field() }}

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <strong>Error:</strong> {{ session('error') }}
                        </div>
                    @endif

                    <div class="form-group{{ $errors->has('cooperativa') ? ' has-error' : '' }}">
                        <label for="cooperativa" class="col-sm-3 control-label">Cooperativa</label>
                        <div class="col-sm-9">
                            <select name="cooperativa" id="cooperativa" class="form-control">
                                <option disabled value="">Seleccione una cooperativa</option>
                                @foreach($cooperativas as $cooperativa)
                                    <option value="{{ $cooperativa->_id }}"
                                        @if(
                                            (count($cooperativas) === 1) ||
                                            (old('cooperativa') == $cooperativa->_id)
                                        ) selected @endif>
                                        {{ $cooperativa->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @if($errors->has('cooperativa'))
                                <span class="help-block">{{ $errors->first('cooperativa') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('archivo') ? ' has-error' : '' }}">
                        <label for="archivo" class="col-sm-3 control-label">Archivo</label>
                        <div class="col-sm-9">
                            <input type="file" name="archivo" id="archivo" class="form-control"/>
                            @if($errors->has('archivo'))
                                <span class="help-block">{{ $errors->first('archivo') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button class="btn btn-info" type="submit" id="importar">
                                <i class="fa fa-upload"></i> Importar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="log" class="col-sm-3 control-label">Log</label>
                        <div class="col-sm-9">
                            <div id="log" style="background: #f8f9fa; border: 1px solid #ccc; border-radius: 4px; padding: 12px; min-height: 150px; max-height: 300px; overflow-y: auto;">
                                @if(session('logs'))
                                    @foreach(session('logs') as $log)
                                        <div style="margin-bottom: 4px;">
                                            @if($log['error'])
                                                <span style="color: #d9534f; font-weight: bold;">
                                                    <i class="fa fa-times-circle"></i> Error: Línea {{ $log['linea'] }}:
                                                </span>
                                                <span style="color: #a94442;">{{ $log['mensaje'] }}</span>
                                            @else
                                                <span style="color: #5cb85c; font-weight: bold;">
                                                    <i class="fa fa-info-circle"></i> Info: Línea {{ $log['linea'] }}:
                                                </span>
                                                <span style="color: #31708f;">{{ $log['mensaje'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span style="color: #888;">No hay registros de importación.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
