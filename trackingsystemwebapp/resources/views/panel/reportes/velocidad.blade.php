@extends('layouts.app')

@section('title')
    Velocidad
@endsection

@section('styles')
    <style>
        #map {
            width : 100%;
            height : 400px;
        }
    </style>
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
                
                request.open("GET", "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latitud+","+longitud+"&key=AIzaSyAGzlRt4p1vYca5Od0gN82lBuKvX5C7ldM" , true);
                request.send(null);
            }

        }
</script>

    <div class="page-title">
        <div class="title_left">
            <h3>Reportes de velocidad</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="x_panel">
                <div class="x_content">
                    <div class="row">
                        <div class="col-sm-12">
                            <form action="{{ route('velocidad.index') }}" method="get" role="form">
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
                                    <div class="form-group{{ $errors->has('desde')?' has-error':'' }}">
                                        <label for="desde">Velocidad mínima</label>
                                        <input value="{{ old('desde') }}" type="text" name="desde" id="desde" placeholder="km/h" class="form-control" />
                                        @if ($errors->has('desde'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('desde') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <div class="form-group{{ $errors->has('hasta')?' has-error':'' }}">
                                        <label for="hasta">Velocidad máxima</label>
                                        <input value="{{ old('hasta') }}" type="text" name="hasta" id="hasta" placeholder="km/h" class="form-control" />
                                        @if ($errors->has('hasta'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('hasta') }}</strong>
                                            </span>
                                        @endif
                                    </div>
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
                            @if(Session::has('recorridos'))
                                <p><strong>Existen {{Session::get('recorridos_count')}} registros mayores a {{ old('hasta') }} km/m</strong></p>
                                <p>* Los recorridos en los que no se marca latitud o longitud no pueden ser vistos en el mapa.</p>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Unidad</th>
                                                <th>Fecha de GPS</th>
                                                <th>Fecha de servidor</th>
                                                <th>Ubicación</th>
                                                <th>Velocidad</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (Session::get('recorridos') as $recorrido)
                                                <tr>
                                                    <td>{{ $recorrido->unidad->descripcion }}</td>
                                                    <td>{{ (new Carbon\Carbon($recorrido->fecha_gps->toDateTime()->format('Y-m-d H:i:s')))->subHours(10) }}</td>
                                                    <td>{{ (new Carbon\Carbon($recorrido->fecha->toDateTime()->format('Y-m-d H:i:s')))->subHours(5) }}</td>
                                                    <td id='{{$recorrido->_id}}'><script type="text/javascript"> selectUnidad_GEOCODE("'"+{{ ($recorrido->latitud == null)?'0':$recorrido->latitud }}+"'","'"+{{ ($recorrido->longitud == null)?'0':$recorrido->longitud }}+"'",'{{$recorrido->_id}}');</script> </td>
                                                    <td>{{ $recorrido->velocidad }}</td>
                                                    <td><button {{ ($recorrido->latitud == null || $recorrido->longitud == null)?'disabled':'' }} onclick="mostrarMapa('{{ $recorrido->latitud }}', '{{ $recorrido->longitud }}')" class="btn btn-info"><i class="fa fa-eye"></i></button></td>
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
                                    {{ Session::get('recorridos')->links() }}
                                </div>
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

    <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalLabel">Velocidad en el mapa</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="latitud">Latitud</label>
                    <input type="text" name="latitud" id="latitud" readonly class="form-control">
                </div>
                <div class="form-group">
                    <label for="longitud">Longitud</label>
                    <input type="text" name="longitud" id="longitud" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <div class="map" id="map"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
        

        


        function mostrarMapa(latitud, longitud) {
            var lat = parseFloat(latitud);
            var lng = parseFloat(longitud);
            if (!isNaN(lat) && !isNaN(lng)) {
                $('#modal').modal('show');
                document.getElementById('latitud').value = lat;
                document.getElementById('longitud').value = lng;

                var request = new XMLHttpRequest();
                request.onreadystatechange = function() {
                    if (request.readyState === 4) {
                        if (request.status === 200) {
                            //document.body.className = 'ok';
                            var obj = JSON.parse(request.responseText);
                            
                            if(obj.status=='OK'){
                                document.getElementById('ubicacion').value =obj.results[1].formatted_address;                                
                            }else{
                                document.getElementById('ubicacion').value='Error al consultar ubicación';
                            }

                            //console.log(request.responseText);
                        } else if (!isValid(this.response) && this.status == 0) {
                        // document.body.className = 'error offline';
                            console.log("The computer appears to be offline.");                
                        } else {
                        // document.body.className = 'error';
                        }
                    }
                };
                request.open("GET", "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latitud+","+longitud+"&key=" , true);
                request.send(null);
              
                let latLng = {
                    lat : lat,
                    lng : lng
                };
                map.setCenter(latLng);
                marker.setPosition(latLng);
            }
            else 
                alert('La unidad no reportó geolocalización en este punto.');
        }
        let map;
        let marker;
        let guayaquil = {lat: -2.1775151734461176, lng: -79.91094589233398};
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: guayaquil,
                scrollwheel: true,
                zoom: 20
            });
            marker = new google.maps.Marker({
                map : map,
                position : guayaquil
            });
            google.maps.event.addDomListener(window, "resize", function() {
                var center = map.getCenter();
                google.maps.event.trigger(map, "resize");
                map.setCenter(center); 
            });
        }

        $('#modal').on('shown.bs.modal', function (e) {
            google.maps.event.trigger(map,'resize');
            map.setCenter(marker.getPosition());
        });
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
    <script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry&callback=initMap"
    async defer></script>
@endsection