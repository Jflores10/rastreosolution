@extends('layouts.app')
@section('title')
Sesiones
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Sesiones</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Historial de sesiones de los usuarios del sistema</h2>
            </div>
            <div class="x_content">
                <div class="col-xs-12">
                    <form class="form-inline" name="form_search" method="GET" action="{{ url('sesiones') }}" id="form_search">
                        {{ csrf_field() }}
                        <div class="search-options">
                            <div class="form-group" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                                <label for="cooperativa">Cooperativa</label>
                                <select name="cooperativa" id="cooperativa" class="form-control">
                                    <option value="" {{ ($cooperativas->count() == 1 || !isset($coop))?'selected':'' }}>Todas</option>
                                    @foreach($cooperativas as $cooperativa)
                                        <option {{ $cooperativaId === $cooperativa->_id?'selected':'' }} value="{{ $cooperativa->_id }}" {{ (isset($coop) && $coop == $cooperativa->_id)?'selected':'' }}>{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <div class="radio">
                                    <label for="hoy"><input {{ $tipo === 'H'?'checked':'' }} onchange="this.form.submit()" type="radio" name="tipo" id="hoy" value="H"> <span>Hoy</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="radio">
                                    <label for="ayer"><input {{ $tipo === 'A'?'checked':'' }} onchange="this.form.submit()" type="radio" name="tipo" id="ayer" value="A"> <span>Ayer</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="radio">
                                    <label for="personalizado"><input {{ $tipo === 'P'?'checked':'' }} onchange="mostrarPersonalizado(this.checked)" type="radio" name="tipo" id="personalizado" value="P"> <span>Personalizado</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="search-options">
                            <div id="divDesde" class="form-group">
                                <label for="desde">Desde</label>
                                <input type="text" name="desde" id="desde" class="form-control" placeholder="YYYY-MM-DD" value="{{ $desde->format('Y-m-d') }}">
                            </div>

                            <div id="divHasta" class="form-group">
                                <label for="hasta">Hasta</label>
                                <input type="text" name="hasta" id="hasta" class="form-control" placeholder="YYYY-MM-DD" value="{{ $hasta->format('Y-m-d') }}">
                            </div>
                            <div class="form-group">
                                <input type="submit" name="consultar" class="btn btn-success search-options__button" value="Consultar" />
                            </div>
                            <div class="form-group">
                                <input type="submit" name="pdf" class="btn btn-info search-options__button" value="Exportar como PDF" />
                            </div>
                            <div class="form-group">
                                <input type="submit" name="excel" class="btn btn-primary search-options__button" value="Exportar como Excel" />
                            </div>
                        </div>
                    </form>

                </div>

                <div class="col-xs-12">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>IP</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sesiones as $sesion)
                                    @if ($sesion->usuario !== null)
                                        <tr>
                                            <td>{{ $sesion->usuario->name }} ({{ $sesion->usuario->email }})</td>
                                            <td>{{ $sesion->fecha_sesion }}</td>
                                            <td>{{ $sesion->direccion_ip }}</td>
                                            <td>{{ $sesion->conexion }}</td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <strong>No se ha encontrado resultados.</strong>
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

@section('scripts')
<script>
    function mostrarPersonalizado(mostrar) {
        if (mostrar) {
            $('#divDesde').show()
            $('#divHasta').show()
        }
        else {
            $('#divDesde').hide()
            $('#divHasta').hide()
        }
    }
    mostrarPersonalizado($('#personalizado').is('checked'))
    $('#desde').datepicker({
        dateFormat : 'yy-mm-dd HH:mm:ss'
    })
    $('#hasta').datepicker({
        dateFormat : 'yy-mm-dd'
    })
</script>
@endsection