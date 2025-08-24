<div id="en_linea" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Reporte de rutas en linea</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="form-group">
                    <label>Ruta</label>
                    <select onchange="consultarRutaEnLinea('{{ url('en-linea') }}' + '/' + this.value);" class="form-control" id="rutasLinea">
                        <option disabled selected>Seleccione una ruta...</option>
                        @if (isset($rutas))
                            @foreach ($rutas as $ruta)
                                <option value="{{ $ruta->_id }}">{{ $ruta->descripcion }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    <div class="table-responsive">
                        <table id="tableEnLinea" class="table-bordered table table-hover">
                        </table>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
