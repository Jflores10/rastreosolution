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
            <h3>Ruta ATM: {{$ruta->descripcion}}</h3>
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
                        <div class="col-sm-12 col-md-7 text-center">
                            <div id="div_codigo" class="form-group">
                                <label for="codigo">Codigo de ruta</label>
                                <input value="{{ (isset($ruta))?$ruta->codigo:'' }}" placeholder="Requerido..." type="text" id="codigo" class="form-control"  readonly/>
                                <span class="help-block" id="span_codigo"></span>
                            </div>
                            <div class="form-group" id="div-descripcion">
                                <label for="descripcion">Descripción</label>
                                @if(isset($ruta))
                                    <input placeholder="Nombre de la ruta" name="descripcion" id="descripcion" class="form-control" type="text" value="{{$ruta->descripcion}}" readonly/>
                                @else
                                    <input placeholder="Nombre de la ruta" name="descripcion" id="descripcion" class="form-control" type="text" readonly/>
                                @endif
                                <span class="help-block" id="span_descripcion"></span>
                            </div>
                            <br/><br/>
                            <h5>Puntos de control de la ruta</h5>
                            <div class="form-group">
                                <ol id="sortable"></ol>
                            </div>                        
                            <br/>
                            <script>
                                @if(isset($cooperativa))
                                    id_cooperativa='{{$cooperativa}}';
                                @endif
                            </script>
                            <div class="form-group">
                                @if(isset($ruta) && $tipo_usuario_valor != 2)
                                    <button onclick="consultarRuta('{{ url('/rutas-atm').'/'.$ruta->_id }}','modificar','{{ url('/rutas-atm') }}','{{$tipo_usuario_valor}}',getIdCooperativa());" class="btn btn-primary btn-block" type="button"><i class="fa fa-save"></i> Guardar cambios</button>
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
@endsection

@section('scripts')
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $( function() {
            // $( "#sortable" ).sortable();
            $( "#sortable" ).disableSelection();
        } );
    </script>
    <script>
    
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

            var heatmap = new google.maps.visualization.HeatmapLayer({
                            data: heatmapData
                        });
            // heatmap.setMap(map);

            }

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

            @if(isset($ruta) && $ruta->recorrido != null)
                var array_recorrido=[];
                var array_for=[];
                actual_id='{{$ruta->_id}}';

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

                 @if(isset($ruta) && $ruta->puntos_control!=null && isset($puntos_control))
                    var array_puntos=[];

                    @foreach($ruta->puntos_control as $punto_control)
                        @foreach($puntos_control as $punto)
                            if('{{$punto_control["id"]}}'== '{{$punto["_id"]}}')                            
                                array_puntos.push({
                                    id:'{{$punto_control["id"]}}',
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
            @endif
        }

        function verifyGoogleMSG(){
            var googlemaps=document.getElementById('map');
        
            // console.log(googlemaps.innerHTML);
            if(googlemaps != null && googlemaps != undefined){
                let googlemapmsg=googlemaps.getElementsByTagName('div');
                if(googlemapmsg[googlemapmsg.length-3]!= null && googlemapmsg[googlemapmsg.length-3] != undefined )
                    googlemapmsg[googlemapmsg.length-3].style="";
            }
        
        }

        setInterval(verifyGoogleMSG,100,null);

        function getPuntosControlHtml(param_punto)
        {
            var puntoControlId = 'punto_control_' + param_punto.id+'_'+id_indice_aux;
            // console.log(param_punto);
            var html = '<li class="ui-state-default" id="' + puntoControlId +'">' +
                    '<div class="punto-control">' +
                    '<input onchange="" type="hidden" name="puntos_control[]" id="puntos_control" value=\'' + JSON.stringify({id:param_punto.id,tiempo_llegada:param_punto.tiempo_llegada, secuencia:''}) + '\' />' +
                    '<span>T: ' + param_punto.tiempo_llegada + '|' + param_punto.descripcion + '</span><br/>' +
                    '<button onclick="viewPuntoControlOnMap(' + param_punto.latitud +', ' + param_punto.longitud + ', ' + indice_puntos + ');" type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></button>' +
                    '<button id="set_data_punto" data-toggle="modal" data-target="#modificar_punto" onclick="setDataPuntoControl(\'' + puntoControlId + '\',\''+param_punto.id+'\',\''+param_punto.tiempo_llegada+'\');" type="button" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></button>' +
                    '</div>' +
                    '</li>';
            $('#sortable').append(html);
            verPuntoControl(param_punto.latitud, param_punto.longitud, param_punto.radio);
            indice_puntos++;
            id_indice_aux++;

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

        function setDataPuntoControl(puntoControlId,id,tiempo_llegada)
        {
            document.getElementById('tiempo-llegada-modificacion').value=tiempo_llegada;


            actualPuntoControliD=id;
            actualPuntoControlLiId=puntoControlId;
        }
    </script>
    <script>

      window.onload = function () { 
            $('#menu_toggle').trigger('click');
        }

        var list_json=[];
        var circleMap=[];
        var id_indice_aux=0;

        var indice_puntos=0;


        var id_cooperativa=null;

        @if(isset($cooperativa))
            id_cooperativa='{{$cooperativa}}';
        @endif

         function getIdCooperativa()
         {
             return id_cooperativa;
         }

         function editarPunto()
        {
            if(actualPuntoControliD!=null && actualPuntoControlLiId!=null )
            {
                var tiempo_llegada=document.getElementById('tiempo-llegada-modificacion').value;

                if(tiempo_llegada ==''||tiempo_llegada==null)tiempo_llegada='0';

                if(parseFloat(tiempo_llegada)>=0 )
                {
                        var value= JSON.stringify({
                            id: actualPuntoControliD,
                            tiempo_llegada:tiempo_llegada,
                            secuencia: ''
                        });
                        $('#'+actualPuntoControlLiId).find('input[id="puntos_control"]').val(value);
                        let texto = $('#'+actualPuntoControlLiId).find('span').text();
                        let values = texto.split('|');
                        $('#'+actualPuntoControlLiId).find('span').text('T: ' + tiempo_llegada + '|' + values[1]);
                        $('#'+actualPuntoControlLiId).find('button[id="set_data_punto"]').attr('onclick',
                                "setDataPuntoControl('"+actualPuntoControlLiId+"','"+actualPuntoControliD+"','"+tiempo_llegada+"');");
                }
                else
                    alert("Error: Todos los datos ingresados deben ser mayores o iguales a cero.");

              }
            else
                alert("Ocurrió un error, no se pudo actualizar el punto de control.");
        }

        function consultarRuta(url,opcion,urlRetorno,tipo_usuario_valor,id_cooperativa)
        {
            var descripcion = document.getElementById('descripcion');
            var codigo = document.getElementById('codigo');
        
            var div_descripcion = document.getElementById('div-descripcion');
            var div_codigo = document.getElementById('div_codigo');

            var span_codigo = document.getElementById('span_codigo');
            var span_descripcion = document.getElementById('span_descripcion');
         
            var list = document.getElementsByName('puntos_control[]');
            var list_json=[];
           
            div_descripcion.classList.remove('has-error');
            div_codigo.classList.remove('has-error');


            if(list!=null || list.length>0) {
                for (var i = 0; i < list.length; i++) {
                    aux = JSON.parse(list[i].value);
                    aux.secuencia = i + 1;
                    list_json.push(aux);
                }
            }
            if(opcion=='modificar')
            {
                    $.post(url, {
                        puntos_control : list_json,                        
                        _method : 'PUT'                       
                    } ,function( data ) {
                        if (data.error == true)
                        {
                            alert('Error');
                        }
                        else
                        {
                            alert('Los cambios han sido realizados con éxito.');
                            window.location.replace(urlRetorno);
                        }
                    }, "json");
            }
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry,visualization&callback=initMap"
    async defer></script>




@endsection
