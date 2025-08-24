@extends('layouts.app')
@section('title')
Rutas
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Rutas</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Rutas</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <br />
            <a href="{{url('/rutas/create')}}" class="btn btn-default"><i class="fa fa-plus" onclick="actual_id=null;"></i> Crear nueva</a>
            <br />
            <form class="form-inline" name="form_search" method="GET" action="{{ url('/rutas/search') }}" id="form_search">
                {{ csrf_field() }}
                <div class="form-group" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                    <label for="cooperativa">Cooperativa</label>
                    <select onchange="mostrar();" name="cooperativa" id="cooperativa" class="form-control">
                        <option disabled {{ ($cooperativas->count() > 1)?'selected':'' }}>Seleccione una cooperativa...</option>
                        @foreach($cooperativas as $coo)
                            <option value="{{ $coo->_id }}" {{ ((isset($cooperativa) && $cooperativa == $coo->_id) || $cooperativas->count() == 1)?'selected':'' }}>{{ $coo->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <input value="{{ (isset($search))?$search:'' }}" name="search" type="text" class="form-control" placeholder="Búsqueda">
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button>
                </div>
                <div class="form-group" >
                    <div class="radio">
                        <label for="mostrar_modo_activo"><input name="estado" onchange=mostrar() id="mostrar_modo_activo" type="radio" value="A" checked/> Activos</label>
                        <label for="mostrar_modo_inactivo"><input name="estado" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="I" /> Inactivos</label>
                        <label for="mostrar_modo_todos"><input name="estado" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="T" /> Todos</label>
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
            @if ($rutas->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <th></th>
                        <th>Descripción</th>
                        @if(!isset($cooperativa))
                             <th>Cooperativa</th>
                        @endif
                        <th>Ruta</th>
                        <th>Fecha de creación</th>
                        <th>Fecha de modificación</th>
                        <th>Usuario creador</th>
                        <th>Usuario modificador</th>
                        <th></th>
                        <th>Clonar</th>
                        @foreach ($rutas as $ruta)
                            <tr class="{{($ruta->estado=='I')?'danger':''}}">
                                <td> <a href="{{ url('/rutas/'.$ruta->_id) }}" class="btn btn-primary"> <i class="fa fa-edit" ></i> </a></td>
                                <td>{{ $ruta->descripcion }}</td>
                                @if(!isset($cooperativa))
                                     <td>{{ $ruta->cooperativa->descripcion }}</td>
                                @endif
                                <td>{{ (($ruta->tipo_ruta=='H')?'Hija':(($ruta->tipo_ruta=='I')?'Independiente':(($ruta->tipo_ruta=='P')?'Padre':'Cooperativa')) )}}</td>
                                <td>{{ $ruta->created_at }}</td>
                                <td>{{ $ruta->updated_at }}</td>
                                <td>{{ ($ruta->creador!=null)?$ruta->creador->name:""}}</td>
                                <td>{{ ($ruta->modificador!=null)?$ruta->modificador->name:""}}</td>
                                <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($ruta->estado=='A')?'checked':'' }} onchange="estadoRuta('{{ url('/rutas/' . $ruta->_id) }}',(checked)?true:false, '{{$ruta->descripcion}}');"></td>
                                <td> <a href="{{ url('/rutasclone/'.$ruta->_id) }}" class="btn btn-primary"> <i class="fa fa-copy" ></i> </a></td>
                        @endforeach
                    </table>
                </div>
                {{ $rutas->links() }}
            @else
                <div class="alert alert-info">
                    <strong>No se encontraron resultados.</strong>
                </div>
            @endif
        </div>
    </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/ruta.js') }}"></script>

    <script>
        window.onload = function () { 
            $('#menu_toggle').trigger('click');
        }
        function mostrar()
        {
            document.form_search.submit();
        }
    </script>

@endsection