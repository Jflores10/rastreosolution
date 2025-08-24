let app = document.getElementById('app');
if (app != null) {
    require('./bootstrap');
    Vue.component('sorteos', require('./components/Sorteos.vue'));
    Vue.component('trSorteo', require('./components/TrSorteo.vue'));
    Vue.component('fechasSorteos', require('./components/FechasSorteos.vue'));
    const app = new Vue({
        el: '#app'
    });
}