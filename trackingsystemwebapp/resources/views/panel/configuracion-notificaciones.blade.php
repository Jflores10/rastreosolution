@extends('layouts.app')

@section('title')
    Notificaciones — Configuración
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Configuración (Firebase)</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Cuenta de servicio Firebase (JSON)</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <p>
                    Suba el archivo JSON de la cuenta de servicio de Firebase (Cloud Messaging).
                    Se guarda de forma segura en el servidor como
                    <code>storage/app/firebase/service-account.json</code>.
                </p>
                <p>
                    Estado actual:
                    @if (!empty($firebase_configured))
                        <span class="label label-success">Configurado</span>
                    @else
                        <span class="label label-warning">Pendiente</span>
                    @endif
                </p>

                <form action="{{ route('configuracion-notificaciones.store') }}" method="POST" enctype="multipart/form-data" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="form-group{{ $errors->has('firebase_json') ? ' has-error' : '' }}">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="firebase_json">Archivo JSON</label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <input type="file" name="firebase_json" id="firebase_json" class="form-control" accept=".json,application/json">
                            @if ($errors->has('firebase_json'))
                                <span class="help-block"><strong>{{ $errors->first('firebase_json') }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-3 col-sm-offset-3 col-xs-12 col-md-6">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
