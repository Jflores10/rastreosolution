@extends('layouts.app')

@section('title')
    Fotos
@endsection

@section('styles')
<style>
    .fotos-toolbar .form-group {
        margin-bottom: 8px;
    }
    .fotos-table thead th {
        background: #2A3F54;
        color: #fff;
        text-align: center;
        vertical-align: middle;
    }
    .fotos-table tbody td {
        vertical-align: middle;
    }
    .fotos-thumb {
        max-width: 140px;
        max-height: 90px;
        border: 1px solid #ddd;
        padding: 2px;
        border-radius: 4px;
        background: #fff;
    }
</style>
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Fotos</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_content">
                    <form method="GET" action="{{ route('fotos.index') }}" class="form-inline fotos-toolbar" style="margin-bottom: 15px;">
                        <div class="form-group" style="margin-right: 8px;">
                            <label for="cooperativa_id">Cooperativa</label>
                            <select id="cooperativa_id" name="cooperativa_id" class="form-control select2" style="margin-left: 8px; min-width: 200px;" @if (empty($es_distribuidor)) disabled @endif>
                                @if (!empty($es_distribuidor))
                                    <option value="">Todas</option>
                                @endif
                                @foreach ($cooperativas as $cooperativa)
                                    <option value="{{ $cooperativa->_id }}" {{ (string) $cooperativa_id === (string) $cooperativa->_id ? 'selected' : '' }}>
                                        {{ $cooperativa->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-right: 8px;">
                            <label for="unidad_id">Unidad</label>
                            <select id="unidad_id" name="unidad_id[]" multiple class="form-control select2" style="margin-left: 8px; min-width: 260px;">
                                <option value="">Todas</option>
                                @foreach ($unidades as $unidad)
                                    <option
                                        value="{{ $unidad->_id }}"
                                        data-cooperativa="{{ (string) $unidad->cooperativa_id }}"
                                        {{ in_array((string) $unidad->_id, array_map('strval', $unidad_ids ?? []), true) ? 'selected' : '' }}
                                    >
                                        {{ $unidad->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-right: 8px;">
                            <label for="desde">Desde</label>
                            <input
                                type="date"
                                id="desde"
                                name="desde"
                                class="form-control"
                                value="{{ $desde }}"
                                style="margin-left: 8px;"
                            >
                        </div>

                        <div class="form-group">
                            <label for="hasta">Hasta</label>
                            <input
                                type="date"
                                id="hasta"
                                name="hasta"
                                class="form-control"
                                value="{{ $hasta }}"
                                style="margin-left: 8px;"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-left: 8px;">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                        @if (empty($es_distribuidor) && !empty($cooperativa_id))
                            <input type="hidden" name="cooperativa_id" value="{{ $cooperativa_id }}">
                        @endif
                        <a href="{{ route('fotos.index') }}" class="btn btn-default" style="margin-left: 4px;" title="Limpiar filtros">
                            <i class="fa fa-eraser"></i>
                        </a>
                    </form>

                    @if ($items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped fotos-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Unidad</th>
                                        <th>Fecha registro</th>
                                        <th>Fecha Foto</th>
                                        <th>Imagen</th>
                                        @if (!empty($es_distribuidor))
                                            <th>Marcada</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $row)
                                        @php
                                            $unidadTexto = $unidades_por_imei[(string) $row->imei] ?? ('IMEI: ' . (string) $row->imei);
                                            $fechaFotoVista = '';
                                            if (!empty($row->photo_time_fc)) {
                                                try {
                                                    $fechaFotoVista = \Carbon\Carbon::parse($row->photo_time_fc)->subHours(5)->format('Y-m-d H:i:s');
                                                } catch (\Exception $e) {
                                                    $fechaFotoVista = (string) $row->photo_time_fc;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ !empty($row->num_img) ? (int) $row->num_img : '—' }}</td>
                                            <td>{{ $unidadTexto }}</td>
                                            <td>{{ $row->fecha }}</td>
                                            <td>{{ $fechaFotoVista }}</td>
                                            <td>
                                                @if (!empty($row->imagen))
                                                    <a
                                                        href="javascript:void(0)"
                                                        onclick="abrirModalFoto('{{ route('fotos.imagen', ['id' => (string) $row->getKey()]) }}', '{{ addslashes($unidadTexto) }}')"
                                                        title="Ver imagen"
                                                    >
                                                        <img
                                                            src="{{ route('fotos.imagen', ['id' => (string) $row->getKey()]) }}"
                                                            alt="Foto unidad {{ $row->imei }}"
                                                            class="fotos-thumb"
                                                        >
                                                    </a>
                                                @else
                                                    <span class="text-muted">Sin imagen</span>
                                                @endif
                                            </td>
                                            @if (!empty($es_distribuidor))
                                                <td class="text-center">
                                                    <input
                                                        type="checkbox"
                                                        class="foto-marcada-check"
                                                        data-id="{{ (string) $row->getKey() }}"
                                                        {{ !empty($row->marcada) ? 'checked' : '' }}
                                                        title="Marcar o desmarcar foto"
                                                    >
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $items->links() }}
                    @else
                        <div class="alert alert-info">No hay fotos registradas.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFotoUnidad" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document" style="max-width: 560px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tituloModalFoto">Foto de unidad</h4>
                </div>
                <div class="modal-body text-center">
                    <img id="imagenModalFoto" src="" alt="Foto unidad" style="max-width: 100%; max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    var unidadesTodas = [];

    function initFiltrosSelect2Fotos() {
        $('#cooperativa_id').select2({
            width: '200px'
        });
        $('#unidad_id').select2({
            width: '260px'
        });

        unidadesTodas = [];
        $('#unidad_id option').each(function () {
            var v = $(this).val();
            if (!v) return;
            unidadesTodas.push({
                value: String(v),
                text: $(this).text(),
                cooperativa: String($(this).data('cooperativa') || '')
            });
        });
    }

    function renderUnidadesPorCooperativa() {
        var cooperativaId = String($('#cooperativa_id').val() || '');
        var seleccion = $('#unidad_id').val();
        var seleccionadas = Array.isArray(seleccion) ? seleccion.map(String) : [];
        var haySeleccion = seleccionadas.length > 0;

        var opciones = [];
        for (var i = 0; i < unidadesTodas.length; i++) {
            var u = unidadesTodas[i];
            if (haySeleccion || cooperativaId === '' || u.cooperativa === cooperativaId) {
                opciones.push(u);
            }
        }

        var html = '';
        for (var j = 0; j < opciones.length; j++) {
            var o = opciones[j];
            var selected = (seleccionadas.indexOf(o.value) !== -1) ? ' selected' : '';
            html += '<option value="' + o.value + '" data-cooperativa="' + o.cooperativa + '"' + selected + '>' + o.text + '</option>';
        }

        $('#unidad_id').html(html);
        $('#unidad_id').val(seleccionadas).trigger('change.select2');
    }

    function actualizarMarcadaFoto(checkbox) {
        var $check = $(checkbox);
        var id = $check.data('id');
        if (!id) {
            return;
        }

        var marcada = $check.prop('checked') ? 1 : 0;
        var estadoAnterior = !marcada;

        $check.prop('disabled', true);

        $.ajax({
            url: '{{ url('/fotos') }}/' + id + '/marcar',
            type: 'POST',
            dataType: 'json',
            data: { marcada: marcada },
            success: function (response) {
                if (response && response.success) {
                    $check.prop('checked', !!response.marcada);
                } else {
                    $check.prop('checked', estadoAnterior);
                    alert((response && response.message) ? response.message : 'No se pudo actualizar la foto.');
                }
                $check.prop('disabled', false);
            },
            error: function (xhr) {
                $check.prop('checked', estadoAnterior);
                $check.prop('disabled', false);
                var msg = 'No se pudo actualizar la foto.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    }

    function abrirModalFoto(urlImagen, imei) {
        document.getElementById('imagenModalFoto').src = urlImagen;
        document.getElementById('tituloModalFoto').textContent = 'Foto de unidad ' + (imei || '');
        $('#modalFotoUnidad').modal('show');
    }

    $('#modalFotoUnidad').on('hidden.bs.modal', function () {
        document.getElementById('imagenModalFoto').src = '';
    });

    $(document).ready(function () {
        initFiltrosSelect2Fotos();
        renderUnidadesPorCooperativa();

        $('#cooperativa_id').on('change', function () {
            // Si no hay unidad seleccionada, mostrar solo unidades de esa cooperativa.
            // Si hay unidad seleccionada, mantener listado de unidades visible.
            renderUnidadesPorCooperativa();
        });

        $('#unidad_id').on('change', function () {
            // Si el usuario limpia unidad, volvemos a filtrar por cooperativa.
            renderUnidadesPorCooperativa();
        });

        $(document).on('change', '.foto-marcada-check', function () {
            actualizarMarcadaFoto(this);
        });
    });
</script>
@endsection