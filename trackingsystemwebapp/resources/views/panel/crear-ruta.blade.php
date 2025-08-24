@extends('layouts.app')
@section('title')
Crear ruta
@endsection
@section('styles')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
    #map  
    {
        min-height: 350px;
        width: 100%;
    }

    #punto_control_map , #view_map
    {
        min-height: 350px;
        width: 100%;
    }
    .punto-control 
    {
        padding:4px;
    }

</style>
@endsection

@section('content')

<div class="page-title">
    <div class="title_left">
        @if(isset($ruta))
            <h3>Ruta: {{$ruta->descripcion}}</h3>
        @else
            <h3>Nueva ruta</h3>
        @endif
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Formulario</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <br/>
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <h4>Mapa</h4>
                    <div class="col-sm-12">
                        <div class="col-sm-12">
                            <div id="map"></div>
                        </div>
                        <div class="col-sm-12 col-md-5">
                            <h4>Información histórica de la ruta</h4>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                @if(isset($cooperativas))
                                    <div class="form-group" id="div-cooperativa" >
                                        <label for="cooperativa_id">Cooperativa</label>
                                        <select class="form-control" id="cooperativa_id" name="cooperativa_id" onchange="llenarUnidades('{{url('/rutas')}}','1',null);">
                                            <option value="" disabled selected hidden>Seleccione...</option>
                                            @foreach ($cooperativas as $cooperativa_id)
                                                <option {{ ((isset($ruta) && $ruta->cooperativa_id == $cooperativa_id->_id) || $cooperativas->count() == 1)?'selected':'' }} value="{{ $cooperativa_id->_id }}">
                                                    {{ $cooperativa_id->descripcion }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="help-block" id="span_cooperativa"></span>
                                    </div>
                                @endif
                                <div class="form-group" id="div-unidad">
                                    <label for="unidad_id">Unidad</label>
                                    <select class="form-control" id="unidad_id" name="unidad_id">
                                        <option value="" disabled selected hidden>Seleccione...</option>
                                        @foreach ($unidades as $unidad_id)
                                            <option value="{{ $unidad_id->_id }}">
                                                {{ $unidad_id->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="help-block" id="span_unidad"></span>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group" id="div-fecha-inicio">
                                    <label for="fecha_inicio">Fecha inicial</label>
                                    <input name="fecha_inicio" id="fecha_inicio" class="form-control" type="text" />
                                    <span class="help-block" id="span_fecha_inicio"></span>
                                </div>

                                <div class="form-group" id="div-fecha-fin">
                                    <label for="fecha_fin">Fecha final</label>
                                    <input name="fecha_fin" id="fecha_fin" class="form-control" type="text" />
                                    <span class="help-block" id="span_fecha_fin"></span>
                                </div>
                            
                                <div class="form-group">
                                    @if(isset($ruta))
                                         <button onclick="consultarRuta('{{ url('/consulta') }}','consulta', '{{ url('/rutas') }}','{{$tipo_usuario_valor}}',getIdCooperativa());" type="button" class="btn btn-info" id="bt_consultar"><i class="fa fa-search"></i> Consultar otra ruta</button>
                                    @else
                                        <button onclick="consultarRuta('{{ url('/consulta') }}','consulta', '{{ url('/rutas') }}','{{$tipo_usuario_valor}}',getIdCooperativa());" type="button" class="btn btn-info" id="bt_consultar"> Consultar</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7 text-center">
                            <div id="div_codigo" class="form-group">
                                <label for="codigo">Codigo de ruta</label>
                                <input value="{{ (isset($ruta))?$ruta->codigo:'' }}" placeholder="Requerido..." type="text" id="codigo" class="form-control" />
                                <span class="help-block" id="span_codigo"></span>
                            </div>
                            <div class="form-group" id="div-descripcion">
                                <label for="descripcion">Descripción</label>
                                @if(isset($ruta))
                                    <input placeholder="Nombre de la ruta" name="descripcion" id="descripcion" class="form-control" type="text" value="{{$ruta->descripcion}}"/>
                                @else
                                    <input placeholder="Nombre de la ruta" name="descripcion" id="descripcion" class="form-control" type="text" />
                                @endif
                                <span class="help-block" id="span_descripcion"></span>
                            </div>
                            <div class="form-group" id="div-color_ruta" >
                                <label for="color_ruta">Color ruta</label>
                                <select class="form-control" id="color_ruta" name="color_ruta">
                                    <option value="V">Verde</option>
                                    <option value="A">Azul</option>
                                    <option value="R">Rojo</option>
                                    <option value="M">Morado</option>
                                    <option value="N">Negro</option>
                                    <option value="C">Cafe</option>
                                </select>
                                <span class="help-block" id="span_tipo_ruta"></span>
                            </div>
                            <div class="form-group" id="div-tipo_ruta" >
                                <label for="tipo_ruta">Tipo ruta</label>
                                <select class="form-control" id="tipo_ruta" name="tipo_ruta"  onchange="verificarruta();">
                                    <option value="" disabled selected hidden>Seleccione...</option>
                                    <option value="I">Individual</option>
                                    <option value="C">Cooperativa</option>
                                    <option value="P">Padre</option>
                                    <option value="H">Hijo</option>
                                </select>
                                <span class="help-block" id="span_tipo_ruta"></span>
                            </div>
                            <div id="div_cronograma" class="form-group">
                                <label for="cronograma">Cronograma</label>
                                <button onclick="agregarCronograma();" type="button" class="btn btn-default" id="btnAgregarCronograma"><i class="fa fa-plus"></i></button>
                                <div class="table-responsive">
                                    <table id="cronograma" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Clonar</th>
                                                <th>Día</th>
                                                <th>Desde</th>
                                                <th>Hasta</th>
                                                <th>Eliminar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cronograma_body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="form-group" style="display:none;" id="div-tipo_ruta_padre" >
                                <label for="tipo_ruta_padre">Ruta Padre</label>
                                <select class="form-control" id="tipo_ruta_padre" name="tipo_ruta_padre">
                                    <option value="" disabled selected hidden>Seleccione...</option>
                                </select>
                                <span class="help-block" id="span_tipo_ruta_padre"></span>
                            </div>
                            <div class="form-group"  id="div-tipo_ruta_atm" >
                                <label for="tipo_ruta_atm">Ruta ATM</label>
                                <select class="form-control" id="tipo_ruta_atm" name="tipo_ruta_atm">
                                    <option value="" disabled selected hidden>Seleccione...</option>
                                </select>
                            </div>        
                                <br/><br/>
                                <h5>Puntos de control de la ruta</h5>
                                <div class="form-group">
                                    <button onclick="cleanFormAgregarPunto();" data-toggle="modal" data-target="#search_modal" class="btn btn-info" type="submit"><i class="fa fa-plus"></i> Agregar punto de control</button>
                                    <ol id="sortable"></ol>
                                </div>
                                <br/>
                               <script>
                                   @if(isset($cooperativa))
                                       id_cooperativa='{{$cooperativa}}';
                                   @endif
                               </script>
                               <div class="form-group">
                               @if(!isset($ruta))
                                 <button onclick="consultarRuta('{{ url('/consulta') }}','guardar', '{{ url('/rutas') }}','{{$tipo_usuario_valor}}',getIdCooperativa());" class="btn btn-primary btn-block" type="button"><i class="fa fa-save"></i> Guardar</button>
                               @else
                                 <button onclick="consultarRuta('{{ url('/rutas').'/'.$ruta->_id }}','modificar','{{ url('/rutas') }}','{{$tipo_usuario_valor}}',getIdCooperativa());" class="btn btn-primary btn-block" type="button"><i class="fa fa-save"></i> Guardar cambios</button>
                               @endif
                               </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="search_modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalLabel">Punto de control</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="form-group">
                        @if(isset($cooperativa))
                           <input onkeyup="buscarPuntoControl(this.value,'{{$cooperativa}}');" type="text" name="search" id="search" class="form-control" placeholder="Buscar..."/>
                        @else
                           <input onkeyup="buscarPuntoControl(this.value,getCooperativaId());" type="text" name="search" id="search" class="form-control" placeholder="Buscar..."/>
                        @endif
                    </div>
                    <div class="form-group" id="div-set-punto-control">
                        <select onchange="setPuntoControlMap(this.value);" multiple class="form-control" name="punto_control" id="punto_control"></select>
                    </div>
                    <div class="form-group" id="div-tiempo-llegada">
                        <label for="tiempo-llegada">Tiempo de llegada (minutos)</label>
                        <input name="tiempo-llegada" id="tiempo-llegada" class="form-control" type="number" />
                        <span class="help-block" id="span_tiempo_llegada"></span>
                    </div>
                    <div class="form-group">
                        <label for="adelanto">Multa por adelanto</label>
                        <input name="adelanto" id="adelanto" class="form-control" type="number"/>
                        <span class="help-block" id="span_adelanto"></span>
                    </div>
                    <div class="form-group" id="div-atraso">
                        <label for="atraso">Multa por atraso</label>
                        <input name="atraso" id="atraso" class="form-control" type="number"/>
                        <span class="help-block" id="span_atraso"></span>
                    </div>
                </div>
                <div class="col-lg-8 col-md-8 col-sm-12">
                    <div class="form-group">
                        <div id="punto_control_map"></div>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
        <button type="button"  onclick="seleccionar();"  data-dismiss="modal" class="btn btn-primary"><i class="fa fa-play"></i> Aceptar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modificar_punto" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalLabel">Editar punto de control</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="form-group" id="div-tiempo-llegada-modificacion">
                                <label for="tiempo-llegada-modificacion">Tiempo de llegada (minutos)</label>
                                <input name="tiempo-llegada-modificacion" id="tiempo-llegada-modificacion" class="form-control" type="number" />
                                <span class="help-block" id="span_tiempo_llegada_modificacion"></span>
                            </div>
                            <div class="form-group" id="div-atraso-modificacion">
                                <label for="atraso_modificacion">Multa por atraso</label>
                                <input name="atraso-modificacion" id="atraso-modificacion" class="form-control" type="number" />
                                <span class="help-block" id="span_atraso_modificacion"></span>
                            </div>
                            <div class="form-group" id="div-adelanto-modificacion">
                                <label for="adelanto-modificacion">Multa por adelanto</label>
                                <input name="adelanto-modificacion" id="adelanto-modificacion" class="form-control" type="number" />
                                <span class="help-block" id="span_adelanto_modificacion"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
                <button type="button"  onclick="editarPunto();"  data-dismiss="modal" class="btn btn-primary"><i class="fa fa-play"></i> Aceptar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="view" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalLabel">Punto de control</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div id="view_map"></div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $( function() {
            $( "#sortable" ).sortable();
            $( "#sortable" ).disableSelection();
        } );
    </script>
    <script>
    

        var puntoControlMap;
        var actualPuntoControliD;
        var actualPuntoControlLiId;
        var map;
        var marker;
        var circle;
        var viewMap;
        var viewMarker;
        var viewCircle;
        var line;
        var line_2;


        function initMap() {
            @if(!isset($rutas))
                //  map = new google.maps.Map(document.getElementById('map'), {
                //     center: {lat: -2.1613905698142006, lng: -79.91300582885742},
                //     scrollwheel: false,
                //     zoom: 12
                // });
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {lat: -2.1613905698142006, lng: -79.91300582885742},
                    scrollwheel: true,
                    zoom: 12,
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
            @endif
            // viewMap = new google.maps.Map(document.getElementById('view_map'), {
            //     center: {lat: -34.397, lng: 150.644},
            //     scrollwheel: false,
            //     zoom: 14
            // });
            viewMap = new google.maps.Map(document.getElementById('view_map'), {
                center: {lat: -34.397, lng: 150.644},
                scrollwheel: true,
                zoom: 14,
                mapTypeId: "OSM",
                mapTypeControl: true,
                streetViewControl: true});

            viewMap.mapTypes.set("OSM", new google.maps.ImageMapType({
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

            // puntoControlMap = new google.maps.Map(document.getElementById('punto_control_map'), {
            //     center: {lat: -34.397, lng: 150.644},
            //     scrollwheel: false,
            //     zoom: 14
            // });

            puntoControlMap = new google.maps.Map(document.getElementById('punto_control_map'), {
                center: {lat: -34.397, lng: 150.644},
                scrollwheel: true,
                zoom: 14,
                mapTypeId: "OSM",
                mapTypeControl: true,
                streetViewControl: true});

            puntoControlMap.mapTypes.set("OSM", new google.maps.ImageMapType({
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
            
            google.maps.event.addDomListener(window, "resize", function() {
                google.maps.event.trigger(puntoControlMap, "resize");
            });
            google.maps.event.addDomListener(window, "resize", function() {
                google.maps.event.trigger(viewMap, "resize");
            });
            marker = new google.maps.Marker({
                map: puntoControlMap
            });
            circle = new google.maps.Circle({
                strokeColor: '#00942b',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#50ff88',
                fillOpacity: 0.35,
                map: puntoControlMap
            });
            viewMarker = new google.maps.Marker({
                map: viewMap
            });
            viewCircle = new google.maps.Circle({
                strokeColor: '#00942b',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#50ff88',
                fillOpacity: 0.35,
                map: viewMap
            });
            line = new google.maps.Polyline({
                geodesic: true,
                strokeColor: '#2ecc71',
                strokeOpacity: 1.0,
                strokeWeight: 4
            });
            line.setMap(map);

            line_2 = new google.maps.Polyline({
                geodesic: true,
                strokeColor: '#FFF',
                strokeOpacity: 1.0,
                strokeWeight: 2
            });
            line_2.setMap(map);

            map.addListener('zoom_changed', function() {


                if(map.getZoom() >= 15 && map.getZoom() < 18){

                    line.setOptions({strokeWeight: 13});

                    line_2.setOptions({strokeWeight: 2});

                }else{
                    if(map.getZoom() >= 18){
                        if(map.getZoom() > 18){

                            line.setOptions({strokeWeight: 20});
                            line2.setOptions({strokeWeight: 3});
                        }
                    }
                }

            });

            @if(isset($ruta) and $ruta->recorrido != null)
                var array_recorrido=[];
                var array_for=[];
                actual_id='{{$ruta->_id}}';

                document.getElementById('color_ruta').value='{{$ruta->color}}';
                console.log('{{$ruta->color}}');

                @foreach($ruta->recorrido as $recorrido)
                   @foreach($recorrido as $campo)
                        array_for.push('{{$campo}}');
                @endforeach
                array_recorrido.push({
                    'lat':parseFloat(array_for[0]),
                    'lng':parseFloat(array_for[1])
                });
                array_for=[];
                @endforeach
                 array_ruta=array_recorrido;
                 generateRoute(array_recorrido);
                 map.setCenter(array_ruta[0]);
            @endif

            @if(isset($ruta) and $ruta->puntos_control!=null and isset($puntos_control))
                var array_puntos=[];

                @foreach($ruta->puntos_control as $punto_control)
                        @foreach($puntos_control as $punto)
                            if('{{$punto_control["id"]}}'== '{{$punto["_id"]}}')
                                array_puntos.push({
                                    id:'{{$punto_control["id"]}}',
                                    adelanto:'{{$punto_control["adelanto"]}}',
                                    atraso:'{{$punto_control["atraso"]}}',
                                    tiempo_llegada:'{{$punto_control["tiempo_llegada"]}}',
                                    secuencia:'{{$punto_control["secuencia"]}}',
                                    descripcion:'{{$punto["descripcion"]}}',
                                    latitud:'{{$punto["latitud"]}}',
                                    longitud:'{{$punto["longitud"]}}',
                                    radio:'{{$punto["radio"]}}'
                                });
                        @endforeach
               @endforeach

                for(var i=0;i<array_puntos.length;i++)
                {
                    for(var j=0;j<array_puntos.length;j++)
                    {
                        if(parseInt(array_puntos[j].secuencia) == i+1)
                        {
                            getPuntosControlHtml(array_puntos[j]);
                        }
                    }
                }
          @endif
            google.maps.event.addDomListener(window, "resize", function() {
                var center = puntoControlMap.getCenter();
                google.maps.event.trigger(puntoControlMap, "resize");
                puntoControlMap.setCenter(center); 
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
    </script>
    <script src="{{ asset('js/ruta.js') }}"></script>
    <script>

      window.onload = function () { 
            $('#menu_toggle').trigger('click');
            verificarruta();
        }

        var list_json=[];
        var list=document.getElementsByName('puntos_control[]');
        var circleMap=[];
        var id_indice_aux=0;

        var indice_puntos=0;

        function agregarCronograma(dia = 1, desde = null, hasta = null) {
            var idDesde = 'desde' + $('#cronograma_body tr').length;
            var idHasta = 'hasta' + $('#cronograma_body tr').length;
            var idDia = 'dia' + $('#cronograma_body tr').length;
            var html = '<tr>'
                +   '<td><button onclick="agregarCronograma($(\'#' + idDia + '\').val(), $(\'#' + idDesde + '\').val(), $(\'#' + idHasta + '\').val());" class="btn btn-success" type="button"><i class="fa fa-clone"></i></button></td>'
                +   '<td><select id="' + idDia + '" class="form-control" name="dia[]">'
                        + '<option' + ((dia == 0)?' selected':'') + ' value="0">Domingo</option>' 
                        + '<option' + ((dia == 1)?' selected':'') + ' value="1">Lunes</option>'
                        + '<option' + ((dia == 2)?' selected':'') + ' value="2">Martes</option>'
                        + '<option' + ((dia == 3)?' selected':'') + ' value="3">Miércoles</option>'
                        + '<option' + ((dia == 4)?' selected':'') + ' value="4">Jueves</option>'
                        + '<option' + ((dia == 5)?' selected':'') + ' value="5">Viernes</option>'
                        + '<option' + ((dia == 6)?' selected':'') + ' value="6">Sábado</option>'
                    + '</select></td>'
                +   '<td><input id="' + idDesde + '" name="desde[]" type="text" class="form-control" value="' + ((desde == null)?'':desde) + '" /></td>'
                +   '<td><input id="' + idHasta + '" name="hasta[]" type="text" class="form-control" value="' + ((hasta == null)?'':hasta) + '" /></td>'
                +   '<td><button onclick="$(this).parent().parent().remove();" class="btn btn-danger" type="button"><i class="fa fa-trash-o"></i></button></td>'
                + '</tr>';
            var options = {
                datepicker:false,
                format:'H:i'
            };
            $('#cronograma_body').append(html);
            $('#' + idDesde).datetimepicker(options);
            $('#' + idHasta).datetimepicker(options);
        }

        @if (isset($ruta) && isset($ruta->cronogramas))
            @foreach($ruta->cronogramas as $cronograma)
                agregarCronograma({{ $cronograma->dia }}, '{{ $cronograma->desde->format('H:i') }}', '{{ $cronograma->hasta->format('H:i') }}');
            @endforeach
        @endif

        function verificarruta(){
            var eTipoRuta = document.getElementById('tipo_ruta');
            if (eTipoRuta) {
                var tipo_ruta= eTipoRuta.value;
                var div_tipo_ruta_padre=document.getElementById('div-tipo_ruta_padre');
                var div_tipo_ruta_atm=document.getElementById('div-tipo_ruta_atm');
                var tipo_ruta_padre=document.getElementById('tipo_ruta_padre');

                div_tipo_ruta_atm.style="display:none;";
                if(tipo_ruta=='H'){
                    div_tipo_ruta_padre.style="";
                    if (masc)
                        $('#div_cronograma').show();
                }
                else if(tipo_ruta == 'I' && masc)
                    $('#div_cronograma').show();
                else{
                    if (tipo_ruta_padre != null) {
                        tipo_ruta_padre.value="";
                    }
                    div_tipo_ruta_padre.style="display:none;";
                    div_tipo_ruta_atm.style="";
                    $('#div_cronograma').hide();
                }
            }
        }

        var coop = document.getElementById('cooperativa_id').value;
        if (coop != undefined && coop != null && coop != '') {
            $('#cooperativa_id').trigger('change');
        }

        function getCooperativaId()
        {
            return document.getElementById('cooperativa_id').value;
        }

        function generateRoute(path) {

            var array_aux = [];
            var heatmapData = [];

            for (var i = 0; i < path.length; i++)
            {
                array_aux.push({lat:parseFloat(path[i].lat),lng:parseFloat(path[i].lng)});
                heatmapData.push(new google.maps.LatLng(parseFloat(path[i].lat),parseFloat(path[i].lng)));
            }
            if (array_aux.length > 0)
            {
                line.setPath(array_aux);
                line_2.setPath(array_aux);
                map.setCenter(array_aux[0]);
                map.setZoom(13);
            }

            // var heatmap = new google.maps.visualization.HeatmapLayer({
            //                 data: heatmapData
            //             });
           // heatmap.setMap(map);

        }


        function buscarPuntoControl(descripcion, id_cooperativa)
        {
            var params = {
                search : descripcion,
                cooperativa_id:id_cooperativa
            };
            var url = "{{ url('/puntos-de-control/search-json') }}";
            $('#punto_control').empty();
            $.post(url , params ,function( data ) {
                for (var i = 0; i < data.length; i++)
                {
                    $('#punto_control').append($('<option>', {
                        value : data[i]._id,
                        text : data[i].descripcion
                    }));
                }
                if (data.length > 0)
                {
                    document.getElementById('punto_control').value = data[0]._id;
                    setPuntoControlMap(data[0]._id);
                }
                else
                {
                    marker.setMap(null);
                    circle.setMap(null);
                }
            }, "json");
        }

        function setDataPuntoControl(adelanto,atraso,puntoControlId,id,tiempo_llegada)
        {
            document.getElementById('adelanto-modificacion').value=adelanto;
            document.getElementById('atraso-modificacion').value=atraso;
            document.getElementById('tiempo-llegada-modificacion').value=tiempo_llegada;

            actualPuntoControliD=id;
            actualPuntoControlLiId=puntoControlId;
        }

        function editarPunto()
        {
            if(actualPuntoControliD!=null && actualPuntoControlLiId!=null )
            {
                var adelanto=document.getElementById('adelanto-modificacion').value;
                var atraso=document.getElementById('atraso-modificacion').value;
                var tiempo_llegada=document.getElementById('tiempo-llegada-modificacion').value;

                if(adelanto==''||adelanto==null)adelanto='0';
                if(atraso==''||atraso==null)atraso='0';
                if(tiempo_llegada ==''||tiempo_llegada==null)tiempo_llegada='0';

                if(parseFloat(tiempo_llegada)>=0  && parseFloat(adelanto)>=0 && parseFloat(atraso)>=0)
                {
                        var value= JSON.stringify({
                            id: actualPuntoControliD,
                            adelanto: adelanto,
                            atraso: atraso,
                            tiempo_llegada:tiempo_llegada,
                            secuencia: ''
                        });
                        $('#'+actualPuntoControlLiId).find('input[id="puntos_control"]').val(value);
                        let texto = $('#'+actualPuntoControlLiId).find('span').text();
                        let values = texto.split('|');
                        $('#'+actualPuntoControlLiId).find('span').text('AT: ' + atraso + '|AD: + ' + adelanto + '|T: ' + tiempo_llegada + '|' + values[3]);
                        $('#'+actualPuntoControlLiId).find('button[id="set_data_punto"]').attr('onclick',
                                "setDataPuntoControl('"+adelanto+"','"+atraso+"','"+actualPuntoControlLiId+"','"+actualPuntoControliD+"','"+tiempo_llegada+"');");
                }
                else
                    alert("Error: Todos los datos ingresados deben ser mayores o iguales a cero.");

              }
            else
                alert("Ocurrió un error, no se pudo actualizar el punto de control.");
        }

        function removePuntoControl(id,i)
        {
            $('#' + id).remove();
            if (circleMap[i]!=null)
               circleMap[i].setMap(null);
            else
                alert("Error al eliminar el punto de control.");
        }

        function verPuntoControl(latitud, longitud, radio)
        {
           circleMap[indice_puntos]=new google.maps.Circle({
                strokeColor: '#00942b',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#50ff88',
                fillOpacity: 0.35,
                map: map
            });
            var latLng = { lat: parseFloat(latitud), lng : parseFloat(longitud) };
            circleMap[indice_puntos].setCenter(latLng);
            circleMap[indice_puntos].setRadius(parseFloat(radio));
            map.setCenter(latLng);
        }

        function viewPuntoControlOnMap(latitud, longitud, indice)
        {
          var latLng = { lat: parseFloat(latitud), lng : parseFloat(longitud) };
          for(var i=0 ; i<14 ; i++)
          {
              if(circleMap[i]!=null)
              {
                  if(i==indice)
                      circleMap[i].setOptions(
                              {
                                  strokeColor: '#0000FF',
                                  fillColor: '#2E64FE'
                              });
                  else
                      circleMap[i].setOptions(
                              {
                                  strokeColor: '#00942b',
                                  fillColor: '#50ff88'
                              });
              }
          }
            map.setCenter(latLng);
            map.setZoom(16);
        }

        $('#search').on('shown.bs.modal', function (e) {
            google.maps.event.trigger(puntoControlMap,'resize');
        });
        $('#view').on('shown.bs.modal', function (e) {
            google.maps.event.trigger(viewMap,'resize');
            viewMap.setCenter(viewMarker.getPosition());
        });


        function setPuntoControlMap(id)
        {
            var url = "{{ url('/puntos-de-control') }}/" + id;
            $.get(url, function (data) {
                marker.setPosition({
                    lat : parseFloat(data.latitud),
                    lng : parseFloat(data.longitud)
                });
                circle.setCenter(marker.getPosition());
                circle.setRadius(parseFloat(data.radio));
                marker.setMap(puntoControlMap);
                circle.setMap(puntoControlMap);
                puntoControlMap.setCenter(marker.getPosition());
            }, 'json');
        }

        function seleccionar() {
            var id = document.getElementById('punto_control').value;
            var adelanto = document.getElementById('adelanto').value;
            var atraso = document.getElementById('atraso').value;
            var tiempo_llegada = document.getElementById('tiempo-llegada').value;
            if(adelanto==null||adelanto=='' )adelanto='0';
            if(atraso == null||atraso=='')atraso = '0';
            if(tiempo_llegada==null||tiempo_llegada=='')tiempo_llegada='0';


            if (id != null) {

                    if (list.length == 18) {
                        alert('Error: Una ruta no puede tener más de 14 puntos de control.');
                        return;
                    }
                    else {
                        var aux;
                        var cont=1;
                        for (var i = 0; i < list.length; i++) {
                            aux = JSON.parse(list[i].value);
                            if (aux.id == id) {
                                if(cont==2)
                                {
                                    alert('No puede ingresar un mismo punto de control más de dos veces.');
                                    return;
                                }
                                else
                                    cont++;
                            }
                        }
                    }

                    if(parseFloat(tiempo_llegada)>=0 && parseFloat(adelanto)>=0 && parseFloat(atraso)>=0)
                    {
                        var id_punto_aux=[];
                        var punto_control=null;

                        @foreach($puntos_control as $punto_control)
                                punto_control ='{{$punto_control["_id"]}}';
                              if(punto_control == id)
                                {
                                    id_punto_aux.push(
                                       {
                                           latitud:'{{$punto_control["latitud"]}}',
                                           longitud:'{{$punto_control["longitud"]}}',
                                           descripcion:'{{$punto_control["descripcion"]}}',
                                           radio:'{{$punto_control["radio"]}}'
                                       }
                                    );
                                }
                        @endforeach

                        var puntoControlId = 'punto_control_' + id+'_'+id_indice_aux;

                        var value= JSON.stringify({
                                id:  id,
                                adelanto: adelanto,
                                atraso: atraso,
                                tiempo_llegada: tiempo_llegada,
                                secuencia: ''
                            });
                            if (id_punto_aux.length != 0) {
                                var html = '<li class="ui-state-default" id="' + puntoControlId + '">' +
                                    '<div class="punto-control">' +
                                    '<input onchange="" type="hidden" name="puntos_control[]" id="puntos_control" value=\'' + value + '\' />' +
                                    '<span>AT: ' + atraso + '|AD: ' + adelanto + '|T: ' + tiempo_llegada + '|' + id_punto_aux[0].descripcion + '</span><br/>' +
                                    '<button onclick="viewPuntoControlOnMap(' + id_punto_aux[0].latitud + ', ' + id_punto_aux[0].longitud + ', ' + indice_puntos + ');" type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></button>' +
                                    '<button onclick="removePuntoControl(\'' + puntoControlId + '\',\'' + indice_puntos + '\');" type="button" class="btn btn-danger btn-sm"><i class="fa fa-power-off"></i></button>' +
                                    '<button id="set_data_punto" data-toggle="modal" data-target="#modificar_punto" onclick="setDataPuntoControl(\'' + adelanto + '\',\'' + atraso + '\',\'' + puntoControlId + '\',\''+id+'\',\''+tiempo_llegada+'\');" type="button" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></button>' +
                                    '</div>' +
                                    '</li>';
                                $('#sortable').append(html);
                                verPuntoControl(id_punto_aux[0].latitud, id_punto_aux[0].longitud, id_punto_aux[0].radio);
                                indice_puntos++;
                                id_indice_aux++;
                            }
                    }
                    else
                        alert("Error: Todos los datos ingresados deben ser mayores o iguales a cero.");
            }
        }

        function getPuntosControlHtml(param_punto)
        {
            var puntoControlId = 'punto_control_' + param_punto.id+'_'+id_indice_aux;
            // console.log(param_punto);
            var html = '<li class="ui-state-default" id="' + puntoControlId +'">' +
                    '<div class="punto-control">' +
                    '<input onchange="" type="hidden" name="puntos_control[]" id="puntos_control" value=\'' + JSON.stringify({id:param_punto.id, adelanto:param_punto.adelanto, atraso:param_punto.atraso,tiempo_llegada:param_punto.tiempo_llegada, secuencia:''}) + '\' />' +
                    '<span>AT: ' + param_punto.atraso + '|AD: ' + param_punto.adelanto + '|T: ' + param_punto.tiempo_llegada + '|' + param_punto.descripcion + '</span><br/>' +
                    '<button onclick="viewPuntoControlOnMap(' + param_punto.latitud +', ' + param_punto.longitud + ', ' + indice_puntos + ');" type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></button>' +
                    '<button onclick="removePuntoControl(\'' + puntoControlId + '\',\'' +indice_puntos+ '\');" type="button" class="btn btn-danger btn-sm"><i class="fa fa-power-off"></i></button>' +
                    '<button id="set_data_punto" data-toggle="modal" data-target="#modificar_punto" onclick="setDataPuntoControl(\'' + param_punto.adelanto + '\',\'' + param_punto.atraso + '\',\'' + puntoControlId + '\',\''+param_punto.id+'\',\''+param_punto.tiempo_llegada+'\');" type="button" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></button>' +
                    '</div>' +
                    '</li>';
            $('#sortable').append(html);
            verPuntoControl(param_punto.latitud, param_punto.longitud, param_punto.radio);
            indice_puntos++;
            id_indice_aux++;

        }

        var id_cooperativa=null;

        @if(isset($cooperativa))
                id_cooperativa='{{$cooperativa}}';
        @endif

       /* if('{{$tipo_usuario_valor}}'='1')
        {
            llenarUnidades('{{url('/rutas')}}','2',id_cooperativa);
            $('#div-tipo_ruta_padre').empty();
            $('#div-tipo_ruta').empty();
        }
        else
        {*/
            $('#div-unidad').empty();
            $('#div-tipo_ruta_padre').empty();
            $('#div-tipo_ruta').empty();
        //}


         function getIdCooperativa()
         {
             return id_cooperativa;
         }

        $('#fecha_inicio').datetimepicker();
        $('#fecha_fin').datetimepicker();
        $('#search_modal').on('shown.bs.modal', function (e) {
            google.maps.event.trigger(puntoControlMap, "resize");
            puntoControlMap.setCenter(marker.getPosition());
        });
        $('#div_cronograma').hide();
        var tipo = null;
        @if (isset($ruta))
            tipo = '{{ $ruta->tipo_ruta }}';
        @endif

        $('#todo_usuario').click(function () {
            var checked = $('#todo_usuario').is(':checked');
            $("#usuario_id").find("option").each(function() {
                $(this).prop('selected', checked);
                $('#usuario_id').trigger('chosen:updated');
            });
            if (!checked)
                $('#div_usuarios').show();
            else
                $('#div_usuarios').hide();
        });

        @if(isset($ruta) && isset($ruta->todo_usuario))
            @if($ruta->todo_usuario)
                $('#todo_usuario').prop('checked', true);
                $('#div_usuarios').hide();
            @else
                $('#div_usuarios').show();
            @endif
        @endif

        function llenarUnidades(url, tipo_usuario_valor, id_cooperativa)
        {
            var cooperativa_id;

            if(tipo_usuario_valor=='1')
                cooperativa_id = document.getElementById('cooperativa_id').value;
            else
                cooperativa_id=id_cooperativa;

            var div_unidad=  $('#div-unidad');
            div_unidad.empty();

            var tipo_ruta_padre=  $('#div-tipo_ruta_padre');
            tipo_ruta_padre.empty();

            var tipo_ruta_atm=  $('#div-tipo_ruta_atm');
            tipo_ruta_atm.empty();

            var usuarios=$('#div_usuarios');
            usuarios.empty();

            var tipo_ruta=  $('#div-tipo_ruta');
            tipo_ruta.empty();

            $.post(url, {
                cooperativa_id:cooperativa_id,
                opcion:'getUnidades'
            }, function( data ) {
                var coo = data.cooperativa;
                masc = (coo.mascara == 'S');
                div_unidad.append(
                    '<label for="unidad_id">Unidad</label>'+
                    '<select class="form-control" id="unidad_id" name="unidad_id">'+
                    ' <option value="" disabled selected hidden>Seleccione...</option>'+
                    ' </select>'+
                    ' <span class="help-block" id="span_unidad"></span>'
                );
                var select=$('#unidad_id');
                for (var i = 0, len = data.unidades.length; i < len; i++)
                    select.append('<option  value=\''+ data.unidades[i]._id + '\'> '+  data.unidades[i].descripcion +'</option>');
            }, "json");

            $.post(url, {
                cooperativa_id:cooperativa_id,
                opcion:'getRutasPadres'
            }, function( data ) {
                tipo_ruta.append(
                    '<label for="tipo_ruta">Tipo ruta</label>'+
                    '<select class="form-control" id="tipo_ruta" name="tipo_ruta"  onchange="verificarruta();">'+
                    '<option value="" disabled selected hidden>Seleccione...</option>'+
                    '<option value="I">Individual</option>'+
                    '<option value="C">Cooperativa</option>'+
                    '<option value="P">Padre</option>'+
                    '<option value="H">Hijo</option>'+
                    ' </select>'+
                    ' <span class="help-block" id="span_tipo_ruta"></span>'
                );
                if (tipo != null)
                    $('#tipo_ruta').val(tipo);
                $('#tipo_ruta').trigger('change');
                tipo_ruta_padre.append(
                    '<label for="tipo_ruta_padre">Ruta Padre</label>'+
                    '<select class="form-control" id="tipo_ruta_padre" name="tipo_ruta_padre">'+
                    '<option value="" disabled selected hidden>Seleccione...</option>'+
                    ' </select>'+
                    ' <span class="help-block" id="span_tipo_ruta_padre"></span>'
                );

                var select=$('#tipo_ruta_padre');
                for (var i = 0, len = data.rutaspadres.length; i < len; i++)
                    select.append('<option  value=\''+ data.rutaspadres[i]._id + '\'> '+  data.rutaspadres[i].descripcion +'</option>');
            }, "json");

            $.post(url, {
                cooperativa_id:cooperativa_id,
                opcion:'getRutasATM'
            }, function( data ) {
                tipo_ruta_atm.append(
                    '<label for="tipo_ruta_atm">Ruta ATM</label>'+
                    '<select class="form-control" id="tipo_ruta_atm" name="tipo_ruta_atm">'+
                    '<option value="" disabled selected hidden>Seleccione...</option>'+
                    ' </select>'
                );

                var select=$('#tipo_ruta_atm');
                for (var i = 0, len = data.rutasatm.length; i < len; i++)
                    select.append('<option  value=\''+ data.rutasatm[i]._id + '\'> '+  data.rutasatm[i].descripcion +'</option>');
                
                
                @if(isset($ruta) && isset($ruta->ruta_atm))
                    var select_tipo_ruta_atm=document.getElementById('tipo_ruta_atm');
                    select_tipo_ruta_atm.value='{{$ruta->ruta_atm}}';
                @endif

            }, "json");
            
            $.post(url, {
                cooperativa_id:cooperativa_id,
                opcion:'getUserCompartido'
            }, function( data ) {
                usuarios.append(
                    '<select class="form-control" data-placeholder="Usuarios Compartido" multiple name="usuarios[]"  id="usuario_id">'+
                    ' </select>'+
                    ' <span class="help-block" id="span_unidad"></span>'
                );
                var select=$('#usuario_id');
                $('#usuario_id').chosen({
                    width : '100%'
                });
                for (var i = 0, len = data.usuarios.length; i < len; i++){
                    select.append('<option  value=\''+ data.usuarios[i]._id + '\'> '+  data.usuarios[i].name +'</option>');
                    $('#usuario_id').trigger('chosen:updated');
                }

                @if(isset($ruta) && isset($ruta->usuarios_ruta))
                    var usuarios_select=[];
                    @if($ruta->usuarios_ruta != null)
                        @foreach ($ruta->usuarios_ruta as $usuario)
                            usuarios_select.push('{{ $usuario }}');
                        @endforeach

                        $('#usuario_id').val(usuarios_select).trigger('chosen:updated');
                    @endif
                @endif
            //  console.log(data.ruta.usuarios_ruta);
                //$('#ruta_id').val(data.ruta).trigger('chosen:updated');
            },"json");
        }

    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry,visualization&callback=initMap"
    async defer></script>




@endsection
