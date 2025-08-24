@extends('layouts.app')
@section('title')
    Reportes online
@endsection
@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Reportes online</h3>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="x_panel">
                <div class="x_content">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group" {{ ($cooperativas->count() === 1)?'style=display:none;':'' }}>
                                <label for="cooperativa">Cooperativa</label>
                                <select onchange="cargarRutas(this.value);" class="form-control" name="cooperativa" id="cooperativa">
                                    <option disabled {{ ($cooperativas->count() > 1)?'selected':'' }}>Seleccione una cooperativa...</option>
                                    @foreach($cooperativas as $cooperativa)
                                        <option {{ ($cooperativas->count() === 1)?'selected':'' }} value="{{ $cooperativa->_id }}">{{ $cooperativa->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ruta</label>
                                <select onchange="consultarRutaEnLinea('{{ url('en-linea') }}' + '/' + this.value);" class="form-control" id="rutasLinea">
                                    <option selected disabled>Seleccione una ruta...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div id="divReporte" class="table-responsive">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>

        window.onload = function () { 
            $('#menu_toggle').trigger('click');
        }

        var index_page=0;

        setInterval(updateDespachoEnLinea,40000,null);

        function cargarRutas(cooperativa) {
            $.get('{{ url('rutas') }}' + '/' + cooperativa + '/listar', function (data) {
                
                var select = $('#rutasLinea');
                select.empty();
                select.append($('<option>', {
                    text: 'Seleccione una ruta...',
                    disabled : true,
                    selected : true
                }));
                for(var i = 0; i < data.length; i++) 
                    select.append($('<option>', {
                        value : data[i]._id,
                        text : data[i].descripcion
                    }));
            });
        }
        
        function consultarRutaEnLinea(url)
        {
            $('#progress').modal('show');  
            $.get(url, function (data) {
               // console.log(data);
                var t = document.createElement('table');
                t.id = 'tableEnLinea';
                t.classList.add('table');
                t.classList.add('table-bordered');
                var d = document.getElementById('divReporte');
                $('#divReporte').empty();
                d.appendChild(t);
                $('#tableEnLinea').html(data);
            }).always(function () {
                $('#progress').modal('hide');  
                $('#tableEnLinea').DataTable({"pageLength":25,
                    "language":{
                                "paginate": {
                                    "first":      "Primero",
                                    "last":       "Ultimo",
                                    "next":       "Siguiente",
                                    "previous":   "Anterior"
                                },
                            "lengthMenu": "Mostrando _MENU_ registros por página",
                            "search":         "Buscar:",
                            "info":           "Mostrando _START_ hasta _END_ de _TOTAL_ registros",
                            "infoEmpty":      "Mostrando 0 desde 0 hasta 0 registros",
                            "loadingRecords": "Cargando...",
                            "processing":     "Procesando...",
                            "zeroRecords":    "No se encontro registros",
                            "infoFiltered":   "(filtrado desde _MAX_ total registros)"
                            },
                            "bLengthChange": false,
                            "order": [[ 1, "desc" ]],
                            "ordering": false
                });

                $('#tableEnLinea_filter').hide();
            });
        }
        @if ($cooperativas->count() == 1)
            $('#cooperativa').trigger('change');
        @endif

        function updateDespachoEnLinea(){
            var ruta=document.getElementById('rutasLinea');
            if(ruta.value != null && ruta.value != undefined ){
                if(ruta.value != "Seleccione una ruta..."){
                    var url="{{ url('en-linea') }}"+"/"+ruta.value;
                    
                    $.get(url, function (data) {
                        let tb = $('#tableEnLinea').DataTable();
                        let info= tb.page.info();
                        index_page=info.page;
                        //console.log('Currently showing page '+(info.page+1)+' of '+info.pages+' pages.');

                        var t = document.createElement('table');
                        t.id = 'tableEnLinea';
                        t.classList.add('table');
                        t.classList.add('table-bordered');
                        var d = document.getElementById('divReporte');
                        $('#divReporte').empty();
                        d.appendChild(t);
                        $('#tableEnLinea').html(data);
                    }).always(function () {
                        $('#tableEnLinea').DataTable({"pageLength":25,
                            "language":{
                                "paginate": {
                                    "first":      "Primero",
                                    "last":       "Ultimo",
                                    "next":       "Siguiente",
                                    "previous":   "Anterior"
                                },
                            "lengthMenu": "Mostrando _MENU_ registros por página",
                            "search":         "Buscar:",
                            "info":           "Mostrando _START_ hasta _END_ de _TOTAL_ registros",
                            "infoEmpty":      "Mostrando 0 desde 0 hasta 0 registros",
                            "loadingRecords": "Cargando...",
                            "processing":     "Procesando...",
                            "zeroRecords":    "No se encontro registros",
                            "infoFiltered":   "(filtrado desde _MAX_ total registros)"
                            },
                            "bLengthChange": false,
                            "order": [[ 1, "desc" ]],
                            "ordering": false
                        });

                        $('#tableEnLinea_filter').hide();

                        let tb_ind = $('#tableEnLinea').DataTable();
                        let info_ind= tb_ind.page.info();
                        let paginas = info_ind.pages;
                        if(paginas>0){
                            if(index_page >paginas){
                                tb_ind.page('last').draw('page');
                            }else{
                                tb_ind.page(index_page).draw('page');
                            }
                        }

                    });

                }
            }
            
        }

    </script>
@endsection