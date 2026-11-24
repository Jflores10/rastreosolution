'use strict';

const fs = require('fs');
const path = require('path');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcodeTerminal = require('qrcode-terminal');
const log = require('./logger');

/**
 * Administra el cliente de whatsapp-web.js:
 *  - sesión persistente con LocalAuth (el QR se escanea una sola vez),
 *  - último QR disponible para el endpoint GET /qr,
 *  - reconexión automática con espera creciente ante "disconnected" o fallos al iniciar.
 */
class WhatsAppManager {
    constructor({ sessionDir, chromePath, webVersion, timeoutMs, carpetaLogs, numeroVinculacion }) {
        // Si se define, se vincula con código de 8 caracteres en lugar de QR
        this.numeroVinculacion = numeroVinculacion || undefined;
        this.ultimoCodigo = null;
        this.sessionDir = sessionDir;
        this.chromePath = chromePath || undefined;
        this.webVersion = webVersion || undefined;
        // Tiempo máximo para que cargue WhatsApp Web (servidores lentos necesitan más de 30 s)
        this.timeoutMs = timeoutMs > 0 ? timeoutMs : 120000;
        this.carpetaLogs = carpetaLogs;

        this.client = null;
        this.listo = false;
        this.ultimoQr = null;
        this.intentos = 0;
        this.reconectando = false;
        this.detenido = false;
    }

    /** true cuando el cliente está conectado y puede enviar mensajes. */
    get conectado() {
        return this.listo && this.client !== null;
    }

    _crearCliente() {
        const opciones = {
            authStrategy: new LocalAuth({ dataPath: this.sessionDir }),
            authTimeoutMs: this.timeoutMs,
            puppeteer: {
                headless: true,
                executablePath: this.chromePath,
                timeout: this.timeoutMs,
                protocolTimeout: this.timeoutMs,
                // Argumentos necesarios para Chrome en servidores Linux / Docker
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--no-first-run',
                    '--no-zygote',
                ],
            },
        };

        // Fijar una versión concreta de WhatsApp Web (útil si una actualización rompe la librería)
        if (this.webVersion) {
            opciones.webVersion = this.webVersion;
            opciones.webVersionCache = {
                type: 'remote',
                remotePath: `https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/${this.webVersion}.html`,
            };
        }

        // Vinculación por número: WhatsApp > Dispositivos vinculados > Vincular un dispositivo >
        // "Vincular con el número de teléfono" y escribir el código que sale en el log.
        if (this.numeroVinculacion) {
            opciones.pairWithPhoneNumber = {
                phoneNumber: this.numeroVinculacion,
                showNotification: true,
                intervalMs: 180000,
            };
        }

        const client = new Client(opciones);

        client.on('code', (codigo) => {
            this.ultimoCodigo = codigo;
            log.info(`CÓDIGO DE VINCULACIÓN: ${codigo}  -> En el teléfono: WhatsApp > Dispositivos vinculados > ` +
                'Vincular un dispositivo > "Vincular con el número de teléfono" y escriba este código.');
        });

        client.on('qr', (qr) => {
            if (this.numeroVinculacion) return; // con vinculación por número no se usa el QR
            this.ultimoQr = qr;
            log.info('QR recibido. Escanéalo desde WhatsApp > Dispositivos vinculados (o abre GET /qr).');
            qrcodeTerminal.generate(qr, { small: true });
        });

        client.on('authenticated', () => {
            log.info('Sesión autenticada.');
        });

        client.on('auth_failure', (mensaje) => {
            this.listo = false;
            log.error('Fallo de autenticación. Será necesario escanear el QR de nuevo.', { mensaje });
        });

        client.on('ready', () => {
            this.listo = true;
            this.ultimoQr = null;
            this.ultimoCodigo = null;
            this.intentos = 0;
            log.info('WhatsApp listo para enviar mensajes.', { numero: client.info && client.info.wid ? client.info.wid.user : null });
        });

        client.on('change_state', (estado) => {
            log.info('Cambio de estado de WhatsApp.', { estado });
        });

        client.on('disconnected', (motivo) => {
            this.listo = false;
            log.error('WhatsApp desconectado.', { motivo });
            this._programarReconexion();
        });

        return client;
    }

    /** Inicia (o reinicia) el cliente. */
    async iniciar() {
        if (this.detenido) return;
        this.client = this._crearCliente();
        try {
            log.info('Iniciando cliente de WhatsApp...');
            await this.client.initialize();
        } catch (err) {
            this.listo = false;
            log.error('No se pudo iniciar el cliente de WhatsApp.', { error: err.message });
            await this._capturarPantalla();
            this._programarReconexion();
        }
    }

    /** Guarda una captura de lo que muestra WhatsApp Web, para diagnosticar fallos al iniciar. */
    async _capturarPantalla() {
        const pagina = this.client && this.client.pupPage;
        if (!pagina || !this.carpetaLogs) return;
        try {
            fs.mkdirSync(this.carpetaLogs, { recursive: true });
            const archivo = path.join(this.carpetaLogs, 'error-inicio.png');
            await pagina.screenshot({ path: archivo, fullPage: true });
            log.info(`Captura de WhatsApp Web guardada en ${archivo}`, { url: pagina.url() });
        } catch (e) {
            log.error('No se pudo guardar la captura de pantalla.', { error: e.message });
        }
    }

    /**
     * Destruye el cliente actual y vuelve a iniciarlo tras una espera creciente
     * (5 s, 10 s, 20 s... hasta 5 minutos).
     */
    _programarReconexion() {
        if (this.reconectando || this.detenido) return;
        this.reconectando = true;
        this.intentos += 1;
        const espera = Math.min(5000 * Math.pow(2, this.intentos - 1), 5 * 60 * 1000);
        log.info(`Reconexión programada en ${Math.round(espera / 1000)} s (intento ${this.intentos}).`);

        setTimeout(async () => {
            const anterior = this.client;
            this.client = null;
            if (anterior) {
                try {
                    await anterior.destroy();
                } catch (err) {
                    log.error('Error al destruir el cliente anterior.', { error: err.message });
                }
            }
            this.reconectando = false;
            await this.iniciar();
        }, espera);
    }

    /**
     * Devuelve el id de WhatsApp ("593...@c.us") o null si el número no tiene WhatsApp.
     */
    async obtenerIdNumero(numero) {
        const id = await this.client.getNumberId(numero);
        return id ? id._serialized : null;
    }

    /** Envía un MessageMedia con texto opcional como pie de foto. Devuelve el id del mensaje. */
    async enviarMedia(chatId, media, texto) {
        const opciones = texto ? { caption: texto } : {};
        const mensaje = await this.client.sendMessage(chatId, media, opciones);
        return mensaje && mensaje.id ? mensaje.id._serialized : null;
    }

    /** Cierra el navegador al apagar el proceso (sin cerrar la sesión de WhatsApp). */
    async detener() {
        this.detenido = true;
        if (this.client) {
            try {
                await this.client.destroy();
            } catch (err) {
                log.error('Error al cerrar el cliente.', { error: err.message });
            }
        }
    }
}

module.exports = { WhatsAppManager };
