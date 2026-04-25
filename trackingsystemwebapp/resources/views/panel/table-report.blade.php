<style>
/* Cambiar color de las filas pares en table-striped */
.table-striped > tbody > tr:nth-of-type(odd) {
  background-color: #E7E7E7 !important; 
}
</style>
@if (isset($reportes) && count($reportes) > 0)
    @foreach ($reportes as $reporte)
        <h4>Unidad: {{ $reporte['unidad']['descripcion'] . '| Ruta: ' . $reporte['ruta']['descripcion'] }}</h4>
        <button onclick="printmult('{{$reporte['unidad']['_id']}}','{{$reporte['ruta']['_id']}}','N');" type="button" class="btn btn-default">TICKET MULTA</button>
        <button onclick="printmult2('{{$reporte['unidad']['_id']}}','{{$reporte['ruta']['_id']}}','N');"type="button" class="btn btn-default">TICKET MULTA 2 </button>
        <button onclick="printmult('{{$reporte['unidad']['_id']}}','{{$reporte['ruta']['_id']}}','S');" type="button" class="btn btn-default">TICKET PADRE MULTA</button>
        <button onclick="printmult2('{{$reporte['unidad']['_id']}}','{{$reporte['ruta']['_id']}}','S');"type="button" class="btn btn-default">TICKET PADRE MULTA 2 </button>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
                <thead>
                    <tr>
                        <th></th>
                        @foreach ($reporte['ruta']->puntos_control as $punto_control)
                            <th colspan="5">{{ App\PuntoControl::findOrFail($punto_control['id'])->descripcion }}</th>
                        @endforeach
                        <th colspan="3">Contador</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr>
                        <td><strong>Fecha</strong></td>
                        @foreach ($reporte['ruta']->puntos_control as $punto_control)
                            <td><strong>Reloj</strong></td>
                            <td><strong>Marca</strong></td>
                            <td><strong>AT/AD</strong></td>
                            <td><strong>Cont</strong></td>
                            <td><strong>Desc.</strong></td>
                        @endforeach
                        <td><strong>I</strong></td>
                        <td><strong>F</strong></td>
                        <td><strong>P</strong></td>
                        <td><strong>C. Tubo</strong></td>
                        <td>T. AT</td>
                        <td>T. AD</td>
                        <td>Multa</td>
                        <td>Usuario CR.</td>
                        <td>Usuario FN.</td>
                        <td>Usuario RE.</td>
                        <td></td>
                        <td colspan="2"></td>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($reporte['despachos'] as $despacho)
                        <tr>
                            <td class="text-center align-middle" style="vertical-align: middle;">
                                <div style="display: inline-block; white-space: nowrap;">
                                    @if($despacho->unidad)
                                        <button type="button" class="btn btn-info btn-xs" title="Ver recorrido" onclick="verReproductor('{{ $despacho->_id }}')" style="margin-right: 6px;">
                                            <i class="fa fa-road" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    <span style="vertical-align: middle; font-size: 13px;">
                                        {{ $despacho->fecha->addHours(5)->format('d/m/Y H:i') }}
                                    </span>
                                    <a href="#" onclick="recalcularv2('{{ $despacho->_id }}');">*</a>

                                </div>
                            </td>
                            @foreach ($despacho->puntos_control as $punto_control)
                                @if($tip_calculo=='algoritmo')
                                    @php
                                        $marcaptorecorrido = \App\Helper\FunctionsHelper::diferenciaPuntoRecorrido(
                                            $punto_control,
                                            $despacho->unidad_id,
                                            $despacho->fecha->addHours(5),
                                            $despacho->fecha_culminacion['date'],
                                            $loop->index
                                        );
                                    @endphp
                                @endif

                                <td>{{ $punto_control['tiempo_esperado']->toDateTime()->format('H:i') }}</td>
                                @if($tip_calculo=='algoritmo')
                                    <td>{{ (!isset($marcaptorecorrido))?'-':DateTime::createFromFormat('Y-m-d H:i:s', $marcaptorecorrido->fecha)->format('H:i') }}</td>
                                    <td>{{ (!isset($marcaptorecorrido))? '-' : $marcaptorecorrido->tiempo}}</td>

                                @else
                                    <td>{{ (!isset($punto_control['marca']))?'-':DateTime::createFromFormat('Y-m-d H:i:s', $punto_control['marca'])->format('H:i:s') }}</td>
                                    <td>{{ (!isset($punto_control['tiempo_atraso']))? '-' . (!isset($punto_control['tiempo_adelanto'])?'':$punto_control['tiempo_adelanto']):'+' . $punto_control['tiempo_atraso'] }}</td>
                                @endif
                              
                                <td>{{ (!isset($punto_control['contador_marca']))?'-':$punto_control['contador_marca'] }}</td>
                                @php 
                                   $intervalo = ($tip_calculo == 'algoritmo')
                                        ? ($marcaptorecorrido->minutos ?? 0)
                                        : ($punto_control['intervalo'] ?? 0);
                                    
                                    $atraso   = $punto_control['atraso']   ?? 0;
                                    $adelanto = $punto_control['adelanto'] ?? 0;

                                    if ($tip_calculo == 'algoritmo') {
                                        $desc = (isset($marcaptorecorrido->estado) && $marcaptorecorrido->estado === 'n')
                                            ? $intervalo * $adelanto
                                            : $intervalo * $atraso;
                                    } else {
                                        $desc = isset($punto_control['tiempo_atraso'])
                                            ? $intervalo * $atraso
                                            : $intervalo * $adelanto;
                                    }
                                @endphp
                                <td>{{ abs($desc) }}</td>
                            @endforeach
                            <td>{{ $despacho->contador_inicial }}</td>
                            <td>{{ $despacho->contador_final }}</td>
                            @php 
                                $cont_result=$despacho->contador_final - $despacho->contador_inicial;
                                if( $cont_result < 0 ){
                                    $cont_result=($despacho->contador_final + 65535) - $despacho->contador_inicial;
                                }
                                if( $cont_result < 0 )
                                    $cont_result='-';

                            @endphp
                            <td>{{ $cont_result }}</td>                       
                            <td>{{ $despacho->corte_tubo }}</td>
                         @php
                            $atrasos = 0;
                            $adelantos = 0;
                            $multa = 0;

                            foreach ($despacho->puntos_control as $punto_control) {
                                
                                if($tip_calculo == 'algoritmo'){
                                    $marcaptorecorrido1 = \App\Helper\FunctionsHelper::diferenciaPuntoRecorrido(
                                            $punto_control,
                                            $despacho->unidad_id,
                                            $despacho->fecha->addHours(5),
                                            $despacho->fecha_culminacion['date'],
                                            $loop->index
                                        );
                                    $valor = 0;
                                    if(isset($marcaptorecorrido1) && $marcaptorecorrido1->estado === 'n'){
                                        $valor = ($marcaptorecorrido1->minutos * -1) * floatval($punto_control['adelanto'] ?? 0);
                                        $adelantos += $valor;
                                    } else {
                                        $valor = ($marcaptorecorrido1->minutos ?? 0) * floatval($punto_control['atraso'] ?? 0);
                                        $atrasos += $valor;
                                    }
                                    $multa += $valor;
                                } else {
                                    if(isset($punto_control['tiempo_atraso'])){
                                        $valor = ($punto_control['intervalo'] ?? 0) * floatval($punto_control['atraso'] ?? 0);
                                        $atrasos += $valor;
                                    } elseif(isset($punto_control['tiempo_adelanto'])){
                                        $valor = ($punto_control['intervalo'] ?? 0) * -1 * floatval($punto_control['adelanto'] ?? 0);
                                        $adelantos += $valor;
                                    }
                                }
                            }
                        @endphp
                            <td>{{ $atrasos }}</td>
                            <td>{{ $adelantos }}</td>
                            <td>{{ ($tip_calculo == 'algoritmo')?number_format($multa, 2): $despacho->multa }}</td>
                            <td>{{ ($despacho->creador!=null)?$despacho->creador->name:""}}</td>
                            <td>{{ ($despacho->user_end!=null)?$despacho->user_end->name:""}}</td>
                            <td>{{ ($despacho->user_recalculo!=null)?$despacho->user_recalculo->name:""}}</td>

                            <td><a href="#" onclick="recalcular('{{ $despacho->_id }}');">Recalcular</a> 
                            </td>
                            
                            <td style="white-space: nowrap;">
                                <a href="#" onclick="construirImpresionInfo('{{ $despacho->_id }}');"><i class="fa fa-print"></i>&nbsp;Info</a>

                            </td>
                               
                            <td style="white-space: nowrap;">
                                <a href="#" onclick="construirImpresion('{{ $despacho->_id }}');"><i class="fa fa-print"></i>&nbsp;Imprimir</a>

                            </td>

                        </tr>
                    @endforeach
                </tbody>
              
            </table>
        </div>
    @endforeach
@else 
    <strong>No hay resultados que mostrar.</strong>
@endif

<script>
    
        function construirImpresionInfo(id) {
            var desc1=0;
            var desc2=0;
            var url = '{{ url('/despachos/info') }}' + '/' + id;
            $('#progress').modal('show');
            var w = window.open("", "Imprimir", "width=257,height=400");
            $.get(url, function(data) {
                $('#progress').modal('hide');
                var tabla = [];
                tabla.push('<!DOCTYPE html>');
                tabla.push('<html>');
                tabla.push('<head>');
                tabla.push('<style>');
                tabla.push('@media print {input : { visibility:hidden !important; }}');
                tabla.push('@page {size: auto;margin: 0mm;padding:1;}');

                tabla.push('#info, #info tr, #info td, #info th { border: 1px solid black; }');
                tabla.push('#info, #info tr { border-left: 0px; border-right: 0px; }');
                tabla.push('#info .left { border-left: 1px solid black;}');
                tabla.push('#info .right { border-right: 0px; }');
                tabla.push('#info { border-collapse: collapse; }');

                tabla.push('</style>')
                tabla.push('</head>');
                tabla.push('<body style="display:block;overflow:auto;">');
                tabla.push('<div>');
                tabla.push(
                    '<input type="button" id="btImprimirTicket" value="Imprimir" onclick="imprimirTicket();" />'
                );

                let rutaId = (data.ruta.rutapadre == null) ? data.ruta._id : data.ruta.rutapadre._id;
                const ruta44 = '5edb924c2243df11d23c9e62';
                const ruta46 = '5ec4619c2243df3c3074fd32'; 

                tabla.push('<table style="width:100%;">');
                tabla.push('<tbody>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3" align="center"><strong>TICKET INFO.</strong></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                if (data.unidad.cooperativa_id != '5cc725bc2243df3e4d365362' &&
                    data.unidad.cooperativa_id != '5e0778022243df6cb03a0943' &&
                    data.unidad.cooperativa_id != '5ac3032b2243df3f4d02f243') // RAMOVAL 10AGOSTO           
                    tabla.push('<td  colspan="2">' + data.conductor.nombre + '</td>');
                tabla.push('<td>' + data.contador_inicial + '/' + data.contador_final + '</td>');

                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td>' + ((data.ruta.rutapadre == null) ? data.ruta.descripcion : data.ruta.rutapadre
                    .descripcion) + '</td>');
                tabla.push('<td>' + new Date(data.fecha).format('d/m/Y') + '</td>');
                tabla.push('<td><strong>' + data.unidad.descripcion + '</strong></td>');
                tabla.push('</tbody>');
                tabla.push('</table>');

                tabla.push('<table id="info" style="width:100%; text-align:center;">');
                tabla.push('<thead><th>PT.</th><th>R</th><th>M</th><th>A-|A+</th></thead>');
                //CUANDO LA VUELTA ANTERIOR TIENE MAS PUNTOS QUE LA QUE SE VA A REALIZAR SE DEBE RECORRER APARTE EL DATA.ANTERIOR.PUNTOS_CONTROL PARA CON ESTO LUEGO HACER UN APPEND LA TABLA, 
                let recorrido_puntos = data.puntos_control.length;
                var anteriorBusAT = 0;
                var anteriorBusAD = 0;
                var descuentoTotalPuntos = 0;
                var rutaPuntosControl = (data.ruta && data.ruta.puntos_control) ? data.ruta.puntos_control : [];

                for (var i = 0; i < recorrido_puntos; i++) {
                    tabla.push('<tr style="line-height : 0.7;">');
                    let descripcionReal = data.unidad.cooperativa_id == '68012ee36d838a101b60fb13';
                    tabla.push('<td class="left">' + ((data.puntos_control[i] != null) ? (
                        descripcionReal?data.puntos_control[i].original_descripcion:data.puntos_control[i].descripcion
                    ) : '-') + '</td>');

                    if (data.puntos_control[i] != null) {
                        if (data.puntos_control[i].tiempo_esperado != null &&
                            data.puntos_control[i].tiempo_esperado != undefined) {
                            console.log(data.puntos_control[i]);
                            var horaAnterior = new Date(parseInt(data.puntos_control[i].tiempo_esperado.$date
                                .$numberLong));
                            horaAnterior.setHours(horaAnterior.getHours() + 5);
                            var marcahora = "-";
                            let marca = data.puntos_control[i].marca;

                            if (marca) {
                                marcahora = marca.substring(11, 19);
                            }
                            tabla.push('<td> ' + horaAnterior.format('H:i') + '</td>');
                            tabla.push('<td> ' + marcahora + '</td>');
                            if (data.puntos_control[i].marca != null) {
                                var intervalo= (data.puntos_control[i].intervalo!=null &&
                                    data.puntos_control[i].intervalo!=undefined)?data.puntos_control[i].intervalo:0;
                                var atraso = (data.puntos_control[i].atraso != null && data.puntos_control[i]
                                    .atraso != undefined) ? data.puntos_control[i].atraso : 0;
                                var adelanto = (data.puntos_control[i].adelanto != null && 
                                data.puntos_control[i].adelanto != undefined) ? data.puntos_control[i].adelanto : 0;
                                var tt=(data.puntos_control[i].intervalo>0)?"+"+data.puntos_control[i].intervalo:data.puntos_control[i].intervalo;
                                tabla.push('<td>' + tt  + '</td>');
                                if (data.puntos_control[i].intervalo > 0)
                                    anteriorBusAT += data.puntos_control[i].intervalo;
                                else
                                    anteriorBusAD += data.puntos_control[i].intervalo * -1;

                                var desc = 0;
                                if (data.puntos_control[i].tiempo_atraso != null && data.puntos_control[i].tiempo_atraso != undefined) {
                                    desc = intervalo * atraso;
                                } else {
                                    desc = intervalo * adelanto;
                                }

                                var cfgPunto = null;
                                for (var rp = 0; rp < rutaPuntosControl.length; rp++) {
                                    if (String(rutaPuntosControl[rp].id) === String(data.puntos_control[i].id)) {
                                        cfgPunto = rutaPuntosControl[rp];
                                        break;
                                    }
                                }
                                if (cfgPunto) {
                                    var aplicarDesc = (cfgPunto.aplicar_descuento === 1 || cfgPunto.aplicar_descuento === '1' || cfgPunto.aplicar_descuento === true);
                                    if (aplicarDesc) {
                                        var pctDesc = (typeof toFloat === 'function') ? toFloat(cfgPunto.descuento) : (parseFloat(cfgPunto.descuento) || 0);
                                        var multaPunto = (typeof toFloat === 'function') ? toFloat(desc) : (parseFloat(desc) || 0);
                                        if (pctDesc > 0) {
                                            descuentoTotalPuntos += (multaPunto * (pctDesc / 100));
                                        }
                                    }
                                }

                                if (rutaId == ruta44) {
                                    if(i<4)
                                        desc1+=toFloat(desc);
                                    else if (i >= 4 && i < 6)
                                        desc2+=toFloat(desc);
                                }
                                else if (rutaId == ruta46) {
                                    if(i<4)
                                        desc1+=toFloat(desc);
                                    else if (i >= 5 && i < 9)
                                        desc2+=toFloat(desc);
                                }
                            } else
                                tabla.push('<td>-</td>');
                        } else {
                            tabla.push('<td>-</td>');
                            tabla.push('<td>-</td>');
                        }
                    } else {
                        tabla.push('<td>-</td>');
                        tabla.push('<td>-</td>');
                    }
                    tabla.push('</tr>');
                }

                tabla.push('</table><br/>');
                tabla.push('<table style="width:100%; text-align:center;">');
                tabla.push('<tr>');

                tabla.push('<td >' + new Date().format('d/m/Y H:i:s') + '</td>');
                tabla.push('</tr>');
                tabla.push('</table>');
                if (data.unidad.cooperativa_id == '5829c7407aea9111257dd831') {
                    tabla.push('<b>Descuento 1: $ ' + desc1.toFixed(2) + '</b><br/>');
                    tabla.push('<b>Descuento 2: $ ' + desc2.toFixed(2) + '</b><br/>');
                    let descuentoTotal = desc1 + desc2;
                    tabla.push('<b>Total: $ ' + descuentoTotal.toFixed(2) + '</b><br/>'); 
                }
                if (data.multa == null) {
                    tabla.push('<b>Cobro Total: $ -</b><br/>');
                } else {
                    var cobroTotal = (typeof toFloat === 'function') ? toFloat(data.multa) : (parseFloat(data.multa) || 0);
                    var totalConDescuento = cobroTotal - descuentoTotalPuntos;
                    if (totalConDescuento < 0) totalConDescuento = 0;
                    tabla.push('<b>Cobro Total: $ ' + cobroTotal.toFixed(2) + '</b><br/>');
                    tabla.push('<b>Descuento Total: $ ' + descuentoTotalPuntos.toFixed(2) + '</b><br/>');
                    tabla.push('<b>Total: $ ' + totalConDescuento.toFixed(2) + '</b><br/>');
                }
                tabla.push('<b>Corte de tubo:' + ((data == null) ? '-' : data.corte_tubo) + '</b><br/>');
                tabla.push('<b>Total AD ant.:' + anteriorBusAD + '</b>&nbsp&nbsp&nbsp&nbsp');
                tabla.push('<b>Total AT ant.:' + anteriorBusAT + '</b><br/>');
                tabla.push('</div>');
                tabla.push('</body>');
                tabla.push('<script>');
                tabla.push('function imprimirTicket()');
                tabla.push('{');
                tabla.push('var doc=document.getElementById("btImprimirTicket");');
                tabla.push('doc.setAttribute("style","display:none");');
                tabla.push('window.print();');
                tabla.push('}');
                tabla.push('<\/script>');
                tabla.push('</html>');
                html = tabla.join('');
                w.document.write(html);
                w.document.close();
            }, 'json');
        }


    function printmult($unidad_id,$ruta_id,$ruta_padre){
        var url = '{{ url("/reportes/multasticket") }}';
        var w=window.open("", "Imprimir", "width=250,height=400");
        $.get(url,{
            unidad_id:$unidad_id,
            ruta_id:$ruta_id,
            desde:$('#fecha_inicio').val(),
            hasta:$('#fecha_fin').val(),
            ruta_padre:$ruta_padre
        }, function(data) {
            console.log(data);
            var tabla  = [];
            tabla.push('<!DOCTYPE html>');
            tabla.push('<html>');
            tabla.push('<head>');
            tabla.push('<style>');
            tabla.push('@media print {input : { visibility:hidden !important; }}');
            tabla.push('@page {size: auto;margin: 1;padding:1;}');
            tabla.push('</style>')
            tabla.push('</head>');
            tabla.push('<body style="display:block;overflow:auto;">');
            tabla.push('<div>');
            tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
        
            tabla.push('<table style="width:100%;  border-collapse: collapse;">');
            for (var i = 0; i < data.despachos.length; i++)
            {
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.despachos[i].conductor.nombre + '</b></td>');
                tabla.push('<td colspan="2">' + data.despachos[i].contador_inicial + '/'+data.despachos[ data.despachos.length-1].contador_final+'</b></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.ruta.descripcion + '</b></td>');
                tabla.push('<td colspan="2">' +  new Date( data.despachos[i].fecha).format('d/m/Y')  +'</b></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.unidad.descripcion + '</b></td>');
                tabla.push('</tr>');
                break;
            
            }
            tabla.push('</table>');


            tabla.push('<table style="width:100%;  border-collapse: collapse;">');
            var multaTotal=0.0;
            var totalAD=0.0;
            var totalAT=0.0;
            var corteTubo="NO";
            tabla.push('<tr>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">VUELTA</td>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">H. INICIO</td>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">H. FINAL</td>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">COBRO</td>');
            tabla.push('</tr>');

            for (var i = 0; i < data.despachos.length; i++)
            {
                tabla.push('<tr>');
                tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + (i+1) + '</td>');
                var horaInicio = new Date(parseInt(data.despachos[i].puntos_control[0].tiempo_esperado.$date.$numberLong));
                horaInicio.setHours(horaInicio.getHours() + 5);
                var horaFin = new Date(parseInt(data.despachos[i].puntos_control[data.despachos[i].puntos_control.length-1].tiempo_esperado.$date.$numberLong));
                horaFin.setHours(horaFin.getHours() + 5);
                tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + new Date(horaInicio).format('H:i') + '</td>');
                tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + new Date(horaFin).format('H:i') + '</td>');
                tabla.push('<td style="border: 1px solid #000000; text-align: left;">$' +  ((data.despachos[i].multa == null)?'-':toFloat(data.despachos[i].multa).toFixed(2))+'</td>');
                tabla.push('</tr>');

                if(data.despachos[i].corte_tubo != null){
                    if(data.despachos[i].corte_tubo =="Si"){
                        corteTubo="SI";
                    }
                }
            
                multaTotal=multaTotal+ ((data.despachos[i].multa == null)?0:toFloat(data.despachos[i].multa));
            
            }
            // for (var i = 0; i < data.ultimo.length; i++)
            // {
            //     for (var j = 0; j < data.ultimo[i].puntos_control.length; j++){
            //         console.log(data.ultimo[i].puntos_control);
            //         if (data.ultimo[i].puntos_control[j].marca != null) {
            //             console.log(data.ultimo[i].puntos_control[j].intervalo );
            //             if (data.ultimo[i].puntos_control[j].intervalo > 0)
            //                 totalAT += data.ultimo[i].puntos_control[j].intervalo;
            //             else
            //                 totalAD += data.ultimo[i].puntos_control[j].intervalo * -1;
            //         }
                
            //     }
                

            // }

            tabla.push('<tr>');
            tabla.push('<td> <br/> <br/></td>');
            tabla.push('</tr>');

            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>Cobro: $' + toFloat(multaTotal).toFixed(2) + '</b></td>');
            tabla.push('</tr>');
            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>Corte de Tubo: ' +corteTubo + '</b></td>');
            tabla.push('</tr>');
            // tabla.push('<tr>');
            // tabla.push('<td colspan="2"> <b>Total AD: ' +totalAD + '</b></td>');
            // tabla.push('</tr>');
            // tabla.push('<tr>');
            // tabla.push('<td colspan="2"> <b>Total AT: ' +totalAT + '</b></td>');
            // tabla.push('</tr>');
            
            tabla.push('</tr>');
            tabla.push('</table>');
            tabla.push('</div>');
            tabla.push('</body>');
            tabla.push('</html>');
            html = tabla.join('');
            w.document.body.innerHTML = html;
        }, 'json');
    }

    function verReproductor(despachoId) {
        let url = '{{ route('despachos.reproductor', ':despachoId') }}';
        url = url.replace(':despachoId', despachoId);
        window.open(url, '_blank');
    }

    function printmult2($unidad_id,$ruta_id,$ruta_padre){
        var url = '{{ url("/reportes/multasticket") }}';
        var w=window.open("", "Imprimir", "width=250,height=400");
        $.get(url,{
            unidad_id:$unidad_id,
            ruta_id:$ruta_id,
            desde:$('#fecha_inicio').val(),
            hasta:$('#fecha_fin').val(),
            ruta_padre:$ruta_padre
        }, function(data) {
            console.log(data);
            var tabla  = [];
            tabla.push('<!DOCTYPE html>');
            tabla.push('<html>');
            tabla.push('<head>');
            tabla.push('<style>');
            tabla.push('@media print {input : { visibility:hidden !important; }}');
            tabla.push('@page {size: auto;margin: 1;padding:1;}');
            tabla.push('</style>')
            tabla.push('</head>');
            tabla.push('<body style="display:block;overflow:auto;">');
            tabla.push('<div>');
            tabla.push('<input type="button" value="Imprimir" onclick="window.print();" />');
        
            tabla.push('<table style="width:100%;  border-collapse: collapse;">');
            for (var i = 0; i < data.despachos.length; i++)
            {
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.despachos[i].conductor.nombre + '</b></td>');
                tabla.push('<td colspan="2">' + data.despachos[i].contador_inicial + '/'+data.despachos[ data.despachos.length-1].contador_final+'</b></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.ruta.descripcion + '</b></td>');
                tabla.push('<td colspan="2">' +  new Date( data.despachos[i].fecha).format('d/m/Y')  +'</b></td>');
                tabla.push('</tr>');
                tabla.push('<tr>');
                tabla.push('<td colspan="3">' + data.unidad.descripcion + '</b></td>');
                tabla.push('</tr>');
                break;
            
            }
            tabla.push('</table>');


            tabla.push('<table style="width:100%;  border-collapse: collapse;">');
            var multaTotal=0.0;
            var totalAD=0.0;
            var totalAT=0.0;
            var totalATAD=0.0;
            var totalATADCobro=0.0;
            var corteTubo="NO";
            tabla.push('<tr>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">PTS</td>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">A+/A-</td>');
            tabla.push('<td style="border: 1px solid #000000; text-align: left;">COBRO</td>');
            tabla.push('</tr>');

            if(data.despachos.length>0){
                for (var i = 0; i < data.despachos[0].puntos_control.length; i++)
                {
                    tabla.push('<tr>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' + (i+1) + '</td>');
                    totalATAD=0.0;
                    totalATADCobro=0.0;
                    for (var j = 0; j < data.despachos.length; j++)
                    {
                        totalATAD +=data.despachos[j].puntos_control[i].intervalo;    
                       // totalATADCobro += toFloat(data.despachos[j].puntos_control[i].atraso);
                       if (data.despachos[j].puntos_control[i].marca != null) { 
                        if (data.despachos[j].puntos_control[i].intervalo > 0)
                            totalATADCobro += toFloat(data.despachos[j].puntos_control[i].atraso) * data.despachos[j].puntos_control[i].intervalo  ;  
                        else
                            totalATADCobro += toFloat(data.despachos[j].puntos_control[i].adelanto) * (data.despachos[j].puntos_control[i].intervalo-1);  
                       }
                    }

                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">' +totalATAD + '</td>');
                    tabla.push('<td style="border: 1px solid #000000; text-align: left;">$' + totalATADCobro.toFixed(2)+'</td>');
                    tabla.push('</tr>');
                }
            }

            for (var i = 0; i < data.despachos.length; i++)
            {
                if(data.despachos[i].corte_tubo != null){
                    if(data.despachos[i].corte_tubo =="Si"){
                        corteTubo="SI";
                    }
                }
            
                multaTotal=multaTotal+ ((data.despachos[i].multa == null)?0:toFloat(data.despachos[i].multa));
            
            }

            tabla.push('<tr>');
            tabla.push('<td> <br/> <br/></td>');
            tabla.push('</tr>');

            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>Cobro: $' + toFloat(multaTotal).toFixed(2) + '</b></td>');
            tabla.push('</tr>');
            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>Corte de Tubo: ' +corteTubo + '</b></td>');
            tabla.push('</tr>');
            tabla.push('<tr>');
            tabla.push('<td colspan="2"> <b>Total Vueltas: ' + data.despachos.length + '</b></td>');
            tabla.push('</tr>');
            
            tabla.push('</tr>');
            tabla.push('</table>');
            tabla.push('</div>');
            tabla.push('</body>');
            tabla.push('</html>');
            html = tabla.join('');
            w.document.body.innerHTML = html;
        }, 'json');
    }
</script>