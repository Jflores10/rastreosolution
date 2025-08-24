@extends('layouts.app')

@section('title')
    Puntos virtuales
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Puntos virtuales</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lista de puntos virtuales</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="row">
                    <div class="col-sm-12">
                        <form class="form-inline" method="GET" action="{{ route('puntos-virtuales.search') }}">
                            <div class="form-group">
                                <a href="{{ route('puntos-virtuales.create') }}" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo punto virtual</a>
                            </div>
                            <div class="form-group{{ $errors->has('cooperativa_id')?' has-error':'' }}">
                                <label for="cooperativa">Cliente</label>
                                <select id="cooperativa" class="form-control" name="cooperativa_id">
                                    @forelse ($cooperativas as $cooperativa)
                                        <option {{ ($cooperativas->count() > 1 && !isset($cooperativa_id))?'selected':'' }} hidden disabled>Seleccione el cliente</option>
                                        <option value="{{ $cooperativa->_id }}" {{ ((isset($cooperativa_id) && $cooperativa_id == $cooperativa->_id) || old('cooperativa_id') == $cooperativa->_id || $cooperativas->count() === 1)?'selected':'' }}>{{ $cooperativa->descripcion }}</option>
                                    @empty
                                        <option selected hidden disabled>No hay clientes agregados.</option>
                                    @endforelse
                                </select>
                                @if($errors->has('cooperativa_id'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('cooperativa_id') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="consulta">Consulta</label>
                                <input type="text" id="consulta" name="consulta" class="form-control" value="{{ (isset($consulta) && old('consulta') === null)?$consulta:old('consulta') }}"/>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Cooperativa</th>
                                        <th>Descripcion</th>
                                        <th>Pista</th>
                                        <th>Latitud</th>
                                        <th>Longitud</th>
                                        <th>Radio</th>
                                        <th>Fecha de creacion</th>
                                        <th>Fecha de modificacion</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($puntosVirtuales as $puntoVirtual)
                                        <tr>
                                            <td>
                                                <a class="btn btn-info" href="{{ route('puntos-virtuales.edit', $puntoVirtual->_id) }}">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </td>
                                            <td>{{ $puntoVirtual->cooperativa->descripcion }}</td>
                                            <td>{{ $puntoVirtual->descripcion }}</td>
                                            <td>{{ $puntoVirtual->pista }}</td>
                                            <td>{{ $puntoVirtual->latitud }}</td>
                                            <td>{{ $puntoVirtual->longitud }}</td>
                                            <td>{{ $puntoVirtual->radio }}</td>
                                            <td>{{ $puntoVirtual->created_at }}</td>
                                            <td>{{ $puntoVirtual->updated_at }}</td>
                                            <td>
                                                <form onsubmit="return confirm('Estas seguro que deseas eliminar este punto virtual?');" method="POST" action="{{ route('puntos-virtuales.destroy', $puntoVirtual->_id) }}">
                                                    {{ csrf_field() }}
                                                    <input type="hidden" name="_method" value="DELETE" />
                                                    <button class="btn btn-danger" type="submit">
                                                        <i class="fa fa-trash-o"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">
                                                <div class="alert alert-info">
                                                    <strong>No hay puntos virtuales disponibles, <a href="{{ route('puntos-virtuales.create') }}">deseo crear uno nuevo.</a></strong>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection