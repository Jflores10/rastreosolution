<style>
    #estadisticasContadorModal .modal-dialog { width: 82%; max-width: 980px; }
    #estadisticasContadorModal .modal-body { max-height: 78vh; overflow-y: auto; }
    #estadisticasContadorModal .stats-chart-wrap { position: relative; width: 100%; height: 420px; overflow: hidden; }
    #estadisticasContadorModal .stats-chart-wrap--participacion { height: 280px; min-height: 280px; overflow: hidden; }
    #estadisticasContadorModal .stats-chart-wrap canvas { display: block; max-width: 100%; }
    #estadisticasContadorModal .stats-meta-info { font-size: 12px; color: #73879C; margin-top: 8px; }
    #estadisticasContadorModal .stats-filtros-row .form-group { margin-bottom: 12px; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-col { padding-left: 15px; padding-right: 15px; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-botones { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 16px; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-botones .btn { margin: 0; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-seccion { margin-bottom: 16px; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-seccion-titulo { font-weight: 600; margin-bottom: 8px; color: #555; }
    #estadisticasContadorModal .stats-filtros-row .select2-container { width: 100% !important; max-width: 100%; }
    #estadisticasContadorModal .stats-filtros-row .stats-filtros-acciones-derecha { text-align: right; margin-top: 16px; }
    @media (min-width: 992px) {
        #estadisticasContadorModal .stats-filtros-row .stats-filtros-col--fechas { border-left: 1px solid #e8e8e8; }
    }
</style>

<div class="modal fade" id="estadisticasContadorModal" tabindex="-1" role="dialog" aria-labelledby="estadisticasContadorLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="estadisticasContadorLabel"><i class="fa fa-bar-chart"></i> Estadísticas — contador diario</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Filtros</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content stats-filtros-row">
                                <div class="row">
                                    <div class="col-md-6 col-sm-12 stats-filtros-col">
                                        <div class="form-group">
                                            <label>Cooperativa</label>
                                            @if(Auth::user()->tipo_usuario->valor == 1)
                                                <select class="form-control select2" id="stats_cooperativa" data-placeholder="Seleccione cooperativa...">
                                                    <option value=""></option>
                                                    @foreach ($cooperativas as $coop)
                                                        <option value="{{ $coop->_id }}">{{ $coop->descripcion }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" id="stats_cooperativa" value="{{ Auth::user()->cooperativa_id }}">
                                                <p class="form-control-static" style="margin:0; padding-top:6px;">
                                                    <strong>{{ Auth::user()->cooperativa ? Auth::user()->cooperativa->descripcion : '' }}</strong>
                                                </p>
                                            @endif
                                        </div>

                                        <div class="form-group">
                                            <label>Unidades</label>
                                            <select class="form-control select2" id="stats_unidades" multiple data-placeholder="Seleccione una o más unidades..."></select>
                                        </div>

                                        <div class="stats-filtros-botones">
                                            <button type="button" class="btn btn-default btn-sm" onclick="statsSeleccionarTodas();">Todas</button>
                                            <button type="button" class="btn btn-default btn-sm" onclick="statsLimpiarSeleccion();">Ninguna</button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-12 stats-filtros-col stats-filtros-col--fechas">
                                        <div class="stats-filtros-seccion" style="margin-bottom:0;">
                                            <div class="stats-filtros-seccion-titulo">Rango de fechas</div>
                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <div class="form-group" style="margin-bottom:8px;">
                                                        <label>Desde</label>
                                                        <input type="text" class="form-control" id="stats_fecha_inicio" autocomplete="off" />
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group" style="margin-bottom:8px;">
                                                        <label>Hasta</label>
                                                        <input type="text" class="form-control" id="stats_fecha_fin" autocomplete="off" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="stats-filtros-botones" style="margin-bottom:0;">
                                                <button type="button" class="btn btn-default btn-sm" onclick="statsEstablecerFechaHoy();">Hoy</button>
                                                <button type="button" class="btn btn-default btn-sm" onclick="statsEstablecerFechaAyer();">Ayer</button>
                                            </div>
                                            <div class="stats-filtros-acciones-derecha">
                                                <button type="button" class="btn btn-primary" onclick="statsAplicarGrafico();">
                                                    <i class="fa fa-refresh"></i> Actualizar gráficos
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Contador diario por unidad</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class="stats-chart-wrap">
                                    <canvas id="statsChartContadorDiario"></canvas>
                                </div>
                                <p class="stats-meta-info" id="statsUltimaActualizacion">Seleccione cooperativa y unidades para ver los gráficos.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Participación por unidad (%)</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <p class="text-muted" style="margin-bottom:12px;">Porcentaje del contador diario de cada unidad.</p>
                                <div class="stats-chart-wrap stats-chart-wrap--participacion" id="stats-participacion-wrap">
                                    <canvas id="statsChartParticipacion"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
