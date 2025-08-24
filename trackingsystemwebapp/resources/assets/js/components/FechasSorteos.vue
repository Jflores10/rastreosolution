<template>
    <div class="ts_modal">
        <div class="panel">
            <div class="panel_heading">
                <h3>Impresión de sorteos</h3>
            </div>
            <div class="panel_body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="cabeceraReporte">Cabecera</label>
                            <input class="form-control" type="text" name="cabeceraReporte" id="cabeceraReporte" v-model="form.cabecera">
                        </div>
                        <div class="form-group">
                            <label for="desde">Fecha inicial</label>
                            <input class="form-control" placeholder="AAAA-MM-DD" type="text" name="desde" id="desde" v-model="form.desde">
                        </div>
                        <div class="form-group">
                            <label for="hasta">Fecha final</label>
                            <input class="form-control" placeholder="AAAA-MM-DD" type="text" name="hasta" id="hasta">
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <button @click="consultar" class="btn btn-success" type="button"><i class="fa fa-search"></i><span>Imprimir</span></button>
                <button @click="$emit('cerrar')" class="btn btn-danger" type="button"><i class="fa fa-ban"></i><span>Cerrar</span></button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props : {
        fechaInicial : {
            type : String,
            required : false,
            default : ''
        },
        cabecera : {
            type : String,
            required : true
        }
    },
    data : function () {
        return {
            form : {
                desde : this.fechaInicial,
                hasta : '',
                cabecera : this.cabecera
            }
        };
    },
    mounted : function () {
        $('#desde').datepicker({
            dateFormat : 'yy-mm-dd'
        });
        $('#hasta').datepicker({
            dateFormat : 'yy-mm-dd'
        });
    },
    methods : {
        consultar : function () {
            this.form.desde = $('#desde').val();
            this.form.hasta = $('#hasta').val();
            if (this.form.desde.length !== 10)
                this.$swal('La fecha inicial no tiene el formato correcto.', 'Aviso', 'warning');
            else if (this.form.hasta.length !== 10)
                this.$swal('La fecha final no tiene el formato correcto.', 'Aviso', 'warning');
            else {
                let desde = new Date(this.form.desde);
                if (desde == 'Invalid Date')
                {
                    this.$swal('Fecha inicial inválida.', 'Aviso', 'warning');
                    return;
                }
                let hasta = new Date(this.form.hasta);
                if (hasta == 'Invalid Date') {
                    this.$swal('La fecha final es inválida.', 'Aviso', 'warning');
                    return;
                }
                if (desde > hasta)
                    this.$swal('La fecha inicial no debe ser mayor que la fecha final.', 'Aviso', 'warning');
                else 
                    window.open('/sorteos/imprimir?desde=' + this.form.desde + '&hasta=' + this.form.hasta + '&cabecera=' + this.form.cabecera, '_blank');
            }
        }
    }
}
</script>
