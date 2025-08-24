<template>
    <div>
        <div class="page-title">
            <div class="title_left">
                <h3>Sorteos</h3>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_content">
                        <div class="row">
                            <div class="col-sm-12 col-md-3">
                                <div class="form-group">
                                    <label for="cooperativa">Cooperativa</label>
                                    <select :disabled="sorteoConsultado" class="form-control" v-model="form.cooperativa_id"
                                        name="cooperativa_id" id="cooperativa">
                                        <option disabled value="">Seleccione...</option>
                                        <option :value="cooperativa._id" v-for="cooperativa in cooperativas"
                                            :key="cooperativa._id">
                                            {{ cooperativa.descripcion }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha del sorteo</label>
                                    <input :readonly="sorteoConsultado" v-model="form.fecha" class="form-control"
                                        type="text" name="fecha" id="fecha">
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-3">
                                <div class="form-group">
                                    <label for="cantidad_sorteos">Cant. de sorteos</label>
                                    <input :readonly="sorteosGenerados || !sorteoConsultado" v-model="form.cantidad_sorteos"
                                        class="form-control" type="number" name="cantidad_sorteos" id="cantidad_sorteos">
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-3">
                                <div class="form group">
                                    <label for="cabecera">Cabecera de reporte</label>
                                    <input :readonly="!sorteoConsultado || existeSorteo" class="form-control" type="text"
                                        name="cabecera" id="cabecera" v-model="form.cabecera" placeholder="Cabecera">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <button v-show="!sorteoConsultado" type="button" @click="aceptarFecha"
                                    class="btn btn-default"><span>Consultar</span></button>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-sm-12">
                                <button v-show="!sorteosGenerados && sorteoConsultado" @click="aceptarCantidadSorteos"
                                    class="btn btn-primary" type="button"><span>Aceptar</span></button>
                                <button v-show="sorteoConsultado" @click="cancelarCantidadSorteos" class="btn btn-danger"
                                    type="button"><span>Cancelar</span></button>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th># Sorteo</th>
                                                <th>Intervalo</th>
                                                <th>Hora de inicio</th>
                                                <th v-show="!existeSorteo">Lista de unidades</th>
                                                <th>Acción</th>
                                                <th>Programación</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr :selected-indices="selectedIndices" :ref="'row' + index"
                                                @changed="changedSorteo" :editable="!existeSorteo"
                                                :indice-inicial="index > 0 ? obtenerSorteados(index) : 1"
                                                v-model="form.sorteos[index]" :unidades="filteredUnidades"
                                                v-for="(sorteo, index) in form.sorteos" :key="index" is="tr-sorteo"></tr>
                                            <tr v-show="form.sorteos.length === 0">
                                                <td :colspan="existeSorteo ? 5 : 6">
                                                    <strong>No se ha generado sorteos</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-sm-12">
                                <button @click="guardarSorteo"
                                    v-show="permiteGuardar && sorteoConsultado && sorteosGenerados && !existeSorteo"
                                    class="btn btn-primary" type="button"><span>Guardar</span></button>
                                <button @click="eliminarSorteo" v-show="existeSorteo && sorteoConsultado"
                                    class="btn btn-danger" type="button"><i
                                        class="fa fa-trash"></i><span>Eliminar</span></button>
                                <button @click="imprimirSorteos" v-show="existeSorteo" class="btn btn-success"
                                    type="button"><i class="fa fa-print"></i><span>Imprimir</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <modal name="imprimir" height="auto" width="50%" scrollable>
            <fechas-sorteos :cabecera="form.cabecera" @cerrar="$modal.hide('imprimir')"
                :fecha-inicial="form.fecha"></fechas-sorteos>
        </modal>
    </div>
</template>

<script>
export default {
    methods: {
        obtenerIndexExistente: function (index) {
            for (let i = 0; i < this.selectedIndices.length; i++)
                if (this.selectedIndices[i] === index)
                    return i;
            return -1;
        },
        changedSorteo: function (index, selected) {
            let indice = this.obtenerIndexExistente(index);
            if (indice > -1) {
                if (!selected)
                    this.selectedIndices.splice(indice, 1);
            }
            else
                this.selectedIndices.push(index);
        },
        imprimirSorteos: function () {
            this.$modal.show('imprimir');
        },
        eliminarSorteo: function () {
            let that = this;
            swal.fire({
                title: '¿Deseas eliminar este sorteo?',
                text: 'Se eliminará el sorteo actual.',
                type: 'warning',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Eliminar'
            }).then(function (result) {
                if (result.value) {
                    $.post('/' + that.id + '/sorteo', {
                        _method: 'DELETE'
                    }, function (data) {
                        that.cancelarCantidadSorteos();
                        swal.fire('El sorteo ha sido eliminado.', 'Realizado', 'success');
                    }).fail(function () {
                        swal.fire('Hubo un error, intente nuevamente.', 'Error', 'error');
                    });
                }
            });
        },
        guardarSorteo: function () {
            let that = this;
            $.post('/sorteos', this.form, function (response) {
                swal.fire('El sorteo ha sido almacenado exitósamente.', 'Realizado', 'success');
                that.existeSorteo = true;
                that.id = response._id;
            }).fail(function () {
                swal.fire('Hubo un error, intente nuevamente.', 'Error', 'error');
            });
        },
        obtenerSorteados: function (index) {
            let sorteados = 0;
            for (let i = 0; i < index; i++)
                sorteados += this.form.sorteos[i].unidades.length;
            return sorteados + 1;
        },
        aceptarCantidadSorteos: function () {
            let cantidadSorteos = parseInt(this.form.cantidad_sorteos);
            if (isNaN(cantidadSorteos))
                swal.fire('Debe especificar un número válido.', 'Aviso', 'warning');
            else if (cantidadSorteos === 0)
                swal.fire('La cantidad de sorteos debe ser mayor a cero.', 'Aviso', 'warning');
            else if (cantidadSorteos > this.unidades.length)
                swal.fire('La cantidad de sorteos no puede ser mayor a la cantidad total de unidades.', 'Aviso', 'warning');
            else {
                this.filteredUnidades.splice(0, this.filteredUnidades.length);
                for (let i = 0; i < this.unidades.length; i++) {
                    if (this.unidades[i].cooperativa_id == this.form.cooperativa_id)
                        this.filteredUnidades.push(this.unidades[i]);
                }
                for (let i = 0; i < cantidadSorteos; i++)
                    this.form.sorteos.push({
                        intervalo: '',
                        hora_inicio: '',
                        numero_unidades: '',
                        unidades: [],
                        selectedUnidades: []
                    });
                this.sorteosGenerados = true;
            }
        },
        cancelarCantidadSorteos: function () {
            this.existeSorteo = false;
            this.sorteosGenerados = false;
            this.sorteoConsultado = false;
            this.id = '';
            this.form.cabecera = '';
            this.form.sorteos.splice(0, this.form.sorteos.length);
            this.selectedIndices.splice(0, this.selectedIndices.length);
        },
        aceptarFecha: function () {
            this.form.fecha = $('#fecha').val();
            if (this.form.fecha.length === 10) {
                let date = new Date(this.form.fecha);
                if (date == 'Invalid Date')
                    swal.fire('La fecha es inválida.', 'Aviso', 'warning');
                else {
                    let that = this;
                    $.get('/sorteo', {
                        fecha: this.form.fecha,
                        cooperativa_id: this.form.cooperativa_id
                    }, function (response) {
                        let sorteo = response.sorteo;
                        if (sorteo !== null) {
                            that.sorteosGenerados = true;
                            that.existeSorteo = true;
                            that.form.cantidad_sorteos = sorteo.cantidad_sorteos;
                            that.form.cabecera = sorteo.cabecera;
                            that.id = sorteo._id;
                            for (let i = 0; i < sorteo.sorteos.length; i++) {
                                let s = {
                                    intervalo: sorteo.sorteos[i].intervalo,
                                    hora_inicio: sorteo.sorteos[i].hora_inicio,
                                    numero_unidades: sorteo.sorteos[i].numero_unidades,
                                    unidades: [],
                                    selectedUnidades: []
                                };
                                for (let j = 0; j < sorteo.sorteos[i].unidades.length; j++) {
                                    s.unidades.push({
                                        'id': sorteo.sorteos[i].unidades[j].id,
                                        'descripcion': sorteo.sorteos[i].unidades[j].descripcion,
                                        'hora': sorteo.sorteos[i].unidades[j].hora
                                    });
                                    s.selectedUnidades.push(sorteo.sorteos[i].unidades[j].id);
                                }
                                that.form.sorteos.push(s);
                            }
                        }
                        if (that.tipoUsuario != 3 && that.tipoUsuario != 4)
                            that.sorteoConsultado = true;
                    });
                }
            }
            else
                swal.fire('El formato de fecha no es correcto.', 'Aviso', 'warning');
        }
    },
    data: function () {
        return {
            form: {
                fecha: '',
                cantidad_sorteos: 1,
                sorteos: [],
                cabecera: '',
                cooperativa_id: ''
            },
            sorteoConsultado: false,
            sorteosGenerados: false,
            existeSorteo: false,
            id: '',
            filteredUnidades: [],
            selectedIndices: []
        };
    },
    props: {
        unidades: {
            type: Array,
            required: true
        },
        tipoUsuario: {
            required: true,
            default: ''
        },
        cooperativas: {
            type: Array,
            required: false,
            default: function () {
                return [];
            }
        }
    },
    computed: {
        permiteGuardar: function () {
            let permite = true;
            if (this.form.cabecera === '')
                permite = false;
            else
                for (let i = 0; i < this.form.sorteos.length; i++) {
                    if (this.form.sorteos[i].unidades.length == 0)
                        permite = false;
                }
            return permite;
        }
    },
    mounted: function () {
        if (this.cooperativas.length == 1)
            this.form.cooperativa_id = this.cooperativas[0]._id;
    }
}
</script>
