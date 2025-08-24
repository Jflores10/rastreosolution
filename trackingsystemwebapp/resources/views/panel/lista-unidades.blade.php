@extends('layouts.app')
@section('title')
Unidades
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Lista de unidades</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Tabla</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            @if($tipo_usuario_valor=='1')
            <br />
            <button onclick="cleanForm('{{$tipo_usuario_valor}}');" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
            <br />
            @endif
            <form class="form-inline" name="form_search" method="GET" action="{{ url('/unidades/search') }}" id="form_search">
                {{ csrf_field() }}
                <div class="form-group{{ ($errors->has('cooperativa'))?' has-error':'' }}" {{ ($cooperativas->count() == 1)?'style=display:none;':'' }}>
                    <label for="cooperativa">Cooperativa</label>
                    <select onchange="mostrar();" name="cooperativa" id="cooperativa" class="form-control">
                        <option disabled {{ (!isset($coop))?'selected':'' }}>Seleccione una cooperativa...</option>
                        @foreach($cooperativas as $cooperativa)
                            <option {{ ((isset($coop) && $coop == $cooperativa->_id))?'selected':'' }} value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('cooperativa'))
                        <span class="help-block">
                            <strong>{{ $errors->first('cooperativa') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <input value="{{ isset($search)?$search:old('search') }}" name="search" type="text" class="form-control" placeholder="Búsqueda">
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button>
                    <input type="submit" value="Exportar" class="btn btn-success" name="exportar" />
                </div>
                <div class="form-group">
                    <div class="checkbox">
                        <input  {{ ((isset($estado) && $estado == 'A') || old('estado') == 'A')?'checked':'' }} name="estado" onchange=mostrar() id="mostrar_modo_activo" type="radio" value="A"/>Activos
                        <input {{ ((isset($estado) && $estado == 'I') || old('estado') == 'I')?'checked':'' }} name="estado" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="I" />   Inactivos
                        <input {{ ((isset($estado) && $estado == 'T') || !isset($estado) || old('estado') == 'T')?'checked':'' }} name="estado" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="T" />   Todos
                    </div>
                </div>
            </form>

            <script>
              @if(isset($opcion))
                var opcion ='{{$opcion}}';

                switch(opcion)
                {
                    case 'activos':
                        document.getElementById('mostrar_modo_activo').checked=true;
                        break;

                    case 'inactivos':
                        document.getElementById('mostrar_modo_inactivo').checked=true;
                        break;

                    case 'todos':
                        document.getElementById('mostrar_modo_todos').checked=true;
                        break;

                    default: break;
                }
                @endif
            </script>

            @if ($unidades->count() > 0)
            @php
                $userId = Auth::user()->_id;   
            @endphp
            <div class="table-responsive">
                <table class="table">
                    @if($tipo_usuario_valor=='1')
                     <th></th>
                    @endif
                    <th>Placa</th>
                    <th>Disco</th>
                    <th>Estado</th>
                    <th>Contador diario</th>
                    <th>Contador total</th>
                    <th>Velocidad actual</th>
                    @if(!isset($id_cooperativa))
                        <th>Cooperativa</th>
                    @endif
                    <th>Tipo de unidad</th>
                    <th>Registrada en ATM</th>
                    <th>Fecha Servidor</th>
                    <th>Fecha de creación</th>
                    <th>Fecha de modificación</th>
                    <th>Usuario creador</th>
                    <th>Usuario modificador</th>
                    @if($tipo_usuario_valor=='1')
                        <th></th>
                    @endif
                    @foreach ($unidades as $unidad)
                        <tr class="{{($unidad->estado=='I')?'danger':''}}">
                            @if($tipo_usuario_valor=='1')
                              <td><button onclick="editarUnidad('{{ url('/unidades/' . $unidad->_id) }}','{{$tipo_usuario_valor}}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                            @endif
                            <td>{{ $unidad->placa }}</td>
                            <td>{{ $unidad->descripcion }}</td>
                            <td>{{ ($unidad->estado_movil!=null)?$unidad->estado_movil:""}}</td>
                            <td>{{ $unidad->contador_diario }}</td>
                            <td>{{ $unidad->contador_total}}</td>
                            <td>{{ $unidad->velocidad_actual}} km/h</td>
                            @if(!isset($id_cooperativa))
                                 <td>{{ ($unidad->cooperativa!=null)?$unidad->cooperativa->descripcion:""}}</td>
                            @endif
                            <td>{{ ($unidad->tipo_unidad!=null)?$unidad->tipo_unidad->descripcion:""}}</td>
                            <td>{{ $unidad->atm }}</td>
                            @php
                                if($unidad->fecha!=null){
                                    $fechaServer= $unidad->fecha->toDateTime();
                                    date_sub($fechaServer, date_interval_create_from_date_string('5 hours'));
                                }
                            @endphp
                            <td>{{ ($unidad->fecha!=null)?$fechaServer->format('Y-m-d H:i:s'):""}}</td>
                            <td>{{ ($unidad->created_at!=null)?$unidad->created_at:""}}</td>
                            <td>{{ ($unidad->updated_at!=null)?$unidad->updated_at:""}}</td>
                            <td>{{ ($unidad->creador!=null)?$unidad->creador->name:""}}</td>
                            <td>{{ ($unidad->modificador!=null)?$unidad->modificador->name:""}}</td>
                           @if($tipo_usuario_valor=='1' && $userId=='591f208f3ebdfd46b3637212')
                            <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($unidad->estado=='A')?'checked':'' }} onchange="estadoUnidad('{{ url('/unidades/' . $unidad->_id) }}',(checked)?true:false);"></td>
                           @endif
                        </tr>
                    @endforeach
                </table>
            </div>
                    {{ $unidades->links() }}
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
        <h4 class="modal-title" id="modalLabel">Unidad</h4>
      </div>
      <div class="modal-body">
          <div class="row">
              <div class="col-lg-6 col-md-6 col-sm-12">
                  <div class="form-group" id="div-imei">
                      <label for="imei">IMEI</label>
                      <input class="form-control" name="imei" id="imei" type="text"/>
                      <span class="help-block" id="span_imei"></span>
                  </div>
                  <div class="form-group" id="div-placa">
                      <label for="placa">Placa</label>
                      <input class="form-control" name="placa" id="placa" type="text"/>
                      <span class="help-block" id="span_placa"></span>
                  </div>
                  <div class="form-group" id="div-descripcion">
                      <label for="descripcion">Disco</label>
                      <input class="form-control" name="descripcion" id="descripcion" type="text"/>
                      <span class="help-block" id="span_descripcion"></span>
                  </div>
                  <div class="form-group" id="div-cooperativa">
                      <label for="cooperativa_id">Cooperativa</label>
                      <select class="form-control" id="cooperativa_id" name="cooperativa_id">
                          <option disabled selected hidden>Seleccione...</option>
                          @foreach ($cooperativas as $cooperativa_id)
                              <option  value="{{ $cooperativa_id->_id }}">
                                  {{ $cooperativa_id->descripcion }}
                              </option>
                          @endforeach
                      </select>
                      <span class="help-block" id="span_cooperativa"></span>
                  </div>
                  <div class="form-group" id="div-tipo-unidad">
                      <label for="tipo_unidad_id">Tipo Unidad</label>
                      <select class="form-control" id="tipo_unidad_id" name="tipo_unidad_id">
                          <option value="" disabled selected hidden>Seleccione...</option>
                          @foreach ($tipos_unidades as $tipo_unidad_id)
                              <option value="{{ $tipo_unidad_id->_id }}">
                                  {{ $tipo_unidad_id->descripcion }}
                              </option>
                          @endforeach
                      </select>
                      <span class="help-block" id="span_tipo_unidad"></span>
                  </div>
                  <div class="form-group" id="div-marca">
                      <label for="marca">Marca</label>
                      <input class="form-control" name="marca" id="marca" type="text"/>
                      <span class="help-block" id="span_marca"></span>
                  </div>
                  <div class="form-group" id="div-modelo">
                      <label for="modelo">Modelo</label>
                      <input class="form-control" name="modelo" id="modelo" type="text"/>
                      <span class="help-block" id="span_modelo"></span>
                  </div>
              </div>
              <div class="col-lg-6 col-md-6 col-sm-12">
                  <div class="form-group" id="div-serie">
                      <label for="serie">Serie</label>
                      <input class="form-control" name="serie" id="serie" type="text"/>
                      <span class="help-block" id="span_serie"></span>
                  </div>
                  <div class="form-group" id="div-motor">
                      <label for="motor">Motor</label>
                      <input class="form-control" name="motor" id="motor" type="text"/>
                      <span class="help-block" id="span_motor"></span>
                  </div>
                  <div class="form-group" id="div-email-alarma">
                      <label for="email_alarma">Email</label>
                      <input class="form-control" name="email_alarma" id="email_alarma" type="email_alarma"/>
                      <span class="help-block" id="span_email_alarma"></span>
                  </div>
                  <div class="form-group">
                    <label for="velocidad">Velocidad máxima</label>
                    <input type="number" id="velocidad" name="velocidad" placeholder="Opcional" class="form-control" />
                  </div>
                  <div class="form-group">
                      <label>Enviar alarma cuando</label>
                      <div class="checkbox">
                          <label><input type="checkbox" name="sistema_energizado" id="sistema_energizado"
                                         /> El sistema se encuentra energizado</label>
                      </div>
                      <div class="checkbox">
                          <label><input type="checkbox" name="contador_cero_manual" id="contador_cero_manual"
                                        /> El contador ha sido puesto en cero manualmente</label>
                      </div>
                      <div class="checkbox">
                          <label><input type="checkbox" name="desconexion_sistema" id="desconexion_sistema"
                                        /> El sistema se encuentra desconectado</label>
                      </div>
                      <div class="checkbox">
                        <label><input type="checkbox" name="climatizada" id="climatizada"
                                      /> Unidad climatizada</label>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="rampa" id="rampa"
                                      /> Unidad con rampa</label>
                    </div>
                      <div class="checkbox">
                          <label><input type="checkbox" name="control_velocidad" id="control_velocidad"
                                         /> Control exceso velocidad</label>
                      </div>
                  </div>
                  <hr/>
                  <div class="form-group">
                      <div class="checkbox">
                        <label for="atm"><input name="atm" id="atm" type="checkbox" value="S"/> La unidad se encuentra registrada en ATM</label>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
        <button onclick="guardar();" type="button" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
        <button onclick="setUnidadConteo('{{ url('/unidad/conteo') }}');" type="button" class="btn btn-warning"><i class="fa fa-eraser"></i> Reiniciar Contador</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/unidad.js') }}"></script>
    <script>
        var id_cooperativa=null;
        @if(isset($id_cooperativa))
                id_cooperativa='{{$id_cooperativa}}';
        @endif

        function guardar()
        {
            if (actual_id == null)
            {
                crearUnidad('{{ url('/unidades') }}','{{$tipo_usuario_valor}}',id_cooperativa);
            }
            else
            {
                actualizarUnidad('{{ url('/unidades') }}' + '\/' + actual_id,'{{$tipo_usuario_valor}}',id_cooperativa);
            }
        }

    </script>

    <script>
        function mostrar()
        {
            document.form_search.submit();
        }
    </script>
    
@endsection