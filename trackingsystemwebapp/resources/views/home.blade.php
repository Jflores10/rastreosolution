@extends('layouts.app')
@section('styles')
<style>
    #map
    {
        width : 100%;
        height : 700px;
    }
    #div-unidad li i { cursor: pointer; }
    #div-unidad li img { cursor: pointer; }
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
      #jsPanel-replacement-container, .jsPanel-minimized-box, .jsPanel-minimized-container {
            left : auto;
            right: 0;
        }
        .jsPanel .jsPanel-title {
            font-size: 15px;
        }
        .jsPanel .jsPanel-content{
            font-size: 15px;
}
</style>
@endsection
@section('title')
Dashboard
@endsection
@section('content')
<link href="css/speedometer.css" rel="stylesheet" type="text/css" />
<div class="clearfix"></div>
<!-- -->
<div class="row">
     <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <div class="col-lg-12 col-md-12 col-sm-12">
                                        <form name="form_coop" method="GET" action="{{ url('/homeUniCoop') }}" id="form_coop">
                                            {{ csrf_field() }}
                                            <div class="form-group" id="div-cooperativa">
                                                <label>Cooperativa</label>
                                                <select type="submit" class="form-control" name="cooperativa" id="cooperativa" onchange="this.form.submit();">
                                                    @if(Auth::user()->tipo_usuario->valor==1)
                                                    <option value="" disabled selected hidden>Seleccione...</option>
                                                    @endif
                                                    @foreach ($cooperativas as $cooperativa)
                                                        <option value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if(Auth::user()->tipo_usuario->valor!=1)
                                                <i id="i_cooperativa" class="fa fa-globe" style="color:#2a62bc"></i>
                                                <label> {{ $cooperativa->descripcion }} </label>
                                            @endif
                                        </form>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input type="text" name="consulta" id="consulta" class="form-control" onkeypress="return runScript(event)" placeholder="Buscar unidad"/>
                                                <span class="input-group-btn">
                                                <button class="btn btn-primary" type="button" onclick="searchUnidad(true);" /><i class="fa fa-search"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                </div>
                                <div class="col-lg-12">
                                <label>Cantidad: </label><span id="cantidad">0</span>&nbsp&nbsp
                                <i class="fa fa-bus" style="color:#00AA88"></i>&nbsp:&nbsp<span id="cantidad_movimiento">0</span>&nbsp&nbsp
                                <i class="fa fa-bus" style="color:#F44336"></i>&nbsp:&nbsp<span id="cantidad_stop">0</span>&nbsp&nbsp
                                <i class="fa fa-bus" style="color:#f49a16"></i>&nbsp:&nbsp<span id="cantidad_e">0</span>&nbsp&nbsp
                                <i class="fa fa-bus" style="color:#990073"></i>&nbsp:&nbsp<span id="cantidad_no">0</span>&nbsp&nbsp
                                @if(Auth::user()->tipo_usuario->valor == 1)
                                    &nbsp&nbsp&nbsp&nbsp<button class="btn btn-info btn-link btn-sm" type="button" onclick="verLogsTramas();"><i class="fa fa-table"></i><span>&nbsp&nbspVer tramas</span></button>
                                @endif 
                                </div>
                                    <div class="col-lg-12 col-md-12 col-sm-12" id="div-mensaje">
                                    </div>
                                    <div class="col-lg-12 col-md-12 col-sm-12" id="div-unidad" style="height:47em;overflow: auto;">
                                        <ul class="list-group" id="ul_unidades">
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-8 col-sm-12">
                                <div class="col-lg-12 col-md-12 col-sm-12">
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" type="button" onclick="$('#progress').modal('show');playClick();"/><i class="fa fa-play"></i></button>
                                    </div>
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" type="button" onclick="pauseClick();"/><i class="fa fa-pause"></i></button>
                                    </div>
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" type="button" onclick="stopClick();" /><i class="fa fa-stop"></i></button>
                                    </div>
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" type="button" onclick="eraseClick();" /><i class="fa fa-eraser"></i></button>
                                    </div>
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" onclick="setOpciones();"  type="button" data-toggle="modal" data-target="#form" type="button"><i class="fa fa-cog"></i></button>
                                    </div>
                                    <div class="col-lg-1 col-md-1 col-sm-12">
                                        <button class="btn btn-info btn-xs" type="button" onclick="eraseClick();cargarTodasLasUnidades();" /><i class="fa fa-spinner"></i></button>
                                    </div>
                                    @if(Auth::user()->tipo_usuario->valor!=4)
                                        <div class="col-lg-1 col-md-1 col-sm-12">
                                            <button class="btn btn-info btn-xs" type="button" onclick="consultarEnLinea();" /><i class="fa fa-eye"></i></button>
                                        </div>
                                    @endif
                                    <div class="col-lg-5 col-md-5 col-sm-12">
                                        <select class="form-control" multiple data-placeholder="Ruta Unidad" name="ruta" id="ruta">
                                            @if (isset($rutas))
                                                @foreach ($rutas as $ruta)
                                                    <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                       
                                         <!-- Ruta ATM -->
                                        @if (isset($atm) && $atm =='S')
                                            <div>
                                                <label>Ruta ATM</label>
                                                <select class="form-control control-panel__route" multiple data-placeholder="Ruta" name="ruta_atm" id="ruta_atm">
                                                    @if (isset($rutas_atm))
                                                        @foreach ($rutas_atm as $ruta)
                                                            <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-lg-5 col-md-5 col-sm-12">
                                        <select class="form-control" multiple data-placeholder="Rutas General" name="ruta_general" id="ruta_general">
                                            @if (isset($rutas))
                                                @foreach ($rutas as $ruta)
                                                    <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <br><br><br>
                                <input class="form-control" type="text" placeholder="Escriba una referencia..." id="address" name="address"/>
                                <div id="map"></div>
                                <div style='margin-top: -150px;'>
                                    <input id="velocimetro" style='display:none;'  />
                                    <br>
                                </div>
                            </div>
                        </div>
                        <label id="lbl_hora_mapa" style="text-size:14px;color:white;background:black;"></label>
                    </div>
                </div>                
            </div>
        </div>
     </div>
</div>


<div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Consola de comandos</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <label for="commandImei">IMEI</label>
                    <input type="text" readonly name="commandImei" id="commandImei" class="form-control" />
                </div>
                <div class="form-group">
                    <label for="commandMessage">Comando</label>
                    <textarea id="commandMessage" name="commandMessage" class="form-control" rows="3" placeholder="Ingrese el comando a enviar..."></textarea>
                </div>
                <div class="form-group text-center">
                    <button id="btnEnviar" type="button" class="btn btn-primary" style="margin-bottom: 15px;">
                        <i class="fa fa-paper-plane"></i> Enviar comando
                    </button>
                </div>
                <div class="btn-group btn-group-justified" role="group" aria-label="Comandos">
                    <a id="btnApagar" class="btn btn-danger" role="button">
                        <i class="fa fa-lock"></i> Bloquear Vehículo
                    </a>
                    <a id="btnEncender" class="btn btn-warning" role="button">
                        <i class="fa fa-unlock"></i> Desbloquear Vehículo
                    </a>
                    <a id="btnReset" class="btn btn-success" role="button">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </div>
            </div>
        </div>
      </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">
        <i class="fa fa-times"></i> Cerrar
      </button>
    </div>
    </div>
  </div>
</div>


<div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true" style="color: #fff;">&times;</span>
          </button>
          <h4 class="modal-title" id="logsModallLabel">
            <i class="fa fa-list-alt"></i> Logs de tramas
          </h4>
        </div>
        <div class="modal-body" style="background: #f7f7f7;">
          <div class="row mb-3">
            <div class="col-md-9 col-sm-12">
              <input type="text" id="logsContent" name="logsContent" class="form-control" placeholder="Buscar contenido..." />
            </div>
            <div class="col-md-3 col-sm-12">
              <button onclick="getLogsTramas()" type="button" id="btnBuscarLogs" class="btn btn-success btn-block">
                <i class="fa fa-search"></i> Buscar
              </button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-12">
              <textarea disabled id="logsTramas" name="logsTramas" class="form-control" rows="25" style="background: #222; color: #b9f1c0; font-family: monospace; resize: vertical;"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #f1f1f1;">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cerrar
          </button>
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
                <h4 class="modal-title" id="modalLabel">Opciones del reproductor</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="form-group" id="div-unidades-reproductor">
                            <label>Unidad</label>
                            <select  class="form-control" name="unidad_reproductor" id="unidad_reproductor">
                            </select>
                        </div>
                       <!-- <div class="form-group" id="div-rutas-reproductor">
                            <label>Ruta</label>
                            <select class="form-control" name="ruta_reproductor" id="ruta_reproductor">
                            </select>
                        </div>-->
                        <div class="form-group" id="div-velocidad-reproductor">
                            <label>Velocidad de reproducción</label>
                            <select class="form-control" name="velocidad_reproductor" id="velocidad_reproductor">
                                <option value="L">Lento</option>
                                <option value="N">Normal</option>
                                <option value="R">Rápido</option>
                                <option value="MR">Muy rápido</option>
                            </select>
                        </div>
                        <div class="form-group" id="div-velocidad-reproductor">
                            <label>Auto-centrado</label><br/>
                            <input name="autocentrado"  id="autocentrado_si" type="radio" value="si"/>Si
                            <input name="autocentrado"  id="autocentrado_no" type="radio" value="no" checked/>No
                        </div>
                        <div class="form-group">
                            <label for="evento">Evento</label>
                            <select name="evento" id="evento" class="form-control">
                                <option disabled>Seleccione un evento...</option>
                                <option selected value="T">Todos</option>
                                <option value="GTFRI">Normal (GV300)</option>
                                <option value="GPRMC">Normal (MT2500)</option>
                                <option value="GTGEO">Puntos de control</option>
                                <option value="GTDIS">Puertas</option>
                                <option value="GTSOS">Pánico</option>
                                <option value="GTIGF">Desconexion Dispositivo</option>
                                <option value="GTIGN">Conexion Dispositivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="form-group" id="div-fecha-consulta-reproductor">
                            <label>Fecha de consulta</label>
                            <select class="form-control" name="fecha_consulta_reproductor" id="fecha_consulta_reproductor" onchange="cambioFechas(this.value);">
                                <option value="P">Personalizado</option>
                                <option value="H">Hoy</option>
                                <option value="A">Ayer</option>
                            </select>
                        </div>
                        <div class="form-group" id="div-fecha-inicio-reproductor">
                            <label>Desde</label>
                            <input name="fecha_inicio" autocomplete="off" autocorrect="off" id="fecha_inicio" class="form-control" type="text" />
                        </div>
                        <div class="form-group" id="div-fecha-fin-reproductor">
                            <label>Hasta</label>
                            <input name="fecha_fin" autocomplete="off" autocorrect="off" id="fecha_fin" class="form-control" type="text" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-check"></i> Aceptar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bitacoraModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Bitacoras</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12" >
                <div style="max-height:350px;" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>Descripción</th>
                                <th>Fecha Inicio</th>
                                <th>Tipo</th>
                                <th>Usuario Creador</th>
                                <th>Usuario Modificador </th>
                            </tr>
                        </thead>
                        <tbody id="tbody_bitacora">
                            <tr>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
<script src="js/speedometer.js"></script>
<script>
    function seleccionarTodos(seleccionar) {
        let check = seleccionar;
        let unidades = $('#uNotificaciones');
        unidades.children().each(function (index, el) {
            el.selected = check;
        });
        unidades.trigger('chosen:updated');
        unidades.trigger('change');

        if(check)
            $('#uNotificaciones_chosen').hide();
        else
            $('#uNotificaciones_chosen').show();
    }

    var nuevasNotificaciones = 0;
    var panelAbierto = true;
    var panelNotificaciones = jsPanel.create({
        theme : 'primary',
        headerTitle : 'NOTIFICACIONES',
        position : 'right-bottom 0 15 right',
        contentSize : '450 450',
        content : '<div class="container-fluid"><div class="row"><div class="col-sm-12"><div class="form-group"><div class="checkbox"><label for="uSeleccionar"><input onchange="seleccionarTodos(this.checked);" type="checkbox" id="uSeleccionar"/> <span>Todas las unidades</span></label></div><select class="form-control" id="uNotificaciones" data-placeholder="Seleccione una o varias unidades..." multiple></select></div></div><div class="col-sm-12"><ul class="list-group" id="notificaciones"></ul></div></div></div>',
        closeOnEscape : false,
        headerControls : {
            close : 'remove',
            maximize : 'remove'
        }
    });
    document.addEventListener('jspanelminimized', function (event) {
        panelAbierto = false;
    });
    document.addEventListener('jspanelnormalized', function (event) {
        panelAbierto = true;
        nuevasNotificaciones=0;
        panelNotificaciones.setHeaderTitle('NOTIFICACIONES');
    });

    $('#uNotificaciones').chosen({
        width : '100%'
    }).change(function () {
        let ids = $(this).val();
        let notificaciones = $('#notificaciones');
        if (ids !== null) {
            notificaciones.children().each(function (index, element) {
                $(element).hide();
            });
            if (ids.length > 0) {
                for (let i = 0; i < ids.length; i++) {
                    notificaciones.children().each(function (index, element) {
                        if (element.id.indexOf(ids[i]) !== -1)
                            $(element).show();
                    });
                }
            }
            else 
                notificaciones.children().each(function (index, element) {
                    $(element).show();
                });
        }
        else 
            notificaciones.children().each(function (index, element) {
                $(element).show();
            });
    });
</script>
    <script>

$("#velocimetro").myfunc({divFact:10});
    /*window.onload = function () { 
        $('#menu_toggle').trigger('click');
    }*/

    function velocimetro_change(velocidad){
        //console.log(velocidad);
        var velo = document.getElementById('velocimetro');
        velo.value=velocidad;
        $('#velocimetro').trigger('change');
    }

    function consultarEnLinea()
    {
        var win = window.open('{{ url('en-linea') }}', '_blank');
        win.focus();
    }
    var unidad_cargada=false;
    var line;
    var line_2;
    var polyline;
    var polyline2;
    var polyline_general;
    var polyline2_general;
    var map;
    var circleMap=[];
    var icon;

    var rutas_ids=[];
    var playing = true;
    var currentIndex = 0;
    var processes = [];
    var estaReproduciendo=false;
    var array_marcas=[];
    var array_tiposMarcas=[];   
    var zoomUnidad=false;    
    var zoomUnidadID;

    function cambioFechas(opcion)
    {
        var div_inicio = document.getElementById('div-fecha-inicio-reproductor');
        var div_fin = document.getElementById('div-fecha-fin-reproductor');

        if(opcion == 'P')
        {
            div_inicio.style="";
            div_fin.style="";
        }
        else
        {
            div_inicio.style="display:none;";
            div_fin.style="display:none;";
        }
    }
    
    function getRuta(ruta_id)
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
            }
        }, "json");
    }
    
    function getHistoricoUnidad(unidad_id)
    {
        var opcion_fecha = document.getElementById('fecha_consulta_reproductor').value;
        var fecha_inicio = document.getElementById('fecha_inicio').value;
        var fecha_fin = document.getElementById('fecha_fin').value;
        var evento = document.getElementById('evento').value;
        var autocentrado_si =document.getElementById('autocentrado_si');
        var velocidad_reproductor = document.getElementById('velocidad_reproductor').value;
        var autocentrado;

        if(autocentrado_si.checked==true)
            autocentrado=true;
        else
            autocentrado=false;

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
                reproducir_recorrido(data.recorrido,autocentrado,velocidad_reproductor);
            }
            else
                alert('Error al reproducir recorrido. Verifique que las fechas esten ingresadas correctamente.');
            $('#progress').modal('hide');  
        }, "json");
    }
    function reproducir_recorrido(recorrido,autocentrado,velocidad_reproductor)
    {
        var posicion;
        var velocidad;

        switch(velocidad_reproductor)
        {
            case 'L':velocidad=1500;break;
            case 'N':velocidad=750;break;
            case 'R':velocidad=335;break;
            case 'MR':velocidad=100;break;
            default:velocidad=750;break;
        }
        if(recorrido.length>0)
        {
            estaReproduciendo=true;
            
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
                    addMarkerWithTimeout(posicion, velocidad * (i - currentIndex), recorrido[i].angulo, autocentrado, recorrido[i].velocidad, i,recorrido[i].fecha, recorrido[i]);
                    velocimetro_change(recorrido[i].velocidad);
            }
            
        }
        else
            alert("No se encontró ningún recorrido.");
    }

    function addMarkerWithTimeout(posicion, timeout, angulo, autocentrado, velocidad, index, fecha, recorrido)
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
                    //console.log(recorrido);
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
                    '<li><strong>C. Total:</strong>&nbsp'+recorrido.contador_total+'</li>' +
                    '<li><strong>C. Diario:</strong>&nbsp'+recorrido.contador_diario+'</li>' +
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

    function eraseClick()
    {
        zoomUnidad=false;
        zoomUnidadID=null;

        for (var i = 0; i < unidadRecorridos.length; i++)
            unidadRecorridos[i].setMap(null);
        for(var i=currentIndex;i>=0;i--)
        {
            if (array_marcas[i] != null)
                array_marcas[i].setMap(null);
        }
    }
    function playClick()
    {
        var unidad_id = document.getElementById('unidad_reproductor').value;
        var fecha_inicio = document.getElementById('fecha_inicio').value;
        var fecha_fin = document.getElementById('fecha_fin').value;fecha_consulta_reproductor
        var fecha_consulta_reproductor = document.getElementById('fecha_consulta_reproductor').value;

        
        //var ruta_id = document.getElementById('ruta_reproductor').value;
        playing = true;
        if(unidad_id != '' && fecha_consulta_reproductor =='P'){
            if(unidad_id != ''  && fecha_inicio != ''  && fecha_fin != '')
            {
                getHistoricoUnidad(unidad_id);
            }else{
                $('#progress').modal('hide');  
                alert('Configure la consulta de recorrido a buscar');
            }
        }else{
            if(unidad_id != '')
            {
                getHistoricoUnidad(unidad_id);
            }else{
                $('#progress').modal('hide');  
                alert('Configure la consulta de recorrido a buscar');
            }
        }   
    }

    function pauseClick()
    {
        playing = false;
        clearProcesses();
    }
    
    function stopClick()
    {
        playing = false;
        eraseClick();
        currentIndex = 0;
        clearProcesses();            
        array_marcas=[];     
        array_tiposMarcas=[];           
        //playClick();
    }

    function clearProcesses()
    {
        for (var j = 0; j < processes.length; j++)
                clearTimeout(processes[j]);
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
    function setOpciones()
    {
        if(!unidad_cargada)
        {
            llenarUnidadesReproductor();
            //llenarRutaReproductor();
            unidad_cargada=true;
        }
    }
    function llenarUnidadesReproductor()
    {
        var url='{{url('/historicos')}}';
        var cooperativa_id_actual=document.getElementById('cooperativa').value;

        var div_unidad=  $('#div-unidades-reproductor');
        div_unidad.empty();

        $.post(url, {
            cooperativa_id:cooperativa_id_actual,
            opcion:'getUnidades'
        }, function( data ) {
            div_unidad.append(
                    '<label for="unidad_reproductor">Unidad</label>'+
                    '<select class="form-control" id="unidad_reproductor" name="unidad_reproductor">'+
                    '<option value="" disabled selected hidden>Seleccione...</option>'+
                    '</select>'+
                    '<span class="help-block" id="span_unidad_reproductor"></span>'
            );
            var select=$('#unidad_reproductor');
            for (var i = 0, len = data.unidades.length; i < len; i++)
                select.append('<option  value=\''+ data.unidades[i]._id + '\'> '+  data.unidades[i].descripcion +'</option>');
            $('#unidad_reproductor').chosen({ width : '100%' });
        }, "json");
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

    var guayaquil = {lat: -2.1775151734461176, lng: -79.91094589233398};

    function initMap() {
        // map = new google.maps.Map(document.getElementById('map'), {
        //     center: guayaquil,
        //     scrollwheel: true,
        //     zoom: 13
        // });

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
        var address = document.getElementById('address');
        var searchBox = new google.maps.places.SearchBox(address);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(address);
        searchBox.addListener('places_changed', function () {
            var places = searchBox.getPlaces();
            if (places.length == 0) 
                return;
            places.forEach(function(place) {
                if (place.geometry) {
                    var location = place.geometry.location;
                    map.setCenter(location);
                }
            });
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

        var label_hora_mapa = document.getElementById("lbl_hora_mapa");
        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(label_hora_mapa);
        $("#lbl_hora_mapa").text("");

        @if(isset($id_coop))
        //console.log('coop');
          document.getElementById('cooperativa').value='{{$id_coop}}';
	      setUnidadesOnMap('{{$id_coop}}',true);
		  setInterval(setUnidadesOnMap,30000,'{{$id_coop}}');
        @endif

        @if(Auth::user()->tipo_usuario->valor==2 || Auth::user()->tipo_usuario->valor==3 || Auth::user()->tipo_usuario->valor==4
        || Auth::user()->tipo_usuario->valor==5)
		   setUnidadesOnMap('',true);
          setInterval(setUnidadesOnMap,30000,null);
        @endif

        @if(Auth::user()->tipo_usuario->valor != 1)
           document.getElementById('div-cooperativa').style="display:none;";
        @endif

        polyline = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#2ecc71',
            strokeOpacity: 1.0,
            strokeWeight: 4
            });
        polyline2 = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#fff',
            strokeOpacity: 1.0,
            strokeWeight: 2
            });

        polyline_general = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#2ecc71',
            strokeOpacity: 1.0,
            strokeWeight: 4
            });
        polyline2_general = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#fff',
            strokeOpacity: 1.0,
            strokeWeight: 2
            });

        var icono_bus = {
        url: '{{url("/images/autobu.png")}}',
        scale: 1,
        labelOrigin: new google.maps.Point(4, 25)
        };
        var icono_flecha;
        var ii,jj;

        map.addListener('zoom_changed', function() {
            if(map.getZoom() <=13 ) 
                    for(ii=0;ii<array_marcador.length;ii++)                        
                    array_marcador[ii].setIcon(icono_bus);
            else
            {
                if(map.getZoom()>13)
                {
                    for(ii=0;ii<array_marcador.length;ii++)
                        {
                            for(jj=0;jj<array_marcador_angulos.length;jj++)
                            {
                                if(array_marcador_angulos[jj].indice==ii)
                                {
                                    icono_flecha= {
                                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                                            scale: 2,
                                            strokeColor: 'red',
                                            strokeOpacity: 2.0,
                                            strokeWeight: 4,
                                            rotation:array_marcador_angulos[jj].rotacion,
                                            labelOrigin: new google.maps.Point(1, 10)
                                    }
                                }
                            }
                            array_marcador[ii].setIcon(icono_flecha);
                        }

                }
            }

            if(map.getZoom() >= 15 && map.getZoom() < 18){
                polyline.setOptions({strokeWeight: 13});
                polyline2.setOptions({strokeWeight: 2});
                for(i=0;i<arrayPoly.length;i++){
                    arrayPoly[i].setOptions({strokeWeight: 13});
                    arrayPoly2[i].setOptions({strokeWeight: 2});
                }
               // polyline_general.setOptions({strokeWeight: 13});
                //polyline2_general.setOptions({strokeWeight: 2});
            }
            else{
                if(map.getZoom() >= 18){
                    if(map.getZoom() > 18){
                        polyline.setOptions({strokeWeight: 20});
                        polyline2.setOptions({strokeWeight: 3});
                        for(i=0;i<arrayPoly.length;i++){
                            arrayPoly[i].setOptions({strokeWeight: 20});
                            arrayPoly2[i].setOptions({strokeWeight: 3});
                        }
                       // polyline_general.setOptions({strokeWeight: 20});
                       // polyline2_general.setOptions({strokeWeight: 3});
                    }
                }
            }
        }); 

        $('#menu_toggle').trigger('click');       
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

    function setMarcadorUnidad(unidad,fecha_gps_,fecha_servidor,_is)
    {
        var estado;
        switch(unidad.estado_movil)
        {
            case "M":estado="En movimiento";break;
            case "D":estado="Detenido";break;
            case "E":estado="Perdida de GPS";break;
            default:estado="-";break;
        }

        if( estado =="-")
        {
            if(parseFloat(unidad.velocidad_actual)==0)
                estado="Detenido";
            else
                estado="En movimiento";
        }
        var fecha_gps;
        var fecha;
        if( fecha_gps_!=null && _is==0)
          fecha_gps =new Date(fecha_gps_.fecha_gps.date).format('d-m-Y H:i:s');
        

        if( fecha_servidor!=null && _is==0)
          fecha =new Date(fecha_servidor.fecha_servidor.date).format('d-m-Y H:i:s');
        
        if(_is==1){
            fecha=fecha_servidor;
            fecha_gps=fecha_gps_;
        }
        

        var html =// '<div class="panel">'+
               // '<div class="panel-heading"><h3>'+unidad.descripcion+'</h3></div>'+
                '<div class="panel-body"  style="height:12em;overflow: auto;margin: 2px; padding: 2px; ">'+
                '<ul  style="list-style-type: none; margin: 3px; padding: 3px;overflow: auto;width=100px;overflow-y: hidden;">'+
                '<li><strong>Disco:</strong>&nbsp'+unidad.descripcion+'</li>' +
                '<li><strong>Placa:</strong>&nbsp'+unidad.placa+'</li>' +
                '<li><strong>Velocidad:</strong>&nbsp'+unidad.velocidad_actual+' km/h'+'</li>' +
                '<li><strong>Voltaje:</strong>&nbsp'+unidad.voltaje+' v'+'</li>' +
                '<li><strong>Mileage:</strong>&nbsp'+unidad.mileage+' km'+'</li>' +
                '<li><strong>C. Total:</strong>&nbsp'+unidad.contador_total+'</li>' +
                '<li><strong>C. Diario:</strong>&nbsp'+unidad.contador_diario+'</li>' +
                '<li><strong>C. Total 2:</strong>&nbsp'+((unidad.contador_total_sensor_2 != undefined)?unidad.contador_total_sensor_2:'-')+'</li>' +
                '<li><strong>C. Diario 2:</strong>&nbsp'+((unidad.contador_diario_sensor_2 != undefined )?unidad.contador_diario_sensor_2:'-')+'</li>' +
                '<li><strong>C. Total 3:</strong>&nbsp'+((unidad.contador_total_sensor_3 != undefined)?unidad.contador_total_sensor_3:'-')+'</li>' +
                '<li><strong>C. Diario 3:</strong>&nbsp'+((unidad.contador_diario_sensor_3 != undefined )?unidad.contador_diario_sensor_3:'-')+'</li>' +
                '<li><strong>Estado:</strong>&nbsp'+estado+'</li>' +
                '<li><strong>Fecha de servidor:</strong>&nbsp'+'<br/>'+fecha+'</li>' +
                '<li><strong>Fecha de GPS:</strong>&nbsp'+'<br/>'+fecha_gps+'</li>' +
                '</ul>'+
                '<div class="form-group">'+
                '<button onclick="openCommandForm(\'' + unidad.imei + '\');velocimetro_change('+unidad.velocidad_actual+');" class="btn btn-primary btn-block">Consola de comando</button>'
                '</div>'+
               // '</div>'+
                '</div>';
        if ((currentUnidad == null || currentUnidad == unidad._id) && unidad.latitud != undefined && unidad.longitud != undefined)
	        addMarker(html, unidad.latitud,
	                unidad.longitud,
	                unidad._id,
	                unidad.angulo,
                    (unidad.orden != null)?unidad.descripcion + '(' + unidad.orden + ')':unidad.descripcion,
                    unidad.velocidad_actual
            );
        else if (unidad.latitud == undefined && unidad.longitud == undefined) 
            alert('Esta unidad no tiene coordenadas registradas.');
    }
    function openCommandForm(imei)
    {
      $('#commandModal').modal('show');
      $('#commandImei').val(imei);
    }

    var logTramasInterval = null;

    function getLogsTramas() {
        let urlLogs = '{{url('/api/command/read-logs')}}';
        let logsContent = $('#logsContent').val();
        $.get(urlLogs, { content: logsContent }, function (data) {
            let stringTramas = data.tramas.map(function (item) {
                return item.created_at + ': ' + item.contenido;
            }).join('\n');
            $('#logsTramas').text(stringTramas);
        });
    }

    function verLogsTramas()
    {
        getLogsTramas();
        if (logTramasInterval != null) {
            clearInterval(logTramasInterval);
        }
        logTramasInterval = setInterval(function () {
            getLogsTramas();
        }, 10000);
        $('#logsModal').modal('show');
    }

    var unidadRecorridos = [];
    var currentUnidad = null;

    function selectUnidad(u,fecha_gps_,fecha_servidor,_is)
    {
        currentUnidad = u._id;
        zoomUnidad=true;
        zoomUnidadID=u._id;
        velocimetro_change(u.velocidad_actual);
        setMarcadorUnidad(u,fecha_gps_,fecha_servidor,_is);
    }

    function selectUnidad_GEOCODE(latitud,longitud)
    {
        var url="{{ url("/geocoding/reverse") }}?lat="+latitud+"&lon="+longitud;
			$.get(url, function (data) {
                $('#progress').modal('hide');
				alert(data.display_name);
			});
    }

    function selectUnidad_Bitacora(unidad_id){
       
        var url='{{ url("/bitacora/unidades") }}';

        param={
            unidad_id : unidad_id
        };
        $.post(url, param, function( data ) {

            var tbody=$('#tbody_bitacora');
            tbody.empty();
        
            for(var i=0;i<data.length;i++)
			{
                let url='';
                let fechaIni=new Date(data[i].fechaInicio).addHours(5);
                let tcreador='';
                if(data[i].creador!=null){
                    let tcreador=data[i].creador.name;

                }
                

                if(data[i].tipo_bitacora=='M')
                    url='/images/mantenimiento.png';
                else
                    if(data[i].tipo_bitacora=='R')
                        url='/images/police.png';
                    else   
                        url='/images/other.png';
                    
                tbody.append(
                    '<tr>'+
                    '<td>'+data[i].unidad.descripcion+'</td>'+
                    '<td>'+data[i].descripcion+'</td>'+
                    '<td>'+fechaIni.format('d-m-Y H:i:s')+'</td>'+
                    '<td><img width="30" height="30" src="'+url+'"/></td>'+
                    '<td>'+tcreador+'</td>'+
                    '<td>'+((data[i].modificador==null)?'--':data[i].modificador.name)+'</td>'+
                    '</tr>'
                );
            }

            $('#bitacoraModal').modal('show');
            $('#progress').modal('hide');
        },"json");
    }

    function setUnidadConteo(unidad_id){
       
       var url='{{ url("/unidad/conteo") }}';
       $('#progress').modal('show');
       param={
           unidad_id : unidad_id
       };
       $.post(url, param, function( data ) {
           $('#progress').modal('hide');
       },"json");
   }

    function runScript(e) {
        if (e.keyCode == 13) {
            searchUnidad(true);
            return false;
        }
    }

    function searchUnidad(modalLoad)
    {
        if(modalLoad)
            $('#progress').modal('show');
        var url = '{{url('/home')}}';
        var cooperativa_id=document.getElementById('cooperativa').value;
        var consulta=document.getElementById('consulta').value;
       /* var div_unidad=  $('#div-unidad');

        div_unidad.empty();*/

        $.post(url, {
            cooperativa:cooperativa_id,
            consulta:consulta
        }, function( data ) {
            if(modalLoad)
                $('#progress').modal('hide');
            appendUnidades(data);
        }, "json");
    }

    function llenarUnidades(opc)
    {
        if(opc==1)
        {
            for(var i=0;i<array_marcador.length;i++)
            {
                array_marcador[i].setMap(null);
            }
        }
        var id_cooperativa = document.getElementById('cooperativa').value;

        if(id_cooperativa!='')
        {
            var url = '{{url('/historicos')}}';
            var rutas = $('#ruta').val();
            var hayRutas = (rutas != null)?true:null;
            $.post(url, {
                cooperativa_id:id_cooperativa,
                opcion:'getUnidades',
                rutas_ids : rutas,
                hay_rutas : hayRutas
            }, function( data ) {
                appendUnidades(data);
            }, "json");
        }
    }
	function getMarkerById(id)
	{
		for(var i=0;i<array_marcador.length;i++)
		{
			if (array_marcador[i].getTitle() == id)
				return array_marcador[i];
		}
		return null;
	}
    function setUnidadesOnMap(coop,load)
    {
        if(load)
            $('#progress').modal('show');

		if(estaReproduciendo==false)
		{
			var id_cooperativa = document.getElementById('cooperativa').value;
			var url = '{{url('/historicos')}}';
             var param; 
            if(rutas_ids.length==0) 
            { 
                param={ 
                    cooperativa_id:id_cooperativa,
                    opcion:'getUnidades' 
                 }; 
            } 
            else 
            { 
                param={ 
                    cooperativa_id:id_cooperativa, 
                    opcion:'getUnidades', 
                    rutas_ids:rutas_ids, 
                    hay_rutas:true 
                }; 
            } 

            /*param={ 
                cooperativa_id:id_cooperativa, 
                opcion:'getUnidades' 
            }; */
            
			$.post(url, param, function( data ) {
                let consulta=$("#consulta").val();
                if(consulta =='')
                    appendUnidades(data);
                else
                    searchUnidad(false);

                if(load)
                 $('#progress').modal('hide');

				for(var j=0;j<data.unidades.length;j++)
				{
					if(data.unidades[j].latitud!=null && data.unidades[j].longitud!=null 
                        && data.unidades[j].latitud!=undefined && data.unidades[j].longitud!=undefined){
						setMarcadorUnidad(data.unidades[j],data.array_fechas[j],data.array_fechas[j],0);
                    }
				}

			}, "json");
		}
    }

    function llenarNotificaciones(unidades) {
        let uNotificaciones = $('#uNotificaciones');
        let listaNotificaciones = $('#notificaciones');
        let oldIds = uNotificaciones.val();
        uNotificaciones.empty();
        let date = null;
        let id;
        let exists;

        for (let i = 0; i < unidades.unidades.length; i++)
        {
            uNotificaciones.append('<option value="' + unidades.unidades[i]._id + '">' + unidades.unidades[i].descripcion + '</option>');
            let unidad = unidades.unidades[i];
            let notificaciones = unidades.notificaciones[i];

            if (notificaciones != null) {
                let notificacion = notificaciones;
                if(notificacion.ack_gtdat_fecha !== null && notificacion.ack_gtdat_message !== null && notificacion.ack_gtdat_trama !== null && notificacion.ack_gtdat_fecha !== undefined && notificacion.ack_gtdat_message !== undefined && notificacion.ack_gtdat_trama !== undefined)
                {
                    date = new Date(notificacion.ack_gtdat_fecha);
                    id = date.format('YmdHis') + unidad._id + 'ack';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><strong>' + unidad.descripcion + ':</strong> ' + notificacion.ack_gtdat_message + ' <i>' + notificacion.ack_gtdat_fecha + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_velocidad_fecha !== null && notificacion.alerta_velocidad_message !== null && notificacion.alerta_velocidad_fecha !== undefined && notificacion.alerta_velocidad_message !== undefined)
                {
                    id = notificacion.alerta_velocidad_fecha + unidad._id + 'velocidad';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-tachometer" style="color:#FF4545"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_velocidad_message + ' <i>' + notificacion.alerta_velocidad_fecha + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_puerta_fecha !== null && notificacion.alerta_puerta_message !== null && notificacion.alerta_puerta_fecha !== undefined && notificacion.alerta_puerta_message !== undefined)
                {
                    id = notificacion.alerta_puerta_fecha+ unidad._id + 'puerta';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        if(notificacion.alerta_puerta_message =='PUERTA ABIERTA (DELANTERA)')
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><img src="../images/opendoor.png" height="20" width="20">&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_puerta_message + ' <i>' + notificacion.alerta_puerta_fecha + '</i></li>');
                        else
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><img src="../images/closedoor.png" height="20" width="20">&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_puerta_message + ' <i>' + notificacion.alerta_puerta_fecha + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_puerta_fecha_trasera !== null && notificacion.alerta_puerta_message_trasera !== null && notificacion.alerta_puerta_fecha_trasera !== undefined && notificacion.alerta_puerta_message_trasera !== undefined)
                {
                    id = notificacion.alerta_puerta_fecha_trasera+ unidad._id + 'puerta';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        if(notificacion.alerta_puerta_message_trasera =='PUERTA ABIERTA (TRASERA)')
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><img src="../images/opendoor.png" height="20" width="20">&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_puerta_message_trasera + ' <i>' + notificacion.alerta_puerta_fecha_trasera + '</i></li>');
                        else
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><img src="../images/closedoor.png" height="20" width="20">&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_puerta_message_trasera + ' <i>' + notificacion.alerta_puerta_fecha_trasera + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_desconx_fecha !== null && notificacion.alerta_desconx_message !== null && notificacion.alerta_desconx_fecha !== undefined && notificacion.alerta_desconx_message !== undefined)
                {
                    id = notificacion.alerta_desconx_fecha + unidad._id + 'desconx';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        if(notificacion.alerta_desconx_message.includes('encendid'))
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-plug" style="color:#28F82B"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_desconx_message + ' <i>' + notificacion.alerta_desconx_fecha + '</i></li>');
                        else
                            listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-plug" style="color:#FF0000"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_desconx_message + ' <i>' + notificacion.alerta_desconx_fecha + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_gtgeo_fecha !== null && notificacion.alerta_gtgeo_message !== null && notificacion.alerta_gtgeo_fecha !== undefined && notificacion.alerta_gtgeo_message !== undefined)
                {
                    id = notificacion.alerta_gtgeo_fecha + unidad._id + 'gtgeo';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-compass" style="color:#F821D7"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_gtgeo_message + ' <i>' + notificacion.alerta_gtgeo_fecha + '</i></li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_panico_message !== null && notificacion.alerta_panico_fecha_message !== null &&
                notificacion.alerta_panico_message !== undefined && notificacion.alerta_panico_fecha_message !== undefined)
                {
                    id = notificacion.alerta_panico_fecha_message + unidad._id + 'panico_gv300';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        var fecha=new Date(notificacion.alerta_panico_fecha_message);
                        fecha.setMinutes( fecha.getMinutes() + 15 );
                        var hoy=new Date();
                        // console.log(notificacion.alerta_panico_fecha_message);
                        // console.log(fecha);
                        // console.log(hoy);
                        if(fecha>=hoy)
                            alert('ALERTA '+unidad.descripcion +' BOTÓN PÁNICO '+ notificacion.alerta_panico_fecha_message+' '+notificacion.alerta_panico_message);
                        
                        listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-exclamation-circle" style="color:#ff0000"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_panico_message + ' <i>' + notificacion.alerta_panico_fecha_message + '</i>'+
                        '</li>');
                        nuevasNotificaciones++;
                    }
                }
                if(notificacion.alerta_cortetubo !== null && notificacion.alerta_fecha_cortetubo !== null &&
                notificacion.alerta_cortetubo !== undefined && notificacion.alerta_fecha_cortetubo !== undefined)
                {
                    id = notificacion.alerta_fecha_cortetubo + unidad._id + 'cortetubo';
                    exists = document.getElementById(id);
                    if (exists === null)
                    {
                        // var fecha=new Date(notificacion.alerta_fecha_cortetubo);
                        // fecha.setMinutes( fecha.getMinutes() + 15 );
                        // var hoy=new Date();
                        // // console.log(notificacion.alerta_panico_fecha_message);
                        // // console.log(fecha);
                        // // console.log(hoy);
                        // if(fecha>=hoy)
                        //     alert('ALERTA '+unidad.descripcion +' BOTÓN PÁNICO '+ notificacion.alerta_panico_fecha_message+' '+notificacion.alerta_panico_message);
                        
                        listaNotificaciones.prepend('<li id="' + id + '" class="list-group-item"><i class="fa fa-exclamation" style="color:#0000FF"></i>&nbsp&nbsp<strong>' + unidad.descripcion + ':</strong> ' + notificacion.alerta_cortetubo + ' <i>' + notificacion.alerta_fecha_cortetubo + '</i>'+
                        '</li>');
                        nuevasNotificaciones++;
                    }
                }
                if (!panelAbierto)
                    $('.jsPanel-title').text('NOTIFICACIONES (' + nuevasNotificaciones + ')');
                
            }
        }
        uNotificaciones.val(oldIds);
        uNotificaciones.trigger('chosen:updated');
        uNotificaciones.trigger('change');
    } 

    function appendUnidades(data)
    {
        var div_unidad=  $('#div-unidad');
        var div_mensaje=  $('#div-mensaje');
        var unidad_movimiento=0;
        var unidad_stop=0;
        var unidad_no=0;
        var unidad_e=0;
        var ul=$('#ul_unidades');

        if(data.unidades.length==0)
        {
            ul.empty();
            div_mensaje.empty();
            div_mensaje.append('<div class="alert alert-info"> <strong>No se encontraron resultados.</strong> </div>');
            $('#cantidad').text(0);
            $('#cantidad_no').text(0);
            $('#cantidad_movimiento').text(0);
            $('#cantidad_e').text(0);
            $('#cantidad_stop').text(0);
        }
        else
        {
          llenarNotificaciones(data);
          div_mensaje.empty();
          $('#cantidad').text(data.unidades.length);

            
            ul.empty();

            var geocoder = new google.maps.Geocoder;
            var ubication;           
            
            var fecha_gps;
            var fecha_puerta_abierta;
            var fecha_puerta_cerrada;
            var fecha_puerta_abierta_trasera;
            var fecha_puerta_cerrada_trasera;
            var voltaje;
            var estado;  
            var fecha_gps_marker;
            var fecha_servidor;
            var ruta_actual,ruta_fecha,ruta_conductor;      
            
            for (var i = 0, len = data.unidades.length; i < len; i++)
            {
                fecha_gps=' - ';
                voltaje=' - ';
                estado=data.unidades[i].estado_movil;

                if(data.array_fechas[i].fecha_gps!=null){
                    fecha_gps =new Date(data.array_fechas[i].fecha_gps.date).format('H:i:s');
                    fecha_gps_marker=new Date(data.array_fechas[i].fecha_gps.date).format('d-m-Y H:i:s');;
                }else{
                    fecha_gps_marker='-';
                }

                if(data.array_fechas[i].fecha_servidor!=null){
                    fecha_servidor =new Date(data.array_fechas[i].fecha_servidor.date).format('d-m-Y H:i:s');
                }else{
                    fecha_servidor='-';
                }


                fecha_puerta_abierta='--';
                fecha_puerta_cerrada='--';
                fecha_puerta_abierta_trasera='--';
                fecha_puerta_cerrada_trasera='--';

                ruta_actual='';
                ruta_actual=data.array_rutas[i].ruta_actual;
                ruta_fecha='';
                ruta_fecha=data.array_rutas[i].ruta_fecha;
                ruta_conductor='';
                ruta_conductor=data.array_rutas[i].ruta_conductor;
                
                ruta_hora_fin=data.array_rutas[i].ruta_hora_fin;

                //si ruta_conductor no es null, entonces solo capturar las dos primeras palabras
                if(ruta_conductor!=null && ruta_conductor!='')
                {
                    var arr = ruta_conductor.split(" ");
                    if(arr.length>2)
                        ruta_conductor=arr[0]+' '+arr[1];
                }

                if(data.array_formatted_address[i].formatted_address!=null)
                    ubication=data.array_formatted_address[i].formatted_address;
                
                if(data.array_fechas[i].fecha_puerta_abierta!=null){
                    var dia=new Date();
                    var final=new Date(data.array_fechas[i].fecha_puerta_abierta.date);
                // fecha_puerta_abierta=(new Date().getTime()- new Date(data.array_fechas[i].fecha_puerta_abierta.date).getTime());
                    var starthour = parseInt(dia.getHours());
                    var endhour = parseInt(final.getHours());
                    var startminutes = parseInt(dia.getMinutes());
                    var endminutes = parseInt(final.getMinutes());
                    var startsecond = parseInt(dia.getSeconds());
                    var endsecond = parseInt(final.getSeconds());

                    var timeDiff = Math.abs(dia.getTime() - final.getTime());
                    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24))-1; 
                    
                    if(diffDays==0){
                        fecha_puerta_abierta=Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }else{
                        fecha_puerta_abierta='D: '+diffDays+'  '+Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }
                
                    
                }

                if(data.array_fechas[i].fecha_puerta_abierta_trasera!=null){
                    var dia=new Date();
                    var final=new Date(data.array_fechas[i].fecha_puerta_abierta_trasera.date);
                // fecha_puerta_abierta=(new Date().getTime()- new Date(data.array_fechas[i].fecha_puerta_abierta.date).getTime());
                    var starthour = parseInt(dia.getHours());
                    var endhour = parseInt(final.getHours());
                    var startminutes = parseInt(dia.getMinutes());
                    var endminutes = parseInt(final.getMinutes());
                    var startsecond = parseInt(dia.getSeconds());
                    var endsecond = parseInt(final.getSeconds());

                    var timeDiff = Math.abs(dia.getTime() - final.getTime());
                    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24))-1; 
                    
                    if(diffDays==0){
                        fecha_puerta_abierta_trasera=Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }else{
                        fecha_puerta_abierta_trasera='D: '+diffDays+'  '+Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }
                
                    
                }
                
                if(data.array_fechas[i].fecha_puerta_cerrada!=null){                    
                    var dia=new Date();
                    var final=new Date(data.array_fechas[i].fecha_puerta_cerrada.date);
                    var starthour = parseInt(dia.getHours());
                    var endhour = parseInt(final.getHours());
                    var startminutes = parseInt(dia.getMinutes());
                    var endminutes = parseInt(final.getMinutes());
                    var startsecond = parseInt(dia.getSeconds());
                    var endsecond = parseInt(final.getSeconds());
                    
                    var timeDiff = Math.abs(dia.getTime() - final.getTime());
                    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24))-1; 
                    
                    if(diffDays==0){
                        fecha_puerta_cerrada=Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }else{
                        fecha_puerta_cerrada='D: '+diffDays+'  '+Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }
                }

                if(data.array_fechas[i].fecha_puerta_cerrada_trasera!=null){                    
                    var dia=new Date();
                    var final=new Date(data.array_fechas[i].fecha_puerta_cerrada_trasera.date);
                    var starthour = parseInt(dia.getHours());
                    var endhour = parseInt(final.getHours());
                    var startminutes = parseInt(dia.getMinutes());
                    var endminutes = parseInt(final.getMinutes());
                    var startsecond = parseInt(dia.getSeconds());
                    var endsecond = parseInt(final.getSeconds());
                    
                    var timeDiff = Math.abs(dia.getTime() - final.getTime());
                    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24))-1; 
                    
                    if(diffDays==0){
                        fecha_puerta_cerrada_trasera=Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }else{
                        fecha_puerta_cerrada_trasera='D: '+diffDays+'  '+Math.abs(parseInt(starthour-endhour)) + ':'+Math.abs(parseInt(startminutes-endminutes)) +
                        ':'+Math.abs(parseInt(startsecond-endsecond));
                    }
                }

                fecha_puerta_abierta='--';
                fecha_puerta_cerrada='--';
                fecha_puerta_abierta_trasera='--';
                fecha_puerta_cerrada_trasera='--';

                if(data.unidades[i].voltaje!=null)
                    voltaje= data.unidades[i].voltaje;

                    voltaje=voltaje.toString().substring(0,2);

                if(estado=='-')
                {
                    if(parseFloat(data.unidades[i].velocidad_actual)==0)
                        estado="D";
                    else
                        estado="M";
                }
                
                if(parseFloat(data.unidades[i].velocidad_actual)==0)
                    estado="D";
                else
                    estado="M";

                if(data.array_fechas[i].diferencia!=null) {
                    if (data.array_fechas[i].diferencia > 30)
                    {
                        estado = 'no_envia_trama';
                    }
                }

                if(data.array_fechas[i].fecha_gps==null)
                    estado = 'no_envia_trama';
                    
                var iId = 'i' + data.unidades[i]._id; 
                var gId = 'i' + data.unidades[i]._id; 
                var bId = 'i' + data.unidades[i]._id; 
                console.log(data.unidades[i].climatizada);
                switch(estado)
                {
                    case 'D':
                        unidad_stop++;
                        
                        ul.append(
                                '<li class="list-group-item" id=\''+ data.unidades[i]._id + '\'>'+
                                    ((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                    ((data.unidades[i].rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                '<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#F44336"></i>&nbsp&nbsp'+ 
                                data.unidades[i].descripcion+'&nbsp&nbsp('+fecha_gps+' | '+ data.unidades[i].velocidad_actual+' km/h)'+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#F44336"></i>&nbsp&nbsp'+voltaje+'v'
                                +'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#F44336"></i>&nbsp&nbsp'+data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'<i class="fa fa-globe" style="color:#00AA88"></i>':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'<i class="fa fa-globe" style="color:#00AA88"></i>')):'<i class="fa fa-globe" style="color:#00AA88"></i>')
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp'
                                @if(true)
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                                        ((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                @if(true)//Auth::user()->tipo_usuario->valor==1
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                                        ((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#F44336"></i>&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
                                +'&nbsp&nbsp<font color="black">('+ruta_fecha+')</font>-<font color="red">('+ruta_hora_fin+')</font>&nbsp&nbsp<font color="black">'+ruta_conductor+'</font>'
                                +((data.array_bitacora[i].bitacora !="")?('&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ data.unidades[i]._id+'\');" width="20" height="20" src="'+((data.array_bitacora[i].bitacora=="R")?'/images/police.png"':((data.array_bitacora[i].bitacora=="M")?'/images/mantenimiento.png"':((data.array_bitacora[i].bitacora=="O")?'/images/other.png"':'#"')))+'/>'):'&nbsp&nbsp&nbsp')
                                +'</li>'
                        );
                        break;

                            
                    case 'E':
                        unidad_e++;
                        ul.append(
                                '<li class="list-group-item" id=\''+ data.unidades[i]._id + '\'>'+
                                    ((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                    ((data.unidades[i].rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                '<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#f49a16"></i>&nbsp&nbsp'+ data.unidades[i].descripcion
                                +'&nbsp&nbsp('+fecha_gps+' | '+ data.unidades[i].velocidad_actual+' km/h)'+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#f49a16"></i>&nbsp&nbsp'+voltaje+'v'
                                +'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#f49a16"></i>&nbsp&nbsp'+data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'<i class="fa fa-globe" style="color:#00AA88"></i>':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'<i class="fa fa-globe" style="color:#00AA88"></i>')):'<i class="fa fa-globe" style="color:#00AA88"></i>')
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp'
                                @if(true)
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                                        ((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                @if(true)
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                                        ((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#f49a16"></i>&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
                                // +((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">':'')
                                +'&nbsp&nbsp<font color="black">('+ruta_fecha+')</font>-<font color="red">('+ruta_hora_fin+')</font>&nbsp&nbsp<font color="black">'+ruta_conductor+'</font>'
                                +((data.array_bitacora[i].bitacora !="")?('&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ data.unidades[i]._id+'\');" width="20" height="20" src="'+((data.array_bitacora[i].bitacora=="R")?'/images/police.png"':((data.array_bitacora[i].bitacora=="M")?'/images/mantenimiento.png"':((data.array_bitacora[i].bitacora=="O")?'/images/other.png"':'#"')))+'/>'):'&nbsp&nbsp&nbsp')
                                +'</li>'
                        );
                        break;

                    case 'M':
                        unidad_movimiento++;
                        ul.append(
                                '<li class="list-group-item" id=\''+ data.unidades[i]._id + '\'>'+
                                    ((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                    ((data.unidades[i].rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                '<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#00AA88"></i>&nbsp&nbsp'+ data.unidades[i].descripcion
                                +'&nbsp&nbsp('+fecha_gps+' | '+ data.unidades[i].velocidad_actual+' km/h)'+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#00AA88"></i>&nbsp&nbsp'+voltaje+'v'+'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#00AA88"></i>&nbsp&nbsp'
                                +data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'<i class="fa fa-globe" style="color:#00AA88"></i>':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'<i class="fa fa-globe" style="color:#00AA88"></i>')):'<i class="fa fa-globe" style="color:#00AA88"></i>')
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp'
                                @if(true)
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                                        ((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                @if(true)
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                                        ((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#00AA88"></i>&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
                                // +((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">':'')
                                +'&nbsp&nbsp<font color="black">('+ruta_fecha+')</font>-<font color="red">('+ruta_hora_fin+')</font>&nbsp&nbsp<font color="black">'+ruta_conductor+'</font>'
                                +((data.array_bitacora[i].bitacora !="")?('&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ data.unidades[i]._id+'\');" width="20" height="20" src="'+((data.array_bitacora[i].bitacora=="R")?'/images/police.png"':((data.array_bitacora[i].bitacora=="M")?'/images/mantenimiento.png"':((data.array_bitacora[i].bitacora=="O")?'/images/other.png"':'#"')))+'/>'):'&nbsp&nbsp&nbsp')
                                +'</li>'
                        );
                        break;



                    default:
                        unidad_no++;
                        ul.append(
                                '<li class="list-group-item" id=\'' + data.unidades[i]._id + '\'>' +
                                ((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                ((data.unidades[i].rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                '<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#990073"></i>&nbsp&nbsp' + data.unidades[i].descripcion 
                                + '&nbsp&nbsp(' + fecha_gps + ' | ' + data.unidades[i].velocidad_actual + ' km/h)' + '&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#990073"></i>&nbsp&nbsp' + voltaje + 'v' + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#990073"></i>&nbsp&nbsp' + data.unidades[i].contador_total 
                                + " | " + data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'<i class="fa fa-globe" style="color:#00AA88"></i>':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'<i class="fa fa-globe" style="color:#00AA88"></i>')):'<i class="fa fa-globe" style="color:#00AA88"></i>')
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp'
                                @if(true)
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                                        ((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta !== 'undefined')?((data.unidades[i].puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                @if(true)
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                                        ((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')    
                                @else 
                                    +((data.unidades[i].puerta_trasera !== 'undefined')?((data.unidades[i].puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">':((data.unidades[i].puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">':'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>')
                                @endif
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#990073"></i>&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
                                // +((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">':'')
                                +'&nbsp&nbsp<font color="black">('+ruta_fecha+')</font>-<font color="red">('+ruta_hora_fin+')</font>&nbsp&nbsp<font color="black">'+ruta_conductor+'</font>'
                                +((data.array_bitacora[i].bitacora !="")?('&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ data.unidades[i]._id+'\');" width="20" height="20" src="'+((data.array_bitacora[i].bitacora=="R")?'/images/police.png"':((data.array_bitacora[i].bitacora=="M")?'/images/mantenimiento.png"':((data.array_bitacora[i].bitacora=="O")?'/images/other.png"':'#"')))+'/>'):'&nbsp&nbsp&nbsp')
                                +'</li>'
                        );
                        break;
                }

                var currentLi = document.getElementById(iId);
                var currentU = data.unidades[i];
                var currentFechagps = fecha_gps_marker;
                var currentFecha = fecha_servidor;

                if (currentLi != null && currentLi != undefined)
                {
                    currentLi.currentU = currentU;
                    currentLi.currentFechagps = currentFechagps;
                    currentLi.currentFecha = currentFecha;
                    currentLi.onclick = function () {
                        selectUnidad(this.currentU,this.currentFechagps,this.currentFecha,1);
                        //velocimetro_change(data.unidades[i].velocidad_actual);
                    };
                }
            }
    
            $('#cantidad_no').text(unidad_no);
            $('#cantidad_movimiento').text(unidad_movimiento);
            $('#cantidad_e').text(unidad_e);
            $('#cantidad_stop').text(unidad_stop);
        }

    }
	function cargarTodasLasUnidades()
	{
		currentUnidad = null;
        @if(isset($id_coop))
		    setUnidadesOnMap('{{$id_coop}}',true);
        @endif
	}
    function addMarker(html, latitude, longitude, id, angulo, placa,velocidad)
    {
        var icon;
        var mk;
        if (zoomUnidad)
        {
            if(id == zoomUnidadID){
                for(var i=0;i<array_marcador.length;i++)
                {
                    array_marcador[i].setMap(null);
                }

                if(map.getZoom()<=13)
                    icon = {
                        url: '{{url("/images/autobu.png")}}',
                        scale: 1,
                        labelOrigin: new google.maps.Point(4, 25)
                    };
                else
                    icon = {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 2,
                        strokeColor: 'red',
                        strokeOpacity: 2.0,
                        strokeWeight: 4,
                        rotation:angulo,
                        labelOrigin: new google.maps.Point(1, 10)
                    };

               mk = new google.maps.Marker({
                    position : { lat: parseFloat(latitude), lng : parseFloat(longitude) },
                    map : map,
                    icon:icon,
                    label : {text : placa},
                    title : id,
                    animation : google.maps.Animation.DROP
                });
                array_marcador.push(mk);

                console.log('se puso el marcador');

                google.maps.event.clearInstanceListeners(mk);
                var indice_actual=array_marcador.length-1;
                array_marcador_angulos[indice_actual]={rotacion:angulo,indice:indice_actual};
                var infoWindow = new google.maps.InfoWindow({
                    content : html
                });
            
                mk.addListener('click', function () {
                    infoWindow.open(map, mk);
                    velocimetro_change(velocidad);
                });
                if (currentUnidad != null)
                {
                    map.setCenter(mk.getPosition());
                    map.setZoom(20);
                    currentUnidad=null;
                }
                return mk;
                
            }
        }else{

            mk = getMarkerById(id);
            if(map.getZoom()<=13)
                icon = {
                    url: '{{url("/images/autobu.png")}}',
                    scale: 1,
                    labelOrigin: new google.maps.Point(4, 25)
                };
            else
                icon = {
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: 2,
                    strokeColor: 'red',
                    strokeOpacity: 2.0,
                    strokeWeight: 4,
                    rotation:angulo,
                    labelOrigin: new google.maps.Point(1, 10)
                };
            if (mk != null)
            {
                mk.setPosition({ lat: parseFloat(latitude), lng: parseFloat(longitude)});
                mk.setIcon(icon);
                mk.setMap(map);
            }
            else 
            {
                mk = new google.maps.Marker({
                    position : { lat: parseFloat(latitude), lng : parseFloat(longitude) },
                    map : map,
                    icon:icon,
                    label : {text : placa},
                    title : id,
                    animation : google.maps.Animation.DROP
                });
                array_marcador.push(mk);
            }

            google.maps.event.clearInstanceListeners(mk);
            var indice_actual=array_marcador.length-1;
            array_marcador_angulos[indice_actual]={rotacion:angulo,indice:indice_actual};
            var infoWindow = new google.maps.InfoWindow({
                content : html
            });
        
            mk.addListener('click', function () {
                infoWindow.open(map, mk);
                velocimetro_change(velocidad);
            });
            if (currentUnidad != null)
            {
                map.setCenter(mk.getPosition());
                map.setZoom(20);
                currentUnidad=null;
            }
            return mk;
        }
        
       
    }

    $('#fecha_inicio').datetimepicker();
    $('#fecha_fin').datetimepicker();
    var path = [];

    var marcadoresRutas = [];
    var marcadoresRutas_general = [];
    var arrayPoly=[];
    var arrayPoly2=[];

	$(function(){
        $('#btnEnviar').click(function () {
            var imei = $('#commandImei').val();
            var message = $('#commandMessage').val();
            $.post('{{ url("/api/command") }}', {
                imei : imei,
                message : message
            }, function (data) {
                if (data.error)
                alert('There was an error executing the command.');
                else
                alert('The command was successfully executed.');
            }, 'json');
        });

        $('#btnApagar').click(function () {
            var imei = $('#commandImei').val();
            var message = 'AT+GTOUT=gv300,1,,,0,0,0,0,0,0,0,,0,0,,,,FFFF$';
            $.post('{{ url("/api/command") }}', {
                imei : imei,
                message : message
            }, function (data) {
                if (data.error)
                alert('There was an error executing the command.');
                else
                alert('The command was successfully executed.');
            }, 'json');
        });

        $('#btnEncender').click(function () {
            var imei = $('#commandImei').val();
            var message = 'AT+GTOUT=gv300,0,,,0,0,0,0,0,0,0,,0,0,,,,FFFF$';
            $.post('{{ url("/api/command") }}', {
                imei : imei,
                message : message
            }, function (data) {
                if (data.error)
                alert('There was an error executing the command.');
                else
                alert('The command was successfully executed.');
            }, 'json');
        });

        $('#btnReset').click(function () {
            var imei = $('#commandImei').val();
            var message = 'AT+GTRTO=gv300,3,,,,,,FFFF$';
            $.post('{{ url("/api/command") }}', {
                imei : imei,
                message : message
            }, function (data) {
                if (data.error)
                alert('Hubo un error al ejecutar el comando.');
                else
                alert('Comando ejecutado exitosamente.');
            }, 'json');
        });

        $('#ruta').chosen({ witdh : '100%'}).change(function () {
            $('#progress').modal('show');
            $.get('{{ url("/puntos") }}', {
                    rutas : $('#ruta').val()
                } , function (data) {
                    var puntos = data.puntos;
                    if (polyline != null)
                        polyline.setPath([]);
                    if (polyline2 != null)
                        polyline2.setPath([]);
                    var path = [];
                    for (var i = 0; i < marcadoresRutas.length; i++)
                        marcadoresRutas[i].setMap(null);
                    marcadoresRutas = [];
                    if (puntos === null || puntos === undefined)
                        puntos = [];
                    for (var i = 0; i < puntos.length; i++)
                    {
                        for (var j = 0; j < puntos[i].length; j++)
                        {
                            var html = puntos[i][j].descripcion;
                            var icon = {
                                    url: '{{url("/images/flag.png")}}',
                                    scale: 1,
                                    labelOrigin: new google.maps.Point(4, 25)
                                };
                            var marker = new google.maps.Marker({
                                map : map,
                                position : {
                                    lat : parseFloat(puntos[i][j].latitud),
                                    lng : parseFloat(puntos[i][j].longitud)
                                    },
                                    icon : icon,
                                    label : puntos[i][j].descripcion
                            });
                            marcadoresRutas.push(marker);
                        }
                    }
                    var rutas = data.rutas;
                    if (rutas === null || rutas === undefined)
                        rutas = [];
                    for (var i = 0; i < rutas.length; i++)
                    {
                        var recorrido = rutas[i].recorrido;
                        for (var j = 0; j < recorrido.length; j++)
                        {
                            path.push({
                                lat : parseFloat(recorrido[j].lat),
                                lng : parseFloat(recorrido[j].lng)
                                });
                        }
                    }
                    polyline = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: '#2ecc71',
                        strokeOpacity: 1.0,
                        strokeWeight: 4
                        });
                    polyline2 = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: '#fff',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                        });
                    
                        polyline.setMap(map);
                        polyline2.setMap(map);

                    rutas_ids = []; 
                    if(data.rutas!=null) 
                    {
                        for(var i=0;i<data.rutas.length;i++) 
                        rutas_ids.push(data.rutas[i]._id);
                    }
                    for (var i = 0; i < array_marcador.length; i++)
                        array_marcador[i].setMap(null);
                    array_marcador = [];

                    $('#progress').modal('hide');
                    setUnidadesOnMap();

                }, 'json');
        });

        $('#ruta_atm').chosen({ witdh : '100%'}).change(function () {
            $.get('{{ url("/puntos-atm") }}', {
                    rutas : $('#ruta_atm').val()
                } , function (data) {
                    console.log(data);
                    var puntos = data.puntos;
                    if (polyline != null)
                        polyline.setPath([]);
                    if (polyline2 != null)
                        polyline2.setPath([]);
                    var path = [];
                    for (var i = 0; i < marcadoresRutas.length; i++)
                        marcadoresRutas[i].setMap(null);
                    marcadoresRutas = [];
                    if (puntos === null || puntos === undefined)
                        puntos = [];
                        
                    for(var a=0; a<circleMap.length ; a++)
                    {
                        circleMap[a].setMap(null);
                    }
                    circleMap=[];

                    for (var i = 0; i < puntos.length; i++)
                    {
                        for (var j = 0; j < puntos[i].length; j++)
                        {
                            var html = puntos[i][j].descripcion;
                            var icon = {
                                    url: '{{url("/images/flag.png")}}',
                                    scale: 1,
                                    labelOrigin: new google.maps.Point(4, 25)
                                };
                            var marker = new google.maps.Marker({
                                map : map,
                                position : {
                                    lat : parseFloat(puntos[i][j].latitud),
                                    lng : parseFloat(puntos[i][j].longitud)
                                    },
                                    icon : icon,
                                    label : puntos[i][j].descripcion
                            });

                            circleMap[j]=new google.maps.Circle({
                                strokeColor: '#00942b',
                                strokeOpacity: 0.8,
                                strokeWeight: 2,
                                fillColor: '#50ff88',
                                fillOpacity: 0.35,
                                map: map
                            });
                            circleMap[j].setCenter({lat:parseFloat(puntos[i][j].latitud),
                                lng:parseFloat(puntos[i][j].longitud)});
                            circleMap[j].setRadius(parseFloat(puntos[i][j].radio));

                            marcadoresRutas.push(marker);
                        }
                    }

                    var rutas = data.rutas;
                    if (rutas === null || rutas === undefined)
                        rutas = [];
                    for (var i = 0; i < rutas.length; i++)
                    {
                        var recorrido = rutas[i].recorrido;
                        for (var j = 0; j < recorrido.length; j++)
                        {
                            path.push({
                                lat : parseFloat(recorrido[j].lat),
                                lng : parseFloat(recorrido[j].lng)
                                });
                        }
                    }
                    polyline = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: '#2ecc71',
                        strokeOpacity: 1.0,
                        strokeWeight: 4
                    });
                    polyline2 = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: '#fff',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                    });
                
                    polyline.setMap(map);
                    polyline2.setMap(map);

                    for (var i = 0; i < array_marcador.length; i++)
                        array_marcador[i].setMap(null);
                    array_marcador = [];
                        
                    setUnidadesOnMap();
                }, 'json');
        });

        $('#ruta_general').chosen({ witdh : '100%'}).change(function () {
            $('#progress').modal('show');
            $.get('{{ url("/puntos") }}', {
                    rutas : $('#ruta_general').val()
                } , function (data) {
                    for(i=0;i<arrayPoly.length;i++){
                        arrayPoly[i].setPath([]);
                        arrayPoly2[i].setPath([]);
                    }
                    var puntos = data.puntos;
                   /* if (polyline_general != null)
                        polyline_general.setPath([]);
                    if (polyline2_general != null)
                        polyline2_general.setPath([]);*/
                    var path = [];
                    for (var i = 0; i < marcadoresRutas_general.length; i++)
                        marcadoresRutas_general[i].setMap(null);
                    marcadoresRutas_general = [];
                    if (puntos === null || puntos === undefined){
                        puntos = [];
                    }
                    for (var i = 0; i < puntos.length; i++)
                    {
                        for (var j = 0; j < puntos[i].length; j++)
                        {
                            var html = puntos[i][j].descripcion;
                            var icon = {
                                    url: '{{url("/images/flag.png")}}',
                                    scale: 1,
                                    labelOrigin: new google.maps.Point(4, 25)
                                };
                            var marker = new google.maps.Marker({
                                map : map,
                                position : {
                                    lat : parseFloat(puntos[i][j].latitud),
                                    lng : parseFloat(puntos[i][j].longitud)
                                    },
                                    icon : icon,
                                    label : puntos[i][j].descripcion
                            });
                            marcadoresRutas_general.push(marker);
                        }
                    }
                    var rutas = data.rutas;
                    if (rutas === null || rutas === undefined)
                        rutas = [];
                    for (var i = 0; i < rutas.length; i++)
                    {
                        path = [];
                        var recorrido = rutas[i].recorrido;
                        for (var j = 0; j < recorrido.length; j++)
                        {
                            path.push({
                                lat : parseFloat(recorrido[j].lat),
                                lng : parseFloat(recorrido[j].lng)
                                });
                        }
                        //console.log(recorrido);
                        var color="#2ECC70";
                        if(rutas[i].color == 'A')
                            color='#0048D8';
                         if(rutas[i].color == 'V')
                            color='#2ECC70';     
                        if(rutas[i].color == 'C')
                            color='#715050';    
                        if(rutas[i].color == 'M')
                            color='#922BA0';    
                        if(rutas[i].color == 'R')
                            color='#CC2E2E';    
                        if(rutas[i].color == 'N')
                            color='#000000';    
                            
                        polyline_general = new google.maps.Polyline({
                            path: path,
                            geodesic: true,
                            strokeColor: color,
                            strokeOpacity: 1.0,
                            strokeWeight: 4
                            });
                        polyline2_general = new google.maps.Polyline({
                            path: path,
                            geodesic: true,
                            strokeColor: '#fff',
                            strokeOpacity: 1.0,
                            strokeWeight: 2
                            });
                    
                        polyline_general.setMap(map);
                        polyline2_general.setMap(map);

                        arrayPoly.push(polyline_general);
                        arrayPoly2.push(polyline2_general);
                    }

                    /*var color="#2ecc7";
                    polyline_general = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: color,
                        strokeOpacity: 1.0,
                        strokeWeight: 4
                        });
                    polyline2_general = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: '#fff',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                        });
                    
                        polyline_general.setMap(map);
                        polyline2_general.setMap(map);*/

                    
                    $('#progress').modal('hide');

                }, 'json');
        });
	});

</script>
<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
    </script>
<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places,geometry&callback=initMap"
    async defer></script>
@endsection
