@extends('layouts.app')
@section('title')
    Mi Cuenta
@endsection
@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Mi Cuenta</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <div class="clearfix"></div>
                    <h4 class="modal-title" id="modalLabel">Cambio de datos</h4>
                    <div class="x_content">
                         @if ($usuario->count() > 0)
                            <div class="row">
                                <br/>
                                <div class="col-lg-12 col-md-12 col-sm-6">
                                    <div class="form-group" id="div-nombre">
                                        <label for="name">Nombre</label>
                                        @if($tipo_usuario->valor !='3' && $tipo_usuario->valor != '4')
                                            <input class="form-control" name="name" id="name" type="text" value="{{$usuario->name}}"/>
                                        @else
                                            <input class="form-control" name="name" id="name" type="text" value="{{$usuario->name}}" disabled/>
                                        @endif
                                        <span class="help-block" id="span_name1"></span>
                                    </div>


                                    <div class="form-group" id="div-correo-electronico">
                                        <label for="email">Nombre de usuario</label>
                                        @if($tipo_usuario->valor !='3' && $tipo_usuario->valor != '4')
                                            <input class="form-control" name="email" id="email" type="text" value="{{$usuario->email}}"/>
                                        @else
                                            <input class="form-control" name="email" id="email" type="text" value="{{$usuario->email}}" disabled/>
                                        @endif
                                        
                                        <span class="help-block" id="span_correo_electronico"></span>
                                    </div>
                                </div>
                            </div>
                                <br/><label>Rol:   {{$tipo_usuario->descripcion}} </label>
                                </div>
                            </div>
                         @else
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-6">
                                        <br/><br/>
                                        <label class="label label-danger">No se pudo acceder a los datos de la cuenta</label>
                                    </div>
                                </div>
                         @endif
                             <div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
                                 <div class="modal-dialog" role="document">
                                     <div class="modal-content">
                                         <div class="modal-header">
                                             <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                             <h4 class="modal-title" id="modalLabel">Cambio de Contraseña</h4>
                                         </div>
                                         <div class="modal-body">
                                             <div class="row">
                                                 <div class="col-lg-12 col-md-12 col-sm-6">
                                                     <div class="form-group" id="div-contrasena-actual">
                                                         <label for="contrasena_actual">Contraseña Actual</label>
                                                         <input class="form-control" name="contrasena_actual" id="contrasena_actual" type="password"/>
                                                         <span class="help-block" id="span_contrasena_actual"></span>
                                                     </div>
                                                     <div class="form-group" id="div-contraseña">
                                                         <label for="password">Nueva Contraseña</label>
                                                         <input class="form-control" name="password" id="password" type="password"/>
                                                         <span class="help-block" id="span_password"></span>
                                                     </div>
                                                     <div class="form-group" id="div-password-confirmation">
                                                         <label for="password_confirmation">Verificar contraseña</label>
                                                         <input class="form-control" name="password_confirmation" id="password_confirmation" type="password"/>
                                                         <span class="help-block" id="span_password_confirmation"></span>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="modal-footer">
                                             <button onclick="guardar('cambio_contraseña');" type="button" class="btn btn-primary"><i class="fa fa-save"></i> Aceptar</button>
                                             <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                        <div class="modal-footer">
                            <button onclick="guardar('cambio_datos');" type="button" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
                            <button onclick="cleanForm();" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-edit"></i> Cambiar Contraseña</button>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

@endsection

@section('scripts')
    <script src="{{ asset('js/perfil-usuario.js') }}"></script>
    <script>
        function cleanForm() {

            document.getElementById('span_password').innerHTML = '<strong>' + '' + '</strong>';
            document.getElementById('span_contrasena_actual').innerHTML = '<strong>' + '' + '</strong>';
            document.getElementById('span_password_confirmation').innerHTML = '<strong>' + '' + '</strong>';

            document.getElementById('password').value='';
            document.getElementById('contrasena_actual').value='';
            document.getElementById('password_confirmation').value='';

            document.getElementById('div-contrasena-actual').classList.remove('has-error');
            document.getElementById('div-contraseña').classList.remove('has-error');
            document.getElementById('div-password-confirmation').classList.remove('has-error');


        }
        function guardar(opcion)
        {
            if(opcion=="cambio_datos")
               actualizarUsuario('{{ url('/perfil-usuario') }}' + '/' + '{{Auth::user()->_id}}',"cambio_datos");
            else
                if(opcion=="cambio_contraseña")
                  actualizarUsuario('{{ url('/perfil-usuario') }}' + '/' + '{{Auth::user()->_id}}',"cambio_contraseña");
        }
    </script>
@endsection



