'use strict';

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
    constructor({ sessionDir, chromePath, webVersion }) {
        this.sessionDir = sessionDir;
        this.chromePath = chromePath || undefined;
        this.webVersion = webVersion || undefined;

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
            puppeteer: {
                headless: true,
                executablePath: this.chromePath,
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

        const client = new Client(opciones);

        client.on('qr', (qr) => {
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
            this._programarReconexion();
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
