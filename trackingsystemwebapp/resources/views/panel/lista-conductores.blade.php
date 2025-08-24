@extends('layouts.app')
@section('title')
Conductores
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Conductores</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lista de conductores</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <button onclick="cleanForm();" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
                <br />
                <form class="form-inline" name="form_search" method="GET" action="{{ url('conductores/search') }}" id="form_search">
                    {{ csrf_field() }}
                    <div class="form-group" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                        <label for="cooperativa">Cooperativa</label>
                        <select name="cooperativa" id="coop" class="form-control">
                            <option disabled {{ ($cooperativas->count() == 1 || !isset($coop))?'selected':'' }}>Seleccione la cooperativa</option>
                            @foreach($cooperativas as $cooperativa)
                                <option value="{{ $cooperativa->_id }}" {{ (isset($coop) && $coop == $cooperativa->_id)?'selected':'' }}>{{ $cooperativa->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                <div class="form-group">
                    <input name="search" type="text" class="form-control" placeholder="Búsqueda" value="{{ (isset($search))?$search:old('search') }}">
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button>
                    <input type="submit" name="exportar" class="btn btn-success" value="Exportar" />
                </div>
                <div class="form-group" >
                    <div class="radio">
                        <input name="estado" onchange=mostrar() id="mostrar_modo_activo" type="radio" value="A" checked/>Activos
                        <input name="estado" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="I" />   Inactivos
                        <input name="estado" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="T" />   Todos
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

                @if ($conductores->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <th></th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        @if(!isset($id_cooperativa))
                           <th>Cooperativa</th>
                        @endif
                        <th>Fecha de creación</th>
                        <th>Fecha de modificación</th>
                        <th>Usuario creador</th>
                        <th>Usuario modificador</th>
                        <th></th>
                        @foreach ($conductores as $conductor)
                        <tr class="{{($conductor->estado=='I')?'danger':''}}">
                            <td><button onclick="editarConductor('{{ url('/conductores/' . $conductor->_id) }}','{{$tipo_usuario_valor}}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                            <td>{{ $conductor->cedula }}</td>
                            <td>{{ $conductor->nombre }}</td>
                            @if(!isset($id_cooperativa))
                                <td>{{ ($conductor->cooperativa!=null)?$conductor->cooperativa->descripcion:""}}</td>
                            @endif
                            <td>{{ $conductor->created_at }}</td>
                            <td>{{ $conductor->updated_at }}</td>
                            <td>{{ ($conductor->creador!=null)?$conductor->creador->name:""}}</td>
                            <td>{{ ($conductor->modificador!=null)?$conductor->modificador->name:""}}</td>
                            <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($conductor->estado=='A')?'checked':'' }} onchange="estadoConductor('{{ url('/conductores/' . $conductor->_id) }}',(checked)?true:false);"></td>
                        @endforeach
                    </table>
                </div>
                {{ $conductores->links() }}
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
                <h4 class="modal-title" id="modalLabel">Conductor</h4>
            </div>
            <div class="modal-body">
                <div class="col-sm-12">
                    <div class="col-md-6 col-sm-12">
                        <div id="div-cedula" class="form-group">
                            <label for="cedula">Cédula</label>
                            <input name="cedula" id="cedula" class="form-control" type="text" />
                            <span class="help-block" id="span_cedula"></span>
                        </div>
                        <div id="div-nombre" class="form-group">
                            <label for="nombre">Nombre</label>
                            <input name="nombre" id="nombre" class="form-control" type="text" />
                            <span class="help-block" id="span_nombre"></span>
                        </div>
                        @if(isset($cooperativas))
                        <div class="form-group" id="div-cooperativa" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                            <label for="cooperativa_id">Cooperativa</label>
                            <select class="form-control" id="cooperativa_id" name="cooperativa_id">
                                <option disabled selected hidden>Seleccione...</option>
                                @foreach ($cooperativas as $cooperativa)
                                    <option {{ ($cooperativas->count() == 1)?'selected':'' }} value="{{ $cooperativa->_id }}">
                                        {{ $cooperativa->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="help-block" id="span_cooperativa"></span>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="text" name="email" id="email" class="form-control" placeholder="(Opcional)"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="direccion">Dirección domiciliaria</label>
                            <input id="direccion" class="form-control" name="direccion" placeholder="(Opcional)" />
                        </div>
                        <div class="form-group">
                            <label for="operadora">Operadora</label>
                            <select class="form-control" name="operadora" id="operadora">
                                <option disabled selected>Seleccione una operadora celular...</option>
                                <option value="CL">Claro</option>
                                <option value="MOV">Movistar</option>
                                <option value="CNT">CNT</option>
                                <option value="TUE">Tuenti</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tipo_licencia">Tipo Licencia</label>
                            <select class="form-control" name="tipo_licencia" id="tipo_licencia">
                                <option disabled selected>Seleccione un tipo de licencia...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="F">F</option>
                                <option value="A1">A1</option>
                                <option value="C">C</option>
                                <option value="C1">C1</option>
                                <option value="D">D</option>
                                <option value="D1">D1</option>
                                <option value="E">E</option>
                                <option value="E1">E1</option>
                                <option value="G">G</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="celular">Celular</label>
                            <input type="text" id="celular" class="form-control" placeholder="(Opcional)"/>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Convencional</label>
                            <input type="text" name="telefono" id="telefono" class="form-control" placeholder="(Opcional)"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
                <button type="button"  onclick="guardar();"  class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/conductor.js') }}"></script>
<script>
    var id_cooperativa=null;

    @if(isset($id_cooperativa))
            id_cooperativa='{{$id_cooperativa}}';
    @endif

    function validarCedulaEcuatoriana() {
        var cad = document.getElementById("cedula").value.trim();
        var total = 0;
        var longitud = cad.length;
        var longcheck = longitud - 1;

        if (cad !== "" && longitud === 10){
        for(i = 0; i < longcheck; i++){
            if (i%2 === 0) {
            var aux = cad.charAt(i) * 2;
            if (aux > 9) aux -= 9;
            total += aux;
            } else {
            total += parseInt(cad.charAt(i)); // parseInt o concatenará en lugar de sumar
            }
        }
        total = total % 10 ? 10 - total % 10 : 0;

        if (cad.charAt(longitud-1) == total) {
            return true; // cédula válida
        }else{
            return false; //cédula invalida
        }
        }
    }

    function guardar()
    {
        var direccion = document.getElementById('direccion').value.length;
        var email = document.getElementById('email').value.length;
        var telefono = document.getElementById('telefono').value.length;
        
        if(validarCedulaEcuatoriana()){
            if(direccion >255 || email > 255 || telefono > 20){
                alert('Recuerde que la direccion y el email no debe pasar de 255 caracteres, el telefono no debe pasar de 20 caracteres');
            }else{
                if (actual_id == null)
                {
                    crearConductor('{{ url('/conductores') }}','{{$tipo_usuario_valor}}',id_cooperativa);
                }
                else
                {
                    actualizarConductor('{{ url('/conductores') }}' + '/' + actual_id,'{{$tipo_usuario_valor}}',id_cooperativa);
                }
            }
        }else{
            alert('ingrese una cedula valida');
        }
    }
</script>

<script>
    function mostrar()
    {
        document.form_search.submit();
    }
</script>
@endsection