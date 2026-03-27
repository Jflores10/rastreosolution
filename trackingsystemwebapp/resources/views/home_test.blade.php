@extends('layouts.app')
@section('styles')
<style>

    /* Forzar modal encima de cualquier elemento flotante */
    .modal-backdrop {
        z-index: 9998 !important;
    }

    .modal {
        z-index: 9999 !important;
    }

    /* BUFF blinking marker animation */
    @keyframes blink-marker {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.15; }
    }
    .marker-buff-blink {
        animation: blink-marker 0.65s ease-in-out infinite;
    }

    /* Power ON blinking bolt animation (GTMPN) */
    @keyframes blink-bolt {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.15; }
    }
    .bolt-power-blink {
        animation: blink-bolt 0.65s ease-in-out infinite;
    }

    /* Ajuste botones */
    .comandos-v .btn{
        margin-bottom:5px;
    }

    /* Responsive */
    @media (max-width:768px){

        #commandModal .modal-dialog{
            width:95%;
            margin:10px auto;
        }

        .btn-group-justified > .btn,
        .btn-group-justified > .btn-group{
            display:block;
            width:100%;
            margin-bottom:5px;
        }

        .comandos-v{
            display:flex;
            flex-direction:column;
            width:100%;
        }

        .comandos-v .btn{
            width:100%;
        }

    }

</style>

<style>
.dir-wrap {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    max-width: 150px !important; /* ajusta el ancho máximo del texto */
    display: block !important;
}

.popup-content li {
    max-width: 150px !important;  /* evita que el <li> deforme el modal */
    white-space: normal !important;
}
    
#poi-bar {
    position: absolute;
    top: 180px;
    right: 30px;
    z-index: 9999;
    display: flex;
    flex-direction: column; /* ✅ Vertical */
    background: rgba(255,255,255,0.95);
    padding: 8px;
    border-radius: 10px;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    font-family: Arial, sans-serif;
}

.poi-btn {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
    text-align: left;
    color:black;
}

.poi-btn:hover {
    background: #e6f0ff;
}

/* Activo */
.poi-btn.active {
    background: #B2D0F7;
    color: black;
    border: 2px solid #0022AB;
}

@media(max-width: 768px) {

    #poi-bar {
        top: 63%;                 /* mitad vertical de pantalla */
        transform: translateY(-50%); /* centrar verticalmente */
        right: 30px;               /* pegado al borde derecho */
        padding: 2px;
        gap: 6px;
        background: rgba(255,255,255,0.85);
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        display: flex !important;
        flex-direction: column;
    }

    .poi-btn {
        width: 30px;              /* Tamaño compacto */
        height: 30px;
        padding: 0;
        border-radius: 8px;
        font-size: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .poi-btn::after {
        font-size: 18px;
        content: attr(data-icon);
    }
}

@media (max-width: 768px) {

   
    #trafficToggle {
        padding: 2px !important;     
        border-radius: 6px !important;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12) !important;
    }

    #trafficText {
        display: none !important;   
    }

    #trafficIcon {
        font-size: 20px !important;  /* ✅ solo icono visible */
        display: block;
    }
}

@media (max-width: 768px) {
    #velocimetro-container {
        bottom: -90px !important; 
        left: 50px !important;    
        width: 80px !important;  
        height: 80px !important;
    }

    #velocimetro {
        width: 80px !important;
        height: 80px !important;
    }
}

</style>

<style>
    #reloj
    {
        background: linear-gradient(45deg, #FC4439, #FC4439) !important;

    }
    #map
    {
        width : 100%;
        height : 700px;
    }
    .modal-open #velocimetro-container {
        display: none !important;
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
<style>


footer {
  background: #f8f8f8;
  border-top: 1px solid #ddd;
  padding: 5.7px 20px;
  position: relative;
  bottom: 0;
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
<div class="row" style="margin:0;">
  <div class="col-md-12">
    <div class="x_panel" style="padding:5px;">
      <div class="x_content" style="padding:0;">
        <div class="row" style="margin:0;">
          
          <!-- Panel lateral (buscador y lista de unidades) -->
          <div class="col-lg-3 col-md-4 col-sm-12" style="padding:5px; max-height:80vh;">
            <form name="form_coop" method="GET" action="{{ url('/homeUniCoop2') }}" id="form_coop">
              {{ csrf_field() }}
              <div class="form-group" id="div-cooperativa">
                <label>Cooperativa</label>
                <select class="form-control" name="cooperativa" id="cooperativa" onchange="this.form.submit();">
                  @if(Auth::user()->tipo_usuario->valor==1)
                    <option value="" disabled selected hidden>Seleccione...</option>
                  @endif
                  @foreach ($cooperativas as $cooperativa)
                    <option data-bloques="{{ json_encode($cooperativa->pto_bloques)}}" data-trafico="{{ json_encode($cooperativa->trafico)}}" value="{{ $cooperativa->_id }}">
                      {{ $cooperativa->descripcion }}
                    </option>
                  @endforeach
                </select>
              </div>
              @if(Auth::user()->tipo_usuario->valor!=1)
                <i id="i_cooperativa" class="fa fa-globe" style="color:#2a62bc"></i>
                <label>{{ $cooperativa->descripcion }}</label>
              @endif
            </form>

            <div class="form-group">
              <div class="input-group">
                <input type="text" name="consulta" id="consulta" class="form-control" onkeypress="return runScript(event)" placeholder="Buscar unidad">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" onclick="searchUnidad(true);">
                    <i class="fa fa-search"></i>
                  </button>
                </span>
              </div>
            </div>

            <div>
              <label>Cantidad:</label> <span id="cantidad">0</span>&nbsp;
              <i class="fa fa-bus" style="color:#00AA88"></i>:<span id="cantidad_movimiento">0</span>&nbsp;
              <i class="fa fa-bus" style="color:#F44336"></i>:<span id="cantidad_stop">0</span>&nbsp;
              <i class="fa fa-bus" style="color:#f49a16"></i>:<span id="cantidad_e">0</span>&nbsp;
              <i class="fa fa-bus" style="color:#990073"></i>:<span id="cantidad_no">0</span>&nbsp;
              <span class="label label-primary" id="txtBloque"></span>
              @if(Auth::user()->tipo_usuario->valor == 1)
                <button class="btn btn-info btn-xs" type="button" onclick="verLogsTramas();" style="margin-left:5px;">
                  <i class="fa fa-table"></i> Ver tramas
                </button>
              @endif
            </div>

            <div id="div-mensaje" class="mt-2"></div>

            <div id="div-unidad" style="height:56vh; overflow-y:auto; margin-top:10px;">
              <ul class="list-group" id="ul_unidades"></ul>
            </div>
          </div>

          <!-- Panel principal (mapa) -->
          <div class="col-lg-9 col-md-8 col-sm-12" style="padding:20px;">
            <div class="row" style="margin-bottom:5px;">
              <div class="col-md-3">
                <select class="form-control select2" multiple data-placeholder="Rutas en Despachos" name="ruta" id="ruta">
                  @if (isset($rutas))
                    @foreach ($rutas as $ruta)
                      <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                    @endforeach
                  @endif
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-control select2" multiple data-placeholder="Rutas General" name="ruta_general" id="ruta_general">
                  @if (isset($rutas))
                    @foreach ($rutas as $ruta)
                      <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                    @endforeach
                  @endif
                </select>
              </div>
              <div class="col-md-6 text-right">
                <div class="btn-group " role="group" >
                    <button class="btn btn-info" type="button" onclick="$('#progress').modal('show');playClick();"><i class="fa fa-play"></i></button>
                    <button class="btn btn-info" type="button" onclick="pauseClick();"><i class="fa fa-pause"></i></button>
                    <button class="btn btn-info" type="button" onclick="stopClick();"><i class="fa fa-stop"></i></button>
                    <button class="btn btn-info" type="button" onclick="eraseClick();"><i class="fa fa-eraser"></i></button>
                    <button class="btn btn-info" onclick="setOpciones();" data-toggle="modal" data-target="#form"><i class="fa fa-cog"></i></button>
                    <button class="btn btn-info" type="button" onclick="eraseClick();cargarTodasLasUnidades();"><i class="fa fa-spinner"></i></button>
                    <button class="btn btn-info" type="button" onclick="consultarEnLinea();"><i class="fa fa-eye"></i></button>
                </div>
              </div>
            </div>
            <input class="form-control" type="text" placeholder="Escriba una referencia..." id="address" name="address" style="margin-bottom:8px;" />
            <!-- MAPA -->
            <div id="poi-bar" style="display:none">
                <button class="poi-btn" data-type="police" data-icon="🚓">🚓 Policía</button>
                <button class="poi-btn" data-type="fire_station" data-icon="🚒">🚒 Bomberos</button>
                <button class="poi-btn" data-type="gas_station" data-icon="⛽">⛽ Gasolineras</button>
                <button class="poi-btn" data-type="hospital" data-icon="🏥">🏥 Hospitales</button>
            </div>

            <div id="map" style="width:100%; height:calc(93vh - 184px); border:1px solid #ccc; position:relative; z-index:1;"></div>

            <!-- OVERLAY -->
            <div id="map-overlay" 
                style="position:absolute; top:0; left:0; width:100%; height:calc(100vh - 180px); pointer-events:none; z-index:9999;">

            <label id="lbl_hora_mapa" 
                style="position:absolute; top:70px; right:10px; font-size:13px; 
                        color:white; background:rgba(0,0,0,0.7); ">
            </label>

            <!-- Velocímetro (abajo izquierda) -->
            <div id="velocimetro-container"
                style="position:absolute; bottom:77px; left:20px; width:160px; height:160px; 
                        display:flex; align-items:center; justify-content:center; z-index:10000;">
                <canvas id="velocimetro" width="160" height="160" style="pointer-events:none;"></canvas>
            </div>

            </div>
            
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
        <input type="hidden"  name="latitudc" id="latitudc"  />
        <input type="hidden"  name="longitudc" id="longitudc" />

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
                 <div class="form-group">
                    <label for="responseMessage">Respuesta</label>
                    <textarea id="responseMessage" name="responseMessage" class="form-control" rows="3" placeholder="Respuesta recibida..."></textarea>
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

                <div class="btn-group btn-group-justified">

                    <a id="btnCompartir" class="btn btn-info">
                        <i class="fa fa-map-marker"></i>
                        Compartir Ubicación
                    </a>

                    <a id="btnFoto" class="btn btn-primary">
                        <i class="fa fa-camera"></i>
                        Foto
                    </a>
                    <a id="btnPUbicacion" class="btn btn-success">
                        <i class="fa fa-globe"></i>
                        Petición Ubicación
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
let SSE_CONNECTED = false;

function iniciarSSEGlobal() {
    if (SSE_CONNECTED) return;
    SSE_CONNECTED = true;

    @if(isset($id_coop))
        conectarSSE('{{ $id_coop }}');
    @elseif(Auth::user()->tipo_usuario->valor==2 
        || Auth::user()->tipo_usuario->valor==3 
        || Auth::user()->tipo_usuario->valor==4 
        || Auth::user()->tipo_usuario->valor==5)
        conectarSSE('*');
    @endif
}

// ⚡ Conectar SSE apenas el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    iniciarSSEGlobal();
});
</script>
<script>
let ws = null;
// Ajustes horarios para fechas que vienen del servidor:
// Según HistoricoController: fecha_gps => restar 10 horas, fecha (servidor) => restar 5 horas
const GPS_HOUR_OFFSET = -5; // restar 10 horas a fecha_gps
const SERVER_HOUR_OFFSET = -5; // restar 5 horas a fecha (servidor)

let sse = null;

// Meta cache: stores { data: {...}, ts: <ms> } per unidad id
var unidadesMetaCache = {};
const META_TTL_MS = 30000; // 30s cache TTL for batch refresh
// ensure we only fetch server meta once per unidad when SSE first arrives
var unidadesMetaFetchedOnce = {};

function applyMetaToLi(uid, meta, source) {
    try {
        var li = document.getElementById(uid);
        if (!li) return;
        if (!li.currentU) li.currentU = {};

        // Ruta fields: only overwrite from SSE (source === 'sse') or when the LI has no route yet
        if (meta.ruta_actual && meta.ruta_actual !== '') {
            if (!li.currentU.ruta_actual || li.currentU.ruta_actual === '' || source === 'sse') {
                li.currentU.ruta_actual = meta.ruta_actual;
                li.currentU.ruta_fecha = meta.ruta_fecha || li.currentU.ruta_fecha;
                li.currentU.ruta_conductor = meta.ruta_conductor || li.currentU.ruta_conductor;
                li.currentU.ruta_hora_fin = meta.ruta_hora_fin || li.currentU.ruta_hora_fin;
            }
        }

        // Other meta (tipo_bitacora / bitacora): update when provided
        if (meta.tipo_bitacora && meta.tipo_bitacora !== '') {
            li.currentU.tipo_bitacora = meta.tipo_bitacora;
        }
        if (meta.bitacora && meta.bitacora !== '') {
            li.currentU.bitacora = meta.bitacora;
        }

        // ignicionf: persist into currentU and inject/update icon surgically without a full re-render.
        // This is the authoritative value from the DB on initial load (or periodic refresh).
        // Only update if the meta provides it AND there is no more-recent SSE value already applied.
        if (meta.ignicionf === 'on' || meta.ignicionf === 'off') {
            // Never overwrite an ignicionf that arrived via a live SSE event (unidad.ignicion).
            // We detect that by checking li._ignicion_from_sse flag set in the SSE listener.
            if (!li._ignicion_from_sse) {
                li.currentU.ignicionf = meta.ignicionf;
                // Inject or replace the ignicion icon in the LI
                try {
                    var _iconId  = 'ignicion_' + uid;
                    var _iconEl  = document.getElementById(_iconId);
                    var _iconHtml = (meta.ignicionf === 'on')
                        ? '<i id="' + _iconId + '" class="fa fa-dot-circle-o" title="IG ON"  style="color:green; pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;'
                        : '<i id="' + _iconId + '" class="fa fa-dot-circle-o" title="IG OFF" style="color:red;   pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;';
                    if (_iconEl) {
                        var _tmp = document.createElement('span');
                        _tmp.innerHTML = _iconHtml;
                        _iconEl.parentNode.replaceChild(_tmp.firstChild, _iconEl);
                    } else {
                        var _tmp2 = document.createElement('span');
                        _tmp2.innerHTML = _iconHtml;
                        var _busEl = li.querySelector('.fa-bus');
                        if (_busEl) {
                            _busEl.parentNode.insertBefore(_tmp2.firstChild, _busEl);
                        } else {
                            li.insertBefore(_tmp2.firstChild, li.firstChild);
                        }
                    }
                } catch(e) {}
            }
        }

        // mark where meta came from and timestamp
        li._meta_from_sse = (source === 'sse');
        li._meta_ts = Date.now();

        // tiempo_power / bolt_activo: aplicar desde meta batch (getUnidadesMeta)
        // Misma lógica que ruta_actual: se guarda en currentU para que updateUnidadInList
        // lo lea directamente, igual que lee ruta_actual desde currentU.
        if (source !== 'sse' && meta.bolt_activo !== undefined) {
            var _tpMeta  = (meta.tiempo_power != null) ? parseFloat(meta.tiempo_power) : 0;
            var _tpuMeta = (meta.tiempo_power_update != null) ? meta.tiempo_power_update : null;

            // Aplicar si:
            //  a) currentU no tiene tiempo_power aún (recarga de página / primera carga)
            //  b) bolt_activo es false → el servidor dice que ya expiró, siempre limpiar
            var _noTienePower = !li.currentU.tiempo_power || !li.currentU.tiempo_power_update;
            var _debeApagar   = !meta.bolt_activo;
            if (_noTienePower || _debeApagar) {
                // Guardar en currentU exactamente igual que ruta_actual
                li.currentU.tiempo_power        = _tpMeta;
                li.currentU.tiempo_power_update = _tpuMeta;
                // Mantener también en el LI para el timer periódico (60s)
                li._tiempo_power        = _tpMeta;
                li._tiempo_power_update = _tpuMeta ? new Date(_tpuMeta) : null;
                li._is_power_blink      = !!meta.bolt_activo;
            }
        }

        updateUnidadInList(li.currentU);
    } catch (e) {
        console.warn('applyMetaToLi failed', e);
    }
}

function fetchUnidadesMeta(ids) {
    return new Promise((resolve) => {
        if (!ids || ids.length === 0) return resolve({});
        $.post('/historico/unidades-meta', { unidad_ids: ids }, function (data) {
            resolve(data || {});
        }, 'json').fail(function () {
            resolve({});
        });
    });
}

function refreshVisibleUnidadesMeta() {
    try {
        var lis = Array.from(document.querySelectorAll('#ul_unidades li'));
        var now = Date.now();
        var toFetch = [];
        lis.forEach(function (li) {
            var uid = li.id;
            var cache = unidadesMetaCache[uid];
            if (!cache || (now - cache.ts) > META_TTL_MS) {
                toFetch.push(uid);
            }
        });

        if (toFetch.length === 0) return;

        fetchUnidadesMeta(toFetch).then(function (resp) {
            Object.keys(resp).forEach(function (uid) {
                var m = resp[uid];
                if (!m) return;
                // cache it
                unidadesMetaCache[uid] = { data: m, ts: Date.now() };
                // apply: DO NOT overwrite existing ruta on the LI (unless empty) — only apply route if LI has none
                applyMetaToLi(uid, m, 'batch');
            });
        });
    } catch (e) {
        console.warn('refreshVisibleUnidadesMeta failed', e);
    }
}

// run periodic refresh to keep non-route meta up-to-date; route fields will not overwrite an existing route
setInterval(refreshVisibleUnidadesMeta, 20000);

// Timer periódico: cada 60 s verifica si algún bolt con tiempo_power activo ya expiró
// y detiene el parpadeo automáticamente.
setInterval(function () {
    document.querySelectorAll('#ul_unidades li').forEach(function (li) {
        if (!li._is_power_blink) return;
        if (!li._tiempo_power_update || !li._tiempo_power) {
            li._is_power_blink = false;
            return;
        }
        var _horasRest = li._tiempo_power - ((Date.now() - new Date(li._tiempo_power_update).getTime()) / 3600000);
        if (_horasRest <= 0) {
            li._is_power_blink = false;
            var _boltEl = li.querySelector('.fa-bolt');
            if (_boltEl) _boltEl.classList.remove('bolt-power-blink');
        }
    });
}, 60000);

/**
 * Activa o desactiva el parpadeo del fa-map-marker en el LI de una unidad.
 * isBuff=true  → agrega .marker-buff-blink al ícono y marca li._is_buff=true
 * isBuff=false → remueve la clase y marca li._is_buff=false
 */
function setBuffBlink(uid, isBuff) {
    try {
        if (!uid) return;
        const li = document.getElementById(uid);
        if (!li) return;
        li._is_buff = !!isBuff;
        const markerEl = document.getElementById('g' + uid);
        if (!markerEl) return;
        if (isBuff) {
            markerEl.classList.add('marker-buff-blink');
        } else {
            markerEl.classList.remove('marker-buff-blink');
        }
    } catch (e) { /* silencioso */ }
}

function conectarSSE(coopId) {

    if (sse && sse.readyState === EventSource.OPEN) {
        console.log('ℹ️ SSE ya conectado');
        return;
    }

    const url = `/sse?coop=${encodeURIComponent(String(coopId).trim())}`;
    console.log('🔌 Conectando SSE:', url);

    sse = new EventSource(url);

    sse.addEventListener('open', () => {
        console.log('🟢 SSE CONECTADO');
    });

    sse.addEventListener('unidad.updated', (evt) => {
        try {
            const data = JSON.parse(evt.data);
            if (!(data && data._id)) return;

            // Si es BUFF: solo parpadear, NO actualizar nada en el front
            if (data.is_buff) {
                setBuffBlink(data._id, true);
                return;
            }

            // RESP: detener parpadeo (si estaba activo) y procesar normalmente
            setBuffBlink(data._id, false);

            // Always update marker on map
            actualizarUnidadRealtime(data);

            // If the LI has an active temp lock (set by unidad.location), defer the list update
            const uid = data._id;
            const li = document.getElementById(uid);
            const now = Date.now();

            if (li && li._temp_lock_until && now < li._temp_lock_until) {
                // simply store latest pending update; removal of the icon will apply it
                try {
                    li._pending_update = data;
                } catch (e) { console.warn('failed to set pending update', e); }
                return;
            }

            // No lock: apply immediately (merge meta + update list)
            try {
                const meta = {
                    ruta_actual: data.ruta_actual || '',
                    ruta_fecha: data.ruta_fecha || '',
                    ruta_conductor: data.ruta_conductor || '',
                    ruta_hora_fin: data.ruta_hora_fin || '',
                    tipo_bitacora: data.tipo_bitacora || '',
                    bitacora: data.bitacora || ''
                };

                const anyMeta = Object.keys(meta).some(k => meta[k] && meta[k] !== '');
                if (anyMeta) {
                    unidadesMetaCache[data._id] = { data: meta, ts: Date.now() };
                    applyMetaToLi(data._id, meta, 'sse');
                }

                updateUnidadInList(data);

                if (!unidadesMetaFetchedOnce[data._id]) {
                    unidadesMetaFetchedOnce[data._id] = true;
                    fetchUnidadesMeta([data._id]).then(function(resp) {
                        try {
                            var serverMeta = resp && resp[data._id] ? resp[data._id] : null;
                            if (serverMeta) {
                                unidadesMetaCache[data._id] = { data: serverMeta, ts: Date.now() };
                                applyMetaToLi(data._id, serverMeta, 'sse');
                            }
                        } catch (e) { console.warn('apply server meta after sse failed', e); }
                    }).catch(function(e){ /* ignore */ });
                }
            } catch (e) { console.warn('apply unidad.updated failed', e); }

        } catch (e) {
            console.error('❌ parse unidad.updated', e);
        }
    });

    sse.addEventListener('unidad.sentido.changed', (evt) => {
        try {
            const data = JSON.parse(evt.data);
            console.log(data)

            // Si es BUFF: solo parpadear, NO actualizar sentido ni lista
            if (data.is_buff) {
                setBuffBlink(data.unidad_id, true);
                return;
            }

            const li = document.getElementById(data.unidad_id);
            if (li && li.currentU) {
                setBuffBlink(data.unidad_id, false);
                li.currentU.sentido = data.nuevo_sentido;
                updateUnidadInList(li.currentU);
            }
        } catch (e) {}
    });

    sse.addEventListener('unidad.alerta.puerta', (evt) => {
        try {
            const msg = JSON.parse(evt.data);
            console.log(msg)

            const li = document.getElementById(msg.unidad_id);
            if (!li || !li.currentU) return;

            // Si es BUFF: solo parpadear, NO actualizar estado de puertas ni lista
            if (msg.is_buff) {
                setBuffBlink(msg.unidad_id, true);
                return;
            }

            setBuffBlink(msg.unidad_id, false);

            if (msg.puerta === 'DELANTERA') {
                li.currentU.puerta = msg.estado;
            }
            if (msg.puerta === 'TRASERA') {
                li.currentU.puerta_trasera = msg.estado;
            }
            updateUnidadInList(li.currentU);
        } catch (e) {}
    });

    sse.addEventListener('unidad.location', (evt) => {
        try {
            const msg = JSON.parse(evt.data);
            console.log(msg)
            zoomUnidad=true;
            zoomUnidadID=msg._id || msg.unidad_id;
            map.setZoom(30);
            // 1) Place marker on map using existing helper
            try {
                // actualizarUnidadRealtime already validates lat/lng and calls setMarcadorUnidad
                actualizarUnidadRealtime(msg);
            } catch (e) { console.warn('failed to place marker from unidad.location', e); }

            // 1b) Also update the LI so its displayed time (and other transient fields)
            // reflect the newest location immediately.
            try {
                updateUnidadInList(msg);
            } catch (e) { console.warn('failed to update LI from unidad.location', e); }

            // 2) Show temporary marker icon in the LI for 60 seconds and set a lock
            try {
                const uid = msg._id || msg.unidad_id;
                if (uid) {
                    const li = document.getElementById(uid);
                    if (li) {
                        // avoid duplicate temp markers
                        if (!li.querySelector('.temp-marker')) {
                            var markerEl = document.createElement('span');
                            markerEl.className = 'temp-marker fa fa-globe';
                            markerEl.style.color = '#FF4444';
                            markerEl.style.fontSize = '15px';
                            markerEl.style.marginRight = '6px';

                            // prepend marker to li content for visibility
                            li.insertBefore(markerEl, li.firstChild);

                            // set lock until timestamp (ms)
                            try { li._temp_lock_until = Date.now() + 60000; } catch (e) {}

                            // remove after 60s, clear lock and apply pending update if any
                            setTimeout(function () {
                                try {
                                    const t = li.querySelector('.temp-marker'); if (t) t.remove();
                                } catch (e) {}
                                try {
                                    // clear lock
                                    li._temp_lock_until = 0;
                                    // if there was a pending update received while locked, apply it now
                                    if (li._pending_update) {
                                        try {
                                            const pending = li._pending_update;
                                            li._pending_update = null;
                                            // apply same logic as unidad.updated immediate path
                                            const meta = {
                                                ruta_actual: pending.ruta_actual || '',
                                                ruta_fecha: pending.ruta_fecha || '',
                                                ruta_conductor: pending.ruta_conductor || '',
                                                ruta_hora_fin: pending.ruta_hora_fin || '',
                                                tipo_bitacora: pending.tipo_bitacora || '',
                                                bitacora: pending.bitacora || ''
                                            };
                                            const anyMeta = Object.keys(meta).some(k => meta[k] && meta[k] !== '');
                                            if (anyMeta) {
                                                unidadesMetaCache[pending._id] = { data: meta, ts: Date.now() };
                                                applyMetaToLi(pending._id, meta, 'sse');
                                            }
                                            updateUnidadInList(pending);
                                        } catch (e) { console.warn('apply pending update on unlock failed', e); }
                                    }
                                } catch (e) { console.warn('cleanup after temp marker failed', e); }
                            }, 60000);
                        }
                    }
                }
            } catch (e) { console.warn('failed to add temporary LI marker', e); }

        } catch (e) { console.warn('parse unidad.location', e); }
    });

    // ================== unidad.ignicion (GTIGN / GTIGF) ==================
    // Solo actualiza el icono de ignición. No toca mapa, no re-renderiza el LI completo.
    // El único origen de ignicionf es este evento SSE.
    sse.addEventListener('unidad.ignicion', (evt) => {
        try {
            const msg = JSON.parse(evt.data);
            console.log(msg);
            if (!msg || !msg._id) return;

            const uid = String(msg._id);
            const li = document.getElementById(uid);
            if (!li) return;

            // Mark that this unit's ignicionf was set by a live SSE event.
            // applyMetaToLi will not overwrite this with a stale DB value.
            li._ignicion_from_sse = true;

            // Persist into currentU so future full re-renders include the last known value
            if (li.currentU) {
                li.currentU.ignicionf = msg.ignicionf;
            }

            // Surgical icon swap — only replace the ignicion icon element
            const iconId = 'ignicion_' + uid;
            const iconEl = document.getElementById(iconId);
            const newHtml = (msg.ignicionf === 'on')
                ? '<i id="' + iconId + '" class="fa fa-dot-circle-o" title="IG ON"  style="color:green; pointer-events:none; cursor:default;"></i>&nbsp&nbsp'
                : '<i id="' + iconId + '" class="fa fa-dot-circle-o" title="IG OFF" style="color:red; pointer-events:none; cursor:default;"></i>&nbsp&nbsp';

            if (iconEl) {
                const tmp = document.createElement('span');
                tmp.innerHTML = newHtml;
                iconEl.parentNode.replaceChild(tmp.firstChild, iconEl);
            } else {
                // Icon not in DOM yet — insert before fa-bus so position is correct
                const tmp = document.createElement('span');
                tmp.innerHTML = newHtml;
                const busEl = li.querySelector('.fa-bus');
                if (busEl) {
                    busEl.parentNode.insertBefore(tmp.firstChild, busEl);
                } else {
                    li.insertBefore(tmp.firstChild, li.firstChild);
                }
            }

        } catch (e) { console.warn('parse unidad.ignicion', e); }
    });

    // ================== unidad.power (GTMPN / GTMPF) ==================
    // Cada GTMPN/GTMPF acumula 24h en tiempo_power (descontando tiempo transcurrido).
    // El bolt parpadea mientras queden horas restantes en tiempo_power.
    sse.addEventListener('unidad.power', (evt) => {
        try {
            const msg = JSON.parse(evt.data);
            console.log('unidad.power', msg);
            if (!msg || !msg._id) return;

            const uid = String(msg._id);
            const li = document.getElementById(uid);
            if (!li) return;

            // Persistir en currentU y en las banderas del LI
            if (li.currentU) {
                li.currentU.power               = msg.power;
                li.currentU.tiempo_power        = msg.tiempo_power        != null ? msg.tiempo_power        : li.currentU.tiempo_power;
                li.currentU.tiempo_power_update = msg.tiempo_power_update != null ? msg.tiempo_power_update : li.currentU.tiempo_power_update;
                li.currentU.latitud             = msg.latitud   || li.currentU.latitud;
                li.currentU.longitud            = msg.longitud  || li.currentU.longitud;
                li.currentU.fecha_gps           = msg.fecha_gps || li.currentU.fecha_gps;
                li.currentU.fecha               = msg.fecha     || li.currentU.fecha;
                li.currentU.velocidad_actual    = msg.velocidad_actual != null ? msg.velocidad_actual : li.currentU.velocidad_actual;
                li.currentU.angulo              = msg.angulo    != null ? msg.angulo : li.currentU.angulo;
            }

            // Guardar datos de tiempo_power en el LI para que el timer periódico y
            // el bloque post-render puedan evaluarlos sin tener que leer currentU.
            li._tiempo_power        = (msg.tiempo_power != null) ? parseFloat(msg.tiempo_power) : 0;
            li._tiempo_power_update = msg.tiempo_power_update ? new Date(msg.tiempo_power_update) : new Date();

            // Calcular horas restantes al momento de recibir el evento
            const _horasRestantes = li._tiempo_power - ((Date.now() - li._tiempo_power_update.getTime()) / 3600000);
            li._is_power_blink = _horasRestantes > 0;

            const boltEl = document.getElementById('bolt_' + uid);
            if (li._is_power_blink) {
                if (boltEl) boltEl.classList.add('bolt-power-blink');
            } else {
                if (boltEl) boltEl.classList.remove('bolt-power-blink');
            }

            // Actualizar mapa y LI con nuevas coords/fecha
            try { actualizarUnidadRealtime(msg); } catch(e) {}
            try { updateUnidadInList(li.currentU); } catch(e) {}

        } catch (e) { console.warn('parse unidad.power', e); }
    });


    // fallback
    sse.onmessage = (evt) => {
        try {
            const data = JSON.parse(evt.data);
            if (data && data._id) {
                actualizarUnidadRealtime(data);
                updateUnidadInList(data);
            }
        } catch (e) {}
    };

    sse.onerror = (e) => {
        // ⚠️ ESTO ES NORMAL EN SSE
        if (sse.readyState === EventSource.CONNECTING) {
            console.warn('🟡 SSE reconectando...');
        } else if (sse.readyState === EventSource.CLOSED) {
            console.error('🔴 SSE cerrado');
        }
    };
}




function actualizarUnidadRealtime(unidad) {

    if (
        !unidad ||
        unidad.latitud === undefined ||
        unidad.longitud === undefined ||
        isNaN(parseFloat(unidad.latitud)) ||
        isNaN(parseFloat(unidad.longitud))
    ) {
        return;
    }

    const fakeFecha = {
        fecha_gps: unidad.fecha_gps ?? null,
        fecha_servidor: unidad.fecha ?? null,
        diferencia: unidad.diferencia ?? null,
        fecha_puerta_abierta: null,
        fecha_puerta_cerrada: null
    };
    setMarcadorUnidad(unidad, fakeFecha, fakeFecha, 0);
}
// Buscar el <li> de la unidad en la lista lateral y actualizar sus campos.
function updateUnidadInList(unidad) {
    if (!unidad || !unidad._id) return;

    // Preserve previously-fetched meta (from unidades-meta) so WS updates that lack
    // ruta_* or bitacora do not wipe the values. If there is an existing LI with
    // saved meta, copy those values into the incoming unidad when missing.
    try {
        var existingLiForMerge = document.getElementById(unidad._id);
        if (existingLiForMerge && existingLiForMerge.currentU) {
            var prev = existingLiForMerge.currentU;
            if ((!unidad.ruta_actual || unidad.ruta_actual === '') && prev.ruta_actual) unidad.ruta_actual = prev.ruta_actual;
            if ((!unidad.ruta_fecha || unidad.ruta_fecha === '') && prev.ruta_fecha) unidad.ruta_fecha = prev.ruta_fecha;
            if ((!unidad.ruta_conductor || unidad.ruta_conductor === '') && prev.ruta_conductor) unidad.ruta_conductor = prev.ruta_conductor;
            if ((!unidad.ruta_hora_fin || unidad.ruta_hora_fin === '') && (prev.ruta_hora_fin || prev.ruta_hora_fin === '')) unidad.ruta_hora_fin = prev.ruta_hora_fin;
            if ((!unidad.bitacora || unidad.bitacora === '') && prev.bitacora) unidad.bitacora = prev.bitacora;
            // Preserve ignicionf: never lose it when GTFRI payload arrives without it
            if (!unidad.ignicionf && prev.ignicionf) unidad.ignicionf = prev.ignicionf;
            // Preserve tiempo_power: igual que ruta_actual, se mantiene en currentU entre updates
            if ((unidad.tiempo_power == null || unidad.tiempo_power === 0) && prev.tiempo_power) unidad.tiempo_power = prev.tiempo_power;
            if (!unidad.tiempo_power_update && prev.tiempo_power_update) unidad.tiempo_power_update = prev.tiempo_power_update;
            
            // Preserve sentido (direction) if incoming payload lacks it
            //if ((!unidad.sentido || unidad.sentido === '') && prev.sentido) unidad.sentido = prev.sentido;
        }
    } catch (e) {
        console.warn('Merge existing meta failed', e);
    }

    // Construir campos paralelos a los usados en appendUnidades
    var fecha_gps = unidad.fecha_gps || null;
    var fecha_gps_marker = '-';
    var fecha_gps_marker_c = '-';


    // Preferir la marca de tiempo de recepción (_ts_sent) si está disponible
    try {
        if (fecha_gps) {
            const fecha_gpsDate = new Date(fecha_gps);
            if (!isNaN(fecha_gpsDate.getTime())) {
                fecha_gpsDate.setHours(fecha_gpsDate.getHours() + GPS_HOUR_OFFSET);
                fecha_gps_marker = fecha_gpsDate.toLocaleTimeString('es-EC', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                
            }
        }
    } catch (e) {
        fecha_gps_marker = '-';
    }

     try {
        if (fecha_gps) {
            const fecha_gpsDatec = new Date(fecha_gps);
            if (!isNaN(fecha_gpsDatec.getTime())) {
                fecha_gpsDatec.setHours(fecha_gpsDatec.getHours() + GPS_HOUR_OFFSET);
                fecha_gps_marker_c = fecha_gpsDatec.toLocaleString('es-EC', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });

                
            }
        }
    } catch (e) {
        fecha_gps_marker_c = '-';
    }
    
    var fecha_srv = unidad.fecha || null;
    var fecha_servidor = '-';
    try {
    if (fecha_srv) {
        const d2 = new Date(fecha_srv);
        if (!isNaN(d2.getTime())) {

            d2.setHours(d2.getHours());

            fecha_servidor = d2.toLocaleString('es-EC', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        } 
    }
    } catch (e) {
        fecha_servidor =  '-';
    }


    var voltaje = (unidad.voltaje != null) ? String(unidad.voltaje).substring(0,2) : '--';
    var velocidad_num = Number(unidad.velocidad_actual) || 0;

    var estado = unidad.estado_movil || ((velocidad_num==0)?'D':'M');
    if (unidad.diferencia != null && unidad.diferencia > 30) estado = 'no_envia_trama';
    if (!fecha_gps) estado = 'no_envia_trama';

    var iId = 'i' + unidad._id;
    var gId = 'g' + unidad._id;
    var bId = 'i' + unidad._id;
  
    var sentido = '';
    if (unidad.sentido){
        if (unidad.sentido == 'i') sentido = '<i class="fa fa-arrow-circle-right" title="IDA" style="color:green"></i>&nbsp&nbsp';
        else if (unidad.sentido == 'r') sentido = '<i class="fa fa-arrow-circle-left" title="RETORNO" style="color:#001672"></i>&nbsp&nbsp';
    }

    // Campos auxiliares que pueden venir en el payload
    var ruta_actual = unidad.ruta_actual || '';
    var ruta_fecha = unidad.ruta_fecha || '';
    var ruta_conductor = unidad.ruta_conductor || '';
    var ruta_hora_fin = unidad.ruta_hora_fin || '';

    var fecha_puerta_abierta = '--';
    var fecha_puerta_cerrada = '--';
    var fecha_puerta_abierta_trasera =  '--';
    var fecha_puerta_cerrada_trasera = '--';
    /*
    var fecha_puerta_abierta = unidad.fecha_puerta_abierta || '';
    var fecha_puerta_cerrada = unidad.fecha_puerta_cerrada || '';
    var fecha_puerta_abierta_trasera = unidad.fecha_puerta_abierta_trasera || '';
    var fecha_puerta_cerrada_trasera = unidad.fecha_puerta_cerrada_trasera || '';

    // Normalizar/formatear fechas de puertas según HistoricoController: restar 10 horas y mostrar 'd-m-Y H:i:s'
    function formatDoorDate(raw) {
        if (!raw) return '';
        try {
            let d = new Date(raw);
            if (!isNaN(d.getTime())) {
                // aplicar offset igual que en servidor (restar 10 horas)
                d.setHours(d.getHours() + GPS_HOUR_OFFSET);
                if (typeof d.format === 'function') return d.format('d-m-Y H:i:s');
                // fallback legible
                return d.toISOString().replace('T', ' ').split('.')[0];
            }
            return raw;
        } catch (e) {
            return raw;
        }
    }

    fecha_puerta_abierta = formatDoorDate(fecha_puerta_abierta);
    fecha_puerta_cerrada = formatDoorDate(fecha_puerta_cerrada);
    fecha_puerta_abierta_trasera = formatDoorDate(fecha_puerta_abierta_trasera);
    fecha_puerta_cerrada_trasera = formatDoorDate(fecha_puerta_cerrada_trasera);
    */
    var html = '';

    // Icono de ignición (se incluye directamente en el HTML antes del fa-bus)
    var ignIconId = 'ignicion_' + unidad._id;
    var ignicionHtml = '';
    if (unidad.ignicionf === 'on' || unidad.ignicionf === 'off') {
        ignicionHtml = (unidad.ignicionf === 'on')
            ? '<i id="' + ignIconId + '" class="fa fa-dot-circle-o" title="IG ON"  style="color:green; pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;'
            : '<i id="' + ignIconId + '" class="fa fa-dot-circle-o" title="IG OFF" style="color:red;   pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;';
    }

    // Generar exactamente el HTML con el mismo look según estado
    switch (estado) {
        case 'D':
            html += '' +
                ((unidad.climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ((unidad.rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                sentido + ignicionHtml + '<i id="' + iId + '" onclick="velocimetro_change('+ (unidad.velocidad_actual || 0) +');" class="fa fa-bus" style="color:#F44336"></i>&nbsp' +
                (unidad.descripcion || '') + '&nbsp&nbsp <i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ (unidad.latitud||'') +'\',\''+ (unidad.longitud||'') +'\');" class="fa fa-map-marker" style="color:#F44336"></i>&nbsp' + (fecha_gps_marker || '-') + '  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp' + Math.round(velocidad_num) + '' +
                '&nbsp&nbsp&nbsp<i id="bolt_' + unidad._id + '" class="fa fa-bolt" style="color:#F44336"></i>&nbsp' + voltaje + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#F44336"></i>&nbsp' + (unidad.contador_total||'') + " | " + (unidad.contador_diario||'') +
                '&nbsp&nbsp&nbsp' + ((unidad.is_atm !== undefined && unidad.is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'') +
                '&nbsp&nbsp&nbsp|&nbsp&nbsp';

            html += (unidad.puerta ? ((unidad.puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                ((unidad.puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += (unidad.puerta_trasera ? ((unidad.puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                ((unidad.puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += '&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">' + ruta_actual + '</font>' + '&nbsp&nbsp<font color="black">(' + ruta_fecha + ')</font>-<font color="red">(' + ruta_hora_fin + ')</font>&nbsp&nbsp<font color="black">' + ruta_conductor + '</font>';

            if (unidad.bitacora && unidad.bitacora != "") {
                var img = (unidad.bitacora=="R")?'/images/police.png':((unidad.bitacora=="M")?'/images/mantenimiento.png':'/images/other.png');
                html += '&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ unidad._id + '\');" width="20" height="20" src="'+ img + '"/>';
            }
            break;

        case 'E':
            html += '' +
                ((unidad.climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ((unidad.rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ignicionHtml + '<i id="' + iId + '" onclick="velocimetro_change('+ (unidad.velocidad_actual || 0) +');" class="fa fa-bus" style="color:#f49a16"></i>&nbsp' + (unidad.descripcion||'') +
                '&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ (unidad.latitud||'') +'\',\''+ (unidad.longitud||'') +'\');" class="fa fa-map-marker" style="color:#f49a16"></i>&nbsp' + (fecha_gps_marker || '-') + '  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp' + Math.round(velocidad_num) + '' +
                '&nbsp&nbsp&nbsp<i id="bolt_' + unidad._id + '" class="fa fa-bolt" style="color:#f49a16"></i>&nbsp' + voltaje + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#f49a16"></i>&nbsp' + (unidad.contador_total||'') + " | " + (unidad.contador_diario||'') +
                '&nbsp&nbsp&nbsp|&nbsp&nbsp';

            html += (unidad.puerta ? ((unidad.puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                ((unidad.puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += (unidad.puerta_trasera ? ((unidad.puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                ((unidad.puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += '&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">' + ruta_actual + '</font>' + '&nbsp&nbsp<font color="black">(' + ruta_fecha + ')</font>-<font color="red">(' + ruta_hora_fin + ')</font>&nbsp&nbsp<font color="black">' + ruta_conductor + '</font>';

            if (unidad.bitacora && unidad.bitacora != "") {
                var img2 = (unidad.bitacora=="R")?'/images/police.png':((unidad.bitacora=="M")?'/images/mantenimiento.png':'/images/other.png');
                html += '&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ unidad._id + '\');" width="20" height="20" src="'+ img2 + '"/>';
            }
            break;

        case 'M':
            html += '' +
                ((unidad.climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ((unidad.rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                sentido + ignicionHtml + '<i id="' + iId + '" onclick="velocimetro_change('+ (unidad.velocidad_actual || 0) +');" class="fa fa-bus" style="color:#00AA88"></i>&nbsp' + (unidad.descripcion||'') +
                '&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ (unidad.latitud||'') +'\',\''+ (unidad.longitud||'') +'\');" class="fa fa-map-marker" style="color:#00AA88"></i>&nbsp' + (fecha_gps_marker || '-') + '  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp' + Math.round(velocidad_num) + '' +
                '&nbsp&nbsp&nbsp<i id="bolt_' + unidad._id + '" class="fa fa-bolt" style="color:#00AA88"></i>&nbsp' + voltaje + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#00AA88"></i>&nbsp' + (unidad.contador_total||'') + " | " + (unidad.contador_diario||'') +
                '&nbsp&nbsp&nbsp|&nbsp&nbsp';

            html += (unidad.puerta ? ((unidad.puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                ((unidad.puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += (unidad.puerta_trasera ? ((unidad.puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                ((unidad.puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += '&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">' + ruta_actual + '</font>' + '&nbsp&nbsp<font color="black">(' + ruta_fecha + ')</font>-<font color="red">(' + ruta_hora_fin + ')</font>&nbsp&nbsp<font color="black">' + ruta_conductor + '</font>';

            if (unidad.bitacora && unidad.bitacora != "") {
                var img3 = (unidad.bitacora=="R")?'/images/police.png':((unidad.bitacora=="M")?'/images/mantenimiento.png':'/images/other.png');
                html += '&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ unidad._id + '\');" width="20" height="20" src="'+ img3 + '"/>';
            }
            break;

        default:
            html += '' +
                ((unidad.climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ((unidad.rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                ignicionHtml + '<i id="' + iId + '" onclick="velocimetro_change('+ (unidad.velocidad_actual || 0) +');" class="fa fa-bus" style="color:#990073"></i>&nbsp' + (unidad.descripcion||'') +
                '&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ (unidad.latitud||'') +'\',\''+ (unidad.longitud||'') +'\');" class="fa fa-map-marker" style="color:#990073"></i>&nbsp' + (fecha_gps_marker || '-') + '  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp' + Math.round(velocidad_num) + '' +
                '&nbsp&nbsp&nbsp<i id="bolt_' + unidad._id + '" class="fa fa-bolt" style="color:#990073"></i>&nbsp' + voltaje + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#990073"></i>&nbsp' + (unidad.contador_total||'') + " | " + (unidad.contador_diario||'') +
                '&nbsp&nbsp&nbsp|&nbsp&nbsp';

            html += (unidad.puerta ? ((unidad.puerta==='PUERTA ABIERTA (DELANTERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta:
                ((unidad.puerta==='PUERTA CERRADA (DELANTERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += (unidad.puerta_trasera ? ((unidad.puerta_trasera==='PUERTA ABIERTA (TRASERA)')?'<img src="../images/opendoor.png" height="20" width="20">'+fecha_puerta_abierta_trasera:
                ((unidad.puerta_trasera==='PUERTA CERRADA (TRASERA)')?'<img src="../images/closedoor.png" height="20" width="20">'+ fecha_puerta_cerrada_trasera :'<font color="red"><strong>---</strong></font>')):'<font color="red"><strong>---</strong></font>');

            html += '&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">' + ruta_actual + '</font>' + '&nbsp&nbsp<font color="black">(' + ruta_fecha + ')</font>-<font color="red">(' + ruta_hora_fin + ')</font>&nbsp&nbsp<font color="black">' + ruta_conductor + '</font>';

            if (unidad.bitacora && unidad.bitacora != "") {
                var img4 = (unidad.bitacora=="R")?'/images/police.png':((unidad.bitacora=="M")?'/images/mantenimiento.png':'/images/other.png');
                html += '&nbsp&nbsp&nbsp|&nbsp&nbsp <img id="' + bId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_Bitacora(\''+ unidad._id + '\');" width="20" height="20" src="'+ img4 + '"/>';
            }
            break;
    }

    // Insertar o actualizar el LI sin alterar la interfaz externa
    var li = document.getElementById(unidad._id);
    if (!li) {
        var ul = document.getElementById('ul_unidades');
        if (!ul) return;
        li = document.createElement('li');
        li.className = 'list-group-item';
        li.id = unidad._id;
        ul.prepend(li);
    }

    li.innerHTML = html;
    // Si la unidad está en modo BUFF, re-aplicar la clase de parpadeo
    // (li.innerHTML la borra al reconstruir el contenido)
    if (li._is_buff) {
        try {
            var bEl = document.getElementById('g' + unidad._id);
            if (bEl) bEl.classList.add('marker-buff-blink');
        } catch (e) {}
    }
    // Evaluar bolt-power-blink leyendo tiempo_power desde currentU (igual que ruta_actual),
    // con fallback a las propiedades del LI (establecidas por el SSE unidad.power).
    try {
        var _tp  = (unidad.tiempo_power != null) ? parseFloat(unidad.tiempo_power) : (li._tiempo_power || 0);
        var _tpu = unidad.tiempo_power_update || (li._tiempo_power_update ? new Date(li._tiempo_power_update).toISOString() : null);
        var _boltActivo = false;
        if (_tp > 0 && _tpu) {
            var _horasRest = _tp - ((Date.now() - new Date(_tpu).getTime()) / 3600000);
            _boltActivo = _horasRest > 0;
        }
        // Sincronizar las propiedades del LI con lo que tenemos en currentU
        li._tiempo_power        = _tp;
        li._tiempo_power_update = _tpu ? new Date(_tpu) : null;
        li._is_power_blink      = _boltActivo;
        var boltEl = document.getElementById('bolt_' + unidad._id);
        if (boltEl) {
            if (_boltActivo) {
                boltEl.classList.add('bolt-power-blink');
            } else {
                boltEl.classList.remove('bolt-power-blink');
            }
        }
    } catch (e) {}
    li.currentU = unidad;
    li.currentFechagps = fecha_gps_marker;
    li.currentFechagpsC = fecha_gps_marker_c;

    li.currentFecha = fecha_servidor;

    // Aplicar filtro de búsqueda activo: si hay texto en #consulta,
    // mostrar sólo las unidades cuyo imei o descripcion coincidan.
    try {
        var _consulta = (document.getElementById('consulta').value || '').trim().toLowerCase();
        if (_consulta !== '') {
            var _imei = String(unidad.imei || '').toLowerCase();
            var _desc = String(unidad.descripcion || '').toLowerCase();
            var _match = _imei.indexOf(_consulta) !== -1 || _desc.indexOf(_consulta) !== -1;
            li.style.display = _match ? '' : 'none';
        } else {
            li.style.display = '';
        }
    } catch (e) {}

    li.onclick = function () {
        selectUnidad(this.currentU,this.currentFechagpsC,this.currentFecha,1); 
    };

  
}

</script>


<script>

    $(document).ready(function() {
        $('#ruta_general').select2({
            width: '100%',
        });

        $('#ruta').select2({
            width: '100%',
        });
    });

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
    var panelAbierto = false;
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
    panelNotificaciones.minimize();
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

            // appendUnidades ya limpia su caché y llama refreshVisibleUnidadesMeta internamente.
            // No se necesita fetch adicional post-append aquí.
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
                    '<div class="panel-body popup-content"  style="height:12em;overflow: auto;margin: 2px; padding: 2px; ">'+
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
                    '<li id="li_dir_actual2" style="display:none"><strong>Dirección Actual:</strong>&nbsp'+'<br/><span class="dir-wrap" id="dir_actual_marca2"></span></li>' +
                    '</ul>'+
                    '<button class="btn btn-info btn-block" onclick="ver_direccion(\'' 
                    + recorrido.lat + '\', \'' 
                    + recorrido.lng + '\', \'#dir_actual_marca2\', \'#li_dir_actual2\')">Ver Dirección</button>'+

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

    async function loadOSMRadars() {
    const res = await fetch('/radares');
    const radars = await res.json();

    radars.forEach(r => {
        new google.maps.Marker({
            map: map,
            position: {lat: r.lat, lng: r.lng},
            icon: {
                url: "/images/placeholder.png",
                scaledSize: new google.maps.Size(26, 26)
            },
            title: "Radar de velocidad"
        });
    });
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

    let poiMarkers = {
        police: [],
        fire_station: [],
        gas_station: [],
        hospital: []
    };

    const poiIcons = {
    police:       "https://img.icons8.com/color/96/policeman-male.png",     // Policía
    fire_station: "https://img.icons8.com/color/96/fire-truck.png",     // Bomberos
    gas_station:  "https://img.icons8.com/color/96/gas-station.png",    // Gasolinera
    hospital:     "https://img.icons8.com/color/96/hospital-3.png"      // Hospital
    };


    async function loadPOI(type) {
        const center = map.getCenter();
        const lat = center.lat();
        const lng = center.lng();

        const resp = await fetch(`/poi?lat=${lat}&lng=${lng}&type=${type}`);
        const data = await resp.json();

        if (!data.places) return;

        data.places.forEach(place => {
        
        const name = place.displayName?.text || place.displayName || "Sitio";
        const address = place.formattedAddress?.text || place.formattedAddress || "Dirección no disponible";

        const marker = new google.maps.Marker({
            map,
            position: {
                lat: place.location.latitude,
                lng: place.location.longitude
            },
            title: String(name),
            icon: {
                url: poiIcons[type],
                scaledSize: new google.maps.Size(26, 26), 
                anchor: new google.maps.Point(12, 12) 
            }
        });

        const infowindow = new google.maps.InfoWindow({
            content: `<b>${name}</b>`
        });

        marker.addListener("click", () => infowindow.open(map, marker));
            // Guardar en tu array para limpieza
            poiMarkers[type].push(marker);
        });
    }

    function clearPOI(type) {
        poiMarkers[type].forEach(m => m.setMap(null));
        poiMarkers[type] = [];
    }

    async function loadPuntosImaginarios(cooperativa_id) {
        try {
            const resp = await fetch(`puntos-de-control/list-imaginarios/${cooperativa_id}`);
            const data = await resp.json();

            if (!data.data || data.data.length === 0) {
                console.warn("No hay puntos imaginarios.");
                return;
            }
            console.log(data)

            data.data.forEach(p => {
                const name = p.descripcion || "Punto Imaginario";
                const lat = parseFloat(p.latitud);
                const lng = parseFloat(p.longitud);

                const marker = new google.maps.Marker({
                    map,
                    position: { lat, lng },
                    title: String(name),
                    icon: {
                        url: "https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f4cd.svg", // Ícono externo no circular
                        scaledSize: new google.maps.Size(30, 30),
                        anchor: new google.maps.Point(12, 32)
                    }
                });

                const infowindow = new google.maps.InfoWindow({
                    content: `
                        <div>
                            <b>${name}</b><br>
                        </div>
                    `
                });

                marker.addListener("click", () => infowindow.open(map, marker));

                // Guardar para limpieza si manejas arrays globales
                markersPuntosImaginarios.push(marker);
            });

        } catch (error) {
            console.error("Error cargando puntos imaginarios:", error);
        }
    }
    
    let markersPuntosImaginarios = [];
    function clearPuntosImaginarios() {
        markersPuntosImaginarios.forEach(marker => marker.setMap(null));
        markersPuntosImaginarios = [];
    }


    var array_marcador=[];
    var array_marcador_angulos=[];

    var guayaquil = {lat: -2.1775151734461176, lng: -79.91094589233398};


    function initMap() {
        // === Mapa base de Google (ROADMAP) ===
        map = new google.maps.Map(document.getElementById('map'), {
            center: guayaquil,
            scrollwheel: true,
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP, 
            mapTypeControl: true,
            streetViewControl: true
        });
        map.setMapTypeId("OSM");

        // === Agregar OpenStreetMap como tipo adicional ===
        map.mapTypes.set("OSM", new google.maps.ImageMapType({
            getTileUrl: function(coord, zoom) {
                var tilesPerGlobe = 1 << zoom;
                var x = coord.x % tilesPerGlobe;
                if (x < 0) x = tilesPerGlobe + x;
                return "https://tile.openstreetmap.org/" + zoom + "/" + x + "/" + coord.y + ".png";
            },
            tileSize: new google.maps.Size(256, 256),
            name: "OpenStreetMap",
            maxZoom: 19
        }));

        map.setOptions({
            mapTypeControlOptions: {
                mapTypeIds: [
                    google.maps.MapTypeId.ROADMAP,
                    google.maps.MapTypeId.SATELLITE,
                    google.maps.MapTypeId.HYBRID,
                    "OSM"
                ]
            }
        });

        //TRAFICO
        var trafficLayer = new google.maps.TrafficLayer();
        var trafficVisible = false;

        var trafficControlDiv = document.createElement("div");
        trafficControlDiv.style.margin = "10px";
        trafficControlDiv.style.display = "none"; // Oculto por defecto

        var trafficButton = document.createElement("div");
        trafficButton.innerHTML = `
            <div style="
                display:flex;
                align-items:center;
                gap:6px;
                background:#ffffff;
                color:#202124;
                padding:10px 14px;
                border-radius:12px;
                box-shadow:0 4px 10px rgba(0,0,0,0.15);
                cursor:pointer;
                font-family:'Roboto', Arial, sans-serif;
                font-size:13px;
                font-weight:600;
                transition:all .25s ease;
                border:1px solid #dadce0;
            ">
                <span id="trafficIcon">🚦</span>
                <span id="trafficText">Ver tráfico</span>
            </div>
        `;
        var btn = trafficButton.firstElementChild;

        btn.onmouseover = () => { btn.style.boxShadow = "0 6px 14px rgba(0,0,0,0.25)"; btn.style.transform="translateY(-2px)"; };
        btn.onmouseout = () => { btn.style.boxShadow = "0 4px 10px rgba(0,0,0,0.15)"; btn.style.transform="translateY(0)"; };

        btn.onclick = () => {
            trafficVisible = !trafficVisible;
            var icon = document.getElementById("trafficIcon");
            var text = document.getElementById("trafficText");

            if (trafficVisible) {
                map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
                trafficLayer.setMap(map);
                const trafficOnSVG = `
                <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <!-- Marco activo -->
                <rect width="48" height="48" rx="10" fill="#e0f2f1" stroke="#00838f" stroke-width="3"/>

                <!-- Fondo gris ciudad -->
                <rect x="4" y="4" width="40" height="40" rx="8" fill="#b0bec5"/>

                <!-- Calle horizontal gris -->
                <rect x="4" y="21" width="40" height="6" rx="3" fill="#eceff1"/>

                <!-- Calle vertical gris -->
                <rect x="21" y="4" width="6" height="40" rx="3" fill="#eceff1"/>

                <!-- Verde -->
                <rect x="22" y="4" width="4" height="15" rx="2" fill="#00c853"/>

                <!-- Amarillo -->
                <rect x="22" y="19" width="4" height="10" rx="2" fill="#ffea00"/>

                <!-- Rojo -->
                <rect x="22" y="29" width="4" height="15" rx="2" fill="#d50000"/>
                </svg>
                `;
                icon.innerHTML  = trafficOnSVG;   
                text.textContent = "Vista tráfico";
                btn.style.background = "#fce8e6";
                btn.style.color = "#d93025";
                btn.style.border = "3px solid #f28b82";
                
            } else {
                trafficLayer.setMap(null);
                map.setMapTypeId("OSM");

                icon.textContent = "🚦";
                text.textContent = "Ver tráfico";
                btn.style.background = "#ffffff";
                btn.style.color = "#202124";
                btn.style.border = "1px solid #dadce0";
            }
        };

        trafficControlDiv.appendChild(trafficButton);
        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(trafficControlDiv);
        window.trafficControlDiv = trafficControlDiv;


      // === Buscador de direcciones ===
        var searchBox = new google.maps.places.SearchBox(address);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(address);

        let searchMarker = null; // marcador único
        let bounds = new google.maps.LatLngBounds();

        searchBox.addListener('places_changed', function() {

            var places = searchBox.getPlaces();
            if (!places || places.length === 0) return;

            // limpiar marcador anterior
            if (searchMarker) {
                searchMarker.setMap(null);
            }

            // Limpiar bounds si quieres resetear
            bounds = new google.maps.LatLngBounds();

            places.forEach(function(place) {
                if (!place.geometry || !place.geometry.location) return;

                // Crear marcador como Google Maps hace normalmente
                searchMarker = new google.maps.Marker({
                    map: map,
                    position: place.geometry.location,
                    title: place.name,
                    animation: google.maps.Animation.DROP
                });

                // Ajustar mapa al lugar encontrado
                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });

            map.fitBounds(bounds);
            map.setZoom(16); // puedes ajustar o quitar
        });


        // === Líneas base ===
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

        // === Configuración según tipo de usuario ===
        @if(isset($id_coop))
            document.getElementById('cooperativa').value = '{{$id_coop}}';
            //conectarSSE('{{$id_coop}}');
            setUnidadesOnMap('{{$id_coop}}', true);

            //setInterval(setUnidadesOnMap, 30000, '{{$id_coop}}');
        @endif

        @if(Auth::user()->tipo_usuario->valor==2 || Auth::user()->tipo_usuario->valor==3 || Auth::user()->tipo_usuario->valor==4
            || Auth::user()->tipo_usuario->valor==5)
            //conectarSSE('*');
            setUnidadesOnMap('', true);

            //setInterval(setUnidadesOnMap, 30000, null);
        @endif

        @if(Auth::user()->tipo_usuario->valor != 1)
            document.getElementById('div-cooperativa').style = "display:none;";
        @endif

        // === Polilíneas de rutas ===
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
        var ii, jj;
        var colorFlecha = 'red';

        map.addListener('zoom_changed', function() {
            if (map.getZoom() <= 13) {
                for (ii = 0; ii < array_marcador.length; ii++)
                    array_marcador[ii].setIcon(icono_bus);
            } else {
                if (map.getZoom() > 13) {
                    for (ii = 0; ii < array_marcador.length; ii++) {
                        if (array_marcador[ii].sentido === 'i' || array_marcador[ii].sentido === 'r') {
                            colorFlecha = (array_marcador[ii].sentido === 'r') ? '#0022AB' : '#00AA88';
                        }
                        for (jj = 0; jj < array_marcador_angulos.length; jj++) {
                            if (array_marcador_angulos[jj].indice == ii) {
                                icono_flecha = {
                                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                                    scale: 3,
                                    fillColor: colorFlecha,
                                    fillOpacity: 1.0,
                                    strokeColor: '#000000',
                                    strokeOpacity: 1.0,
                                    strokeWeight: 1.0,
                                    rotation: array_marcador_angulos[jj].rotacion,
                                    labelOrigin: new google.maps.Point(1, 10)
                                };
                            }
                        }
                        array_marcador[ii].setIcon(icono_flecha);
                    }
                }
            }

            if (map.getZoom() >= 15 && map.getZoom() < 18) {
                polyline.setOptions({ strokeWeight: 13 });
                polyline2.setOptions({ strokeWeight: 2 });
                for (i = 0; i < arrayPoly.length; i++) {
                    arrayPoly[i].setOptions({ strokeWeight: 13 });
                    arrayPoly2[i].setOptions({ strokeWeight: 2 });
                }
            } else if (map.getZoom() >= 18) {
                if (map.getZoom() > 18) {
                    polyline.setOptions({ strokeWeight: 20 });
                    polyline2.setOptions({ strokeWeight: 3 });
                    for (i = 0; i < arrayPoly.length; i++) {
                        arrayPoly[i].setOptions({ strokeWeight: 20 });
                        arrayPoly2[i].setOptions({ strokeWeight: 3 });
                    }
                }
            }
        });
        //loadOSMRadars();
        $('#menu_toggle').trigger('click');

         // === BOTONES DE POI ===
        document.querySelectorAll("#poi-bar .poi-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const type = btn.getAttribute("data-type");

                if (btn.classList.contains("active")) {
                    clearPOI(type);
                    btn.classList.remove("active");
                } else {    
                    loadPOI(type);
                    btn.classList.add("active");
                }
            });
        });

    }

    function mostrarBotonTrafico() {
        if (window.trafficControlDiv) {
            window.trafficControlDiv.style.display = "block";
        }
    }

    function ocultarBotonTrafico() {
        if (window.trafficControlDiv) {
            window.trafficControlDiv.style.display = "none";
        }
    }

    function mostrarPoiBar() {
        document.getElementById("poi-bar").style.display = "flex";
    }

    function ocultarPoiBar() {
        document.getElementById("poi-bar").style.display = "none";
    }

    function ver_direccion(lat, lon, label, li){
        $(li).show();
        $(label).text('Buscando...');
        $.ajax({
            url: '/get_address', 
            type: 'POST',
            data: {lat: lat, lon: lon},
            dataType: 'json',
            success: function(response) {
                if(response.success){
                    $(label).text(response.ubicacion);
                }
            },
            error: function(xhr, status, error) {
                $(label).text('');
                console.log("Ocurrió un error  al obtener la dirección");
            }
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

    setInterval(verifyGoogleMSG, 2000, null);

    function setMarcadorUnidad(unidad, fecha_gps_, fecha_servidor_, _is) {

        // =========================
        // ESTADO
        // =========================
        let estado = '-';
        switch (unidad.estado_movil) {
            case 'M': estado = 'En movimiento'; break;
            case 'D': estado = 'Detenido'; break;
            case 'E': estado = 'Pérdida de GPS'; break;
        }

        if (estado === '-') {
            estado = (parseFloat(unidad.velocidad_actual) === 0)
                ? 'Detenido'
                : 'En movimiento';
        }

        // =========================
        // FECHAS (SEGURAS)
        // =========================
        let fecha = '-';
        let fecha_gps = '-';

        try {
            if (_is === 0 && fecha_gps_?.fecha_gps) {
                const dGps = new Date(fecha_gps_.fecha_gps);
                dGps.setHours(dGps.getHours() + GPS_HOUR_OFFSET);
                fecha_gps = dGps.toLocaleString('es-EC', {
                    timeZone: 'America/Guayaquil',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false

                });

            }
            if (_is === 0 && fecha_servidor_?.fecha_servidor) {
                const dSrv = new Date(fecha_servidor_.fecha_servidor);
                fecha = dSrv.toLocaleString('es-EC', {
                    timeZone: 'America/Guayaquil',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false

                });
            }
            if (_is === 1) {
                fecha = fecha_servidor_;
                fecha_gps = fecha_gps_;
            }
        } catch (e) {}

        // =========================
        // SOLO CREAR POPUP SI ES UNIDAD ACTUAL
        // =========================
        let popupHtml = null;

        if (currentUnidad === null || currentUnidad === unidad._id) {
            popupHtml =
                '<div class="panel-body popup-content" style="height:12em;overflow:auto;margin:2px;padding:2px;">' +
                '<ul style="list-style:none;margin:3px;padding:3px;">' +
                `<li><strong>Disco:</strong> ${unidad.descripcion}</li>` +
                `<li><strong>Placa:</strong> ${unidad.placa || '-'}</li>` +
                `<li><strong>Velocidad:</strong> ${unidad.velocidad_actual || 0} km/h</li>` +
                `<li><strong>Voltaje:</strong> ${unidad.voltaje || '-'} v</li>` +
                `<li><strong>Mileage:</strong> ${unidad.mileage || '-'} km</li>` +
                `<li><strong>C. Total:</strong> ${unidad.contador_total || 0}</li>` +
                `<li><strong>C. Diario:</strong> ${unidad.contador_diario || 0}</li>` +
                `<li><strong>Estado:</strong> ${estado}</li>` +
                `<li><strong>Fecha servidor:</strong><br>${fecha}</li>` +
                `<li><strong>Fecha GPS:</strong><br>${fecha_gps}</li>` +
                '<li id="li_dir_actual" style="display:none"><strong>Dirección Actual:</strong><br>' +
                '<span class="dir-wrap" id="dir_actual_marca"></span></li>' +
                '</ul>' +
                '<div class="form-group">' +
                `<button class="btn btn-info btn-block" onclick="ver_direccion('${unidad.latitud}','${unidad.longitud}','#dir_actual_marca','#li_dir_actual')">Ver Dirección</button>` +
                `<button class="btn btn-primary btn-block" onclick="openCommandForm('${unidad.imei}','${unidad.latitud}','${unidad.longitud}');velocimetro_change(${unidad.velocidad_actual || 0});">Consola de comando</button>` +
                '</div>' +
                '</div>';
        }

        // =========================
        // MOVER / ACTUALIZAR MARKER
        // =========================
        if (
            unidad.latitud !== undefined &&
            unidad.longitud !== undefined &&
            !isNaN(parseFloat(unidad.latitud)) &&
            !isNaN(parseFloat(unidad.longitud))
        ) {
            
            const uid1 = unidad._id || unidad.unidad_id;
            addMarker(
                popupHtml,
                unidad.latitud,
                unidad.longitud,
                uid1,
                unidad.angulo,
                (unidad.orden != null)
                    ? `${unidad.descripcion} (${unidad.orden})`
                    : unidad.descripcion,
                unidad.velocidad_actual,
                unidad.sentido
            );
        }
    }
    function openCommandForm(imei, lat, long)
    {
      $('#commandModal').modal('show');
      $('#commandImei').val(imei);
      $('#latitudc').val(lat);
      $('#longitudc').val(long);

    }

    let logTramasInterval = null;
    let logsRequest = null;
    let lastRenderedLogs = '';
    let isFetchingLogs = false;

    function getLogsTramas() {
        if (isFetchingLogs) {
            return;
        }

        let urlLogs = '{{ url('/api/command/read-logs') }}';
        let logsContent = $('#logsContent').val();

        // cancelar request anterior si sigue viva
        if (logsRequest && logsRequest.readyState !== 4) {
            logsRequest.abort();
        }

        isFetchingLogs = true;

        logsRequest = $.ajax({
            url: urlLogs,
            type: 'GET',
            data: { content: logsContent },
            dataType: 'json',
            success: function (data) {
                if (!data || !data.tramas) {
                    return;
                }

                let stringTramas = data.tramas.map(function (item) {
                    return item.created_at + ': ' + item.contenido;
                }).join('\n');

                // solo actualizar si cambió
                if (stringTramas !== lastRenderedLogs) {
                    $('#logsTramas').text(stringTramas);
                    lastRenderedLogs = stringTramas;
                }
            },
            error: function (xhr, status) {
                if (status !== 'abort') {
                    console.error('Error al obtener logs');
                }
            },
            complete: function () {
                isFetchingLogs = false;
            }
        });
    }

    function verLogsTramas() {
        getLogsTramas();

        if (logTramasInterval !== null) {
            clearInterval(logTramasInterval);
        }

        logTramasInterval = setInterval(function () {
            getLogsTramas();
        }, 10000);

        $('#logsModal').modal('show');
    }

    // detener al cerrar modal
    $('#logsModal').on('hidden.bs.modal', function () {
        if (logTramasInterval !== null) {
            clearInterval(logTramasInterval);
            logTramasInterval = null;
        }

        if (logsRequest && logsRequest.readyState !== 4) {
            logsRequest.abort();
        }
    });

    
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
        var url = '{{url('/home-test')}}';
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
                 let mapaCentrado = false;
				for(var j=0;j<data.unidades.length;j++)
				{
					if(data.unidades[j].latitud!=null && data.unidades[j].longitud!=null 
                        && data.unidades[j].latitud!=undefined && data.unidades[j].longitud!=undefined){
						setMarcadorUnidad(data.unidades[j],data.array_fechas[j],data.array_fechas[j],0);

                        if (!mapaCentrado && load) {
                            let lat = parseFloat( data.unidades[j].latitud);
                            let lng = parseFloat( data.unidades[j].longitud);

                            if (!isNaN(lat) && !isNaN(lng)) {
                                map.setCenter({ lat: lat, lng: lng });
                                map.setZoom(13);
                                mapaCentrado = true;
                            }
                        }

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
        clearPuntosImaginarios();
        var div_unidad=  $('#div-unidad');
        var div_mensaje=  $('#div-mensaje');
        var unidad_movimiento=0;
        var unidad_stop=0;
        var unidad_no=0;
        var unidad_e=0;
        var ul=$('#ul_unidades');
        let bloques = $('#cooperativa').find("option:selected").data("bloques");
        let trafico = $('#cooperativa').find("option:selected").data("trafico");

        if (trafico) {
            mostrarBotonTrafico();
            mostrarPoiBar();
        } else {
            ocultarBotonTrafico();
            ocultarPoiBar();

        }

        if(trafico){
            let cooperativa = $('#cooperativa').find("option:selected");
            loadPuntosImaginarios(cooperativa.val());
        }
        else{
            clearPuntosImaginarios();
        }
      
        

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
            $('#txtBloque').val('');
        }
        else
        {

          llenarNotificaciones(data);
          div_mensaje.empty();
          if (bloques === true) {
            $('#txtBloque').text("Activo: Bloque "+data.bloque);
          }
          else{
            $('#txtBloque').val('');

          }

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
                var gId = 'g' + data.unidades[i]._id; 
                var bId = 'i' + data.unidades[i]._id; 
                console.log(data.unidades[i].climatizada);

                sentido='';

                if(data.unidades[i].sentido){
                    if(data.unidades[i].sentido=='i'){
                        sentido='<i class="fa fa-arrow-circle-right" title="IDA" style="color:green"></i>&nbsp&nbsp';
                    }
                    else if(data.unidades[i].sentido=='r'){
                        sentido='<i class="fa fa-arrow-circle-left" title="RETORNO" style="color:#001672"></i>&nbsp&nbsp';
                    }
                }

                // Icono de ignición — se incluye directamente en el HTML antes del fa-bus
                var _ignIconIdI = 'ignicion_' + data.unidades[i]._id;
                var ignicionHtmlI = '';
                var _ignfI = data.unidades[i].ignicionf;
                if (_ignfI === 'on' || _ignfI === 'off') {
                    ignicionHtmlI = (_ignfI === 'on')
                        ? '<i id="' + _ignIconIdI + '" class="fa fa-dot-circle-o" title="IG ON"  style="color:green; pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;'
                        : '<i id="' + _ignIconIdI + '" class="fa fa-dot-circle-o" title="IG OFF" style="color:red;   pointer-events:none; cursor:default;"></i>&nbsp;&nbsp;';
                }

                switch(estado)
                {
                    case 'D':
                        unidad_stop++;
                        ul.append(
                                '<li class="list-group-item" id=\''+ data.unidades[i]._id + '\'>'+
                                    ((data.unidades[i].climatizada==true)?'<img src="../images/snowflake.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                    ((data.unidades[i].rampa==true)?'<img src="../images/disabled.png" height="20" width="20">&nbsp&nbsp':'&nbsp&nbsp')+
                                sentido+ignicionHtmlI+'<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#F44336"></i>&nbsp'+ 
                                data.unidades[i].descripcion+'&nbsp&nbsp <i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#F44336"></i>&nbsp'+fecha_gps+'  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp'+  Math.round(data.unidades[i].velocidad_actual)+''+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#F44336"></i>&nbsp'+voltaje
                                +'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#F44336"></i>&nbsp'+data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'')):'')
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
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
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
                                ignicionHtmlI+'<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#f49a16"></i>&nbsp'+ data.unidades[i].descripcion
                                +'&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#f49a16"></i>&nbsp'+fecha_gps+'  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp'+ Math.round(data.unidades[i].velocidad_actual)+''+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#f49a16"></i>&nbsp'+voltaje
                                +'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#f49a16"></i>&nbsp'+data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'')):'')
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
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
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
                                sentido+ignicionHtmlI+'<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#00AA88"></i>&nbsp'+ data.unidades[i].descripcion
                                +'&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#00AA88"></i>&nbsp'+fecha_gps+'  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp'+ Math.round(data.unidades[i].velocidad_actual)+''+'&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#00AA88"></i>&nbsp'+voltaje+'&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#00AA88"></i>&nbsp'
                                +data.unidades[i].contador_total+" | "+data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'')):'')
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
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
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
                                ignicionHtmlI+'<i id="' + iId + '" onclick="velocimetro_change('+data.unidades[i].velocidad_actual+');$(\'#progress\').modal(\'show\');selectUnidad(\''+ data.unidades[i]._id+'\',\''+fecha_gps_marker+'\',\''+fecha_servidor+'\',1);" class="fa fa-bus" style="color:#990073"></i>&nbsp' + data.unidades[i].descripcion 
                                + '&nbsp&nbsp<i id="' + gId + '" onclick="$(\'#progress\').modal(\'show\');selectUnidad_GEOCODE(\''+ data.unidades[i].latitud+'\',\''+ data.unidades[i].longitud+'\');" class="fa fa-map-marker" style="color:#990073"></i>&nbsp' + fecha_gps + '  <i class="fa fa-tachometer" style="color:#000E4C"></i>&nbsp' + Math.round(data.unidades[i].velocidad_actual) + '' + '&nbsp&nbsp&nbsp<i class="fa fa-bolt" style="color:#990073"></i>&nbsp' + voltaje + '&nbsp&nbsp&nbsp<i class="fa fa-users" style="color:#990073"></i>&nbsp' + data.unidades[i].contador_total 
                                + " | " + data.unidades[i].contador_diario
                                +'&nbsp&nbsp&nbsp'+((data.unidades[i].is_atm !== "undefined")?((data.unidades[i].is_atm===0)?'':((data.unidades[i].is_atm===1)?'<font color="green"><strong>ATM</strong></font>':'')):'')
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
                                +'&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp<font color="black">'+ruta_actual+'</font>'
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
                    };
                    // Establecer estado inicial del bolt blink según tiempo_power guardado en BD
                    var _tp  = (currentU.tiempo_power != null) ? parseFloat(currentU.tiempo_power) : 0;
                    var _tpuRaw = currentU.tiempo_power_update;
                    // tiempo_power_update puede llegar como string ISO, objeto {$date:...} de MongoDB, o null
                    var _tpu = null;
                    if (_tpuRaw) {
                        try {
                            // MongoDB extended JSON: {"$date": "..."} o {"$date": <ms>}
                            if (typeof _tpuRaw === 'object' && _tpuRaw.$date) {
                                _tpu = new Date(_tpuRaw.$date);
                            } else {
                                _tpu = new Date(_tpuRaw);
                            }
                            if (isNaN(_tpu.getTime())) _tpu = null;
                        } catch(e) { _tpu = null; }
                    }
                    if (_tp > 0 && _tpu) {
                        var _horasRestI = _tp - ((Date.now() - _tpu.getTime()) / 3600000);
                        if (_horasRestI > 0) {
                            currentLi._is_power_blink        = true;
                            currentLi._tiempo_power          = _tp;
                            currentLi._tiempo_power_update   = _tpu;
                            var _boltElI = currentLi.querySelector('.fa-bolt');
                            if (_boltElI) _boltElI.classList.add('bolt-power-blink');
                        }
                    }
                }
            }
    
            $('#cantidad_no').text(unidad_no);
            $('#cantidad_movimiento').text(unidad_movimiento);
            $('#cantidad_e').text(unidad_e);
            $('#cantidad_stop').text(unidad_stop);

            // Forzar fetch inmediato de meta (tiempo_power, bolt_activo, ruta, etc.)
            // Limpiar caché Y unidadesMetaFetchedOnce para que el fetch ocurra de inmediato.
            for (var _j = 0; _j < data.unidades.length; _j++) {
                delete unidadesMetaCache[data.unidades[_j]._id];
                delete unidadesMetaFetchedOnce[data.unidades[_j]._id];
            }
            refreshVisibleUnidadesMeta();
        }

    }
	function cargarTodasLasUnidades()
	{
		currentUnidad = null;
        @if(isset($id_coop))
		    setUnidadesOnMap('{{$id_coop}}',true);
        @endif
	}


    function addMarker(html, latitude, longitude, id, angulo, placa,velocidad, sentido = false)
    {
        var icon;
        var mk;
        var colorFlecha='red';
        if (sentido === 'i' || sentido === 'r') {
            colorFlecha = (sentido === 'r') ? '#0022AB' : '#1FA463';
        }
        if (zoomUnidad)
        {
            if(id == zoomUnidadID){
                for(var i=0;i<array_marcador.length;i++)
                {
                    array_marcador[i].setMap(null);
                }
                
                if(map.getZoom()<=13 )
                    icon = {
                        url: '{{url("/images/autobu.png")}}',
                        scale: 1,
                        labelOrigin: new google.maps.Point(4, 25)
                    };
                else
                    icon = {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 3,
                        fillColor: colorFlecha, 
                        fillOpacity: 1.0,   
                        strokeColor: '#000000',  
                        strokeOpacity: 1.0,
                        strokeWeight: 1.0,
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
                mk.sentido = sentido;         
                
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
                    scale: 3,
                    fillColor: colorFlecha, 
                    fillOpacity: 1.0,   
                    strokeColor: '#000000',  
                    strokeOpacity: 1.0,
                    strokeWeight: 1.0,
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
                if (data.error){
                    alert('There was an error executing the command.');
                }
                else{
                    alert('The command was successfully executed.');
                    $('#responseMessage').val(data.respuesta)
                }
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

         $('#btnCompartir').click(function (e) {
            e.preventDefault(); 
            var lat = document.getElementById('latitudc').value.trim();
            var lng = document.getElementById('longitudc').value.trim();

            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                alert(" No existen coordenadas válidas para compartir.");
                return;
            }

            var url = `https://www.google.com/maps?q=${lat},${lng}`;
            window.open(url, '_blank');
        });

        $('#btnFoto').click(function (e) {
            var imei = $('#commandImei').val();
            var message = 'AT+GTTAP=gv300,0,,,2,,,,,FFFF$';
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

        $('#btnPUbicacion').click(function (e) {
            var imei = $('#commandImei').val();
            var message = 'AT+GTRTO=gv300,1,,,,,,FFFF$';
            $.post('{{ url("/api/command") }}', {
                imei : imei,
                message : message
            }, function (data) {
                if (data.error) {
                    alert('Hubo un error al ejecutar el comando.');
                } else {
                    $('#commandModal').modal('hide');
                    alert('Comando ejecutado exitosamente.');
                    // Cerrar modal
                }

            }, 'json');
        });

        

        $('#ruta').change(function () {
            $('#progress').modal('show');
            $.get('{{ url("/puntos") }}', { rutas : $('#ruta').val() }, function (data) {
                var puntos = data.puntos;

                // Limpiar rutas y marcadores previos
                if (polyline != null) polyline.setPath([]);
                if (polyline2 != null) polyline2.setPath([]);
                for (var i = 0; i < marcadoresRutas.length; i++)
                    marcadoresRutas[i].setMap(null);
                marcadoresRutas = [];

                if (!puntos) puntos = [];
                var path = [];

                // 🔸 Dibujar banderas
                for (var i = 0; i < puntos.length; i++) {
                    for (var j = 0; j < puntos[i].length; j++) {
                        const punto = puntos[i][j];
                        let markerPosition = null;

                        // tipo_mar == 1 → bandera normal
                        if (punto.tipo_mar == 1 && punto.latitud && punto.longitud) {
                            markerPosition = {
                                lat: parseFloat(punto.latitud),
                                lng: parseFloat(punto.longitud)
                            };
                        }
                        // tipo_mar == 2 → bandera en el centro del polígono
                        else if (punto.tipo_mar == 2 && punto.poligono) {
                            try {
                                var polyCoords = (typeof punto.poligono === "string")
                                    ? JSON.parse(punto.poligono)
                                    : punto.poligono;

                                var sumLat = 0, sumLng = 0, valid = 0;
                                polyCoords.forEach(function (p) {
                                    var lat = parseFloat(p.lat),
                                        lng = parseFloat(p.lng);
                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        sumLat += lat;
                                        sumLng += lng;
                                        valid++;
                                    }
                                });
                                if (valid > 0) {
                                    markerPosition = {
                                        lat: sumLat / valid,
                                        lng: sumLng / valid
                                    };
                                }
                            } catch (e) {
                                console.log(" Error al calcular centroide:", e);
                            }
                        }

                        // Crear bandera si hay posición válida
                        if (markerPosition) {
                            var icon = {
                                url: '{{url("/images/flag.png")}}',
                                scaledSize: new google.maps.Size(25, 25),
                                labelOrigin: new google.maps.Point(4, 25)
                            };
                            var marker = new google.maps.Marker({
                                map: map,
                                position: markerPosition,
                                icon: icon,
                                label: punto.descripcion
                            });
                            marcadoresRutas.push(marker);
                        }
                    }
                }

                //  Dibujar rutas
                var rutas = data.rutas || [];
                for (var i = 0; i < rutas.length; i++) {
                    var recorrido = rutas[i].recorrido;
                    for (var j = 0; j < recorrido.length; j++) {
                        path.push({
                            lat: parseFloat(recorrido[j].lat),
                            lng: parseFloat(recorrido[j].lng)
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
                if (data.rutas != null) {
                    for (var i = 0; i < data.rutas.length; i++)
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
            $.get('{{ url("/puntos-atm") }}', { rutas : $('#ruta_atm').val() }, function (data) {
                console.log(data);
                var puntos = data.puntos;

                // Limpiar rutas y marcadores previos
                if (polyline != null) polyline.setPath([]);
                if (polyline2 != null) polyline2.setPath([]);
                for (var i = 0; i < marcadoresRutas.length; i++)
                    marcadoresRutas[i].setMap(null);
                marcadoresRutas = [];
                var path = [];

                if (!puntos) puntos = [];

                //  Dibujar banderas
                for (var i = 0; i < puntos.length; i++) {
                    for (var j = 0; j < puntos[i].length; j++) {
                        const punto = puntos[i][j];
                        let markerPosition = null;

                        // tipo_mar == 1 → bandera normal
                        if (punto.tipo_mar == 1 && punto.latitud && punto.longitud) {
                            markerPosition = {
                                lat: parseFloat(punto.latitud),
                                lng: parseFloat(punto.longitud)
                            };
                        }
                        // tipo_mar == 2 → bandera en el centro del polígono
                        else if (punto.tipo_mar == 2 && punto.poligono) {
                            try {
                                var polyCoords = (typeof punto.poligono === "string")
                                    ? JSON.parse(punto.poligono)
                                    : punto.poligono;

                                var sumLat = 0, sumLng = 0, valid = 0;
                                polyCoords.forEach(function (p) {
                                    var lat = parseFloat(p.lat),
                                        lng = parseFloat(p.lng);
                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        sumLat += lat;
                                        sumLng += lng;
                                        valid++;
                                    }
                                });
                                if (valid > 0) {
                                    markerPosition = {
                                        lat: sumLat / valid,
                                        lng: sumLng / valid
                                    };
                                }
                            } catch (e) {
                                console.log(" Error al calcular centroide:", e);
                            }
                        }

                        // Crear bandera si hay posición válida
                        if (markerPosition) {
                            var icon = {
                                url: '{{url("/images/flag.png")}}',
                                scaledSize: new google.maps.Size(25, 25),
                                labelOrigin: new google.maps.Point(4, 25)
                            };
                            var marker = new google.maps.Marker({
                                map: map,
                                position: markerPosition,
                                icon: icon,
                                label: punto.descripcion
                            });
                            marcadoresRutas.push(marker);
                        }
                    }
                }

                //  Dibujar rutas
                var rutas = data.rutas || [];
                for (var i = 0; i < rutas.length; i++) {
                    var recorrido = rutas[i].recorrido;
                    for (var j = 0; j < recorrido.length; j++) {
                        path.push({
                            lat: parseFloat(recorrido[j].lat),
                            lng: parseFloat(recorrido[j].lng)
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


        $('#ruta_general').change(function () {
            $('#progress').modal('show');
            $.get('{{ url("/puntos") }}', {
                rutas : $('#ruta_general').val()
            }, function (data) {

                //  Limpiar rutas anteriores
                for (i = 0; i < arrayPoly.length; i++) {
                    arrayPoly[i].setPath([]);
                    arrayPoly2[i].setPath([]);
                }

                var puntos = data.puntos;
                var path = [];

                //  Limpiar marcadores previos
                for (var i = 0; i < marcadoresRutas_general.length; i++)
                    marcadoresRutas_general[i].setMap(null);
                marcadoresRutas_general = [];

                if (!puntos) puntos = [];

                for (var i = 0; i < puntos.length; i++) {
                    for (var j = 0; j < puntos[i].length; j++) {
                        const punto = puntos[i][j];
                        let markerPosition = null;

                        //  tipo_mar == 1 → coordenada normal
                        if (punto.tipo_mar == 1 && punto.latitud && punto.longitud) {
                            markerPosition = {
                                lat: parseFloat(punto.latitud),
                                lng: parseFloat(punto.longitud)
                            };
                        }
                        //  tipo_mar == 2 → centro del polígono
                        else if (punto.tipo_mar == 2 && punto.poligono) {
                            try {
                                var polyCoords = (typeof punto.poligono === "string")
                                    ? JSON.parse(punto.poligono)
                                    : punto.poligono;

                                var sumLat = 0, sumLng = 0, valid = 0;
                                polyCoords.forEach(function (p) {
                                    var lat = parseFloat(p.lat),
                                        lng = parseFloat(p.lng);
                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        sumLat += lat;
                                        sumLng += lng;
                                        valid++;
                                    }
                                });
                                if (valid > 0) {
                                    markerPosition = {
                                        lat: sumLat / valid,
                                        lng: sumLng / valid
                                    };
                                }
                            } catch (e) {
                                console.log(" Error al calcular centroide:", e);
                            }
                        }

                        //  Crear bandera si hay posición válida
                        if (markerPosition) {
                            var icon = {
                                url: '{{url("/images/flag.png")}}',
                                scaledSize: new google.maps.Size(25, 25),
                                labelOrigin: new google.maps.Point(4, 25)
                            };

                            var marker = new google.maps.Marker({
                                map: map,
                                position: markerPosition,
                                icon: icon,
                                label: punto.descripcion
                            });

                            marcadoresRutas_general.push(marker);
                        }
                    }
                }

                var rutas = data.rutas;
                if (!rutas) rutas = [];

                for (var i = 0; i < rutas.length; i++) {
                    path = [];
                    var recorrido = rutas[i].recorrido;
                    for (var j = 0; j < recorrido.length; j++) {
                        path.push({
                            lat: parseFloat(recorrido[j].lat),
                            lng: parseFloat(recorrido[j].lng)
                        });
                    }

                    var color = "#2ECC70";
                    if (rutas[i].color == 'A') color = '#0048D8';
                    if (rutas[i].color == 'V') color = '#2ECC70';
                    if (rutas[i].color == 'C') color = '#715050';
                    if (rutas[i].color == 'M') color = '#922BA0';
                    if (rutas[i].color == 'R') color = '#CC2E2E';
                    if (rutas[i].color == 'N') color = '#000000';

                    var polyline_general = new google.maps.Polyline({
                        path: path,
                        geodesic: true,
                        strokeColor: color,
                        strokeOpacity: 1.0,
                        strokeWeight: 4
                    });

                    var polyline2_general = new google.maps.Polyline({
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

                $('#progress').modal('hide');
            }, 'json');
        });

	});

</script>


<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
    </script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDfiiN9arSm8MnKyU4ELDSZoj9QV19bMCg&libraries=places,geometry&callback=initMap"
    async defer></script>
@endsection