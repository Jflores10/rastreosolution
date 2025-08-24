@extends('layouts.app')
@section('title')
Dashboard
@endsection
@section('content')
<div class="page-title">
    <div class="title_left">
        <h3>Dashboard</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">                                                 
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">                           
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                 @if(isset($cooperativas))
                                 <form id="form" method="POST">
                                 {{ csrf_field() }} 
                                    <div class="form-group" id="div-cooperativa">
                                        <label for="cooperativa_id">Cooperativa</label>
                                        <select class="form-control" id="cooperativa_id" name="cooperativa_id" onchange="cambioCooperativa(this.value);">
                                            <option value="" disabled selected hidden>Seleccione...</option>                        
                                                @foreach ($cooperativas as $cooperativa_id)
                                                    <option value="{{ $cooperativa_id->_id }}">
                                                        {{ $cooperativa_id->descripcion }}
                                                    </option>
                                                @endforeach                        
                                        </select>
                                    </div>
                                </form>
                                @else
                                <label> Visualizar:</label>
                                @endif  
                            </div>                            
                            <div class="col-lg-10 col-md-10 col-sm-12 col-xs-12" style="display:none;" id="div-ver">
                                <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox1"> Unidades</label>
                                </div>
                                <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox2"> Usuarios</label>
                                </div>
                                <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox3"> Roles</label>  
                                </div>
                                <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox4"> Despachos</label>  
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox5"> Exportación</label>  
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox6"> Corte Tubo</label>  
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <label><input type="checkbox" id="cbox7"> Exceso Velocidad</label>  
                                </div>
                            </div>                         
                        </div>                                     
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div id="chartUnidades" style="height: 300px; width: 100%; margin-top:25px;"></div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div id="chartEstadoUsuarios" style="height: 300px; width: 100%; margin-top:25px;"></div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div id="chartRolesUsuarios" style="height: 300px; width: 100%; margin-top:35px;"></div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" id="div-fecha-de">                    
                        <div id="chartEstadosDespachos" style="height: 300px; width: 100%; margin-top:35px;" ></div>
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border-style: solid; border-width: 0.5px;">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio_de">Desde</label>
                                    <input name="fecha_inicio_de" id="fecha_inicio_de" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin_de">Hasta</label>
                                    <input name="fecha_fin_de" id="fecha_fin_de" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <button type="button" onclick="recargarDespachosEstados('{{ url('/dashboard/de') }}');" class="btn btn-success" style="margin-top:23px;"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>   
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" id="div-fecha-td">
                        <div id="chartTiposDespachos" style="height: 300px; width: 100%; margin-top:35px;"></div>
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border-style: solid; border-width: 0.5px;">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio_td">Desde</label>
                                    <input name="fecha_inicio_td" id="fecha_inicio_td" class="form-control" type="text" />
                                </div>
                            </div> 
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin_td">Hasta</label>
                                    <input name="fecha_fin_td" id="fecha_fin_td" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <button type="button" onclick="recargarTiposDespachos('{{ url('/dashboard/ed') }}');" class="btn btn-success" style="margin-top:23px;"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" id="div-fecha-ct">
                        <div id="chartCorteTubo" style="height: 300px; width: 100%; margin-top:35px;"></div>
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border-style: solid; border-width: 0.5px;">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio_ct">Desde</label>
                                    <input name="fecha_inicio_ct" id="fecha_inicio_ct" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin_ct">Hasta</label>
                                    <input name="fecha_fin_ct" id="fecha_fin_ct" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <button type="button" onclick="recargarCortesTubo('{{ url('/dashboard/ct') }}');" class="btn btn-success" style="margin-top:23px;"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" id="div-fecha-vu">
                        <div id="chartVelocidadUnidad" style="height: 350px; width: 100%; margin-top:35px;"></div>
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border-style: solid; border-width: 0.5px;">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">    
                                <div class="form-group" id="div-unidad">
                                    <label for="unidad">Unidad</label>
                                    <select class="form-control" id="unidad" name="unidad">     
                                        <option value="" disabled selected hidden>Seleccione...</option>       
                                        @if(isset($cooperativa))          
                                            @foreach ($unidades as $unidad)
                                                <option value="{{ $unidad->_id }}">
                                                    {{ $unidad->placa }}
                                                </option>
                                            @endforeach  
                                        @endif                      
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio_vu">Desde</label>
                                    <input name="fecha_inicio_vu" id="fecha_inicio_vu" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin_vu">Hasta</label>
                                    <input name="fecha_fin_vu" id="fecha_fin_vu" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                                <button type="button" onclick="recargarVelocidadUnidad('{{ url('/dashboard/vu') }}');" class="btn btn-success" style="margin-top:23px;"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" id="div-fecha-vg">
                        <p style="color:black; font-weight: bold; font-size: 25px; margin-top:35px;" align="center">Exceso de velocidad</p>
                        <div id="chartVelocidadGeneral" style="height: 300px; width: 100%;"></div>
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border-style: solid; border-width: 0.5px;">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio_vg">Desde</label>
                                    <input name="fecha_inicio_vg" id="fecha_inicio_vg" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin_vg">Hasta</label>
                                    <input name="fecha_fin_vg" id="fecha_fin_vg" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <button type="button" onclick="recargarVelocidadGeneral('{{ url('/dashboard/vg') }}');" class="btn btn-success" style="margin-top:23px;"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    <script src="{{ asset('js/canvasjs/jquery.canvasjs.min.js') }}"></script>
    <script>
        $('#fecha_inicio_de').datetimepicker();
        $('#fecha_fin_de').datetimepicker();
        $('#fecha_inicio_td').datetimepicker();
        $('#fecha_fin_td').datetimepicker();
        $('#fecha_inicio_ct').datetimepicker();
        $('#fecha_fin_ct').datetimepicker();
        $('#fecha_inicio_vg').datetimepicker();
        $('#fecha_fin_vg').datetimepicker();
        $('#fecha_inicio_vu').datetimepicker();
        $('#fecha_fin_vu').datetimepicker();
        @if(isset($cooperativas))
            $('#cooperativa_id').chosen({ width : '100%' });
        @endif
        function cambioCooperativa(id_cooperativa)
        {
            $('#progress').modal('show');
            form.action = '/dashboard/general/'+id_cooperativa;
            form.submit();
        }
        function recargarVelocidadGeneral(url)
        {
            var desde = $('#fecha_inicio_vg').val();
            var hasta = $('#fecha_fin_vg').val()
            if(desde.length == 0 || hasta.length == 0)
                alert("Debe ingresar ambas fechas.");
            else
            {
                @if(isset($cooperativa))
                    var param={
                        cooperativa:'<?php echo $cooperativa; ?>',
                        fecha_inicio:desde,
                        fecha_fin:hasta
                    };
                    $('#progress').modal('show'); 
                    $.post(url, param,
                    function( data ) {                         
                        if(data.error==false)
                        {
                            var registros = [];
                            var i;
                            var cont = 0;
                            for (i = 0; i < data.velocidades.length; i++) { 
                                if(data.velocidades[i]["cantidad_exceso"]>0)
                                {
                                    registros.push({ y: data.velocidades[i]["cantidad_exceso"], label: data.velocidades[i]["unidad"] });
                                    cont++;
                                }                                    
                            }
                            var chart6 = new CanvasJS.Chart("chartVelocidadGeneral", {
                            animationEnabled: true,       
                            axisX:{
                                interval: 1
                            },
                            axisY2:{
                                interlacedColor: "rgba(1,77,101,.2)",
                                gridColor: "rgba(1,77,101,.1)"
                            },
                            data: [{
                                type: "bar",
                                name: "Unidades",
                                axisYType: "secondary",
                                color: "#014D65",
                                dataPoints: registros
                            }]
                            });
                            $altura = cont/10*250;
                            if($altura>298)
                                document.getElementById("chartVelocidadGeneral").style="height: "+$altura+"px; width: 100%;";
                            else
                                document.getElementById("chartVelocidadGeneral").style="height: 298px; width: 100%;";
                            chart6.render();    
                        }
                        else
                            alert('Error al cargar datos.');
                        $('#progress').modal('hide');  
                    }, "json");
                @endif 
            }
        }
        function recargarVelocidadUnidad(url)
        {            
            var desde = $('#fecha_inicio_vu').val();
            var hasta = $('#fecha_fin_vu').val();
            var unidad = $('#unidad').val();
            if(desde.length == 0 || hasta.length == 0 || unidad==null)
                alert("Debe ingresar ambas fechas y seleccionar una unidad.");
            else
            {
                @if(isset($cooperativa))
                $('#progress').modal('show');
                    var param={
                        cooperativa:'<?php echo $cooperativa; ?>',
                        fecha_inicio:desde,
                        fecha_fin:hasta,
                        unidad:unidad
                    };
                    $.post(url, param,
                    function( data ) {                        
                        if(data.error==false)
                        {
                            if(data.velocidades.length>0)
                            {
                                var chart3= new CanvasJS.Chart("chartVelocidadUnidad", {
                                title:{
                                    text: "Exceso de velocidad por unidad"              
                                },
                                data: [              
                                {
                                    // Change type to "doughnut", "line", "splineArea", etc.
                                    type: "column",
                                    dataPoints: [
                                        { label: data.velocidades[0]["label1"],  y: data.velocidades[0]["velocidad1"]},
                                        { label: data.velocidades[0]["label2"],  y: data.velocidades[0]["velocidad2"]},
                                        { label: data.velocidades[0]["label3"],  y: data.velocidades[0]["velocidad3"]},
                                        { label: data.velocidades[0]["label4"],  y: data.velocidades[0]["velocidad4"]},
                                        { label: data.velocidades[0]["label5"],  y: data.velocidades[0]["velocidad5"]},
                                        { label: data.velocidades[0]["label6"],  y: data.velocidades[0]["velocidad6"]},
                                    ]
                                }
                                ]
                                });
                                chart3.render();
                            }
                            else
                                alert("No se encontró ningún registro.");                            
                        }
                        else
                            alert('Error al cargar datos.');
                        $('#progress').modal('hide');  
                    }, "json");
                @endif                
            }

        }
        function recargarDespachosEstados(url)
        {
            var desde = $('#fecha_inicio_de').val();
            var hasta = $('#fecha_fin_de').val();
            if(desde.length == 0 || hasta.length == 0)
                alert("Debe ingresar ambas fechas.");
            else
            {
                @if(isset($cooperativa))
                    var param={
                        cooperativa_id:'<?php echo $cooperativa; ?>',
                        fecha_inicio:desde,
                        fecha_fin:hasta
                    };
                    $('#progress').modal('show');
                    $.post(url, param,
                    function( data ) {                        
                        if(data.error==false)
                        {
                            var chart3= new CanvasJS.Chart("chartEstadosDespachos", {
                            title:{
                                text: "Despachos por estado"              
                            },
                            data: [              
                            {
                                // Change type to "doughnut", "line", "splineArea", etc.
                                type: "column",
                                dataPoints: [
                                    { label: "Pendientes",  y: data.despachos_pendientes},
                                    { label: "Culminados", y: data.despachos_culminados },
                                    { label: "Inactivos", y: data.despachos_inactivos  }
                                ]
                            }
                            ]
                        });
                        chart3.render();
                        }
                        else
                            alert('Error al cargar datos.');
                        $('#progress').modal('hide');  
                    }, "json");
                @endif                
            }
        }
        function recargarTiposDespachos(url)
        {
            var desde = $('#fecha_inicio_td').val();
            var hasta = $('#fecha_fin_td').val();
            if(desde.length == 0 || hasta.length == 0)
                alert("Debe ingresar ambas fechas.");
            else
            {
                @if(isset($cooperativa))
                    var param={
                        cooperativa_id:'<?php echo $cooperativa; ?>',
                        fecha_inicio:desde,
                        fecha_fin:hasta
                    };
                    $('#progress').modal('show');  
                    $.post(url, param,
                    function( data ) {                        
                        if(data.error==false)
                        {
                            var chart4 = new CanvasJS.Chart("chartTiposDespachos", {
                    title:{
                        text: "Despachos por exportación a la ATM"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "doughnut",
                        dataPoints: [
                            { label: "Exportados",  y: data.exportados_si  },
                            { label: "No exportados", y: data.exportados_no   }
                        ]
                    }
                    ]
                });
                chart4.render();
                        }
                        else
                            alert('Error al cargar datos.');
                        $('#progress').modal('hide');  
                    }, "json");
                @endif 
            }
        }
        function recargarCortesTubo(url)
        {
            var desde = $('#fecha_inicio_ct').val();
            var hasta = $('#fecha_fin_ct').val();
            if(desde.length == 0 || hasta.length == 0)
                alert("Debe ingresar ambas fechas.");
            else
            {
                @if(isset($cooperativa))
                    var param={
                        cooperativa_id:'<?php echo $cooperativa; ?>',
                        fecha_inicio:desde,
                        fecha_fin:hasta
                    };
                    $('#progress').modal('show'); 
                    $.post(url, param,
                    function( data ) {                         
                        if(data.error==false)
                        {
                            var chart5 = new CanvasJS.Chart("chartCorteTubo", {
                            title:{
                                text: "Corte de tubo"              
                            },
                            data: [              
                            {
                                // Change type to "doughnut", "line", "splineArea", etc.
                                type: "spline",
                                dataPoints: [
                                    { label: "Si",  y: data.corte_tubo_si },
                                    { label: "No", y: data.corte_tubo_no }
                                ]
                            }
                            ]
                        });
                        chart5.render();
                        }
                        else
                            alert('Error al cargar datos.');
                        $('#progress').modal('hide');  
                    }, "json");
                @endif 
            }
        }
        window.onload = function () {
            @if(!isset($cooperativa))
                document.getElementById("div-fecha-de").style = "display:none;";
                document.getElementById("div-fecha-ct").style = "display:none;";
                document.getElementById("div-fecha-td").style = "display:none;";
                document.getElementById("div-fecha-vg").style = "display:none;";
                document.getElementById("div-fecha-vu").style = "display:none;";
            @else                   
                @if(isset($cooperativas))
                    $('#cooperativa_id').val('<?php echo $cooperativa; ?>');
                    $('#cooperativa_id').trigger("chosen:updated");
                @endif    
                @if($velocidad_maxima == null || $velocidad_maxima == 0)
                    document.getElementById("div-fecha-vg").style = "display:none;";
                    document.getElementById("div-fecha-vu").style = "display:none;";
                @endif 
                $('#unidad').chosen({ width : '100%' });                        
                var chart = new CanvasJS.Chart("chartUnidades", {
                    title:{
                        text: "Unidades"              
                    },
                    data: [              
                        {
                            // Change type to "doughnut", "line", "splineArea", etc.
                            type: "column",
                            dataPoints: [
                                { label: "ATM",  y: {{$datos[0]["unidades_atm"]}}  },
                                { label: "Servidor", y: {{$datos[0]["unidades_servidor"]}} },
                                { label: "Activas", y: {{$datos[0]["unidades_activas"]}}  },
                                { label: "Inactivas",  y: {{$datos[0]["unidades_inactivas"]}} }
                            ]
                        }
                    ]
                });
                chart.render();    
                var chart1 = new CanvasJS.Chart("chartEstadoUsuarios", {
                    title:{
                        text: "Usuarios por estado"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "doughnut",
                        dataPoints: [
                            { label: "Activos",  y: {{$datos[0]["usuarios_activos"]}}  },
                            { label: "Inactivos", y: {{$datos[0]["usuarios_inactivos"]}}  }
                        ]
                    }
                    ]
                });
                chart1.render();
                var chart2 = new CanvasJS.Chart("chartRolesUsuarios", {
                    title:{
                        text: "Usuarios por roles"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "splineArea",
                        dataPoints: [
                            { label: "Distribuidor", y: {{$datos[0]["usuarios_rol1"]}}   },
                            { label: "Administrador de cooperativa", y: {{$datos[0]["usuarios_rol2"]}}  },
                            { label: "Despachador", y: {{$datos[0]["usuarios_rol3"]}} },
                            { label: "Socio",  y: {{$datos[0]["usuarios_rol4"]}}  }
                        ]
                    }
                    ]
                });
                chart2.render();
                var chart3= new CanvasJS.Chart("chartEstadosDespachos", {
                    title:{
                        text: "Despachos por estado"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "column",
                        dataPoints: [
                            { label: "Pendientes",  y: {{$datos[0]["despachos_pendientes"]}}  },
                            { label: "Culminados", y: {{$datos[0]["despachos_culminados"]}}  },
                            { label: "Inactivos", y: {{$datos[0]["despachos_inactivos"]}}  }
                        ]
                    }
                    ]
                });
                chart3.render();
                var chart4 = new CanvasJS.Chart("chartTiposDespachos", {
                    title:{
                        text: "Despachos por exportación a la ATM"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "doughnut",
                        dataPoints: [
                            { label: "Exportados",  y: {{$datos[0]["exportacion_si"]}}   },
                            { label: "No exportados", y: {{$datos[0]["exportacion_no"]}}   }
                        ]
                    }
                    ]
                });
                chart4.render();
                var chart5 = new CanvasJS.Chart("chartCorteTubo", {
                    title:{
                        text: "Corte de tubo"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "spline",
                        dataPoints: [
                            { label: "Si",  y: {{$datos[0]["corte_tubo_si"]}} },
                            { label: "No", y: {{$datos[0]["corte_tubo_no"]}} }
                        ]
                    }
                    ]
                });
                chart5.render();
                var registros = [];
                var contRegistros = 0;
                @for($i = 0; $i < (sizeof($velocidades)); $i++)
                    @if($velocidades[$i]["cantidad_exceso"]>0)
                        registros.push({ y: {{$velocidades[$i]["cantidad_exceso"]}}, label: '{{$velocidades[$i]["unidad"]}}' });
                        contRegistros++;
                    @endif
                @endfor
                var chart6 = new CanvasJS.Chart("chartVelocidadGeneral", {
                animationEnabled: true,       
                axisX:{
                    interval: 1
                },
                axisY2:{
                    interlacedColor: "rgba(1,77,101,.2)",
                    gridColor: "rgba(1,77,101,.1)"
                },
                data: [{
                    type: "bar",
                    name: "Unidades",
                    axisYType: "secondary",
                    color: "#014D65",
                    dataPoints: registros
                }]
                });
                $altura = contRegistros/10*250;    
                if($altura>298)            
                    document.getElementById("chartVelocidadGeneral").style="height: "+$altura+"px; width: 100%;";
                else
                    document.getElementById("chartVelocidadGeneral").style="height: 298px; width: 100%;";
                chart6.render();    
                var chart7 = new CanvasJS.Chart("chartVelocidadUnidad", {
                    title:{
                        text: "Exceso de velocidad por unidad"              
                    },
                    data: [              
                    {
                        // Change type to "doughnut", "line", "splineArea", etc.
                        type: "splineArea",
                        dataPoints: [
                        ]
                    }
                    ]
                });
                chart7.render();            
            @endif

            $('#menu_toggle').trigger('click');
        }
    </script>
@endsection