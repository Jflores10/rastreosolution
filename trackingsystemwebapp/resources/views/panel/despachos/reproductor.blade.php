@extends('layouts.app')
@section('title')
Reproductor de Despacho
@endsection

@section('styles')
<style>
    #map {
        height: 440px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(44, 62, 80, 0.12);
        margin-bottom: 24px;
        border: 1px solid #dfe6e9;
        position: relative;
        transition: box-shadow 0.2s;
    }
    #map:hover {
        box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
    }
    .velocimetro-panel {
        background: transparent;
        border-radius: 6px;
        box-shadow: none;
        padding: 0;
        position: absolute;
        top: 18px;
        right: 18px;
        width: 180px;
        text-align: center;
        border: none;
        z-index: 10;
    }
    .reproductor-header {
        font-size: 1.25em;
        background: linear-gradient(90deg, #2ecc71 0%, #3498db 100%);
        color: #fff;
        padding: 16px 24px;
        border-radius: 10px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(44,62,80,0.07);
        font-weight: 500;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .reproductor-header strong {
        color: #ffeaa7;
        font-weight: 700;
    }
    @media (max-width: 768px) {
        #map { height: 260px; }
        .velocimetro-panel { top: 10px; right: 10px; width: 100%; }
        .reproductor-header { font-size: 1em; padding: 10px 12px; }
    }
</style>
<link href="{{ asset('css/speedometer.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="reproductor-header">
            <span class="glyphicon glyphicon-road" style="margin-right:8px;"></span>
            <span style="font-weight:600;">Reproductor</span>
            <span style="margin-left:18px;">
                <span class="label label-success" style="font-size:1em;">
                    {{ $despacho->unidad->descripcion }}
                </span>
                <span class="label label-primary" style="font-size:1em; margin-left:8px;">
                    {{ $despacho->ruta->descripcion }}
                </span>
            </span>
            <span style="margin-left:18px;">
                <span class="label label-default" style="font-size:1em;">
                    <strong>Inicio:</strong> {{ $despacho->display_fecha_asignacion->format('Y/m/d H:i') }}
                </span>
                @if($despacho->display_fecha_salida)
                    <span class="label label-info" style="font-size:1em; margin-left:8px;">
                        <strong>Fin:</strong> {{ $despacho->display_fecha_salida->format('Y/m/d H:i') }}
                    </span>
                @else
                    <span class="label label-warning" style="font-size:1em; margin-left:8px;">
                        <strong>Fin:</strong> Sin finalizar
                    </span>
                @endif
            </span>
        </div>
        <div class="btn-toolbar mb-3" role="toolbar" aria-label="Reproductor de recorrido" style="margin-bottom: 24px; gap: 12px; display: flex; flex-wrap: wrap; align-items: center;">
            <div class="form-check" style="margin-left: 18px;">
                <label class="checkbox-inline" style="font-weight: 500; display: flex; align-items: center;">
                    <input type="checkbox" id="autocentrado-checkbox" style="margin-right: 8px;">
                    Autocentrar mapa
                </label>
            </div>
            
            <div class="btn-group" role="group" aria-label="Controles de reproducción" style="gap: 4px;">
                <button type="button" class="btn btn-success" id="btn-play" title="Reproducir">
                    <span class="glyphicon glyphicon-play"></span>
                </button>
                <button type="button" class="btn btn-danger" id="btn-stop" title="Detener">
                    <span class="glyphicon glyphicon-stop"></span>
                </button>
            </div>
            
            <div class="btn-group" role="group" aria-label="Velocidad" style="gap: 4px; align-items: center;">
                <button type="button" class="btn btn-info" id="btn-speed-slow" title="Lento">
                    <span class="glyphicon glyphicon-backward"></span>
                    <span class="sr-only">Lento</span>
                </button>
                <button type="button" class="btn btn-info" id="btn-speed-normal" title="Normal">
                    <span class="glyphicon glyphicon-forward"></span>
                    <span class="sr-only">Normal</span>
                </button>
                <button type="button" class="btn btn-info" id="btn-speed-fast" title="Rápido">
                    <span class="glyphicon glyphicon-fast-forward"></span>
                    <span class="sr-only">Rápido</span>
                </button>
            </div>
            <div style="margin-left: 16px; min-width: 120px; display: flex; align-items: center;">
                <span class="glyphicon glyphicon-time" style="margin-right: 6px; color: #2980b9;"></span>
                <label id="lbl_hora_mapa" style="font-weight: 500; font-size: 1.1em; margin-bottom: 0;"></label>
            </div>
        </div>
        <div style="position:relative;">
            <div id="map"></div>
            <div class="velocimetro-panel">
                <input id="velocimetro" style="display:none;" />
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/speedometer.js') }}"></script>
<script>
    var velocidad_reproductor = 'R';
    function clearAll() {
        processes.forEach(function(p) { clearTimeout(p); });
        processes = [];
        array_marcas.forEach(function(m) { m.setMap(null); });
        array_marcas = [];
        currentIndex = 0;
    }
    $(document).ready(function() {
        $("#velocimetro").myfunc({divFact:10});

        $('#btn-play').click(function() {
            clearAll();
            getHistoricoUnidad();
        });

        $('#btn-stop').click(function() {
            clearAll();
        });

        $('#btn-speed-slow').click(function() {
            velocidad_reproductor = 'L';
            clearAll();
            getHistoricoUnidad();
        });

        $('#btn-speed-normal').click(function() {
            velocidad_reproductor = 'N';
            clearAll();
            getHistoricoUnidad();
        });

        $('#btn-speed-fast').click(function() {
            velocidad_reproductor = 'R';
            clearAll();
            getHistoricoUnidad();
        });

        $('#autocentrado-checkbox').change(function() {
            autocentrado = $(this).is(':checked');
        });

        $('#menu_toggle').trigger('click');    
    });
    var map;
    var guayaquil = {lat: -2.1775151734461176, lng: -79.91094589233398};

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: guayaquil,
            scrollwheel: true,
            zoom: 13,
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

        geolocateMap();
    }

    function dibujarRuta(path)
    {
        if (path.length > 0)
        {
            line.setMap(map);
            line_2.setMap(map);
            line.setPath(path);
            line_2.setPath(path);
            map.setCenter(path[0]);
            map.setZoom(13);
        }
    }

    function geolocateMap() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(pos);
            }, function() {
                handleLocationError(true, map.getCenter());
            });
        } else {
            handleLocationError(false, map.getCenter());
        }
        loadRoute();
        getHistoricoUnidad();
    }

    var circleMap=[];
    var line;
    var line_2;

    function loadRoute()
    {
        var ruta_id = '{{ $despacho->ruta_id }}';
        var cooperativa_id= '{{ $despacho->unidad->cooperativa_id }}'
        var url='{{url('/historicos')}}';
        var array_path=[];
        $.post(url, {
            ruta_id:ruta_id,
            opcion:'getRuta',
            cooperativa_id:cooperativa_id
        }, function( data ) {
            if(data.error==false)
            {
                line.setMap(null);
                line_2.setMap(null);

                for(var a=0; a<circleMap.length ; a++)
                {
                    circleMap[a].setMap(null);
                }
                //  circleMap=[];
                for(var i=0; i<data.ruta.recorrido.length ; i++)
                {
                    array_path.push({lat:parseFloat(data.ruta.recorrido[i].lat),
                        lng:parseFloat(data.ruta.recorrido[i].lng)});
                }
                for(var j=0; j<data.ruta.puntos_control.length ; j++)
                {
                    for(var x=0; x<data.puntos_control.length ; x++)
                    {
                        if(data.ruta.puntos_control[j].id==data.puntos_control[x]["_id"])
                        {
                            circleMap[j]=new google.maps.Circle({
                                strokeColor: '#00942b',
                                strokeOpacity: 0.8,
                                strokeWeight: 2,
                                fillColor: '#50ff88',
                                fillOpacity: 0.35,
                                map: map
                            });
                            circleMap[j].setCenter({lat:parseFloat(data.puntos_control[x].latitud),
                                lng:parseFloat(data.puntos_control[x].longitud)});
                            circleMap[j].setRadius(parseFloat(data.puntos_control[x].radio));
                            var icon = {
                                url: '{{url("/images/flag.png")}}',
                                scale: 1,
                                labelOrigin: new google.maps.Point(4, 25)
                            };
                            var marker = new google.maps.Marker({
                                map : map,
                                position : {
                                    lat : parseFloat(data.puntos_control[x].latitud),
                                    lng : parseFloat(data.puntos_control[x].longitud)
                                },
                                icon : icon,
                                label : data.puntos_control[x].descripcion
                            });
                        }
                    }
                }

                dibujarRuta(array_path);
            }
        }, "json");
    }

    function handleLocationError(browserHasGeolocation, pos) {
        var message = browserHasGeolocation ?
            'Error: El servicio de geolocalizacion fallo.' :
            'Error: Tu navegador no soporta geolocalizacion.';
        console.log(message);
    }

    function sortJsonArrayByProperty(objArray, prop, direction){
        if (arguments.length<2) throw new Error("sortJsonArrayByProp requires 2 arguments");
        var direct = arguments.length>2 ? arguments[2] : 1; //Default to ascending

        if (objArray && objArray.constructor===Array){
            var propPath = (prop.constructor===Array) ? prop : prop.split(".");
            objArray.sort(function(a,b){
                for (var p in propPath){
                    if (a[propPath[p]] && b[propPath[p]]){
                        a = a[propPath[p]];
                        b = b[propPath[p]];
                    }
                }
                return ( (a < b) ? -1*direct : ((a > b) ? 1*direct : 0) );
            });
        }
    }

    var array_marcador=[];
    var array_marcador_angulos=[];

    function reproducir_recorrido(recorrido)
    {
        var posicion;
        var velocidad;

        if (autocentrado) {
            switch(velocidad_reproductor)
            {
                case 'L':velocidad=1500;break;
                case 'N':velocidad=750;break;
                case 'R':velocidad=335;break;
                case 'MR':velocidad=100;break;
                default:velocidad=750;break;
            }
        }
        else {
            velocidad = 0;
        }
        if(recorrido.length>0)
        {
            
            for(var i=0;i<array_marcador.length;i++)
            {
                array_marcador[i].setMap(null);
            }

            if(autocentrado==true)
            {
                map.setZoom(15);
            }
            else
            {
                map.setZoom(13);

                for(var j=0;j<recorrido.length;j++)
                {
                    posicion={ lat: parseFloat(recorrido[j].lat), lng : parseFloat(recorrido[j].lng) };
                    if(!isNaN(posicion.lat) && !isNaN(posicion.lng))
                    {
                            map.setCenter(posicion);
                            break;
                    }
                }
                
            }
            for(var i=currentIndex;i<recorrido.length;i++)
            {
                posicion={ lat: parseFloat(recorrido[i].lat), lng : parseFloat(recorrido[i].lng) };
                                    
                    if(i==0 &&  recorrido[i].angulo=='-')
                    {
                        recorrido[i].angulo=0;
                    }
                    else
                    {
                        if( recorrido[i].angulo=='-')
                        {
                            recorrido[i].angulo=recorrido[i-1].angulo;
                        }
                    }
                    addMarkerWithTimeout(posicion, velocidad * (i - currentIndex), recorrido[i].angulo, recorrido[i].velocidad, i,recorrido[i].fecha, recorrido[i]);
                    velocimetro_change(recorrido[i].velocidad);
            }
            
        }
        else
            alert("No se encontró ningún recorrido.");
    }

    var processes = [];
    var array_tiposMarcas=[];   
    var array_marcas=[];
    var currentIndex = 0;

    function addMarkerWithTimeout(posicion, timeout, angulo, velocidad, index, fecha, recorrido)
    {
        processes.push(setTimeout(function() {
            currentIndex = index;
            if(index==0)
                $('#progress').modal('hide');                    
            $("#lbl_hora_mapa").text(fecha);
            if(velocidad==0.0 || velocidad=='-')
            {
                icon = {
                    url: '{{url("/images/stop.png")}}',
                    scale: 1,
                    strokeColor:'red'
                };
            }
            else
            {
                if(recorrido.tipo != "GTGEO"){
                    icon = {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                            scale: 2,
                            strokeColor: 'blue',
                            strokeOpacity: 2.0,
                            strokeWeight: 4,
                            rotation:angulo
                    }
                }else{
                    icon = {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                            scale: 2,
                            strokeColor: 'green',
                            strokeOpacity: 2.0,
                            strokeWeight: 4,
                            rotation:angulo
                    }
                }
            }

            var estado;
            switch(recorrido.estado_movil)
            {
                case "M":estado="En movimiento";break;
                case "D":estado="Detenido";break;
                case "E":estado="Pérdida de GPS";break;
                default:estado="-";break;
            }

            if( estado =="-")
            {
                if(parseFloat(recorrido.velocidad_actual)==0)
                    estado="Detenido";
                else
                    estado="En movimiento";
            }

            if(velocidad=='-')
            {
                velocidad=0.0;
            }
            var html =
                    '<div class="panel-body"  style="height:12em;overflow: auto;margin: 2px; padding: 2px; ">'+
                    '<ul  style="list-style-type: none; margin: 3px; padding: 3px;overflow: auto;width=100px;overflow-y: hidden;">'+
                    '<li><strong>Disco:</strong>&nbsp'+recorrido.disco+' </li>' +
                    '<li><strong>Placa:</strong>&nbsp'+recorrido.placa+' </li>' +
                    '<li><strong>Tipo:</strong>&nbsp'+recorrido.tipo+' </li>' +
                    '<li><strong>Velocidad:</strong>&nbsp'+velocidad+' km/h'+'</li>' +
                    '<li><strong>Voltaje:</strong>&nbsp'+recorrido.voltaje+' v'+'</li>' +
                    '<li><strong>Contador diario:</strong>&nbsp'+recorrido.contador_diario+'</li>' +
                    '<li><strong>Contador total:</strong>&nbsp'+recorrido.contador_total+'</li>' +
                    '<li><strong>Estado:</strong>&nbsp'+estado+'</li>' +
                    '<li><strong>Fecha de servidor:</strong>&nbsp'+'<br/>'+recorrido.fecha_servidor+'</li>' +
                    '<li><strong>Fecha de GPS:</strong>&nbsp'+'<br/>'+recorrido.fecha+'</li>' +
                    '</ul>'+
                    '</div>';
            var infoWindow = new google.maps.InfoWindow({
            content : html
                });
            if(!isNaN(posicion.lat) && !isNaN(posicion.lng))
            {
                if(autocentrado)
                    map.setCenter(posicion);
            }  
                

            array_marcas.push(
                new google.maps.Marker({
                    position : posicion ,
                    map : map,
                    icon: icon
                }));

            array_tiposMarcas.push(recorrido.tipo+"-"+recorrido.entrada);

            if(index>0)  
            {
                icon2=array_marcas[index-1].getIcon();
                if(array_tiposMarcas[index-1] == 'GTGEO-1'){
                    icon2.strokeColor='red';
                }else{ 
                    if(array_tiposMarcas[index-1] == 'GTGEO-0'){
                        icon2.strokeColor='black';
                    }
                    else
                        icon2.strokeColor='green';
                }
                array_marcas[index-1].setIcon(icon2);
            }             

            velocimetro_change(recorrido.velocidad);

            array_marcas[index].addListener('click', function () {
                infoWindow.open(map, array_marcas[index]);
                velocimetro_change(recorrido.velocidad);
            });
        }, timeout));
    }     
    
    function velocimetro_change(velocidad){
        var velo = document.getElementById('velocimetro');
        velo.value=velocidad;
        $('#velocimetro').trigger('change');
    }

    var autocentrado = false;

    function getHistoricoUnidad()
    {
        var unidad_id = '{{ $despacho->unidad_id }}';
        var opcion_fecha = 'P';
        var fecha_inicio = '{{ $despacho->display_fecha_asignacion->format("Y-m-d H:i") }}';
        var fecha_fin = '{{ $despacho->display_fecha_salida? $despacho->display_fecha_salida->format("Y-m-d H:i") : date($despacho->display_fecha_asignacion->format("Y-m-d") . " 23:59") }}';
        var evento = 'T';
        

        var url='{{url('/historicos')}}';

        var param={
            unidad_id:unidad_id,
            fecha_inicio:fecha_inicio,
            fecha_fin:fecha_fin,
            evento:evento,
            opcion_fecha:opcion_fecha,
            opcion:'getHistoricoReproductor'
        };
        $.post(url, param,
            function( data ) {
            if(data.error==false)
            {
                sortJsonArrayByProperty(data.recorrido,'fecha');
                reproducir_recorrido(data.recorrido);
            }
            else
                alert('Error al reproducir recorrido. Verifique que las fechas esten ingresadas correctamente.');
            $('#progress').modal('hide');  
        }, "json");
    }
</script>
<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
    </script>
<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry&callback=initMap"
    async defer></script>
@endsection
