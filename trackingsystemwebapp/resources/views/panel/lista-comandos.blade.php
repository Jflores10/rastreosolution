@extends('layouts.app')

@section('title', 'Comandos')

@section('styles')
    <style>
    .ploading{
        position: fixed;
        width: 100%;
        height: 100vh;
        z-index: 100000;
        background: rgba(0, 0, 0, 0.6);
        top: 0;
        left: 0;
        display: none;
   }

   .disco{
      border: 16px solid #f3f3f3; /* Light grey */
      border-top: 16px solid #7F7F7F; /* Blue */
      border-radius: 50%;
      width: 80px;
      height: 80px;
      margin: auto;
      margin-top: 40vh;
      animation: spin 2s linear infinite;    
   }

   @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
   }
    </style>
@endsection

@section('content')
<div class="ploading" id="loading">
    <div class="disco"></div>
    <h2 class="text-center" style="color: white;">Procesando...</h2>
</div>

<div class="page-title">
    <div class="title_left">
        <h3>Comandos</h3>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">
                <br />
                <div class="mb-3 text-right">
                    <button class="btn btn-success" onclick="nuevoComando()">+ Crear Comando</button>
                </div>
                <br />
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Descripción</th>
                                <th>Modo</th>
                                <th>Cooperativa</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comandos as $comando)
                                <tr >
                                    <td>{{ $comando->descripcion }}</td>
                                    <td>{{ ($comando->automatico)?"Automatico":"Manual" }}</td>
                                    <td>{{  (isset($comando->cooperativa))?$comando->cooperativa->descripcion:"" }}</td>
                                    <td>
                                        <button type="button" onclick="editarComando('{{ url('/comandos/get/' . $comando->_id) }}')" class="btn btn-sm btn-primary">
                                           <i class="fa fa-edit"></i> 
                                        </button>
                                      
                                         <button type="button" onclick="eliminarComando('{{$comando->_id }}')" class="btn btn-sm btn-danger">
                                           <i class="fa fa-trash"></i> 
                                        </button>
                                        <button type="button" onclick="enviarComando('{{ url('/comandos/enviar/' . $comando->_id) }}')" class="btn btn-sm btn-info">
                                           <i class="fa fa-send"></i> Enviar
                                        </button>
                                    
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-comando" tabindex="-1">
    <div class="modal-dialog">
            <input type="text" name="id" id="comando_id" value="">

            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalLabel">Comando</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Comando</label>
                        <textarea  rows="3" name="comando" id="comando" class="form-control" required>
                        </textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Cooperativa</label>
                        <select name="cooperativa_id" id="cooperativa_id"  class="form-control" onchange="cargarUnidades()" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach ($cooperativas as $cooperativa_id)
                                <option data-bloques="{{ json_encode($cooperativa_id->pto_bloques)}}"  value="{{ $cooperativa_id->_id }}">
                                    {{ $cooperativa_id->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Modo</label><br>
                        <label class="mr-3">
                            <input type="radio" name="modo" id="modoA" value="A" required> Automática
                        </label>
                        <label>
                            <input type="radio" name="modo" id="modoM" value="M"> Manual
                        </label>
                    </div>
                    <div class="form-group" id="div-bloques" >
                        <label>Bloque:</label><br>
                        <label class="mr-3">
                            <input type="radio" name="bloque" id="b1" value="1" required> Bloque 1
                        </label>
                        <label>
                            <input type="radio" name="bloque" id="b2" value="2"> Bloque 2
                        </label>
                    </div>


                    <div class="form-group">
                        <label>Buses</label>
                        <div class="checkbox">
                            <label><input type="checkbox" id="seleccionar_unidades" onclick="todas_unidades()" /> Todas</label>
                        </div>
                        <div id="div_unidades">
                            <select name="unidades[]" id="unidades" data-placeholder="Unidades" class="form-control" multiple >
                                <option value="">-- Seleccionar --</option>

                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="save()" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
    </div>
</div>

<div class="modal fade" id="resultadoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
      
            <div class="modal-header">
                <h4 class="modal-title">Resultado</h4>
            </div>
            
            <div class="modal-body">
                <p id="resultadoMensaje"></p>
                <ul id="resultadoBusesFallidos" style="color:red;"></ul>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
      
        </div>
    </div>
</div>

    

@endsection

@section('scripts')
<script>
    
    $(function () {
        $('#cooperativa_id').chosen({
            width : '100%'
        })
        $('#unidades').chosen({
            width : '100%'
        });
    });

    function nuevoComando() {
        document.getElementById('comando_id').value = '';
        $('#modal-comando').modal('show');
    }

    function cargarUnidades(selectedBuses = []) {
    $('#div-bloques').hide();
    $('input[name="modo"]').prop('checked', false);

    var id = $('#cooperativa_id').val();
    $('#unidades').empty();

    var url = '{{ url("/comandos/unidades") }}' + '/' + id;
    $.get(url, function (data) {
        for (var i = 0; i < data.unidades.length; i++) {
            $('#unidades').append('<option value="' + data.unidades[i].imei + '">' + data.unidades[i].descripcion + '</option>');
        }

        // Actualizar Chosen
        $('#unidades').trigger('chosen:updated');

        // Seleccionar buses que vienen en el comando
        if(selectedBuses.length > 0) {
            $('#unidades').val(selectedBuses).trigger('chosen:updated');
        }
    }, 'json');
}

function todas_unidades(){
    var checked = $('#seleccionar_unidades').is(':checked');
    $("#unidades").find("option").each(function() {
        $(this).prop('selected', checked);
        $('#unidades').trigger('chosen:updated');
    });
    if (checked){
        $('#div_unidades').hide();
    }
    else{
        $('#div_unidades').show();
    }
}

function save(){
    var idcomando=$('#comando_id').val();
    console.log(idcomando)
    if(idcomando!=''){
        actualizarComando();
    }
    else{
        guardarComando();
    }
}

function guardarComando() {
  
    var url='{{ url('/comandos') }}';
    // ----- CAMPOS -----
    var descripcion = document.getElementById('descripcion').value;
    var comando = document.getElementById('comando').value;
    var modo = $("input[name='modo']:checked").val();
    var bloque = $("input[name='bloque']:checked").val();



    // ----- COOPERATIVA -----
    cooperativa_id = document.getElementById('cooperativa_id').value;
    
    // ----- UNIDADES -----
     // ----- UNIDADES -----
    var unidades = $("#unidades").val() || []; // SIEMPRE array de opciones seleccionadas

    // ----- PARAMETROS A ENVIAR -----
    var param = {
        descripcion: descripcion,
        comando: comando,
        modo: modo,
        bloque: bloque,
        cooperativa_id: cooperativa_id,
        unidades: unidades,
    };
    // ----- POST -----
    $.post(url, param, function (data) {
        if (data.error == false) {
            alert('El comando ha sido guardado con éxito.');
            location.reload(true);
        } else {
            alert('Error al guardar: ' + JSON.stringify(data));
        }
    }, "json");
}

function actualizarComando() {
    // URL del endpoint de actualización
    var url = '{{ url("/comandos/update") }}'; // Puede ser la misma que guardar, el backend decide si crear o actualizar según comando_id

    // ----- CAMPOS -----
    var comando_id = document.getElementById('comando_id').value; // ID obligatorio para actualizar
    if (!comando_id) {
        alert("No se puede actualizar: falta el ID del comando.");
        return;
    }

    var descripcion = document.getElementById('descripcion').value;
    var comando = document.getElementById('comando').value;
    var modo = $("input[name='modo']:checked").val();
    var bloque = $("input[name='bloque']:checked").val();
    console.log("Hola"+modo)
    // ----- COOPERATIVA -----
    var cooperativa_id = document.getElementById('cooperativa_id').value;

    // ----- UNIDADES -----
    var unidades = $("#unidades").val() || []; // Siempre array de opciones seleccionadas

    // ----- PARAMETROS A ENVIAR -----
    var param = {
        comando_id: comando_id, // ID necesario para actualizar
        descripcion: descripcion,
        comando: comando,
        modo: modo,
        bloque: bloque,
        cooperativa_id: cooperativa_id,
        unidades: unidades,
    };

    // ----- POST -----
    $.post(url, param, function(data) {
        if (data.error == false) {
            alert('El comando ha sido actualizado con éxito.');
            location.reload(true);
        } else {
            alert('Error al actualizar: ' + JSON.stringify(data));
        }
    }, "json");
}


function editarComando(url) {
    // Limpiar formulario antes de llenar
    $("#modal-comando input, #modal-comando textarea, #modal-comando select").val("");
    $("#modal-comando input[type='checkbox']").prop("checked", false);
    $("#modal-comando input[name='modo']").prop("checked", false);
    $("#modal-comando input[name='bloque']").prop("checked", false);

    $.get(url, function(data) {

        // Asignar ID oculto
        $("#comando_id").val(data.comando._id);

        // Llenar campos de texto
        $("#descripcion").val(data.comando.descripcion);
        $("#comando").val(data.comando.comando);
        $("#cooperativa_id").val(data.comando.cooperativa_id);
        $("#cooperativa_id").trigger("chosen:updated");
        $("#bloque").val(data.comando.bloque);

        var automatico = data.comando.automatico === true ;
        var bloque = data.comando.bloque ;

        $("#modoA, #modoM").prop("checked", false);
        $("#b1, #b2").prop("checked", false);
        $("#modoA").val('A');
        $("#modoM").val('M');
        $("#b1").val('1');
        $("#b2").val('2');



        // Marcar el radio correcto
        setTimeout(function() { // asegurar que el DOM está listo
            if (automatico) {
                $("#modoA").prop("checked", true);
                $('#div-bloques').show();
                if(bloque==1){
                    $("#b1").prop("checked", true);
                }
                else if(bloque==2){
                    $("#b2").prop("checked", true);
                }
            } else {
                $("#modoM").prop("checked", true);
                $('#div-bloques').hide();

            }
        }, 0);

        // Seleccionar cooperativa
       
       cargarUnidades(data.comando.buses);

        // Abrir modal
        $("#modal-comando").modal("show");
    }, "json");
}

function eliminarComando(comandoId) {
    if (!comandoId) {
        alert("ID del comando no válido.");
        return;
    }

    if (!confirm("¿Está seguro que desea eliminar este comando?")) {
        return;
    }

    $.ajax({
        url: '/comandos/delete/' + comandoId, 
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            alert(response.message); 
            location.reload(true);
        },
        error: function(xhr, status, error) {
            alert("Ocurrió un error al intentar eliminar el comando.");
        }
    });
}


function enviarComando(url) {
    $("#loading").show();

    $.get(url, function(data) {
        // Mostrar mensaje principal
        $("#resultadoMensaje").text(data.mensaje);

        // Limpiar listado anterior
        $("#resultadoBusesFallidos").empty();

        // Si hay buses fallidos, listarlos
        if (data.buses_fallidos && data.buses_fallidos.length > 0) {
            data.buses_fallidos.forEach(function(imei) {
                $("#resultadoBusesFallidos").append("<li>" + imei + "</li>");
            });
        }

        // Mostrar el modal
        $("#resultadoModal").modal("show");

    }, "json")
    .fail(function() {
        $("#resultadoMensaje").text("Error al cargar el comando.");
        $("#resultadoBusesFallidos").empty();
        $("#resultadoModal").modal("show");
    })
    .always(function() {
        $("#loading").hide();
    });
}

   
</script>

<script>
$(document).ready(function() {
  // Inicialmente ocultamos el div
  $('#div-bloques').hide();

  // Al cambiar el radio
  $('input[name="modo"]').change(function() {
    if ($(this).val() === 'A' ) {
        var bloques = $('#cooperativa_id').find("option:selected").data("bloques");
        if (bloques === true || bloques === "true" || bloques == 1) {
            $('#div-bloques').show();
        }
        else{
            $('#div-bloques').hide();
        }
    } else {
        $('#div-bloques').hide();
    }
  });
});
</script>


@endsection
