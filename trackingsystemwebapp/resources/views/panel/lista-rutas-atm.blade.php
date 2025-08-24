@extends('layouts.app')
@section('title')
Rutas
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Rutas ATM</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_content">
            <form class="form-inline" name="form_search" method="GET" action="{{ url('/rutas-atm/search') }}" id="form_search">
                {{ csrf_field() }}
                <div class="search-options">
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
                        <button class="btn btn-primary search-options__button" type="submit"><i class="fa fa-search"></i> Buscar</button>
                        <input class="btn btn-success" type="submit" name="reportar" value="Exportar"/>
                    </div>
                </div>
            </form>
            @if ($rutas->count() > 0)
                <div class="table-responsive">
                    <table class="table" id="table_id">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Descripción</th>
                                @if(!isset($cooperativa))
                                     <th>Cooperativa</th>
                                @endif
                                <th>Codigo</th>
                                <th>Fecha de importación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rutas as $ruta)
                                <tr class="{{($ruta->estado=='I')?'danger':''}}">
                                    <td> <a href="{{ url('/rutas-atm/'.$ruta->_id) }}" class="btn btn-primary"> <i class="fa fa-edit" ></i> </a></td>
                                    <td>{{ $ruta->descripcion }}</td>
                                    @if(!isset($cooperativa))
                                         <td>{{ $ruta->cooperativa->descripcion }}</td>
                                    @endif
                                    <td>{{ $ruta->codigo }}</td>
                                    <td>{{ $ruta->fecha_importado }}</td>
                                    <td>{{ $ruta->estado }}</td>
                            @endforeach
                        </tbody>
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
    <script>
        window.onload = function () { 
            $('#menu_toggle').trigger('click');
        }
        function mostrar()
        {
            document.form_search.submit();
        }

        $(document).ready( function () {
            $('#table_id').DataTable();
        } );
    </script>

@endsection