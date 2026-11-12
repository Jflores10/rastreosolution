{{-- Direcciones de los reportes: /geocoding/reverse (geocache primero, LocationIQ si no está).
     Cola con máximo 2 peticiones a la vez y sin repetir el mismo punto. --}}
<script>
var reporteDireccion = (function () {
    var url = "{{ url('/geocoding/reverse') }}";
    var maxSimultaneas = 2;
    var activas = 0;
    var cola = [];
    var esperando = {};

    function bombear() {
        while (activas < maxSimultaneas && cola.length) {
            ejecutar(cola.shift());
        }
    }

    function terminar(clave, texto) {
        var callbacks = esperando[clave] || [];
        delete esperando[clave];
        for (var i = 0; i < callbacks.length; i++) {
            try { callbacks[i](texto); } catch (e) {}
        }
    }

    function ejecutar(p) {
        activas++;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url + '?lat=' + encodeURIComponent(p.lat) + '&lon=' + encodeURIComponent(p.lng), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 15000;
        xhr.onloadend = function () {
            var texto = null;
            if (xhr.status === 200) {
                try {
                    var obj = JSON.parse(xhr.responseText);
                    texto = obj.error ? null : obj.display_name;
                } catch (e) {}
            }
            activas--;
            terminar(p.clave, texto);
            bombear();
        };
        xhr.send(null);
    }

    /** callback(texto) — texto es null si no se pudo obtener la dirección. */
    return function (lat, lng, callback) {
        lat = String(lat).replace(/'/g, '');
        lng = String(lng).replace(/'/g, '');
        if (lat === '' || lng === '' || isNaN(lat) || isNaN(lng) || (Number(lat) === 0 && Number(lng) === 0)) {
            callback(null);
            return;
        }
        var clave = lat + ',' + lng;
        if (esperando[clave]) {
            esperando[clave].push(callback);
            return;
        }
        esperando[clave] = [callback];
        cola.push({ clave: clave, lat: lat, lng: lng });
        bombear();
    };
})();

function selectUnidad_GEOCODE(latitud, longitud, id) {
    var la = String(latitud).replace(/'/g, '');
    var lo = String(longitud).replace(/'/g, '');
    if (la === '' || lo === '' || Number(la) === 0 || Number(lo) === 0) {
        return; // sin posición: la celda queda vacía, como antes
    }
    reporteDireccion(latitud, longitud, function (texto) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = texto || 'Error al consultar ubicación';
        }
    });
}
</script>
