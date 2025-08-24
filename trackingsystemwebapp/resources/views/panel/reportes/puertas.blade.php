@extends('layouts.app')

@section('title')
    Puertas
@endsection

@section('content')
<script>
function selectUnidad_GEOCODE(latitud,longitud,id)
        {
            latitud=latitud.replace('\'','');
            latitud=latitud.replace('\'','');
            longitud=longitud.replace('\'','');
            longitud=longitud.replace('\'','');

            if((latitud != null && longitud != null)&& (latitud != '' && longitud != '')
                && (latitud != 0 && longitud != 0)){
                var request = new XMLHttpRequest();
                request.onreadystatechange = function() {
                    if (request.readyState === 4) {
                        if (request.status === 200) {
                            //document.body.className = 'ok';
                            var obj = JSON.parse(request.responseText);
                            
                            if(obj.status=='OK'){
                               // console.log(obj.results[1].formatted_address);
                                //return (obj.results[1].formatted_address);
                                document.getElementById(id).innerHTML=obj.results[1].formatted_address;
                            }else{
                                document.getElementById(id).innerHTML='Error al consultar ubicación';
                                //return ();
                            }

                            //console.log(request.responseText);
                        } else if (!isValid(this.response) && this.status == 0) {
                        // document.body.className = 'error offline';
                            document.getElementById(id).innerHTML='The computer appears to be offline.';
                            //return 'Error al consultar ubicación';
                            console.log("The computer appears to be offline.");                
                        } else {
                            return 'Error al consultar ubicación';
                        // document.body.className = 'error';
                        }
                    }
                };
                
                request.open("GET", "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latitud+","+longitud+"&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE" , true);
                request.send(null);
            }

        }
</script>

    <div class="page-title">
        <div class="title_left">
            <h3>Reportes de puertas</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="x_panel">
                <div class="x_content">
                    <div class="row">
                        <div class="col-sm-12">
                            <form action="{{ route('puertas.index') }}" method="get" role="form">
                                <div class="col-sm-12 col-md-6">
                                    <div class="form-group" {{ ($cooperativas->count() === 1)?'style=display:none;':'' }}>
                                        <label for="cooperativa">Cooperativa</label>
                                        <select data-placeholder="Selecciona la cooperativa..." name="cooperativa" id="cooperativa" class="form-control">
                                            @foreach($cooperativas as $cooperativa)
                                                <option {{ ($cooperativa->_id == old('cooperativa'))?'selected':'' }} value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group{{ $errors->has('unidad')?' has-error':'' }}">
                                        <div class="checkbox">
                                            <label for="checkUnidad"><input {{ (old('checkUnidad') != null)?'checked':'' }} type="checkbox" name="checkUnidad" id="checkUnidad"> Unidad(es)</label>
                                        </div>
                                        <div id="div_unidad">
                                            <select data-placeholder="Selecciona la(s) unidad(es)..." class="form-control" name="unidad[]" id="unidad" multiple>
                                            </select>
                                            @if ($errors->has('unidad'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('unidad') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <div class="form-group">
                                        <div class="radio">
                                            <label for="hoy"><input onclick="mostrarHoy()" id="hoy" type="radio" value="H" name="filtro" {{ (old('filtro') == null || old('filtro') == 'H')?'checked':'' }} />Hoy</label>
                                        </div>
                                        <div class="radio">
                                            <label for="ayer"><input onclick="mostrarAyer()" id="ayer" type="radio" value="A" name="filtro" {{ (old('filtro') == 'A')?'checked':'' }}/>Ayer</label>
                                        </div>
                                        <div class="radio">
                                            <label for="personalizado"><input onclick="mostrarPersonalizado()" id="personalizado" value="P" type="radio" name="filtro" {{ (old('filtro') == 'P')?'checked':'' }}/>Personalizado</label>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('fecha_desde')?' has-error':'' }}">
                                        <label for="fecha_desde">Fecha inicial</label>
                                        <input value="{{ old('fecha_desde') }}" class="form-control" type="text" name="fecha_desde" id="fecha_desde" placeholder="aaaa-MM-dd" />
                                        @if ($errors->has('fecha_desde'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('fecha_desde') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="form-group{{ $errors->has('fecha_hasta')?' has-error':'' }}">
                                        <label for="fecha_hasta">Fecha final</label>
                                        <input value="{{ old('fecha_hasta') }}" class="form-control" type="text" name="fecha_hasta" id="fecha_hasta" placeholder="aaaa-MM-dd" />
                                        @if ($errors->has('fecha_hasta'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('fecha_hasta') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <input type="submit" value="Buscar" name="search" id="search" class="btn btn-primary"/>
                                <input type="submit" value="Exportar" name="exportar" id="exportar" class="btn btn-default"/>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <h3>Reporte por tiempos</h3>
                            @if(Session::has('recorridos'))
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Unidad</th>
                                                <th>Desde</th>
                                                <th>Hasta</th>
                                                <th>Tiempo</th>
                                                <th>Ubicación</th>
                                                <th>Evento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (Session::get('recorridos') as $recorrido)
                                                <tr class="{{ ($recorrido['recorrido']->evento == 'Puerta abierta')?'info':'danger' }}">
                                                    <td>{{ $recorrido['recorrido']->unidad->descripcion }}</td>
                                                    <td>{{ $recorrido['desde'] }}</td>
                                                    <td>{{ $recorrido['hasta'] }}</td>
                                                    <td>{{ $recorrido['tiempo'] }}</td>
                                                    <td id="{{$recorrido['recorrido']->_id}}"><script type="text/javascript"> selectUnidad_GEOCODE("'"+{{ ($recorrido['recorrido']->latitud == null)?'0':$recorrido['recorrido']->latitud }}+"'","'"+{{ ($recorrido['recorrido']->longitud == null)?'0':$recorrido['recorrido']->longitud }}+"'","{{$recorrido['recorrido']->_id}}");</script> </td>
                                                    <td>{{ $recorrido['recorrido']->evento }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="alert alert-info">
                                                            <strong>La consulta no tiene registros.</strong>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    
                                </div>
                            @else 
                                <div class="alert alert-info">
                                    <strong>Realice una consulta.</strong>
                                </div>
                            @endif
                        </div>
                        <div class="col-sm-12">
                            <h3>Reporte por unidad</h3>
                            <div class="table-responsive">
                                @if (Session::has('reportes'))
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Unidad</th>
                                                <th>TA Total</th>
                                                <th>TC Total</th>
                                                <th>CA Total</th>
                                                <th>CC Total</th>
                                                <th>Prom. Apertura</th>
                                                <th>Prom. Cierre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (Session::get('reportes') as $reporte)
                                                <tr>
                                                    <td>{{ $reporte['unidad']->descripcion }}</td>
                                                    <td>{{ $reporte['mAbiertas'] }} min.</td>
                                                    <td>{{ $reporte['mCerradas'] }} min.</td>
                                                    <td>{{ $reporte['cantidadAbiertas'] }}</td>
                                                    <td>{{ $reporte['cantidadCerradas'] }}</td>
                                                    <td>{{ ($reporte['cantidadAbiertas'] === 0)?0:(round($reporte['mAbiertas']/$reporte['cantidadAbiertas'], 2)) }} min.</td>
                                                    <td>{{ ($reporte['cantidadCerradas'] === 0)?0:(round($reporte['mCerradas']/$reporte['cantidadCerradas'], 2)) }} min.</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7">
                                                        <div class="alert alert-info">
                                                            <strong>No hay reportes con la consulta realizada.</strong>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                @else 
                                    <div class="alert alert-info">
                                        <strong>Realice una consulta.</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('select').chosen({
            width : '100%'
        });
        var unidad = $('#unidad');
        var cooperativa = $('#cooperativa');
        var checkUnidad = $('#checkUnidad');
        $('#fecha_desde').datetimepicker();
        $('#fecha_hasta').datetimepicker();
        cooperativa.change(function () {
            unidad.empty();
            $.get('{{ url('unidades')}}' + '/' + $(this).val() + '/lista', function (data) {
                for(let i = 0; i < data.length; i++)
                    unidad.append('<option value="' + data[i]._id + '">' + data[i].descripcion  + '</option>')
                unidad.trigger('chosen:updated');
                checkUnidad.trigger('change');
                @if (old('unidad') != null)
                    let arrayUnidad = [];
                    @foreach(old('unidad') as $unidad) 
                        arrayUnidad.push('{{ $unidad }}');
                    @endforeach
                    unidad.val(arrayUnidad);
                    unidad.trigger('chosen:updated');
                @endif
            });
        });
        cooperativa.trigger('change');
        checkUnidad.change(function () {
            $('#unidad option').prop('selected', this.checked);
            if (this.checked)
                $('#div_unidad').hide();
            else 
                $('#div_unidad').show();
            unidad.trigger('chosen:updated');
        });
        $('.table').DataTable();
        function mostrarHoy() {
            var desde = $('#fecha_desde');
            var hasta = $('#fecha_hasta');
            desde.val('{{ date('Y-m-d') }}'+' 00:00');
            hasta.val('{{ date('Y-m-d') }}'+' 23:59');
            desde.prop('readonly', true);
            hasta.prop('readonly', true);
        }
        function mostrarAyer() {
            var desde = $('#fecha_desde');
            var hasta = $('#fecha_hasta');
            desde.val('{{ date('Y-m-d', strtotime( '-1 days' )) }}'+' 00:00');
            hasta.val('{{ date('Y-m-d', strtotime( '-1 days' )) }}'+' 23:59');
            desde.prop('readonly', true);
            hasta.prop('readonly', true);
        }
        function mostrarPersonalizado() {
            var desde = $('#fecha_desde');
            var hasta = $('#fecha_hasta');
            desde.prop('readonly', false);
            hasta.prop('readonly', false);
        }
        $('input[name="filtro"]:checked').trigger('click');
    </script>
@endsection