@extends('layouts.app')

@section('title')
    cortetubo
@endsection
@section('styles')
    <style>
        #map {
            width : 100%;
            height : 220px;
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
                
                request.open("GET", "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latitud+","+longitud+"&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE" , true);
                request.send(null);
            }

        }
</script>

    <div class="page-title">
        <div class="title_left">
            <h3>Reportes de Corte Tubo</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="x_panel">
                <div class="x_content">
                    <div class="row">
                        <div class="col-sm-12">
                            <form action="{{ route('cortetubo.index') }}" method="get" role="form">
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
                                            <label for="checkUnidad"><input {{ (old('checkUnidad') != null)?'checked':'' }} type="checkbox" name="checkUnidad" id="checkUnidad"> Unidades</label>
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
                                        <input value="{{ old('fecha_desde') }}" class="form-control" type="text" name="fecha_desde" id="fecha_desde" placeholder="aaaa-MM-dd hh:mm" />
                                        @if ($errors->has('fecha_desde'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('fecha_desde') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="form-group{{ $errors->has('fecha_hasta')?' has-error':'' }}">
                                        <label for="fecha_hasta">Fecha final</label>
                                        <input value="{{ old('fecha_hasta') }}" class="form-control" type="text" name="fecha_hasta" id="fecha_hasta" placeholder="aaaa-MM-dd hh:mm" />
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
                                                <th>Conductor</th>
                                                <th>Fecha Despacho</th>
                                                <th>Ruta</th>
                                                <th>Ubicación</th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (Session::get('recorridos') as $recorrido)
                                                <tr>
                                                    <td>{{$recorrido->unidad->descripcion }}</td>
                                                    <td>{{$recorrido->conductor->nombre }}</td>
                                                    <td>{{ (new Carbon\Carbon($recorrido->fecha->format('Y-m-d H:i:s')))->addHours(5) }}</td>
                                                    <td>{{$recorrido->ruta->descripcion }}</td>
                                                    <td id='{{$recorrido->_id}}'><script type="text/javascript"> selectUnidad_GEOCODE("'"+{{ ($recorrido->coord_corte_tubo[0]['lat'] == null)?'0':$recorrido->coord_corte_tubo[0]['lat']}}+"'","'"+{{ ($recorrido->coord_corte_tubo[0]['lng'] == null)?'0':$recorrido->coord_corte_tubo[0]['lng'] }}+"'",'{{$recorrido->_id}}');</script></td>
                                                    <td><button {{ ($recorrido->coord_corte_tubo == null)?'disabled':'' }} onclick="mostrarMapa('{{$recorrido->ruta_id }}','{{$recorrido->unidad_id }}','{{$recorrido->_id }}','{{json_encode($recorrido->coord_corte_tubo)}}')" class="btn btn-info"><i class="fa fa-eye"></i></button></td>
                                                    <td><button onclick="finish('{{ $recorrido->_id}}');" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Recalcular</button></td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="alert alert-info">
                                                            <strong>La consulta no tiene registros</strong>
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
                <h4 class="modal-title" id="modalLabel">Corte de Tubo en el mapa</h4>
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
    window.onload = function () { 
        $('#menu_toggle').trigger('click');
    }
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
        $('#progress').modal('show');  
        $.get('{{ url('unidades')}}' + '/' + $(this).val() + '/lista', function (data) {
            $('#progress').modal('hide');  
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
        desde.val('{{ date('Y-m-d')}}'+' 00:00');
        hasta.val('{{ date('Y-m-d')}}'+' 23:59');
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

    function finish(id)
    {
        var url = '{{ url("/despachos") }}' + '/' + id + '/finish';
        $('#progress').modal('show');
        $.get(url, function (data) {
            if(data.error == false){
                var array_path=[];
                for(var i=0; i<data.rutarecorrido.length ; i++)
                {
                    array_path.push({lat:parseFloat(data.rutarecorrido[i].lat),
                        lng:parseFloat(data.rutarecorrido[i].lng)});
                }
                
                ruta = new google.maps.Polyline({
                    geodesic: true,
                    strokeWeight: 20,
                    path:array_path
                });
                var array_corte=[];
                for(var i=0; i<data.recorridos.length ; i++)
                {
                    let latitud=data.recorridos[i].latitud;
                    let longitud=data.recorridos[i].longitud; 

                    var isLocationOnEdge = google.maps.geometry.poly.isLocationOnEdge;
                    //1e-3
                
                    let result=isLocationOnEdge(new google.maps.LatLng(parseFloat(latitud), parseFloat(longitud)), ruta, 0.00180);

                    if(!result){
                        /*console.log('ERROR');
                        console.log(latitud);
                        console.log(longitud);*/
                        array_corte.push({
                            'lat':parseFloat(latitud),
                            'lng':parseFloat(longitud)
                        });
                        
                    }
                }
                /***GUARDAR COORDENADAS Y SI CORTE TUBO */
                if(array_corte.length>0){
                    let url_corte='{{ url("/despachos")}}'+"/cortetubo";
                    var params = {
                        array_corte : array_corte,
                        despacho_id:id
                    };
                    $.post(url_corte,params, function (data) {
                        $('#progress').modal('hide');
                        document.getElementById('search').click();
                    });
                }else{
                    $('#progress').modal('hide');
                    document.getElementById('search').click();
                }
            }else{
                $('#progress').modal('hide');
                document.getElementById('search').click();
            }
        }, 'json');
    }

    $('input[name="filtro"]:checked').trigger('click');

    function mostrarMapa(ruta_id,unidad_id,despacho_id,recorrido_corte) {
        recorrido_corte=JSON.parse(recorrido_corte);
        var lat = parseFloat(recorrido_corte[0].lat);
        var lng = parseFloat(recorrido_corte[0].lng);

        if (!isNaN(lat) && !isNaN(lng)) {
            $('#modal').modal('show');
            $('#progress').modal('show');  
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
            request.open("GET", "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latitud+","+longitud+"&key=AIzaSyDsCyqbckiGTpFsOzCxBcQRev1ykFIbDgE" , true);
            request.send(null);
            
            let latLng = {
                lat : lat,
                lng : lng
            };
            

                getRuta(ruta_id,latLng,unidad_id,despacho_id,recorrido_corte);
        }
        else 
            alert('La unidad no reportó geolocalización en este punto.');
    }

    let map;
    let marker;
    var line;
    var line_2;
    var circleMap=[];
    var array_marcador=[];
    let guayaquil = {lat: -2.1775151734461176, lng: -79.91094589233398};
    function initMap() {
        // map = new google.maps.Map(document.getElementById('map'), {
        //     center: guayaquil,
        //     scrollwheel: true,
        //     zoom: 20
        // });

         map = new google.maps.Map(document.getElementById('map'), {
            center: guayaquil,
            scrollwheel: true,
            zoom: 20,
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

        /*marker = new google.maps.Marker({
            map : map,
            position : guayaquil
        });*/
        google.maps.event.addDomListener(window, "resize", function() {
            var center = map.getCenter();
            google.maps.event.trigger(map, "resize");
            map.setCenter(center); 
        });

        line = new google.maps.Polyline({
            geodesic: true,
            strokeColor: '#2ecc71',
            strokeOpacity: 1.0,
            strokeWeight: 20
        });

        line.setMap(map);

        line_2 = new google.maps.Polyline({
            geodesic: true,
            strokeColor: '#FFF',
            strokeOpacity: 1.0,
            strokeWeight: 4
        });
        line_2.setMap(map);
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

    $('#modal').on('shown.bs.modal', function (e) {
        google.maps.event.trigger(map,'resize');
        map.setCenter(guayaquil);
    });


    function getRuta(ruta_id,latLng,unidad_id,despacho_id,recorrido_corte)
    {
        
        var cooperativa_id=document.getElementById('cooperativa').value;
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
                        }
                    }
                }

                dibujarRuta(array_path);
                getHistoricoUnidad(unidad_id,despacho_id,recorrido_corte);
            }else{
                $('#progress').modal('hide');  
                alert('Error al cargar la Ruta');
            }

            //map.setCenter(latLng);
            //marker.setPosition(latLng);
            //marker.setTitle('Corte Tubo');

            /* var marker = new google.maps.Marker({
                position: latLng,
                map: map,
                title: 'Corte Tubo'
            });

            marker.setMap(map);*/

        }, "json");
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

    function getHistoricoUnidad(unidad_id,despacho_id,recorrido_corte)//,fecha_inicio,fecha_fin
    {
        var url='{{url('/historicos')}}';

        var param={
            unidad_id:unidad_id,
            fecha_inicio:'2018-01-05 09:11:36',
            fecha_fin:'2018-01-05 11:43:57',
            opcion_fecha:'P',
            despacho_id:despacho_id,
            opcion:'getHistoricoCorteTubo'
        };
        $.post(url, param,
            function( data ) {
            if(data.error==false)
            {
                for(var i=0;i<array_marcas.length;i++)
                {
                    array_marcas[i].setMap(null);
                }
                array_marcas=[];
                sortJsonArrayByProperty(data.recorrido,'fecha');
                reproducir_recorrido(data.recorrido,recorrido_corte);
            }
            else
                alert('Error al imprimir recorrido.');
            $('#progress').modal('hide');  
        }, "json");
    }
    var array_marcas=[];
    function reproducir_recorrido(recorrido,recorrido_corte)
    {
        //  var posicion;
        let array_marcas=[];
        if(recorrido.length>0)
        {                    
            for(var i=0;i<recorrido.length;i++)
            {
                let  posicion={ lat: parseFloat(recorrido[i].lat), lng : parseFloat(recorrido[i].lng) };
                                    
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

                if(recorrido[i].velocidad==0.0 || recorrido[i].velocidad=='-')
                {
                    icon = {
                        url: '{{url("/images/stop.png")}}',
                        scale: 1,
                        strokeColor:'red'
                    };
                }else
                {
                    icon = {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                            scale: 2,
                            strokeColor: 'green',
                            strokeOpacity: 2.0,
                            strokeWeight: 4,
                            rotation:recorrido[i].angulo
                    }
                }

                var html =
                    '<div class="panel-body"  style="height:12em;overflow: auto;margin: 2px; padding: 2px; ">'+
                    '<ul  style="list-style-type: none; margin: 3px; padding: 3px;overflow: auto;width=100px;overflow-y: hidden;">'+
                    '<li><strong>Velocidad:</strong>&nbsp'+recorrido[i].velocidad+' km/h'+'</li>' +
                    '<li><strong>Voltaje:</strong>&nbsp'+recorrido[i].voltaje+' v'+'</li>' +
                    '<li><strong>Contador diario:</strong>&nbsp'+recorrido[i].contador_diario+'</li>' +
                    '<li><strong>Contador total:</strong>&nbsp'+recorrido[i].contador_total+'</li>' +
                    '<li><strong>Fecha de servidor:</strong>&nbsp'+'<br/>'+recorrido[i].fecha_servidor+'</li>' +
                    '<li><strong>Fecha de GPS:</strong>&nbsp'+'<br/>'+recorrido[i].fecha+'</li>' +
                    '</ul>'+
                    '</div>';
                        
                let infoWindow = new google.maps.InfoWindow({
                    content : html
                    });
                
                    
                /*marker_ct.addListener('click', function () {
                    infoWindow.open(map, marker_ct);
                });*/

                array_marcas.push(
                    new google.maps.Marker({
                        position : posicion ,
                        map : map,
                        icon: icon
                }));

                array_marcas[i].addListener('click', function () {
                    infoWindow.open(map, this);
                });
                
            }
            
            for(var i=0;i<recorrido_corte.length;i++){
                let  posicion_corte={ lat: parseFloat(recorrido_corte[i].lat), lng : parseFloat(recorrido_corte[i].lng) };
                icon_corte = {
                            url: 'http://rastreo.infinitysolutionsec.com/images/danger.png',
                            scale: 1,
                            strokeColor:'red'
                        };
                new google.maps.Marker({
                        position : posicion_corte ,
                        map : map,
                        icon: icon_corte
                });
            }
            
        }
        else
            alert("No se encontró ningún recorrido.");
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

</script>
<!-- AIzaSyD5gxX5X4pLocpK0dpQyRON-l4HTPPamkU -->
<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry&callback=initMap"
async defer></script>

@endsection