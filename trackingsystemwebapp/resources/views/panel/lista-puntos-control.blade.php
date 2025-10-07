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

    #map1  
    {
        min-height: 250px;
        width: 100%;
        height: 100%;
    }
    #address1 {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 300px;
    }
    #address1:focus {
        border-color: #4d90fe;
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
                    <th>PDI</th>
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
                            <td><button onclick="editarPuntoControl('{{ url('/puntos-de-control/' . $punto_control->_id) }}','{{$tipo_usuario_valor}}','{{json_encode($punto_control->cooperativa->pto_bloques)}}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                            <td>{{ $punto_control->pdi }}</td>
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
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="form-group" id="div-cooperativa" {{ (!isset($id_cooperativa))?'style=display:none;':'' }}>
                        <label for="cooperativa_id">Cooperativa</label>
                        <select class="form-control" id="cooperativa_id" name="cooperativa_id">
                            <option value='' disabled selected hidden>Seleccione...</option>
                            @foreach ($cooperativas as $cooperativa_id)
                                <option value="{{ $cooperativa_id->_id }}" data-bloques="{{ json_encode($cooperativa_id->pto_bloques)}}">
                                    {{ $cooperativa_id->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        <div style="margin-top:10px;">
                            <label class="radio-inline">
                                <input type="radio" name="tipo_mar" value="1" checked> Radio
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="tipo_mar" value="2">Polígono
                            </label>
                        </div>
                        <span class="help-block" id="span_cooperativa"></span>
                    </div>
                </div>
            </div>
          <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#bloq1" data-toggle="tab">Bloque 1</a></li>
                <li><a href="#bloq2" data-toggle="tab">Bloque 2</a></li>
            </ul>

        <!-- Tab panes -->
            <div class="tab-content" style="margin-top: 15px;">
                <div class="tab-pane fade in active" id="bloq1">
                    <input type="hidden" name="_id" id="_id" value="">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                           
                            <div class="form-group" id="div-descripcion">
                                <label for="descripcion">Descripción</label>
                                <input name="descripcion" id="descripcion" class="form-control" type="text" />
                                <span class="help-block" id="span_descripcion"></span>
                            </div>
                          
                            <div class="form-group" id="div-tipo-ingreso">
                                <label for="tipo_ingreso">Tipo de ingreso</label><br/>
                                <input name="tipo_ingreso" onchange=cambioOpcion(this.value) id="tipo_ingreso" type="radio" value="MP" checked /> Mapa<br/>
                                <input name="tipo_ingreso" onchange=cambioOpcion(this.value) id="tipo_ingreso" type="radio" value="MN"/> Manual
                            </div>
                            <!--
                            <div class="form-group" id="div-dias-semana" >
                                <label for="tipo_ingreso">Dias Habilitados</label><br/>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="1"> Lunes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="2"> Martes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="3"> Miércoles</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="4"> Jueves</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="5"> Viernes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="6"> Sábado</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias[]" value="7"> Domingo</label>
                                </div>
                            </div>

                            -->
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div id="div_lan_lon_radio1">
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
                        <div class="col-lg-12" id="contenedor-poligono1" style="display:none;">
                            <h4>Coordenadas del Polígono </h4>
                            <div class="row">
                                @for ($i = 1; $i <= 4; $i++)
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Latitud Pto. {{ $i }}</label>
                                            <input type="text" class="form-control lat-pol" id="lat{{ $i }}" name="lat{{ $i }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Longitud Pto. {{ $i }}</label>
                                            <input type="text" class="form-control lng-pol" id="lng{{ $i }}" name="lng{{ $i }}" readonly>
                                        </div>
                                    </div>
                                @endfor
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
                <div class="tab-pane fade" id="bloq2">
                    <input type="hidden" name="_id1" id="_id1" value="">

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            
                            <div class="form-group" id="div-descripcion">
                                <label for="descripcion1">Descripción</label>
                                <input name="descripcion1" id="descripcion1" class="form-control" type="text" />
                                <span class="help-block" id="span_descripcion1"></span>
                            </div>
                           
                            <div class="form-group" id="div-tipo-ingreso2">
                                <label for="tipo_ingreso">Tipo de ingreso</label><br/>
                                <input name="tipo_ingreso1" onchange=cambioOpcion(this.value,'2') id="tipo_ingreso1" type="radio" value="MP" checked /> Mapa<br/>
                                <input name="tipo_ingreso1" onchange=cambioOpcion(this.value,'2') id="tipo_ingreso1" type="radio" value="MN"/> Manual
                            </div>
                            <!--
                            <div class="form-group"  id="div-dias-semana1" >
                                <label for="tipo_ingreso">Dias Habilitados</label><br/>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="1"> Lunes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="2"> Martes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="3"> Miércoles</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="4"> Jueves</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="5"> Viernes</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="6"> Sábado</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="dias1[]" value="7"> Domingo</label>
                                </div>
                            </div>
                                -->
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div id="div_lan_lon_radio2">
                                <div class="form-group"  id="div-latitud1">
                                    <label for="latitud">Latitud</label>
                                    <input onkeyup="updateMarkerPosition('2');" name="latitud1" id="latitud1" class="form-control" type="number"/>
                                    <span class="help-block" id="span_latitud1"></span>
                                </div>
                                <div class="form-group"  id="div-longitud1">
                                    <label for="longitud">Longitud</label>
                                    <input onkeyup="updateMarkerPosition('2');" name="longitud1" id="longitud1" class="form-control" type="number" />
                                    <span class="help-block" id="span_longitud1"></span>
                                </div>
                                <div class="form-group" id="div-radio1">
                                    <label for="radio">Radio</label>
                                    <input type="number" onkeyup="updateRadius('2');" name="radio1" id="radio1" class="form-control" />
                                    <span class="help-block" id="span_radio1"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="otro1" name="otro1" value="MT" /> MT2500
                                    </label>
                                </div>
                            </div>
                            <div class="form-group" id="div_entrada">
                                <label for="entrada">Evento de entrada</label>
                                <input readonly type="text" name="entrada1" id="entrada1" class="form-control" />
                                <span class="help-block" id="span_entrada1"></span>
                            </div>
                            <div class="form-group" id="div_salida">
                                <label for="salida">Evento de salida</label>
                                <input readonly type="text" name="salida1" id="salida1" class="form-control" />
                                <span class="help-block" id="span_salida1"></span>
                            </div>
                        </div>
                        <div class="col-lg-12" id="contenedor-poligono2" style="display:none;">
                            <h4>Coordenadas del Polígono</h4>
                            <div class="row">
                                @for ($i = 1; $i <= 4; $i++)
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Latitud Pto. {{ $i }}</label>
                                            <input type="text" class="form-control lat-pol2" id="lat{{ $i }}_b2" name="lat{{ $i }}_b2" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Longitud Pto. {{ $i }}</label>
                                            <input type="text" class="form-control lng-pol2" id="lng{{ $i }}_b2" name="lng{{ $i }}_b2" readonly>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group" id="div-mapa1">
                                <h4>Mapa</h4>
                                <input id="address1" type="text" class="form-control" placeholder="Consultar ubicacion...">
                                <div id="map1"></div>
                            </div>
                        </div>
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
  $(document).ready(function() {
    // Cooperativa → mostrar/ocultar bloque 2
    $("#cooperativa_id").on("change", function() {
      var bloques = $(this).find("option:selected").data("bloques");
      if (bloques === true || bloques === "true" || bloques == 1) {
        $('ul.nav-tabs li:eq(1)').show();
        if (!$('ul.nav-tabs li:eq(0)').hasClass('active')) {
          $('ul.nav-tabs li:eq(0) a').tab('show');
        }
      } else {
        $('ul.nav-tabs li:eq(1)').hide();
        $('ul.nav-tabs li:eq(0) a').tab('show');
      }
    });
    $("#cooperativa_id").trigger("change");

    // Cambiar entre Radio y Polígono
    $("input[name='tipo_mar']").on("change", function () {
      var isPolygon = $(this).val() === "2";

      // Toggle UI
      $("#div_lan_lon_radio1, #div_lan_lon_radio2").toggle(!isPolygon);
      $("#contenedor-poligono1, #contenedor-poligono2").toggle(isPolygon);
      $("#div-tipo-ingreso").toggle(!isPolygon);
      $("#div-tipo-ingreso2").toggle(!isPolygon);


      // Alternar modo en mapas (ambos bloques)
      setPolygonMode('1', isPolygon);
      setPolygonMode('2', isPolygon);
    });

    function validarPdi() {
        var tipo_mar = $("input[name='tipo_mar']:checked").val();
        var val = parseInt($("#pdi").val(), 10);

        if (tipo_mar === "2") { // Polígono
            if (isNaN(val) || val < 20 || val > 39) {
                $("#span_pdi").text("Para Polígono, el PDI debe estar entre 20 y 39.");
                $("#div-pdi").addClass("has-error");
                return false;
            } else {
                $("#span_pdi").text("");
                $("#div-pdi").removeClass("has-error");
                return true;
            }
        } else {
            // Reset si es Radio
            $("#span_pdi").text("");
            $("#div-pdi").removeClass("has-error");
            return true;
        }
    }

    // Validar al salir del input
    $("#pdi").on("blur", validarPdi);

    // Revalidar cuando cambie tipo_mar
    $("input[name='tipo_mar']").on("change", function() {
        $("#pdi").trigger("blur");
    });
  });
</script>

<script>
  // ===== VARIABLES GLOBALES =====
  var geocoder = {}, map = {}, marker = {}, circle = {}, markerRadius = {}, distance = {};
  var polygon = {}, polyPath = {};
  var drawingManager = {}, overlayCompleteListener = {}, pathSetAt = {}, pathInsertAt = {}, pathRemoveAt = {};
  var mapClickRadio = {};
  var guayaquil = {lat: -2.1613905698142006, lng: -79.91300582885742};

  // ===== UTIL =====
  function safeRemoveListener(ref){
    if (!ref) return;
    if (typeof ref.remove === 'function') ref.remove();
    else if (google && google.maps && google.maps.event) google.maps.event.removeListener(ref);
  }

  function detachRadio(b){
    safeRemoveListener(mapClickRadio[b]); mapClickRadio[b] = null;
  }
  function detachPathListeners(b){
    safeRemoveListener(pathSetAt[b]);     pathSetAt[b] = null;
    safeRemoveListener(pathInsertAt[b]);  pathInsertAt[b] = null;
    safeRemoveListener(pathRemoveAt[b]);  pathRemoveAt[b] = null;
  }
  function detachDrawing(b){
    if (overlayCompleteListener[b]) { safeRemoveListener(overlayCompleteListener[b]); overlayCompleteListener[b] = null; }
    if (drawingManager[b]) {
      drawingManager[b].setDrawingMode(null);
      drawingManager[b].setMap(null);
    }
  }

  function clearPolygonPath(b){
    if (!polyPath[b]) return;
    while (polyPath[b].getLength() > 0) polyPath[b].pop();
  }

  function fillPolygonInputs(b){
    if (!polyPath[b]) return;
    var len = Math.min(4, polyPath[b].getLength());
    // limpiar primero
    for (var i=1;i<=4;i++){
      var $lat = $("#lat"+i+"_b"+b), $lng = $("#lng"+i+"_b"+b);
      if ($lat.length) $lat.val('');
      if ($lng.length) $lng.val('');
      else {
        // fallback dentro del tab del bloque
        var $scope = $('#bloq'+b);
        var $lat2 = $scope.find('#lat'+i), $lng2 = $scope.find('#lng'+i);
        if ($lat2.length) $lat2.val('');
        if ($lng2.length) $lng2.val('');
      }
    }
    // llenar
    for (var j=0;j<len;j++){
      var pt = polyPath[b].getAt(j);
      var $lat = $("#lat"+(j+1)+"_b"+b), $lng = $("#lng"+(j+1)+"_b"+b);
      if ($lat.length && $lng.length){
        $lat.val(pt.lat()); $lng.val(pt.lng());
      } else {
        var $scope = $('#bloq'+b);
        var $lat2 = $scope.find('#lat'+(j+1)), $lng2 = $scope.find('#lng'+(j+1));
        if ($lat2.length && $lng2.length){ $lat2.val(pt.lat()); $lng2.val(pt.lng()); }
      }
    }
  }

  // ===== RADIO =====
  function attachRadio(b){
    detachDrawing(b);
    detachPathListeners(b);

    // mostrar radio
    marker[b].setMap(map[b]);
    circle[b].setMap(map[b]);
    markerRadius[b].setMap(map[b]);

    // ocultar polígono
    if (polygon[b]) { polygon[b].setEditable(false); polygon[b].setMap(null); }

    // click en mapa → mover centro del radio
    detachRadio(b);
    mapClickRadio[b] = map[b].addListener('click', function(e){
      var latLng = e.latLng;
      marker[b].setPosition(latLng);
      circle[b].setCenter(latLng);
      map[b].setCenter(latLng);
    });
  }

  // ===== POLÍGONO (DrawingManager como en Google Maps) =====
  function attachPolygon(b){
    // ocultar radio
    detachRadio(b);
    marker[b].setMap(null);
    circle[b].setMap(null);
    markerRadius[b].setMap(null);

    // quitar listeners de path anteriores
    detachPathListeners(b);

    // limpiar polígono previo si existe
    if (polygon[b]) { polygon[b].setEditable(false); polygon[b].setMap(null); polygon[b] = null; polyPath[b] = null; }

    // crear DrawingManager si no existe
    if (!drawingManager[b]) {
      drawingManager[b] = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.POLYGON,
        drawingControl: true,
        drawingControlOptions: { position: google.maps.ControlPosition.TOP_CENTER, drawingModes: ['polygon'] },
        polygonOptions: {
          fillColor: '#FF0000', fillOpacity: 0.35, strokeColor: '#FF0000', strokeWeight: 2,
          clickable: true, editable: true, zIndex: 10
        }
      });
    }
    drawingManager[b].setMap(map[b]);
    drawingManager[b].setDrawingMode(google.maps.drawing.OverlayType.POLYGON);

    // overlay completo (usuario terminó de dibujar: click + doble-click)
    safeRemoveListener(overlayCompleteListener[b]);
    overlayCompleteListener[b] = google.maps.event.addListener(drawingManager[b], 'overlaycomplete', function(e){
      if (e.type !== google.maps.drawing.OverlayType.POLYGON) return;

      // quitar polígono anterior si hubiera
      if (polygon[b]) { polygon[b].setEditable(false); polygon[b].setMap(null); }
      polygon[b] = e.overlay;
      polygon[b].setEditable(true);

      // ya no estamos dibujando, dejamos solo editar
      drawingManager[b].setDrawingMode(null);

      // path y listeners de edición
      polyPath[b] = polygon[b].getPath();
      detachPathListeners(b);
      pathSetAt[b]    = google.maps.event.addListener(polyPath[b], 'set_at',    function(){ fillPolygonInputs(b); });
      pathInsertAt[b] = google.maps.event.addListener(polyPath[b], 'insert_at', function(){ fillPolygonInputs(b); });
      pathRemoveAt[b] = google.maps.event.addListener(polyPath[b], 'remove_at', function(){ fillPolygonInputs(b); });

      // guardar inputs (toma los primeros 4 vértices)
      fillPolygonInputs(b);
    });
  }

  function setPolygonMode(b, enabled){
    if (enabled) attachPolygon(b);
    else attachRadio(b);
  }

  // ===== MAPAS =====
  function initMap(){
    initSingleMap('1', 'map',  'address',  'latitud',  'longitud',  'radio');
    initSingleMap('2', 'map1', 'address1', 'latitud1', 'longitud1', 'radio1');

    // modo inicial: Radio
    setPolygonMode('1', false);
    setPolygonMode('2', false);
  }

  function initSingleMap(bloque, mapId, addressId, latId, lngId, radiusId){
    map[bloque] = new google.maps.Map(document.getElementById(mapId), {
      center: guayaquil, zoom: 16, scrollwheel: true, mapTypeId: "OSM", mapTypeControl: true, streetViewControl: true
    });

    // OSM tiles
    map[bloque].mapTypes.set("OSM", new google.maps.ImageMapType({
      getTileUrl: function(coord, zoom) {
        var tilesPerGlobe = 1 << zoom;
        var x = coord.x % tilesPerGlobe; if (x < 0) x = tilesPerGlobe + x;
        return "https://tile.openstreetmap.org/" + zoom + "/" + x + "/" + coord.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256), name: "OSM", maxZoom: 18
    }));

    geocoder[bloque] = new google.maps.Geocoder();
    var address = document.getElementById(addressId);
    var searchBox = new google.maps.places.SearchBox(address);
    map[bloque].controls[google.maps.ControlPosition.TOP_LEFT].push(address);

    // Radio (marker + circle + markerRadius)
    marker[bloque] = new google.maps.Marker({ map: map[bloque], position: guayaquil, title: 'Punto de control' });
    circle[bloque] = new google.maps.Circle({
      map: map[bloque], center: guayaquil, radius: 0,
      strokeColor: '#FF0000', strokeOpacity: 0.8, strokeWeight: 2,
      fillColor: '#FF0000', fillOpacity: 0.35
    });
    var icon = "{{ asset('/images/radius.png') }}";
    markerRadius[bloque] = new google.maps.Marker({ map: map[bloque], position: guayaquil, draggable: true, title: 'Radio', icon: icon });

    // Polígono (se gestiona al entrar a modo polígono)
    polygon[bloque] = null; polyPath[bloque] = null;

    // Buscar por lugar
    searchBox.addListener('places_changed', function () {
      var places = searchBox.getPlaces(); if (!places || places.length === 0) return;
      var location = places[0].geometry.location;
      map[bloque].setCenter(location);
      circle[bloque].setCenter(location);
      marker[bloque].setPosition(location);
    });

    // Eventos del radio
    google.maps.event.addListener(marker[bloque], 'position_changed', function(){ updateMarkerRadius(bloque); });
    google.maps.event.addListener(markerRadius[bloque], 'position_changed', function(){ update(bloque); });

    // Resize
    google.maps.event.addDomListener(window, "resize", function() {
      var center = map[bloque].getCenter();
      google.maps.event.trigger(map[bloque], "resize");
      map[bloque].setCenter(center);
    });
  }

  // ===== RADIO helpers =====
  function updateMarkerRadius(bloque){
    var latId = (bloque==='1' ? 'latitud' : 'latitud1');
    var lngId = (bloque==='1' ? 'longitud' : 'longitud1');
    var pos = marker[bloque].getPosition();
    markerRadius[bloque].setPosition(pos);
    var latEl = document.getElementById(latId), lngEl = document.getElementById(lngId);
    if (latEl) latEl.value = pos.lat(); if (lngEl) lngEl.value = pos.lng();
  }
  function update(bloque){
    if (!google.maps.geometry) return;
    var d = google.maps.geometry.spherical.computeDistanceBetween(marker[bloque].getPosition(), markerRadius[bloque].getPosition());
    circle[bloque].setRadius(d);
    var id = (bloque==='1' ? 'radio' : 'radio1'); var el = document.getElementById(id);
    if (el) el.value = d;
  }

  // ===== SWITCH tipo_mar (tu UI) =====
  $(document).ready(function(){
    $("input[name='tipo_mar']").on("change", function () {
      var isPolygon = $(this).val() === "2";

      // tu toggle de UI (mantén si ya lo tienes)
      $("#div_lan_lon_radio1, #div_lan_lon_radio2").toggle(!isPolygon);
      $("#contenedor-poligono1, #contenedor-poligono2").toggle(isPolygon);

      setPolygonMode('1', isPolygon);
      setPolygonMode('2', isPolygon);
    });
  });
</script>



<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry,drawing&callback=initMap" async defer></script>

<script>
  // ===== Resto de utilitarios que ya tenías =====
  $(function() {  
    // MT2500 checkbox para tab 1
    $('#otro').click(function () {
      var checked = $(this).is(':checked');
      $('#entrada').prop('readonly', !checked);
      $('#salida').prop('readonly', !checked);
      if (!checked) { $('#entrada').val(null); $('#salida').val(null); }
    });

    // MT2500 checkbox para tab 2
    $('#otro1').click(function () {
      var checked = $(this).is(':checked');
      $('#entrada1').prop('readonly', !checked);
      $('#salida1').prop('readonly', !checked);
      if (!checked) { $('#entrada1').val(null); $('#salida1').val(null); }
    });
  });

  // Actualiza radio desde input
  function updateRadius(bloque='1') {
    var radiusInput = document.getElementById(bloque==='1' ? 'radio' : 'radio1');
    if (radiusInput && radiusInput.value !== '') {
      circle[bloque].setRadius(parseFloat(radiusInput.value));
    }
  }

  // Actualiza posición del marcador desde inputs
  function updateMarkerPosition(bloque='1') {
    var latInput = document.getElementById(bloque==='1' ? 'latitud' : 'latitud1');
    var lngInput = document.getElementById(bloque==='1' ? 'longitud' : 'longitud1');
    if (latInput.value !== '' && lngInput.value !== '') {
      var pos = { lat: parseFloat(latInput.value), lng: parseFloat(lngInput.value) };
      marker[bloque].setPosition(pos);
      circle[bloque].setCenter(marker[bloque].getPosition());
      map[bloque].setCenter(marker[bloque].getPosition());
      update(bloque);
    }
  }

  function mostrar() { document.form_search.submit(); }

  $('#form').on('shown.bs.modal', function () {
    if(map['1']) {
      google.maps.event.trigger(map['1'],'resize');
      map['1'].setCenter(marker['1'].getPosition());
    }
  });

  var id_cooperativa = null, is_bloques = false;
  @if(isset($id_cooperativa)) id_cooperativa='{{$id_cooperativa}}'; @endif
  @if(isset($is_bloques))     is_bloques='{{$is_bloques}}';         @endif

  function guardar() {
    var bloques = $('#cooperativa_id').find('option:selected').data('bloques');
    if (actual_id == null) {
      if (bloques === true || bloques === "true" || bloques == 1) {
        guardarPuntosControl('{{ url('/puntos-de-control-bloques') }}','{{$tipo_usuario_valor}}',id_cooperativa);
      } else {
        crearPuntoControl('{{ url('/puntos-de-control') }}','{{$tipo_usuario_valor}}',id_cooperativa);
      }
    } else {
      if (bloques === true || bloques === "true" || bloques == 1) {
        actualizarPuntoControlBloq('{{ url('/puntos-de-control-bloques') }}/' + actual_id,'{{$tipo_usuario_valor}}',id_cooperativa);
      } else {
        actualizarPuntoControl('{{ url('/puntos-de-control') }}/' + actual_id,'{{$tipo_usuario_valor}}',id_cooperativa);
      }
    }
  }
</script>

@endsection

