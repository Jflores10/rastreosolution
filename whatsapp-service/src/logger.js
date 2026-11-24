'use strict';

/**
 * Logger mínimo: imprime una línea por evento con fecha ISO.
 * PM2 / Docker se encargan de guardar stdout/stderr en archivos.
 */
function fecha() {
    return new Date().toISOString();
}

function info(mensaje, datos) {
    console.log(`[${fecha()}] INFO  ${mensaje}${datos ? ' ' + JSON.stringify(datos) : ''}`);
}

function error(mensaje, datos) {
    console.error(`[${fecha()}] ERROR ${mensaje}${datos ? ' ' + JSON.stringify(datos) : ''}`);
}

/**
 * Registra el resultado de un envío: fecha, número, resultado e id del mensaje.
 */
function envio({ numero, ok, id, error: err, origen }) {
    const linea = `[${fecha()}] ENVIO numero=${numero} origen=${origen} resultado=${ok ? 'OK' : 'ERROR'}` +
        (id ? ` id=${id}` : '') + (err ? ` error="${err}"` : '');
    if (ok) console.log(linea);
    else console.error(linea);
}

module.exports = { info, error, envio };
