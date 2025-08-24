@extends('layouts.app_ingreso')

@section('title')
    Acceso
@endsection

@section('content')
<div class="container-fluid" style="height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel">
                <div class="panel-heading text-center">Control de acceso</div>
                <div class="panel-body">
                    <form role="form" method="POST" action="{{ url('/login') }}">
                        {{ csrf_field() }}
                        <div class="form-group text-center">
                            <img src="/images/logo.png" width="320px" />
                        </div>
                        <div class="form-group text-center">
                            <strong>Sistema de Administración de Flotas</strong>
                        </div>
                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <input placeholder="Nombre de usuario" id="email" type="text" class="form-control" name="email" value="{{ old('email') }}" required autofocus>

                            @if ($errors->has('email'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <input placeholder="Contraseña" id="password" type="password" class="form-control" name="password" required>

                            @if ($errors->has('password'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="remember"> Recordarme
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                Iniciar sesión
                            </button>
                        </div>
                        <div class="form-group text-center">
                            Desarrollado por <a href="">Infinity Solutions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
