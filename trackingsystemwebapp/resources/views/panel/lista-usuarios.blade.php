@extends('layouts.app')
@section('title')
    Lista de usuarios
@endsection
@section('content')

    <style type="text/css">
        .unidad_css li { margin: 0; padding: 0;  }
        .unidad_css label {  display: block;  color: WindowText;  background-color: Window;  margin: 0;  padding: 0; width: 100%;  }
        .unidad_css label:hover {  background-color: Highlight;  color: HighlightText;  }
    </style>

    <div class="page-title">
        <div class="title_left">
            <h3>Lista de usuarios</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Tabla</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">

                    <br />
                    <button onclick="cleanForm('1','{{$tipo_usuario_valor}}');" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
                    <br />
                    <form class="form-inline" name="form_search" method="GET" action="{{ url('/usuarios/search') }}" id="form_search">
                        {{ csrf_field() }}
                        <div class="form-group" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                            <label for="cooperativa">Cooperativa</label>
                            <select onchange="mostrar();" name="cooperativa" id="cooperativa" class="form-control">
                                <option disabled {{ ($cooperativas->count() > 1 && !isset($coop))?'selected':'' }}>Seleccione una cooperativa...</option>
                                @foreach($cooperativas as $cooperativa)
                                    <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (isset($coop))
                            <script>
                                document.getElementById('cooperativa').value = '{{ $coop }}';
                            </script>
                        @endif
                        <div class="form-group">
                            <input name="search" type="text" class="form-control" placeholder="Búsqueda">
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button>
                            <input type="submit" value="Exportar" class="btn btn-success" name="exportar"/>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input name="estado" onchange=mostrar() id="mostrar_modo_activo" type="radio" value="A" checked/> Activos
                                <input name="estado" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="I" /> Suspendidos
                                <input name="estado" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="T" /> Todos
                            </div>
                        </div>
                    </form>

                    <script>
                       @if(isset($opcion))
                        var opcion ='{{$opcion}}';

                        switch(opcion)
                        {
                            case 'A':
                                document.getElementById('mostrar_modo_activo').checked=true;
                                break;

                            case 'I':
                                document.getElementById('mostrar_modo_inactivo').checked=true;
                                break;

                            case 'T':
                                document.getElementById('mostrar_modo_todos').checked=true;
                                break;

                            default: break;
                        }
                        @endif
                    </script>

                    @if ($usuarios->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <th></th>
                                @if($tipo_usuario_valor=="1")
                                    <th>Eliminar</th>
                                @endif
                                <th>Nombre</th>
                                <th>Nombre de usuario</th>
                                <th>Tipo de usuario</th>
                                @if($tipo_usuario_valor=="1")
                                    <th>Cooperativa</th>
                                @endif
                                <th>Fecha de creación</th>
                                <th>Fecha de modificación</th>
                                <th>Usuario creador</th>
                                <th>Usuario modificador</th>
                                <th></th>
                                @foreach ($usuarios as $user)
                                    <tr class="{{($user->estado=='I')?'danger':''}}">
                                        @if($tipo_usuario_valor=="1")
                                            <td><button onclick="eliminarUsuario('{{ url('/usuarios/eliminar/' . $user->_id) }}');" class="btn btn-default"><i class="fa fa-trash"></i></button></td>
                                        @endif
                                        <td><button onclick="editarUsuario('{{ url('/usuarios/' . $user->_id) }}','{{ url('/usuarios') }}','{{$tipo_usuario_valor}}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{($user->tipo_usuario!=null)?$user->tipo_usuario->descripcion:""}}</td>
                                        @if($tipo_usuario_valor=="1")
                                            <td>{{($user->cooperativa!=null)?$user->cooperativa->descripcion:""}}</td>
                                        @endif
                                        <td>{{ ($user->created_at!=null)?$user->created_at:""}}</td>
                                        <td>{{ ($user->updated_at!=null)?$user->updated_at:""}}</td>
                                        <td>{{ ($user->creador!=null)?$user->creador->name:""}}</td>
                                        <td>{{ ($user->modificador!=null)?$user->modificador->name:""}}</td>
                                        <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($user->estado=='A')?'checked':'' }} onchange="$('#progress').modal('show');estadoUsuario('{{ url('/usuarios/' . $user->_id) }}',(checked)?true:false);"></td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        {{ $usuarios->links() }}
                    @else
                        <div class="alert alert-info">
                            <strong>No se encontraron resultados.</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabel">Usuario</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-6">
                            <div class="form-group" id="div-nombre">
                                <label for="name">Nombre</label>
                                <input class="form-control" name="name" id="name" type="text"/>
                                <span class="help-block" id="span_nombre"></span>
                            </div>
                            <div class="form-group" id="div-tipo-usuario" >
                                <label for="tipo_usuario_id">Tipo de usuario</label>
                                <select class="form-control" id="tipo_usuario_id" name="tipo_usuario_id" onchange="mostrarCooperativa();">
                                    <option value='' disabled selected hidden>Seleccione...</option>
                                    @foreach ($tipos_usuarios as $tipo_usuario)
                                        <option value="{{ $tipo_usuario->_id }}">
                                            {{ $tipo_usuario->descripcion }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="help-block" id="span_tipo_usuario"></span>
                            </div>
                            <div class="form-group" id="div-cooperativa">
                                <select class="form-control" id="cooperativa_id" name="cooperativa_id" style="display:none;"></select>
                                <span class="help-block" id="span_cooperativa"></span>
                            </div>

                            <div class="form-group" id="div-unidad" ></div>

                            <div class="form-group" id="div-cooperativa">
                                <select class="form-control" id="cooperativa_id" name="cooperativa_id" style="display:none;"></select>
                                <span class="help-block" id="span_cooperativa"></span>
                            </div>
                            <div class="form-group" id="div-correo-electronico">
                                <label for="email">Nombre de usuario</label>
                                <input class="form-control" name="email" id="email" type="text"/>
                                <span class="help-block" id="span_correo_electronico"></span>
                            </div>
                            <div class="form-group" id="div-contraseña">
                                <label for="password">Contraseña</label>
                                <input class="form-control" name="password" id="password" type="password"/>
                                <span class="help-block" id="span_contraseña"></span>
                            </div>
                            <div class="form-group" id="div-password-confirmation">
                                <label for="password_confirmation">Verificar contraseña</label>
                                <input class="form-control" name="password_confirmation" id="password_confirmation" type="password"/>
                                <span class="help-block" id="span_password_confirmation"></span>
                            </div>
                            <div class="form-group">
                                <label for="correo">E-mail</label>
                                <input type="email" name="correo" id="correo" class="form-control" placeholder="Opcional"/>
                            </div>
                            <div id="div_ip" class="form-group">
                                <label for="ip" class="col-sm-2 control-label">IPs</label>
                                <input name="ip" id="ip" class="form-control" type="text" />
                                <label for="ip_sep" >Separar las ips por ';'</label>
                            </div>
                            <div class="form-group">
                                <label for="operadora">Operadora</label>
                                <select name="operadora" id="operadora" class="form-control">
                                    <option disabled selected>Seleccione la operadora... (Opcional)</option>
                                    <option value="CLR">Claro</option>
                                    <option value="CNT">CNT</option>
                                    <option value="MOV">Movistar</option>
                                    <option value="TUE">Tuenti</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="Opcional"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
                    <button onclick="guardar();" type="button" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    <script src="{{ asset('js/usuario.js') }}"></script>

    <script>

        var id_cooperativa=null;
        var unidades_pertenecientes=null;

        @if(isset($id_cooperativa))
            id_cooperativa='{{$id_cooperativa}}';
        @endif

        function eliminarUsuario(url){
            if(confirm("Desea eliminar el usuario por completo?")){
                $('#progress').modal('show');
                $.post(url, function (data) {
                    $('#progress').modal('hide');
                    location.reload(true);
                }).always(function(){
                    $('#progress').modal('hide');
                });
            }
        }

        function mostrarEditarTipoUsuario(url_tipo, data_anterior)
        {
            var tipo_usuario_id = document.getElementById('tipo_usuario_id');
            var array_id_coop=[];
            var array_descripcion_coop=[];
            var array_id_unidad=[];
            var array_descripcion_unidad=[];
            $('#div-unidad').empty();
            unidades_pertenecientes=null;

            $.post(url_tipo, {
                tipo_usuario_id:data_anterior.tipo_usuario_id,
                opcion:'getTipoUsuario'
                }, function( data ) {
                if (data.error == false){
                    if(data.tipo_usuario_valor == '1')
                    {
                        var div_cooperativa=  $('#div-cooperativa');
                        div_cooperativa.empty();
                    }
                    else
                    {
                        @if(isset($cooperativas))
                            @foreach($cooperativas as $cooperativa)
                                array_id_coop.push('{{$cooperativa->_id}}');
                                array_descripcion_coop.push('{{$cooperativa->descripcion}}');
                            @endforeach
                            llenarCooperativa(array_id_coop,array_descripcion_coop,'{{ url('/usuarios') }}');
                        @endif

                        //llenarUnidad(array_id,array_descripcion);
                        var ul_unidades = $('#ul-unidades').find('input');

                        var cooperativa_id= $('#cooperativa_id');
                        cooperativa_id.val(data_anterior.cooperativa_id);
                        $('#cooperativa_id').trigger('change');
                        $('#cooperativa_id').trigger('chosen:updated');

                        if (data.tipo_usuario_valor == '4' || data.tipo_usuario_valor == '5'){
                            unidades_pertenecientes=data_anterior.unidades_pertenecientes;
                        }

                        /*var cooperativa_id=document.getElementById('cooperativa_id');
                        cooperativa_id.value=data_anterior.cooperativa_id;*/
                    }
                }
                else
                {
                    alert('Ocurrió un error al momento de buscar el tipo de usuario.');
                    location.reload(true);
                }

            }, "json");
        }

        function guardar()
        {
            var tipo_usuario_id = document.getElementById('tipo_usuario_id');

            $.post('{{ url('/usuarios') }}', {
                tipo_usuario_id:tipo_usuario_id.value,
                opcion:'getTipoUsuario'
            }, function( data ) {
                if (data.error == false){
                    if (actual_id == null)
                    {
                        crearUsuario('{{ url('/usuarios') }}',id_cooperativa,data.tipo_usuario_valor);
                    }
                    else
                    {
                        actualizarUsuario('{{ url('/usuarios') }}' + '\/' + actual_id,id_cooperativa,data.tipo_usuario_valor);
                    }
                }
                else
                {
                    document.getElementById('div-tipo-usuario').classList.add('has-error');
                    document.getElementById('span_tipo_usuario').innerHTML = '<strong>' + '* El tipo de usuario es requerido' + '</strong>';
                }

            }, "json");
        }


        function mostrarCooperativa()
        {
            var tipo_usuario_id = document.getElementById('tipo_usuario_id');
            var array_id_coop=[];
            var array_descripcion_coop=[];
            var array_id_unidad=[];
            var array_descripcion_unidad=[];
            $('#div-unidad').empty();

                $.post('{{ url('/usuarios') }}', {
                    tipo_usuario_id:tipo_usuario_id.value,
                    opcion:'getTipoUsuario'
                }, function( data ) {
                    if (data.error == false){
                        if(data.tipo_usuario_valor == '1')
                            {
                                $('#div-cooperativa').empty();
                            }
                            else
                            {
                                @if(isset($cooperativas))
                                     @foreach($cooperativas as $cooperativa)
                                        array_id_coop.push('{{$cooperativa->_id}}');
                                        array_descripcion_coop.push('{{$cooperativa->descripcion}}');
                                     @endforeach
                                     llenarCooperativa(array_id_coop,array_descripcion_coop,'{{ url('/usuarios') }}');
                                @endif
                            }
                    }
                    else
                    {
                        alert('Ocurrió un error al momento de buscar el tipo de usuario.');
                        location.reload(true);
                    }
                }, "json");
        }

        function setUnidadesPertenecientes(unidades_pertenecientes)
        {
            var array_id=[];
            var array_descripcion=[];

            var ul_unidades = $('#ul-unidades').find('input');

            if(unidades_pertenecientes!=null)
            {
                for (var i = 0,  len = ul_unidades.length; i < len; i++)
                    for(var j=0, len2 = unidades_pertenecientes.length; j<len2 ;j++)
                    {
                        if(ul_unidades[i].id==unidades_pertenecientes[j])
                        {
                            ul_unidades[i].checked=true;

                        }
                    }
            }
        }

        function getUnidadesCoop(url)
        {
            var tipo_usuario_id = document.getElementById('tipo_usuario_id');
            var cooperativa_id=document.getElementById('cooperativa_id').value;
            var array_id=[];
            var array_descripcion=[];

            $.post(url, {
                tipo_usuario_id:tipo_usuario_id.value,
                opcion:'getTipoUsuario'
            }, function( data ) {
                if (data.error == false){
                    if(data.tipo_usuario_valor == '4' || data.tipo_usuario_valor == '5')
                    {
                        @foreach($unidades as $unidad)
                            if('{{$unidad->cooperativa_id}}'==cooperativa_id)
                            {
                                array_id.push('{{$unidad->_id}}');
                                array_descripcion.push('{{$unidad->descripcion}}');
                            }
                        @endforeach
                        llenarUnidad(array_id,array_descripcion);
                        setUnidadesPertenecientes(unidades_pertenecientes);
                    }
                }
                else
                {
                    alert('Ocurrió un error al momento de buscar el tipo de usuario.');
                    location.reload(true);
                }
            }, "json");
        }


        function mostrar()
        {
            document.form_search.submit();
        }


    </script>

@endsection
