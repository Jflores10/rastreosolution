@extends('layouts.app')
@section('title')
Puntos de control
@endsection
@section('styles')
<style>
    #map  
    {
        min-height: 250px;
        width: 100%;
        height: 100%;
    }
    #address {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 300px;
      }

      #address:focus {
        border-color: #4d90fe;
      }

      .pac-container {
            z-index: 1051 !important;
        }
</style>
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Puntos de control</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_content">
            <br />
            <button onclick="cleanForm('{{$tipo_usuario_valor}}');" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
            <br />
            <form class="form-inline" name="form_search" method="GET" action="{{ url('/puntos-de-control/search') }}" id="form_search">
                {{ csrf_field() }}
                <div class="form-group" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                    <label for="cooperativa">Cooperativa</label>
                    <select onchange="mostrar();" name="cooperativa" id="cooperativa" class="form-control">
                        <option disabled {{ ($cooperativas->count() > 1)?'selected':'' }}>Seleccione una cooperativa...</option>
                        @foreach($cooperativas as $cooperativa)
                            <option {{ (isset($coop) && $coop == $cooperativa->_id)?'selected':'' }} value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <input value="{{ (isset($search))?$search:'' }}" id="search" name="search" type="text" class="form-control" placeholder="Búsqueda">
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

            @if ($puntos_control->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <th></th>
                    <th>Descripción</th>
                    @if($tipo_usuario_valor=='1')
                        <th>Cooperativa</th>
                    @endif
                    <th>Latitud</th>
                    <th>Longitud</th>
                    <th>Radio</th>
                    <th>Fecha de creación</th>
                    <th>Fecha de modificación</th>
                    <th>Usuario creador</th>
                    <th>Usuario modificador</th>
                    <th></th>
                    @foreach ($puntos_control as $punto_control)
                        <tr class="{{($punto_control->estado=='I')?'danger':''}}">
                            <td><button onclick="editarPuntoControl('{{ url('/puntos-de-control/' . $punto_control->_id) }}','{{$tipo_usuario_valor}}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                            <td>{{ $punto_control->descripcion }}</td>
                            @if($tipo_usuario_valor=='1')
                                <td>{{ ($punto_control->cooperativa!=null)?$punto_control->cooperativa->descripcion:""}}</td>
                            @endif
                            <td>{{ $punto_control->latitud }}</td>
                            <td>{{ $punto_control->longitud }}</td>
                            <td>{{ $punto_control->radio }}</td>
                            <td>{{ $punto_control->created_at }}</td>
                            <td>{{ $punto_control->updated_at }}</td>
                            <td>{{ ($punto_control->creador!=null)?$punto_control->creador->name:""}}</td>
                            <td>{{ ($punto_control->modificador!=null)?$punto_control->modificador->name:""}}</td>
                            <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($punto_control->estado=='A')?'checked':'' }} onchange="estadoPuntoControl('{{  url('/puntos-de-control/' . $punto_control->_id) }}',(checked)?true:false,'{{$punto_control->descripcion}}');"></td>
                        </tr>
                    @endforeach
                </table>
            </div>
              {{ $puntos_control->links() }}
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
        <h4 class="modal-title" id="modalLabel">Punto de control</h4>
      </div>
      <div class="modal-body">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="form-group" id="div-pdi">
                        <label for="pdi">PDI</label>
                        <input name="pdi" id="pdi" class="form-control" type="number" />
                        <span class="help-block" id="span_pdi"></span>
                    </div>
                    <div class="form-group" id="div-descripcion">
                        <label for="descripcion">Descripción</label>
                        <input name="descripcion" id="descripcion" class="form-control" type="text" />
                        <span class="help-block" id="span_descripcion"></span>
                    </div>
                    <div class="form-group" id="div-cooperativa" {{ (!isset($id_cooperativa))?'style=display:none;':'' }}>
                        <label for="cooperativa_id">Cooperativa</label>
                        <select class="form-control" id="cooperativa_id" name="cooperativa_id">
                            <option value='' disabled selected hidden>Seleccione...</option>
                            @foreach ($cooperativas as $cooperativa_id)
                                <option value="{{ $cooperativa_id->_id }}">
                                    {{ $cooperativa_id->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block" id="span_cooperativa"></span>
                    </div>
                    <div class="form-group" id="div-tipo-ingreso">
                        <label for="tipo_ingreso">Tipo de ingreso</label><br/>
                        <input name="tipo_ingreso" onchange=cambioOpcion(this.value) id="tipo_ingreso" type="radio" value="MP" checked /> Mapa<br/>
                        <input name="tipo_ingreso" onchange=cambioOpcion(this.value) id="tipo_ingreso" type="radio" value="MN"/> Manual
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="form-group"  id="div-latitud">
                        <label for="latitud">Latitud</label>
                        <input onkeyup="updateMarkerPosition();" name="latitud" id="latitud" class="form-control" type="number"/>
                        <span class="help-block" id="span_latitud"></span>
                    </div>
                    <div class="form-group"  id="div-longitud">
                        <label for="longitud">Longitud</label>
                        <input onkeyup="updateMarkerPosition();" name="longitud" id="longitud" class="form-control" type="number" />
                        <span class="help-block" id="span_longitud"></span>
                    </div>
                    <div class="form-group" id="div-radio">
                        <label for="radio">Radio</label>
                        <input type="number" onkeyup="updateRadius();" name="radio" id="radio" class="form-control" />
                        <span class="help-block" id="span_radio"></span>
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="otro" name="otro" value="MT" /> MT2500
                            </label>
                        </div>
                    </div>
                    <div class="form-group" id="div_entrada">
                        <label for="entrada">Evento de entrada</label>
                        <input readonly type="text" name="entrada" id="entrada" class="form-control" />
                        <span class="help-block" id="span_entrada"></span>
                    </div>
                    <div class="form-group" id="div_salida">
                        <label for="salida">Evento de salida</label>
                        <input readonly type="text" name="salida" id="salida" class="form-control" />
                        <span class="help-block" id="span_salida"></span>
                    </div>
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="form-group" id="div-mapa">
                        <h4>Mapa</h4>
                        <input id="address" type="text" class="form-control" placeholder="Consultar ubicacion...">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
        <button type="button" onclick="guardar();" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/punto-control.js') }}"></script>
<script>
    var geocoder;
    var map;
    var marker;
    var guayaquil = {lat: -2.1613905698142006, lng: -79.91300582885742};
    var circle;
    var markerRadius;
    var distance;

    function cambioOpcion(opcion)
    {
        var latitud = document.getElementById('div-latitud');
        var longitud = document.getElementById('div-longitud');
        var mapa = document.getElementById('div-mapa');
        var latitude = document.getElementById('latitud');
        var longitude = document.getElementById('longitud');
        var radius = document.getElementById('radio');

    switch(opcion)
    {
        case "MP":
            latitude.readOnly = true;
            longitude.readOnly = true;
            radius.readOnly = true;
            google.maps.event.addListener(marker, 'position_changed', updateMarkerRadius);
            google.maps.event.addListener(markerRadius, 'position_changed', update);
            map.addListener('click', function(e){
                var latLng = e.latLng;
                marker.setPosition(latLng);
                circle.setCenter(latLng);
                map.setCenter(latLng);
            });
            markerRadius.setMap(map);
            markerRadius.setPosition(marker.getPosition());
            circle.setRadius(0);
            radius.value = 0;
        break;

        case "MN":
            latitude.readOnly = false;
            longitude.readOnly = false;
            radius.readOnly = false;
            google.maps.event.clearListeners(map, 'click');
            google.maps.event.clearListeners(marker, 'position_changed');
            google.maps.event.clearListeners(markerRadius, 'position_changed');
            markerRadius.setMap(null);
            break;

        default:break;
    }
    }

    function initMap() {
        // map = new google.maps.Map(document.getElementById('map'), {
        //     center:guayaquil,
        //     scrollwheel: false,
        //     zoom: 16
        // });

        map = new google.maps.Map(document.getElementById('map'), {
            center: guayaquil,
            scrollwheel: true,
            zoom: 16,
            mapTypeId: "OSM",
            mapTypeControl: true,
            streetViewControl: true});

        map.mapTypes.set("OSM", new google.maps.ImageMapType({
            getTileUrl: function(coord, zoom) {
                var tilesPerGlobe = 1 << zoom;
                var x = coord.x % tilesPerGlobe;
                if (x < 0) {
                    x = tilesPerGlobe+x;}
                return "http://tile.openstreetmap.org/" + zoom + "/" + x + "/" + coord.y + ".png";
            },
            tileSize: new google.maps.Size(256, 256),
            name: "OpenStreetMap",
            maxZoom: 18
        }));
        var address = document.getElementById('address');
        var searchBox = new google.maps.places.SearchBox(address);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(address);
        marker = new google.maps.Marker({
            map : map,
            position : guayaquil,
            title : 'Punto de control'
        });
        searchBox.addListener('places_changed', function () {
            var places = searchBox.getPlaces();
            if (places.length == 0) 
                return;
            places.forEach(function(place) {
                if (place.geometry) {
                    var location = place.geometry.location;
                    map.setCenter(location);
                    circle.setCenter(location);
                    marker.setPosition(location);
                }
            });
        });
        circle = new google.maps.Circle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                map: map,
                center: guayaquil,
                radius: 0
            });
        geocoder = new google.maps.Geocoder();

        map.addListener('click', function(e){
            var latLng = e.latLng;
            marker.setPosition(latLng);
            circle.setCenter(latLng);
            map.setCenter(latLng);
        });
        var icon = "{{ asset('/images/radius.png') }}";
        markerRadius = new google.maps.Marker({
            map : map,
            position : marker.getPosition(),
            draggable : true,
            title : 'Radio',
            icon: icon
        });
        google.maps.event.addListener(marker, 'position_changed', updateMarkerRadius);
        google.maps.event.addListener(markerRadius, 'position_changed', update);
        update();
        cambioOpcion('MP');
        google.maps.event.addDomListener(window, "resize", function() {
            var center = map.getCenter();
            google.maps.event.trigger(map, "resize");
            map.setCenter(center); 
        });
    }

    function verifyGoogleMSG(){
        var googlemaps=document.getElementById('map');
        // console.log(googlemaps.innerHTML);
        if(googlemaps != null && googlemaps != undefined){
            var googlemapmsg=googlemaps.getElementsByTagName('div');
            if(googlemapmsg[googlemapmsg.length-3]!= null && googlemapmsg[googlemapmsg.length-3] != undefined )
                googlemapmsg[googlemapmsg.length-3].style="";
        }
    }

    setInterval(verifyGoogleMSG,100,null);
    
    function updateMarkerRadius()
    {
        var latitud = document.getElementById('latitud');
        var longitud = document.getElementById('longitud');
        var position = marker.getPosition();
        markerRadius.setPosition(position);
        latitud.value = position.lat();
        longitud.value = position.lng();
    }
    function update()
    {
        distance = google.maps.geometry.spherical.computeDistanceBetween(marker.getPosition(), 
            markerRadius.getPosition());
        circle.setRadius(distance);
        document.getElementById('radio').value = distance;
    }
    function codeAddress() {
        var address = document.getElementById("address").value;
        geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            map.setCenter(results[0].geometry.location);
            circle.setCenter(results[0].geometry.location);
            marker.setPosition(results[0].geometry.location);
        } else {
            console.log("Localización erronea debido a: " + status);
        }
        });
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry&callback=initMap"
    async defer></script>

<script>
$(function() {
    $('#otro').click(function () {
        var checked = $(this).is(':checked');
        $('#entrada').prop('readonly', !checked);
        $('#salida').prop('readonly', !checked);
        if (!checked)
        {
            $('#entrada').val(null);
            $('#salida').val(null);
        }
    });
});
function updateRadius()
{
    circle.setRadius(parseFloat(document.getElementById('radio').value));
}
function updateMarkerPosition()
{
    var latitude = document.getElementById('latitud').value;
    var longitude = document.getElementById('longitud').value;
    if (latitude != '' && longitude != '')
    {
        marker.setPosition({
            lat : parseFloat(latitude),
            lng : parseFloat(longitude)
        });
    }
    circle.setCenter(marker.getPosition());
    map.setCenter(marker.getPosition());
 }




    function mostrar()
    {
        document.form_search.submit();
    }
    $('#form').on('shown.bs.modal', function (e) {
        google.maps.event.trigger(map,'resize');
        map.setCenter(marker.getPosition());
    });

    var id_cooperativa=null;

    @if(isset($id_cooperativa))
       id_cooperativa='{{$id_cooperativa}}';
    @endif


    function guardar()
    {

        if (actual_id == null)
        {

                crearPuntoControl(
                        '{{ url('/puntos-de-control') }}'
                        ,'{{$tipo_usuario_valor}}'
                        , id_cooperativa
                );

        }
        else
        {
                actualizarPuntoControl('{{ url('/puntos-de-control') }}' + '/' + actual_id
                        ,'{{$tipo_usuario_valor}}'
                        , id_cooperativa
                );

        }

    }
   </script>
@endsection

