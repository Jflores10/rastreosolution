@extends('layouts.app')
@section('title')
UNIDADES
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Tipos de unidades</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Tipos de unidades</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <br />
            <button onclick="cleanForm();" type="button" data-toggle="modal" data-target="#form" class="btn btn-default"><i class="fa fa-plus"></i> Crear nuevo</button>
            <br />
            <form name="form_search" method="GET" action="{{ url('/tipos-de-unidades/search') }}" id="form_search">
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
                        <input name="mostrar_modo" onchange=mostrar() id="mostrar_modo_inactivo" type="radio" value="inactivos" />   Inactivos
                        <input name="mostrar_modo" onchange=mostrar() id="mostrar_modo_todos" type="radio" value="todos" />   Todos
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
            @if ($tipos_de_unidades->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <th></th>
                        <th>Descripción</th>
                        <th>Ícono listado</th>
                        <th>Ícono mapa</th>
                        <th>Fecha de creación</th>
                        <th>Fecha de modificación</th>
                        <th>Usuario creador</th>
                        <th>Usuario modificador</th>
                        <th></th>
                        @foreach ($tipos_de_unidades as $tipo_unidad)
                            <tr class="{{($tipo_unidad->estado=='I')?'danger':''}}">
                                <td><button onclick="editarTipoUnidad('{{ url('/tipos-de-unidades/' . $tipo_unidad->_id) }}');" data-toggle="modal" data-target="#form" class="btn btn-primary"><i class="fa fa-edit"></i></button></td>
                                <td>{{ $tipo_unidad->descripcion }}</td>
                                <td>@if($tipo_unidad->icono_lista_url)<img src="{{ $tipo_unidad->icono_lista_url }}" alt="" style="width:20px;height:20px;object-fit:contain;"/>@endif</td>
                                <td>@if($tipo_unidad->icono_mapa_url)<img src="{{ $tipo_unidad->icono_mapa_url }}" alt="" style="width:32px;height:32px;object-fit:contain;"/>@endif</td>
                                <td>{{ $tipo_unidad->created_at }}</td>
                                <td>{{ $tipo_unidad->updated_at }}</td>
                                <td>{{ ($tipo_unidad->creador!=null)?$tipo_unidad->creador->name:""}}</td>
                                <td>{{ ($tipo_unidad->modificador!=null)?$tipo_unidad->modificador->name:""}}</td>
                                <td><input type="checkbox" name="chk_estado" id="chk_estado" {{ ($tipo_unidad->estado=='A')?'checked':'' }} onchange="estadoTipoUnidad('{{ url('/tipos-de-unidades/' . $tipo_unidad->_id) }}',(checked)?true:false);"></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                {{ $tipos_de_unidades->links() }}
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
        <h4 class="modal-title" id="modalLabel">Tipo de unidad</h4>
      </div>
      <div class="modal-body">
          <div class="form-horizontal">
              <div class="form-group" id="div-descripcion">
                  <label for="descripcion" class="col-sm-2 control-label">Descripción</label>
                  <div class="col-sm-10">
                        <input name="descripcion" id="descripcion" class="form-control" type="text" />
                  </div>
                  <span class="help-block" id="span_descripcion"></span>
              </div>
              @foreach(['icono_lista' => 'Ícono de listado', 'icono_mapa' => 'Ícono de mapa'] as $campo_icono => $label_icono)
              <div class="form-group" id="div-{{ $campo_icono }}">
                  <label for="{{ $campo_icono }}" class="col-sm-2 control-label">{{ $label_icono }}</label>
                  <div class="col-sm-10">
                      <div style="margin-bottom:6px;">
                          @if($campo_icono == 'icono_lista')
                          {{-- Por defecto el listado de homev2 usa el glifo fa-bus --}}
                          <span id="{{ $campo_icono }}_default" style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border:1px solid #ddd;border-radius:4px;background:#fff;"><i class="fa fa-bus" style="font-size:24px;"></i></span>
                          @endif
                          <img id="{{ $campo_icono }}_preview" src="{{ url('/images/autobu.png') }}" data-default="{{ url('/images/autobu.png') }}" alt="{{ $label_icono }}" style="width:48px;height:48px;object-fit:contain;border:1px solid #ddd;border-radius:4px;padding:2px;background:#fff;"/>
                      </div>
                      <input class="form-control" name="{{ $campo_icono }}" id="{{ $campo_icono }}" type="file" accept="image/png,image/jpeg,image/webp" onchange="previsualizarIcono('{{ $campo_icono }}');"/>
                      <p class="help-block" style="margin-bottom:0;">PNG, JPG o WEBP. Máximo 2 MB. Sin imagen se usa el ícono de bus.</p>
                      <div class="checkbox" id="div-quitar_{{ $campo_icono }}" style="display:none;">
                          <label><input type="checkbox" id="quitar_{{ $campo_icono }}" onchange="toggleQuitarIcono('{{ $campo_icono }}');"/> Quitar ícono actual</label>
                      </div>
                      <span class="help-block" id="span_{{ $campo_icono }}"></span>
                  </div>
              </div>
              @endforeach
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Cerrar</button>
        <button onclick="guardar(); " type="button" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/tipo-unidad.js') }}"></script>
<script>
    function guardar()
    {
        if (actual_id == null)
        {
            crearTipoUnidad('{{ url('/tipos-de-unidades') }}');
        }
        else 
        {
            actualizarTipoUnidad('{{ url('/tipos-de-unidades') }}' + '/' + actual_id);
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