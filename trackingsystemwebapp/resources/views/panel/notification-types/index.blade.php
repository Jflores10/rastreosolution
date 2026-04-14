@extends('layouts.app')

@section('title')
    Notificaciones — Tipos
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Tipos de notificación</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_content">
                    <br />
                    <div class="mb-3 text-right">
                        <button type="button" class="btn btn-success" onclick="nuevoTipoNotificacion()">+ Crear tipo</button>
                    </div>
                    <br />
                    @if ($items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Activo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $row)
                                        @php $ntKey = (string) $row->getKey(); @endphp
                                        <tr class="{{ empty($row->activo) ? 'danger' : '' }}">
                                            <td><code>{{ $row->code }}</code></td>
                                            <td>{{ $row->nombre }}</td>
                                            <td>{{ is_string($row->descripcion) && strlen($row->descripcion) > 80 ? substr($row->descripcion, 0, 80) . '…' : $row->descripcion }}</td>
                                            <td>{{ !empty($row->activo) ? 'Sí' : 'No' }}</td>
                                            <td>
                                                <button type="button" onclick="editarTipoNotificacion('{{ url('/notification-types/get/' . $ntKey) }}')" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="eliminarTipoNotificacion('{{ $ntKey }}')" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $items->links() }}
                    @else
                        <div class="alert alert-info">No hay tipos registrados.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Misma estructura que modal-comando en lista-comandos: input oculto + modal-content --}}
    <div class="modal fade" id="modalTipoNotificacion" tabindex="-1">
        <div class="modal-dialog">
            <input type="hidden" name="notification_type_id" id="notification_type_id" value="">

            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabelTipoNotificacion">Tipo de notificación</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group" id="nt-div-code">
                        <label for="nt_code">Código</label>
                        <input type="text" name="code" id="nt_code" class="form-control" maxlength="64" placeholder="ej: entrada_punto_control" autocomplete="off">
                        <span class="help-block text-danger" id="nt-span-code"></span>
                    </div>

                    <div class="form-group" id="nt-div-nombre">
                        <label for="nt_nombre">Nombre</label>
                        <input type="text" name="nombre" id="nt_nombre" class="form-control" maxlength="255" autocomplete="off">
                        <span class="help-block text-danger" id="nt-span-nombre"></span>
                    </div>

                    <div class="form-group" id="nt-div-descripcion">
                        <label for="nt_descripcion">Descripción</label>
                        <textarea rows="3" name="descripcion" id="nt_descripcion" class="form-control"></textarea>
                        <span class="help-block text-danger" id="nt-span-descripcion"></span>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="activo" id="nt_activo" value="1" checked> Activo
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="saveTipoNotificacion()" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>

    function ntLimpiarErrores() {
        $('#nt-div-code,#nt-div-nombre,#nt-div-descripcion').removeClass('has-error');
        $('#nt-span-code,#nt-span-nombre,#nt-span-descripcion').text('');
    }

    function ntMostrarErrores(data) {
        ntLimpiarErrores();
        if (!data.messages) return;
        var msgs = data.messages;
        for (var key in msgs) {
            if (!msgs.hasOwnProperty(key)) continue;
            var arr = msgs[key];
            var text = Array.isArray(arr) ? arr.join(' ') : String(arr);
            var div = document.getElementById('nt-div-' + key);
            var span = document.getElementById('nt-span-' + key);
            if (div) div.classList.add('has-error');
            if (span) span.textContent = text;
        }
    }

    function nuevoTipoNotificacion() {
        document.getElementById('notification_type_id').value = '';
        $('#nt_code').val('').prop('readonly', false);
        $('#nt_nombre').val('');
        $('#nt_descripcion').val('');
        $('#nt_activo').prop('checked', true);
        ntLimpiarErrores();
        $('#modalTipoNotificacion').modal('show');
    }

    function saveTipoNotificacion() {
        var idtipo = $('#notification_type_id').val();
        if (idtipo != '') {
            actualizarTipoNotificacion();
        } else {
            guardarTipoNotificacion();
        }
    }

    function guardarTipoNotificacion() {
        var url = '{{ url('/notification-types') }}';
        var code = document.getElementById('nt_code').value;
        var nombre = document.getElementById('nt_nombre').value;
        var descripcion = document.getElementById('nt_descripcion').value;
        var param = {
            code: code,
            nombre: nombre,
            descripcion: descripcion
        };
        if ($('#nt_activo').is(':checked')) {
            param.activo = '1';
        }
        $.post(url, param, function (data) {
            if (data.error == false) {
                alert('El tipo de notificación ha sido guardado con éxito.');
                location.reload(true);
            } else {
                if (data.messages) {
                    ntMostrarErrores(data);
                } else {
                    alert('Error al guardar: ' + JSON.stringify(data));
                }
            }
        }, 'json');
    }

    function actualizarTipoNotificacion() {
        var url = '{{ url('/notification-types/update') }}';
        var notification_type_id = document.getElementById('notification_type_id').value;
        if (!notification_type_id) {
            alert('No se puede actualizar: falta el ID del tipo.');
            return;
        }
        var param = {
            notification_type_id: notification_type_id,
            nombre: document.getElementById('nt_nombre').value,
            descripcion: document.getElementById('nt_descripcion').value
        };
        if ($('#nt_activo').is(':checked')) {
            param.activo = '1';
        }
        $.post(url, param, function (data) {
            if (data.error == false) {
                alert('El tipo de notificación ha sido actualizado con éxito.');
                location.reload(true);
            } else {
                if (data.messages) {
                    ntMostrarErrores(data);
                } else {
                    alert('Error al actualizar: ' + JSON.stringify(data));
                }
            }
        }, 'json');
    }

    function editarTipoNotificacion(url) {
        $("#modalTipoNotificacion input[type='text'], #modalTipoNotificacion textarea").val("");
        document.getElementById('notification_type_id').value = '';
        $('#nt_activo').prop('checked', false);

        $.get(url, function (data) {
            if (!data || data.error !== false || !data.notification_type) {
                alert('No se pudo cargar el registro.');
                return;
            }
            var t = data.notification_type;
            $("#notification_type_id").val(t._id);
            $("#nt_code").val(t.code).prop('readonly', true);
            $("#nt_nombre").val(t.nombre);
            $("#nt_descripcion").val(t.descripcion || '');
            $("#nt_activo").prop('checked', !!t.activo);
            ntLimpiarErrores();
            $("#modalTipoNotificacion").modal("show");
        }, 'json').fail(function () {
            alert('No se pudo cargar el registro.');
        });
    }

    function eliminarTipoNotificacion(id) {
        if (!id) {
            alert('ID no válido.');
            return;
        }
        if (!confirm('¿Está seguro que desea eliminar este tipo de notificación?')) {
            return;
        }
        $.ajax({
            url: '{{ url('/notification-types/delete') }}/' + id,
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                alert(response.message);
                location.reload(true);
            },
            error: function () {
                alert('Ocurrió un error al intentar eliminar el tipo de notificación.');
            }
        });
    }

</script>
@endsection
