<template>
    <tr>
        <th scope="row">
            {{ $vnode.key + 1 }}
        </th>
        <td>
            <input class="form-control" :readonly="!editable || sorteados" type="number" v-model="modelValue.intervalo">
        </td>
        <td>
            <input class="form-control" :readonly="!editable || sorteados" :id="'horaInicio' + $vnode.key" type="time" v-model="modelValue.hora_inicio">
        </td>
        <td v-show="editable">
            <div v-show="!indiceSeleccionado(index)" v-for="(unidad, index) in unidades" :key="index" class="checkbox">
                <label :for="'unidad' + $vnode.key + index">
                    <input :editable="!editable" @change="changeUnidades(index)" :id="'unidad' + $vnode.key + index" v-model="modelValue.selectedUnidades" type="checkbox" :value="unidad._id"/><span>{{ unidad.descripcion }}</span>
                </label>
            </div>
        </td>
        <td>
            <button @click="sortear" :disabled="!editable" class="btn btn-default"><i class="fa fa-random"></i></button>
        </td>
        <td>
            <ul class="list-unstyled">
                <li v-for="(unidad, index) in modelValue.unidades" :key="index">
                    ({{ index + indiceInicial }}) {{ unidad.descripcion }} - {{ unidad.hora }}
                </li>
                <li v-show="modelValue.unidades.length === 0">
                    No se ha sorteado esta fila.
                </li>
            </ul>
        </td>
    </tr>
</template>

<script>
export default {
    props : {
        value : {
            type : Object,
            required : false,
            default : function () {
                return {
                    intervalo : '',
                    hora_inicio : '',
                    numero_unidades : '',
                    unidades : [],
                    selectedUnidades : []
                };
            }
        },
        unidades : {
            required : false,
            default : function () {
                return [];
            }
        },
        indiceInicial : {
            required : false,
            default : 1,
            type : Number
        },
        editable : {
            type : Boolean,
            required : false,
            default : true
        },
        selectedIndices : {
            type : Array,
            required : false,
            default : function () {
                return [];
            }
        }
    },
    data : function () {
        return {
            modelValue : this.value,
            rand : null,
            sorteados : false
        };
    },
    methods : {
        indiceSeleccionado : function (index) {
            for (let i = 0; i < this.selectedIndices.length; i++)
                if (this.selectedIndices[i] === index) 
                {
                    let unidad = this.unidades[index];
                    let indiceUnidad = this.obtenerIndiceSeleccionado(unidad._id);
                    if (indiceUnidad !== -1)
                        return false;
                    else 
                        return true;
                }
            return false;
        },
        changeUnidades : function (index) {
            let unidad = this.unidades[index];
            let selected = false;
            for (let i = 0; i < this.modelValue.selectedUnidades.length; i++)
                if (this.modelValue.selectedUnidades[i] === unidad._id) {
                    selected = true;
                    break;
                }
            this.$emit('changed', index, selected);
        },
        obtenerIndiceSeleccionado : function (id) {
            for (let i = 0; i < this.modelValue.selectedUnidades.length; i++) 
                if (this.modelValue.selectedUnidades[i] === id)
                    return i;
            return -1;
        },
        obtenerIndice : function (id) {
            for (let i = 0; i < this.unidades.length; i++) 
                if (this.unidades[i]._id === id)
                    return i;
            return -1;
        },
        calcularHorarios : function (indices) {
            let that = this;
            if (that.editable) {
                that.modelValue.unidades.splice(0, that.modelValue.unidades.length);
                let horaInicio = that.modelValue.hora_inicio;
                if(horaInicio.length !== '' && horaInicio.length === 5) {
                    let intervalo = parseInt(that.modelValue.intervalo);
                    let partesHora = horaInicio.split(':');
                    let hours = parseInt(partesHora[0]);
                    let minutes = parseInt(partesHora[1]);
                    let date = new Date();
                    date.setHours(hours);
                    date.setMinutes(minutes);
                    date.setSeconds(0);
                    date.setMilliseconds(0);
                    for (let i = 0; i < that.modelValue.selectedUnidades.length; i++) {
                        let index = indices[i];
                        if (index > -1) {
                            let unidad = that.unidades[that.obtenerIndice(that.modelValue.selectedUnidades[index])];
                            if (unidad !== undefined) {
                                that.modelValue.unidades.push({
                                    id : unidad._id,
                                    descripcion : unidad.descripcion,
                                    hora : moment(date).format('HH:mm')
                                });
                                date.setMinutes(date.getMinutes() + intervalo);
                            }
                        }
                    }
                    this.sorteados = true;
                }
                else 
                    swal('La hora de inicio proporcionada no es válida.', 'Aviso', 'warning');
            }
        },
        sortear : function () {
            let intervalo = parseFloat(this.modelValue.intervalo);
            if (isNaN(intervalo))
                intervalo = 0;
            if (intervalo === 0)
                this.$swal('El intervalo debe ser mayor a cero.', 'Aviso', 'warning');
            else {
                if (this.modelValue.selectedUnidades.length > 0) {
                    this.rand = uniqueRandom(0, this.modelValue.selectedUnidades.length - 1);
                    let indices = [];
                    for (let i = 0; i < this.modelValue.selectedUnidades.length; i++)
                    {
                        let newNumber = this.rand();
                        let existe = false;
                        for (let j = 0; j < indices.length; j++)
                            if (indices[j] === newNumber)
                            {
                                existe = true;
                                break;
                            }
                        if (!existe)
                            indices.push(newNumber);
                        else 
                            i--;
                    }
                    this.calcularHorarios(indices);
                }
                else 
                    this.$swal('Debe seleccionar al menos una unidad.', 'Aviso', 'warning');
            }
        }
    }
}
</script>
