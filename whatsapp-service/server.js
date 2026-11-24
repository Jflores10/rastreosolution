'use strict';

/**
 * Microservicio de envío de imágenes por WhatsApp usando whatsapp-web.js.
 *
 * Endpoints (todos requieren el header x-api-key, salvo GET /qr que también acepta ?key=):
 *   GET  /estado          -> { conectado }
 *   GET  /qr              -> imagen PNG del QR para vincular (204 si ya está conectado)
 *   POST /enviar-url      -> JSON { numero, url, texto? }
 *   POST /enviar-archivo  -> multipart/form-data { numero, texto?, imagen }
 *   POST /enviar-base64   -> JSON { numero, base64, mimetype, texto? }
 *
 * Respuestas JSON: { ok, error?, id? }
 */

require('dotenv').config();

const crypto = require('crypto');
const path = require('path');
const express = require('express');
const multer = require('multer');
const QRCode = require('qrcode');
const { MessageMedia } = require('whatsapp-web.js');

const log = require('./src/logger');
const { normalizarNumero } = require('./src/numeros');
const { ColaEnvios } = require('./src/cola');
const { WhatsAppManager } = require('./src/whatsapp');

// ---------------------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------------------
const PORT = parseInt(process.env.PORT || '3100', 10);
const HOST = process.env.HOST || '127.0.0.1';
const API_KEY = process.env.API_KEY || '';
const CODIGO_PAIS = process.env.CODIGO_PAIS || '593';
const SESSION_DIR = path.resolve(process.env.SESSION_DIR || './.wwebjs_auth');
const MAX_BYTES = 15 * 1024 * 1024; // 15 MB
const MIMES_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];
const EXTENSIONES = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' };

if (!API_KEY || API_KEY.length < 16) {
    log.error('API_KEY no está definida en .env o es demasiado corta (mínimo 16 caracteres).');
    process.exit(1);
}

const whatsapp = new WhatsAppManager({
    sessionDir: SESSION_DIR,
    chromePath: process.env.CHROME_PATH,
    webVersion: process.env.WWEBJS_WEB_VERSION,
    timeoutMs: parseInt(process.env.TIMEOUT_INICIO_MS || '120000', 10),
    carpetaLogs: path.resolve('./logs'),
});

const cola = new ColaEnvios({
    pausaMinMs: parseInt(process.env.PAUSA_MIN_MS || '3000', 10),
    pausaMaxMs: parseInt(process.env.PAUSA_MAX_MS || '8000', 10),
    maximo: parseInt(process.env.MAX_COLA || '100', 10),
});

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

/** Error con código HTTP asociado, para responder de forma consistente. */
class ErrorHttp extends Error {
    constructor(status, mensaje) {
        super(mensaje);
        this.status = status;
    }
}

/** Compara la clave en tiempo constante para no filtrar información por tiempos de respuesta. */
function claveValida(clave) {
    if (typeof clave !== 'string') return false;
    const a = crypto.createHash('sha256').update(clave).digest();
    const b = crypto.createHash('sha256').update(API_KEY).digest();
    return crypto.timingSafeEqual(a, b);
}

function responderError(res, status, mensaje) {
    return res.status(status).json({ ok: false, error: mensaje });
}

/** Valida el MIME (solo jpg/png/webp) y el tamaño de los datos en base64. */
function validarImagen(mimetype, base64) {
    const mime = String(mimetype || '').toLowerCase().split(';')[0].trim();
    if (!MIMES_PERMITIDOS.includes(mime))
        throw new ErrorHttp(415, 'Tipo de imagen no permitido. Solo jpg, png o webp.');
    if (!base64)
        throw new ErrorHttp(400, 'La imagen está vacía.');
    const bytes = Buffer.byteLength(base64, 'base64');
    if (bytes > MAX_BYTES)
        throw new ErrorHttp(413, 'La imagen supera el máximo de 15 MB.');
    return mime;
}

/**
 * Flujo común de envío:
 *  1) WhatsApp listo (503), 2) número válido (400), 3) el número tiene WhatsApp (404),
 *  4) cola con espacio (429), 5) envío en cola con pausa aleatoria entre mensajes.
 */
async function enviarImagen({ numero, media, texto, origen }) {
    if (!whatsapp.conectado)
        throw new ErrorHttp(503, 'WhatsApp no está listo. Revise GET /estado o vincule el QR en GET /qr.');

    const normalizado = normalizarNumero(numero, CODIGO_PAIS);
    if (!normalizado)
        throw new ErrorHttp(400, 'Número inválido.');

    const chatId = await whatsapp.obtenerIdNumero(normalizado);
    if (!chatId) {
        log.envio({ numero: normalizado, ok: false, error: 'El número no tiene WhatsApp', origen });
        throw new ErrorHttp(404, 'El número no tiene WhatsApp.');
    }

    if (cola.llena)
        throw new ErrorHttp(429, 'Demasiados envíos en cola. Intente más tarde.');

    try {
        const id = await cola.agregar(async () => {
            // El cliente pudo desconectarse mientras el trabajo esperaba en la cola
            if (!whatsapp.conectado)
                throw new ErrorHttp(503, 'WhatsApp se desconectó antes de enviar el mensaje.');
            return whatsapp.enviarMedia(chatId, media, texto ? String(texto).slice(0, 1024) : '');
        });
        log.envio({ numero: normalizado, ok: true, id, origen });
        return id;
    } catch (err) {
        log.envio({ numero: normalizado, ok: false, error: err.message, origen });
        throw err;
    }
}

/** Envuelve un handler async: cualquier error se convierte en { ok:false, error }. */
function manejar(handler) {
    return async (req, res) => {
        try {
            await handler(req, res);
        } catch (err) {
            const status = err instanceof ErrorHttp ? err.status : 500;
            if (status === 500) log.error('Error inesperado.', { ruta: req.path, error: err.message });
            responderError(res, status, status === 500 ? 'Error interno al enviar el mensaje.' : err.message);
        }
    };
}

// ---------------------------------------------------------------------------
// Aplicación Express
// ---------------------------------------------------------------------------
const app = express();
app.disable('x-powered-by');

// Base64 de 15 MB ocupa ~20 MB en texto
app.use(express.json({ limit: '21mb' }));

// Autenticación con x-api-key (GET /qr acepta también ?key= para abrirlo desde el navegador)
app.use((req, res, next) => {
    const clave = req.get('x-api-key') || (req.method === 'GET' && req.path === '/qr' ? req.query.key : undefined);
    if (!claveValida(clave)) return responderError(res, 401, 'No autorizado.');
    next();
});

// Subida de archivos en memoria, máx 15 MB, solo jpg/png/webp
const subida = multer({
    storage: multer.memoryStorage(),
    limits: { fileSize: MAX_BYTES, files: 1 },
    fileFilter: (req, file, cb) => {
        if (MIMES_PERMITIDOS.includes(file.mimetype)) cb(null, true);
        else cb(new ErrorHttp(415, 'Tipo de imagen no permitido. Solo jpg, png o webp.'));
    },
});

app.get('/estado', (req, res) => {
    res.json({ ok: true, conectado: whatsapp.conectado, enCola: cola.tamano });
});

app.get('/qr', manejar(async (req, res) => {
    if (whatsapp.conectado) return res.status(204).end();
    if (!whatsapp.ultimoQr)
        return responderError(res, 404, 'Aún no hay un QR disponible. Espere unos segundos y recargue.');
    const png = await QRCode.toBuffer(whatsapp.ultimoQr, { type: 'png', width: 400, margin: 2 });
    res.set('Cache-Control', 'no-store');
    res.type('png').send(png);
}));

app.post('/enviar-url', manejar(async (req, res) => {
    const { numero, url, texto } = req.body || {};
    if (!numero || !url) throw new ErrorHttp(400, 'Los campos numero y url son obligatorios.');

    let destino;
    try {
        destino = new URL(url);
    } catch (e) {
        throw new ErrorHttp(400, 'URL inválida.');
    }
    if (!['http:', 'https:'].includes(destino.protocol))
        throw new ErrorHttp(400, 'Solo se permiten URL http o https.');

    let media;
    try {
        media = await MessageMedia.fromUrl(destino.href, { unsafeMime: true });
    } catch (err) {
        throw new ErrorHttp(400, 'No se pudo descargar la imagen desde la URL.');
    }
    const mime = validarImagen(media.mimetype, media.data);
    media.mimetype = mime;
    if (!media.filename) media.filename = `imagen.${EXTENSIONES[mime]}`;

    const id = await enviarImagen({ numero, media, texto, origen: 'url' });
    res.json({ ok: true, id });
}));

app.post('/enviar-archivo', (req, res, next) => {
    // Los errores de multer (tamaño, tipo) se responden en JSON
    subida.single('imagen')(req, res, (err) => {
        if (!err) return next();
        if (err instanceof multer.MulterError && err.code === 'LIMIT_FILE_SIZE')
            return responderError(res, 413, 'La imagen supera el máximo de 15 MB.');
        if (err instanceof ErrorHttp) return responderError(res, err.status, err.message);
        return responderError(res, 400, 'Archivo inválido.');
    });
}, manejar(async (req, res) => {
    const { numero, texto } = req.body || {};
    if (!numero) throw new ErrorHttp(400, 'El campo numero es obligatorio.');
    if (!req.file) throw new ErrorHttp(400, 'El campo imagen es obligatorio.');

    const base64 = req.file.buffer.toString('base64');
    const mime = validarImagen(req.file.mimetype, base64);
    const media = new MessageMedia(mime, base64, req.file.originalname || `imagen.${EXTENSIONES[mime]}`);

    const id = await enviarImagen({ numero, media, texto, origen: 'archivo' });
    res.json({ ok: true, id });
}));

app.post('/enviar-base64', manejar(async (req, res) => {
    let { numero, base64, mimetype, texto } = req.body || {};
    if (!numero || !base64) throw new ErrorHttp(400, 'Los campos numero y base64 son obligatorios.');

    // Aceptar también "data:image/png;base64,...."
    const dataUrl = /^data:([^;]+);base64,(.*)$/s.exec(String(base64));
    if (dataUrl) {
        mimetype = mimetype || dataUrl[1];
        base64 = dataUrl[2];
    }
    base64 = String(base64).replace(/\s/g, '');
    if (!/^[A-Za-z0-9+/]+={0,2}$/.test(base64)) throw new ErrorHttp(400, 'base64 inválido.');

    const mime = validarImagen(mimetype, base64);
    const media = new MessageMedia(mime, base64, `imagen.${EXTENSIONES[mime]}`);

    const id = await enviarImagen({ numero, media, texto, origen: 'base64' });
    res.json({ ok: true, id });
}));

app.use((req, res) => responderError(res, 404, 'Ruta no encontrada.'));

// JSON mal formado u otros errores de Express
app.use((err, req, res, next) => {
    if (err && err.type === 'entity.too.large') return responderError(res, 413, 'La petición es demasiado grande.');
    if (err && err.type === 'entity.parse.failed') return responderError(res, 400, 'JSON inválido.');
    log.error('Error no controlado.', { error: err && err.message });
    responderError(res, 500, 'Error interno.');
});

// ---------------------------------------------------------------------------
// Arranque y apagado
// ---------------------------------------------------------------------------
const servidor = app.listen(PORT, HOST, () => {
    log.info(`Microservicio WhatsApp escuchando en http://${HOST}:${PORT}`);
});

whatsapp.iniciar();

async function apagar(senal) {
    log.info(`Señal ${senal} recibida. Cerrando...`);
    servidor.close();
    await whatsapp.detener();
    process.exit(0);
}
process.on('SIGINT', () => apagar('SIGINT'));
process.on('SIGTERM', () => apagar('SIGTERM'));

process.on('unhandledRejection', (err) => {
    log.error('Promesa rechazada sin manejar.', { error: err && err.message });
});
