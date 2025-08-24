@extends('layouts.app')
@section('title')
Cooperativas
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Cooperativas</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Lista de cooperativas</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <br />
            <button onclick="cleanForm();" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
            <br />
            <form name="form_search" method="GET" action="{{ url('/cooperativas/search') }}" id="form_search">
                {{ csrf_field() }}
                <div class="form-group">
                    <div class="input-group">
                        <input name="search" type="text" class="form-control" placeholder="Búsqueda">
                        <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                    </span>
                    </div>
                </div>
                <div class="form-group" >
                    <label for="mostrar_modo" class="col-sm-2 control-label">Mostrar:</label>
                    <div class="col-sm-10">
                        <input name="mostrar_modo" onchange=mostrar() id="mostrar_modo_activo" type="radio" value="activos" checked/>Activos
                        <input name="mostrar_modo" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="inactivos" />Inactivos
                        <input name="mostrar_modo" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="todos" />Todos
                    </div><br/><br/>
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


            @if ($cooperativas->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <th></th>
                    <th>RUC</th>
                    <th>Descripción</th>
                    <th>Despachos ATM</th>
                    <th>Maneja taxis</th>
                    <th>Ruta con máscara</th>
                    <th>Multa de tubo</th>
                    <th>Fecha de creación</th>
                    <th>Fecha de modificación</th>
                    <th>Usuario creador</th>
                    <th>Usuario modificador</th>
                    <th></th>
                    @foreach ($cooperativas as $cooperativa)
                        <tr class="{{($cooperativa->estado=='I')?'danger':''}}" >
                            <td><button onclick="editarCooperativa('{{ url('/cooperativas/' . $cooperativa->_id) }}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                            <td>{{ $cooperativa->ruc }}</td>
                            <td>{{ $cooperativa->descripcion }}</td>
                            <td>{{ ($cooperativa->despachos_atm == 'S')?'Si':'No' }}</td>
                            <td>{{ ($cooperativa->taxis != null && $cooperativa->taxis === true)?'Si':'No' }}</td>
                            <td>{{ ($cooperativa->mascara == 'S')?'Sí':'No' }}</td>
                            <td>{{ $cooperativa->multa_tubo }}</td>
                            <td>{{ $cooperativa->created_at }}</td>
                            <td>{{ $cooperativa->updated_at }}</td>
                            <td>{{ ($cooperativa->creador!=null)?$cooperativa->creador->name:""}}</td>
                            <td>{{ ($cooperativa->modificador!=null)?$cooperativa->modificador->name:""}}</td>
                            <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($cooperativa->estado=='A')?'checked':'' }} onchange="estadoCooperativa('{{ url('/cooperativas/' . $cooperativa->_id) }}',(checked)?true:false);"></td>
                        </tr>
                    @endforeach
                </table>
            </div>
                {{ $cooperativas->links() }}
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
        <h4 class="modal-title" id="modalLabel">Cooperativa</h4>
      </div>
      <div class="modal-body">
          <div class="form-horizontal">
              <div id="div-descripcion" class="form-group">
                  <label for="descripcion" class="col-sm-2 control-label">Descripción</label>
                  <div class="col-sm-10">
                      <input name="descripcion" id="descripcion" class="form-control" type="text" />
                  </div>
                  <span class="help-block" id="span_descripcion"></span>
              </div>
              <div class="form-group">
                <label for="email" class="col-sm-2 control-label">E-mail</label>
                <div class="col-sm-10">
                    <input type="email" name="email" id="email" placeholder="Opcional" class="form-control"/>
                </div>
              </div>
              <div id="div_ruc" class="form-group">
                <label for="ruc" class="col-sm-2 control-label">RUC</label>
                <div class="col-sm-10">
                      <input name="ruc" id="ruc" class="form-control" type="text" />
                  </div>
                  <span class="help-block" id="span_ruc"></span>
              </div>
              <!-- <div id="div_ip" class="form-group">
                <label for="ip" class="col-sm-2 control-label">IPs</label>
                <div class="col-sm-10">
                      <input name="ip" id="ip" class="form-control" type="text" />
                  </div>
                  <label for="ip_sep" class="col-sm-2 control-label">Separar las ips por ';'</label>
              </div> -->
              <div class="form-group">
                <div class="checkbox">
                    <label for="despachos_atm">
                        <input type="checkbox" id="despachos_atm" name="despachos_atm" value="S"/> Envía despachos a la ATM
                    </label>
                </div>
                <div class="checkbox">
                    <label for="despachos_job">
                        <input type="checkbox" id="despachos_job" name="despachos_job" value="S"/> Despachos terminar día
                    </label>
                </div>
              </div>
              <div class="form-group">
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="taxis" id="taxis" /> Maneja taxis
                  </label>
                </div>
              </div>
              <div class="form-group">
                  <div class="checkbox">
                    <label>
                        <input type="checkbox" name="mascara" id="mascara" /> Acepta ruta con máscara
                    </label>
                  </div>
              </div>
              <div class="form-group">
                  <div class="checkbox">
                    <label>
                        <input type="checkbox" name="importador_despachos" id="importador_despachos" /> Permitir importación de despachos
                    </label>
                  </div>
              </div>
              <div class="form-group">
                  <div class="checkbox">
                    <label>
                        <input type="checkbox" name="finalizacion_automatica" id="finalizacion_automatica" /> Finalización automática de despachos
                    </label>
                  </div>
              </div>
              <div class="form-group">
                  <div class="checkbox">
                    <label>
                        <input type="checkbox" name="redondear_tiempos_atraso" id="redondear_tiempos_atraso" /> Redondear tiempos de atraso (hacia arriba)
                    </label>
                  </div>
              </div>
              <div id="div-multa-tubo" class="form-group">
                  <label for="multa_tubo" class="col-sm-2 control-label">Multa tubo</label>
                  <div class="col-sm-10">
                      <input name="multa_tubo" id="multa_tubo" class="form-control" type="number" />
                  </div>
                  <span class="help-block" id="span_multa_tubo"></span>
              </div>
              <div id="div-tolerancia_buffer_minutos" class="form-group">
                  <label for="multa_tubo" class="col-sm-2 control-label">Tolerancia de GPS en minutos</label>
                  <div class="col-sm-10">
                      <input name="tolerancia_buffer_minutos" id="tolerancia_buffer_minutos" class="form-control" type="number" />
                  </div>
                  <span class="help-block" id="span_tolerancia_buffer_minutos"></span>
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
        <button type="button"  onclick="guardar();"  class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/cooperativa.js') }}"></script>
    <script>
        function guardar()
        {
            if (actual_id == null)
            {
                crearCooperativa('{{ url('/cooperativas') }}');
            }
            else
            {
                actualizarCooperativa('{{ url('/cooperativas') }}' + '\/' + actual_id);
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
