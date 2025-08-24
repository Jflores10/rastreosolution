@extends('layouts.app')

@section('title')
    {{ (isset($puntoVirtual))?'Editar':'Crear' }} punto virtual
@endsection

@section('styles')
    <style>
        #map  
        {
            min-height: 350px;
            width: 100%;
            height: 100%;
        }
    </style>
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Punto virtual</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>{{ (isset($puntoVirtual))?'Editar':'Crear' }} punto virtual</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="row">
                    <form method="POST" action="{{ isset($puntoVirtual)?route('puntos-virtuales.update', $puntoVirtual->_id):route('puntos-virtuales.store') }}">
                        {{ csrf_field() }}
                        @if(isset($puntoVirtual))
                            <input type="hidden" name="_method" value="PUT" />
                        @endif
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group{{ $errors->has('cooperativa')?' has-error':'' }}">
                                <label for="cooperativa">Cliente</label>
                                <select name="cooperativa_id" id="cooperativa" class="form-control">
                                    <option hidden disabled {{ (!isset($puntoVirtual) && old('cooperativa') === null)?'selected':'' }}>Seleccione el cliente...</option>
                                    @foreach($cooperativas as $cooperativa)
                                        <option {{ ($cooperativas->count() === 1)?'selected':'' }} value="{{ $cooperativa->_id }}" {{ ((isset($puntoVirtual) && $puntoVirtual->cooperativa_id == $cooperativa->_id) || old('cooperativa_id') == $cooperativa->_id)?'selected':'' }}>{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                                @if($errors->has('descripcion'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('descripcion') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('pista')?' has-error':'' }}">
                                <label for="pista">Pista</label>
                                <input type="text" name="pista" id="pista" class="form-control" value="{{ (isset($puntoVirtual) && old('pista') === null)?$puntoVirtual->pista:old('pista') }}" />
                                @if($errors->has('pista'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('pista') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('descripcion')?' has-error':'' }}">
                                <label for="descripcion">Descripcion</label>
                                <input type="text" name="descripcion" id="descripcion" class="form-control" value="{{ (isset($puntoVirtual) && old('descripcion') === null)?$puntoVirtual->descripcion:old('descripcion') }}" />
                                @if($errors->has('descripcion'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('descripcion') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('tipo_posicion')?' has-error':'' }}">
                                <label>Tipo de posicionamiento</label>
                                <div class="radio">
                                    <label for="normal"><input onclick="cambiarPosicionamiento(this.value);" type="radio" value="N" name="tipo_posicion" {{ ((isset($puntoVirtual) && $puntoVirtual->tipo_posicion == 'N') || old('tipo_posicion') == 'N' || (old('tipo_posicion') === null))?'checked':'' }} /> Normal</label>
                                </div>
                                <div class="radio">
                                    <label for="manual"><input onclick="cambiarPosicionamiento(this.value);"  type="radio" value="M" name="tipo_posicion" {{ ((isset($puntoVirtual) && $puntoVirtual->tipo_posicion == 'M') || old('tipo_posicion') == 'M')?'checked':'' }} /> Manual</label>
                                </div>
                                @if($errors->has('tipo_posicion'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('tipo_posicion') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group{{ $errors->has('clave_equipo')?' has-error':'' }}">
                                <label for="clave_equipo">Clave del equipo</label>
                                <input type="password" name="clave_equipo" id="clave_equipo" class="form-control" value="{{ (isset($puntoVirtual) && old('clave_equipo') === null)?$puntoVirtual->clave_equipo:old('clave_equipo') }}" />
                                @if($errors->has('clave_equipo'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('clave_equipo') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('latitud')?' has-error':'' }}">
                                <label for="latitud">Latitud</label>
                                <input onkeyup="validarManualmente();" {{ ((isset($puntoVirtual) && $puntoVirtual->tipo_posicion == 'N') || old('tipo_posicion') == 'N' || (old('tipo_posicion') === null))?'readonly':'' }} type="text" name="latitud" id="latitud" class="form-control" value="{{ (isset($puntoVirtual) && old('latitud') === null)?$puntoVirtual->latitud:old('latitud') }}" />
                                @if($errors->has('latitud'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('latitud') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('longitud')?' has-error':'' }}">
                                <label for="descripcion">Longitud</label>
                                <input onkeyup="validarManualmente();" {{ ((isset($puntoVirtual) && $puntoVirtual->tipo_posicion == 'N') || old('tipo_posicion') == 'N' || (old('tipo_posicion') === null))?'readonly':'' }} type="text" name="longitud" id="longitud" class="form-control" value="{{ (isset($puntoVirtual) && old('longitud') === null)?$puntoVirtual->longitud:old('longitud') }}" />
                                @if($errors->has('longitud'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('longitud') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('radio')?' has-error':'' }}">
                                <label for="radio">Radio</label>
                                <input onkeyup="validarManualmente();" {{ ((isset($puntoVirtual) && $puntoVirtual->tipo_posicion == 'N') || old('tipo_posicion') == 'N' || (old('tipo_posicion') === null))?'readonly':'' }} type="text" name="radio" id="radio" class="form-control" value="{{ (isset($puntoVirtual) && old('radio') === null)?$puntoVirtual->radio:old('radio') }}" />
                                @if($errors->has('radio'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('radio') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <div class="input-group">
                                    <input id="direccion" type="text" class="form-control" placeholder="Buscar en el mapa...">
                                    <span class="input-group-btn">
                                        <button onclick="buscar();" class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div id="map"></div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Guardar
                                </button>
                                <a class="btn btn-default" href="{{ route('puntos-virtuales.index') }}">
                                    <i class="fa fa-close"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        var map;
        var guayaquil = {lat: (document.getElementById('latitud').value == '')?-2.1613905698142006:parseFloat(document.getElementById('latitud').value), lng: (document.getElementById('longitud').value == '')?-79.91300582885742:parseFloat(document.getElementById('longitud').value) };
        var geocoder;
        var marker;
        var distanceMarker;
        var circle;
        var icon = "{{ asset('/images/radius.png') }}";
        var radio = (document.getElementById('radio').value == '')?0:parseFloat(document.getElementById('radio').value);
        function initMap() 
        {
            map = new google.maps.Map(document.getElementById('map'), {
                center:guayaquil,
                scrollwheel: false,
                zoom: 16
            });
            circle = new google.maps.Circle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                map: map,
                center: guayaquil,
                radius: radio
            });
            
            geocoder = new google.maps.Geocoder();
            marker = new google.maps.Marker({
                map: map,
                position: guayaquil,
                draggable: true
            });
            distanceMarker = new google.maps.Marker({
                map: map,
                position: guayaquil,
                draggable: true,
                icon: icon
            });
            google.maps.event.addListener(marker, 'position_changed', function () {
                var pos = marker.getPosition();
                map.setCenter(pos);
                circle.setCenter(pos);
                document.getElementById('latitud').value = pos.lat();
                document.getElementById('longitud').value = pos.lng();
            });
            google.maps.event.addListener(distanceMarker, 'position_changed', function () {
                var distance = google.maps.geometry.spherical.computeDistanceBetween(marker.getPosition(), 
                    distanceMarker.getPosition());
                circle.setRadius(distance);
                document.getElementById('radio').value = distance;
            });
            document.getElementById('latitud').value = guayaquil.lat;
            document.getElementById('longitud').value = guayaquil.lng;
            document.getElementById('radio').value = radio;
        }
        function cambiarPosicionamiento(posicionamiento) 
        {
            $('#latitud').prop('readonly', (posicionamiento === 'N'));
            $('#longitud').prop('readonly', (posicionamiento === 'N'));
            $('#radio').prop('readonly', (posicionamiento === 'N'));
        }
        function validarManualmente()
        {
            var latitud = parseFloat(document.getElementById('latitud').value);
            var longitud = parseFloat(document.getElementById('longitud').value);
            var radio = parseFloat(document.getElementById('radio').value);
            if (!isNaN(latitud) && !isNaN(longitud))
                marker.setPosition({
                    lat: latitud,
                    lng: longitud
                });
            if (!isNaN(radio))
                circle.setRadius(radio);
        }
        function buscar() 
        {
            var query = document.getElementById('direccion').value;
            geocoder.geocode({
                address: query
            }, function (results, status) {
                if (status === 'OK') {
                    var pos = results[0].geometry.location;
                    map.setCenter(pos);
                    marker.setPosition(pos);
                    circle.setCenter(pos);
                    distanceMarker.setPosition(pos);
                    document.getElementById('latitud').value = pos.lat();
                    document.getElementById('longitud').value = pos.lng();
                }
                else 
                    alert('Lugar no encontrado.');
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap&libraries=geometry"
    async defer></script>
@endsection