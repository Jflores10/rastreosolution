'use strict';

/**
 * Cola de envíos en serie, sin dependencias.
 * Ejecuta un trabajo a la vez y, después de cada uno, espera una pausa aleatoria
 * (por defecto 3 a 8 segundos) antes del siguiente, para no enviar en ráfaga.
 */
class ColaEnvios {
    constructor({ pausaMinMs = 3000, pausaMaxMs = 8000, maximo = 100 } = {}) {
        this.pausaMinMs = pausaMinMs;
        this.pausaMaxMs = Math.max(pausaMaxMs, pausaMinMs);
        this.maximo = maximo;
        this.trabajos = [];
        this.procesando = false;
    }

    /** Cantidad de trabajos pendientes (incluido el que se está ejecutando). */
    get tamano() {
        return this.trabajos.length + (this.procesando ? 1 : 0);
    }

    /** true si la cola ya no admite más trabajos. */
    get llena() {
        return this.tamano >= this.maximo;
    }

    /**
     * Agrega un trabajo (función async) y devuelve una promesa con su resultado.
     */
    agregar(trabajo) {
        return new Promise((resolve, reject) => {
            this.trabajos.push({ trabajo, resolve, reject });
            this._procesar();
        });
    }

    _pausaAleatoria() {
        const ms = this.pausaMinMs + Math.floor(Math.random() * (this.pausaMaxMs - this.pausaMinMs + 1));
        return new Promise((r) => setTimeout(r, ms));
    }

    async _procesar() {
        if (this.procesando) return;
        this.procesando = true;
        try {
            while (this.trabajos.length > 0) {
                const { trabajo, resolve, reject } = this.trabajos.shift();
                try {
                    resolve(await trabajo());
                } catch (err) {
                    reject(err);
                }
                // Pausa entre mensajes (también tras un error, para no reintentar en ráfaga)
                await this._pausaAleatoria();
            }
        } finally {
            this.procesando = false;
        }
    }
}

module.exports = { ColaEnvios };
