#!/usr/bin/env node
'use strict';

/**
 * trackingsystem.js (OPTIMIZADO para SSE rápido y fluido)
 *
 * ✅ Cambios clave:
 * 1) Redis PUB/SUB (gps-channel) inmediato tras actualizar unidad (GTFRI) — sin XADD/stream (evita RDB gigante)
 * 2) Trabajo pesado (emails/consultas extra/inserts secundarios) se manda a setImmediate()
 * 3) Throttle inteligente por unidad: solo limita si NO hay movimiento (lat/lng iguales)
 * 4) Inserts livianos (writeConcern w:0) para recorridos/tramas (reduce bloqueo)
 * 5) Buffer de TRAMAS para insertar por lotes (mejora brutal si llegan muchas)
 *
 * Nota: mantengo tu lógica original lo más intacta posible, solo reorganizada.
 */

const net = require('net');
const moment = require('moment');
const nodemailer = require('nodemailer');
const schedule = require('node-schedule');
const fs = require('fs');
const { execSync } = require('child_process');
const ObjectID = require('mongodb').ObjectID;
const { ObjectId } = require('mongodb');
const MongoClient = require('mongodb').MongoClient;

const WebSocket = require('ws');

// Redis publisher
const redis = require('redis');
const redisPub = redis.createClient();
redisPub.on('error', err => console.error('Redis PUB Error (parser):', err));

const http = require('http');
const https = require('https');
const url = require('url');
const path = require('path');
const dotenv = require('dotenv');

// Load env vars from Laravel app first, then fallback to parser .env if present.
dotenv.config({ path: path.resolve(__dirname, '../trackingsystemwebapp/.env') });
dotenv.config();

// ===================== CONFIG =====================
const PORT = 8085;
const connection = 'mongodb://trackingsystem:uVC7x254i1VJ@127.0.0.1:27017/dbtrackingsystem?authSource=admin';

const GTHBD = 'GTHBD';
const GTDAT = 'GTDAT';
const GTFRI = 'GTFRI';
const GTGEO = 'GTGEO';
const GPRMC = 'GPRMC';
const GTDIS = 'GTDIS';
const GTMPN = 'GTMPN';
const GTMPF = 'GTMPF';
const GTIGF = 'GTIGF';
const GTIGN = 'GTIGN';
const GTDTT = 'GTDTT';
const GTDTTDGT = 'DGT';
const ADMIN = 'ADMIN';
const CMD_VIGILANTE_AT_GTOUT = 'AT+GTOUT=gv300,1,,,0,0,0,0,0,0,0,,0,0,,,,FFFF$';
const GTLOG = 'GTLOG';
const GTGOT = 'GTGOT';
const GTGIN = 'GTGIN';
const GTRTL = 'GTRTL';
const GTSPD = 'GTSPD';
const GTPHD = 'GTPHD';
const GTPHL = 'GTPHL';


const ACK = 'ACK';
const GTALC = 'GTALC';
const BUFF = 'BUFF';


const ATM = '*ATM*';
const DATE_FORMAT = 'YYYY-MM-DD HH:mm:ss';
const DEVICE_DATE_FORMAT = 'YYYYMMDDHHmmss';
const GPRMC_DATE_FORMAT = 'DDMMYYHHmmss';

const restartHour = 4, restartMinute = 0, restartSecond = 0, restartMilisecond = 0;
const correoinfinty = 'management.infinity.fleets@gmail.com';
const debug = false;
const PUSH_DEBUG = String(process.env.PUSH_DEBUG || '1').trim() !== '0';

function maskSecret(value) {
  const v = String(value || '');
  if (!v) return '(vacio)';
  if (v.length <= 6) return '[len=' + v.length + ']';
  return v.substring(0, 3) + '***' + v.substring(v.length - 2);
}

function pushDebugLog() {
  if (!PUSH_DEBUG) return;
  const args = Array.prototype.slice.call(arguments);
  console.log.apply(console, ['[push-debug]'].concat(args));
}
/** URL completa del endpoint Laravel, ej: http://127.0.0.1/api/internal/push-by-unidad */
const LARAVEL_PUSH_URL = (process.env.LARAVEL_PUSH_URL || '').trim();
/** Mismo valor que PARSER_PUSH_SECRET en .env de Laravel */
const LARAVEL_PUSH_SECRET = (process.env.LARAVEL_PUSH_SECRET || '').trim();
/** Finalizar despacho al ingresar al último punto: http://127.0.0.1/api/internal/finish-despacho */
const LARAVEL_FINISH_DESPACHO_URL = (process.env.LARAVEL_FINISH_DESPACHO_URL || (
  LARAVEL_PUSH_URL
    ? LARAVEL_PUSH_URL.replace(/push-by-unidad\/?$/, 'finish-despacho')
    : ''
)).trim();

/** Evita llamar end() dos veces para el mismo despacho en paralelo */
const finishingDespachoIds = new Set();

/**
 * Deben coincidir con `code` en notification_types (activos):
 */
const PUSH_TYPE_GEOFENCE =  'geofence';
const PUSH_TYPE_ONOFF =  'onoff';
const PUSH_TYPE_IGN = 'ign';
const PUSH_TYPE_OVERSPEED = 'overspeed';
const PUSH_TYPE_BPANICO = 'bpanico';

const PUSH_NOTIFICATION_TIMEZONE = (process.env.PUSH_NOTIFICATION_TIMEZONE || 'America/Guayaquil').trim();
const PUSH_GPS_DATETIME_AS_UTC = String(process.env.PUSH_GPS_DATETIME_AS_UTC || '1').trim() === '1';
const PUSH_GPS_HOUR_OFFSET = Number(process.env.PUSH_GPS_HOUR_OFFSET || -5);

// ===================== GLOBALS =====================
let dbTrackingSystem = null;

const socketArray = [];
let logsAdmin = [];

// WS client (si lo quieres usar como fallback; en tu flujo actual publicas Redis)
let wsClient = null;
let wsReconnectTimeout = 3000;
const unidadStateCache = new Map();

function buildUnidadPayloadRealtime(base, extra = {}) {
  const key = String(base.imei || base._id || '').trim();
  const prev = unidadStateCache.get(key) || {};

  // Merge: lo nuevo pisa lo viejo, pero NO borra campos
  const payload = Object.assign({}, prev, base, extra);

  // Do NOT persist raw TCP message into the cache. If callers passed
  // `_raw_message` it should be used only for the immediate publish
  // decision in enviarALaravelPorWS, but must not survive in the
  // cached state (otherwise a single BUFF would block future sends).
  const cacheCopy = Object.assign({}, payload);
  try { delete cacheCopy._raw_message; } catch (e) { }
  unidadStateCache.set(key, cacheCopy);

  return payload;
}

/**
 * Publica tracking por Redis como GTFRI (`type: 'unidad.updated'`).
 * No sustituye otros envíos con type propio (ignición, power, puerta, etc.).
 */
function enviarUnidadUpdatedEstiloGTFRI(partial, rawMessage) {
  try {
    const msg = rawMessage != null ? String(rawMessage) : '';
    const isBuffMessage = msg.includes(BUFF);
    const isRespMessage = !isBuffMessage;
    const payload = buildUnidadPayloadRealtime(Object.assign(
      { type: 'unidad.updated', _raw_message: rawMessage, fecha: new Date() },
      partial
    ));
    enviarALaravelPorWS(payload, { force: isRespMessage, skipThrottleUpdate: isRespMessage });
  } catch (e) {
    if (debug) console.error('enviarUnidadUpdatedEstiloGTFRI:', e);
  }
}

function connectWebSocketClient() {
  try {
    wsClient = new WebSocket("ws://127.0.0.1:6001");

    wsClient.on("open", () => console.log("Conectado a WebSocket local"));
    wsClient.on("close", (code, reason) => {
      console.warn(`WS local cerrado. Reconectando en ${wsReconnectTimeout}ms (code=${code})`);
      setTimeout(connectWebSocketClient, wsReconnectTimeout);
    });
    wsClient.on("error", (err) => console.error('WS local error:', err && err.message ? err.message : err));
  } catch (e) {
    console.error('Error creando WS client:', e);
    setTimeout(connectWebSocketClient, wsReconnectTimeout);
  }
}
connectWebSocketClient();

// ===================== THROTTLE (INTELIGENTE) =====================
// key -> { ts, lat, lng }
const lastSentByUnit = new Map();
const MIN_SEND_MS = 2000; // recomendado 1500-3000. Tú pediste 2000.

// ===================== TRAMAS BUFFER (BATCH INSERT) =====================
const TRAMAS_BUFFER = [];
const TRAMAS_FLUSH_MS = 250;  // cada 250ms
const TRAMAS_BATCH_MAX = 300; // o por tamaño
const PHOTO_EVENTS_DIR = path.resolve(__dirname, '../trackingsystemwebapp/storage/app/images');
const PHOTO_PARTIALS_MAX_AGE_MS = 60000;
const PHOTO_PARTIAL_IDLE_MIN_MS = 20000;
const PHOTO_FRAME_GAP_ESTIMATE_MS = 4000;
/** Escalado por totalFrames del dispositivo (20, 120, 200, etc.). */
const PHOTO_ASSEMBLY_MS_PER_FRAME = 6000;
const PHOTO_PARTIAL_IDLE_MS_PER_FRAME = 400;
/** Tope de espera en silencio antes de construir parcial (aunque falten frames, p.ej. 190/200). */
const PHOTO_PARTIAL_IDLE_MAX_MS = 90000;
const PHOTO_LOCATION_CACHE_MAX_AGE_MS = 120000 + (300 * PHOTO_ASSEMBLY_MS_PER_FRAME);
const photoAssemblies = new Map();
const lastPhotoLocationByImei = new Map();
const MAX_ACTIVE_PHOTO_ASSEMBLIES = 100;

function gtpHdTotalFrames(st) {
  return Math.max(1, toInteger(st && st.totalFrames) || 1);
}

function gtpHdPartialIdleMs(st) {
  const total = gtpHdTotalFrames(st);
  const avgGap = Math.max(PHOTO_FRAME_GAP_ESTIMATE_MS, toInteger(st && st.avgFrameGapMs) || PHOTO_FRAME_GAP_ESTIMATE_MS);
  // 200 frames no deben exigir minutos de silencio: cap en PHOTO_PARTIAL_IDLE_MAX_MS
  const scaled = PHOTO_PARTIAL_IDLE_MIN_MS + Math.min(total, 100) * PHOTO_PARTIAL_IDLE_MS_PER_FRAME;
  return Math.min(
    PHOTO_PARTIAL_IDLE_MAX_MS,
    Math.max(PHOTO_PARTIAL_IDLE_MIN_MS, Math.round(avgGap * 5), scaled)
  );
}

function gtpHdMsSinceLastFrame(st) {
  return Date.now() - (st.lastFrameAt || st.updatedAt || Date.now());
}

/**
 * Regla de ensamblaje GTPHD:
 * 1) Si están TODOS los frames (1..N) → construir imagen COMPLETA de inmediato.
 * 2) Si faltan frames → construir imagen PARCIAL solo tras silencio real
 *    (ya no llegan más tramas). Nunca parcial mientras sigan llegando frames.
 */
function gtpHdReadyToPersistPartial(st, opts) {
  opts = opts || {};
  if (!st || st.frames.size === 0) return false;
  // Completa siempre tiene prioridad; el caller debe persistir completa, no parcial.
  if (gtpHdFramesComplete(st)) return false;
  if (!opts.forcePartial) return false;

  const silentMs = gtpHdMsSinceLastFrame(st);
  const baseIdle = gtpHdPartialIdleMs(st);
  const total = gtpHdTotalFrames(st);
  const maxFrame = st.maxFrameReceived || 0;
  const missing = gtpHdMissingFrameIndices(st).length;
  const avgGap = Math.max(PHOTO_FRAME_GAP_ESTIMATE_MS, toInteger(st && st.avgFrameGapMs) || PHOTO_FRAME_GAP_ESTIMATE_MS);

  // Aún llegan frames (o acaban de llegar): no construir parcial.
  if (silentMs < baseIdle) return false;

  // Si ya llegó el frame N pero faltan huecos, esperar más: pueden ir retrasados.
  // Especialmente crítico cuando falta 1–3 (caso 46/47).
  if (maxFrame >= total) {
    const extraWait = missing <= 3
      ? Math.max(avgGap * 8, 30000)
      : Math.max(avgGap * 3, 10000);
    return silentMs >= (baseIdle + extraWait);
  }

  // Todavía no llega el último índice: esperar cola, luego parcial.
  const tailMissing = Math.max(0, total - maxFrame);
  const tailWait = Math.min(tailMissing, 15) * avgGap * 2;
  return silentMs >= Math.min(baseIdle + tailWait, PHOTO_PARTIAL_IDLE_MAX_MS + 60000);
}

function gtpHdMaxAssemblyAgeMs(st) {
  const total = gtpHdTotalFrames(st);
  return Math.max(PHOTO_PARTIALS_MAX_AGE_MS, 120000 + (total * PHOTO_ASSEMBLY_MS_PER_FRAME));
}

function gtpHdFramesComplete(st) {
  if (!st || !st.totalFrames || st.totalFrames <= 0) return false;
  if (st.frames.size < st.totalFrames) return false;
  for (let i = 1; i <= st.totalFrames; i++) {
    if (!st.frames.has(i)) return false;
  }
  return true;
}

function gtpHdTrackFrameArrival(st, currentFrameValue, meta) {
  const now = Date.now();
  if (st.lastFrameAt) {
    const gap = now - st.lastFrameAt;
    if (gap > 0 && gap < 120000) {
      st.avgFrameGapMs = st.avgFrameGapMs
        ? Math.round(st.avgFrameGapMs * 0.65 + gap * 0.35)
        : gap;
    }
  }
  st.lastFrameAt = now;
  st.updatedAt = now;
  st.maxFrameReceived = Math.max(st.maxFrameReceived || 0, toInteger(currentFrameValue));

  // Historial de llegada (para campo log en photos_unidad)
  if (!st.arrivalLog) st.arrivalLog = [];
  const m = meta || {};
  st.arrivalLog.push({
    seq: st.arrivalLog.length + 1,
    frame: toInteger(currentFrameValue),
    len: toInteger(m.len) || (st.frames.has(currentFrameValue) ? String(st.frames.get(currentFrameValue) || '').length : 0),
    declaredLen: toInteger(m.declaredLen) || null,
    duplicado: !!m.duplicado,
    at: new Date(now).toISOString()
  });
}

/** Arma el log ordenado 1..N para inspeccionar qué frames llegaron al ensamblador. */
function gtpHdBuildPhotoLog(st) {
  const total = gtpHdTotalFrames(st);
  const porNumero = [];
  for (let i = 1; i <= total; i++) {
    if (st.frames.has(i)) {
      const payload = String(st.frames.get(i) || '');
      porNumero.push({
        n: i,
        ok: true,
        len: payload.length,
        preview: payload.substring(0, 24)
      });
    } else {
      porNumero.push({ n: i, ok: false, len: 0, preview: null });
    }
  }
  const faltantes = porNumero.filter(function (f) { return !f.ok; }).map(function (f) { return f.n; });
  const ordenLlegada = Array.isArray(st.arrivalLog)
    ? st.arrivalLog.map(function (e) { return e.frame; })
    : [];
  const descartados = Array.isArray(st.arrivalLog)
    ? st.arrivalLog.filter(function (e) { return e.descartado; })
    : [];
  const texto = porNumero.map(function (f) {
    return f.ok ? (f.n + ':OK(' + f.len + ')') : (f.n + ':FALTA');
  }).join(' | ');

  return {
    total: total,
    recibidos: st.frames.size,
    faltantes: faltantes,
    orden_llegada: ordenLlegada,
    por_numero: porNumero,
    llegada: Array.isArray(st.arrivalLog) ? st.arrivalLog.slice() : [],
    descartados: descartados,
    texto: texto
  };
}

function gtpHdClearIdleTimer(st) {
  if (st && st.idlePersistTimer) {
    clearTimeout(st.idlePersistTimer);
    st.idlePersistTimer = null;
  }
}

function gtpHdCleanupAssembly(assemblyKey, locationCacheKey) {
  const st = photoAssemblies.get(assemblyKey);
  gtpHdClearIdleTimer(st);
  if (locationCacheKey) lastPhotoLocationByImei.delete(locationCacheKey);
  photoAssemblies.delete(assemblyKey);
}

function gtpHdNeedCoords(st) {
  return toFloat(st.latitud) === 0 && toFloat(st.longitud) === 0;
}

function gtpHdContiguousFrameCount(st) {
  if (!st || !st.frames || !st.frames.has(1)) return 0;
  let count = 0;
  const total = gtpHdTotalFrames(st);
  for (let i = 1; i <= total; i++) {
    if (!st.frames.has(i)) break;
    count++;
  }
  return count;
}

function gtpHdAssemblePartialBuffer(st) {
  if (!st || !st.frames || st.frames.size === 0) return null;
  const pushFrames = function (indices) {
    const buffers = [];
    for (let j = 0; j < indices.length; j++) {
      try {
        buffers.push(Buffer.from(st.frames.get(indices[j]), 'base64'));
      } catch (e) {
        return null;
      }
    }
    return buffers.length ? Buffer.concat(buffers) : null;
  };

  if (st.frames.has(1)) {
    const contiguous = [];
    const total = gtpHdTotalFrames(st);
    for (let i = 1; i <= total; i++) {
      if (!st.frames.has(i)) break;
      contiguous.push(i);
    }
    if (contiguous.length > 0) {
      const buf = pushFrames(contiguous);
      if (buf) return buf;
    }
  }

  const indices = Array.from(st.frames.keys()).filter(function (n) { return n > 0; }).sort(function (a, b) { return a - b; });
  return pushFrames(indices);
}

function gtpHdMissingFrameIndices(st) {
  if (!st || !st.totalFrames) return [];
  const missing = [];
  for (let i = 1; i <= st.totalFrames; i++) {
    if (!st.frames.has(i)) missing.push(i);
  }
  return missing;
}

function gtpHdPrepareJpegBuffer(buffer, isComplete) {
  if (!buffer || buffer.length < 2) return null;
  let out = buffer;
  if (out[0] !== 0xFF || out[1] !== 0xD8) {
    out = Buffer.concat([Buffer.from([0xFF, 0xD8]), out]);
  }
  const hasEoi = out.length >= 2 && out[out.length - 2] === 0xFF && out[out.length - 1] === 0xD9;
  if (!hasEoi) {
    if (isComplete) {
      console.warn('⚠️ GTPHD JPEG completo sin EOI (FF D9); se cierra artificialmente — revisar último frame');
    }
    out = Buffer.concat([out, Buffer.from([0xFF, 0xD9])]);
  }
  return out;
}

/** Guarda foto: completa si están todos los frames; parcial solo si forcePartial y aún incompleta. */
function gtpHdTryPersistPhoto(assemblyKey, opts) {
  opts = opts || {};
  const forcePartial = !!opts.forcePartial;
  const st = photoAssemblies.get(assemblyKey);
  if (!st || st._persisted) return;

  const complete = gtpHdFramesComplete(st);

  // Regla 1: completa → siempre persistir completa (ignora forcePartial).
  if (complete) {
    // caer a writePhoto más abajo
  } else if (!forcePartial) {
    // Regla 2: incompleta sin pedido de parcial → no guardar todavía.
    return;
  } else if (!opts.finalize && !gtpHdReadyToPersistPartial(st, opts)) {
    // Parcial solo tras silencio real.
    return;
  }

  if (st.frames.size === 0) return;
  if (!complete && st._partialPersistedFrames != null) {
    const hasNewFrames = st.frames.size > st._partialPersistedFrames
      || (st.maxFrameReceived || 0) > (st._partialPersistedMaxFrame || 0);
    if (!hasNewFrames && !opts.finalize) return;
  }

  const locationCacheKey = st.imei + '_' + st.photoTime;
  const imeiValue = st.imei;
  const photoTimeValue = st.photoTime;

  function writePhoto() {
    const stNow = photoAssemblies.get(assemblyKey);
    if (!stNow || stNow._persisted) return;

    const isComplete = gtpHdFramesComplete(stNow);
    let buffer = null;
    let framesReported = stNow.frames.size;
    const missingFrames = isComplete ? [] : gtpHdMissingFrameIndices(stNow);

    if (isComplete) {
      const buffers = [];
      for (let i = 1; i <= stNow.totalFrames; i++) {
        try {
          buffers.push(Buffer.from(stNow.frames.get(i), 'base64'));
        } catch (e) {
          console.error('❌ Error base64 GTPHD (chunk):', e);
          gtpHdCleanupAssembly(assemblyKey, locationCacheKey);
          return;
        }
      }
      buffer = gtpHdPrepareJpegBuffer(Buffer.concat(buffers), true);
      framesReported = stNow.totalFrames;
    } else {
      const partialBuf = gtpHdAssemblePartialBuffer(stNow);
      if (!partialBuf) return;
      buffer = gtpHdPrepareJpegBuffer(partialBuf, false);
      framesReported = stNow.frames.size;
    }

    const minLen = isComplete ? 1000 : 64;
    if (!buffer || buffer.length < minLen) {
      if (debug) {
        console.log('GTPHD buffer invalido o pequeño', {
          key: assemblyKey,
          len: buffer ? buffer.length : 0,
          complete: isComplete,
          frames: framesReported,
          totalFrames: stNow.totalFrames
        });
      }
      return;
    }

    if (isComplete && (buffer[0] !== 0xFF || buffer[1] !== 0xD8)) {
      gtpHdCleanupAssembly(assemblyKey, locationCacheKey);
      return;
    }

    // Evita que un upsert parcial async sobrescriba uno completo (o uno parcial más nuevo).
    stNow._persistGeneration = (stNow._persistGeneration || 0) + 1;
    const persistGeneration = stNow._persistGeneration;
    const persistIsComplete = isComplete;

    let imeiSafe = String(imeiValue || '').replace(/[^0-9A-Za-z_-]/g, '');
    let fileName = photoTimeValue + '_' + Date.now() + (isComplete ? '' : '_parcial') + '.jpg';
    let dir = path.join(PHOTO_EVENTS_DIR, imeiSafe);
    let filePath = path.join(dir, fileName);

    if (!isComplete && stNow._partialPersistFilePath) {
      filePath = stNow._partialPersistFilePath;
      fileName = path.basename(filePath);
    }

    try {
      fs.mkdirSync(dir, { recursive: true, mode: 0o775 });
      if (process.platform === 'linux') {
        try {
          const uid = Number(String(execSync('id -u www-data')).trim());
          const gid = Number(String(execSync('id -g www-data')).trim());
          if (!Number.isNaN(uid) && !Number.isNaN(gid)) {
            fs.chownSync(dir, uid, gid);
          }
        } catch (ownerErr) {
          if (debug) console.log('No se pudo asignar owner www-data:www-data:', ownerErr.message || ownerErr);
        }
      }
      fs.writeFileSync(filePath, buffer);
      if (process.platform === 'linux') {
        try {
          const uid = Number(String(execSync('id -u www-data')).trim());
          const gid = Number(String(execSync('id -g www-data')).trim());
          if (!Number.isNaN(uid) && !Number.isNaN(gid)) fs.chownSync(filePath, uid, gid);
        } catch (e) { }
      }
    } catch (e) {
      console.error('❌ Error guardando imagen GTPHD:', e);
      stNow._persisted = false;
      gtpHdCleanupAssembly(assemblyKey, locationCacheKey);
      return;
    }

    let storagePath = 'images/' + imeiSafe + '/' + fileName;

    if (dbTrackingSystem) {
      // Solo al persistir: photo_time como Date (como fecha_gps), no como string
      const photoTimeDate = (toInteger(photoTimeValue) != 0)
        ? moment(photoTimeValue, DEVICE_DATE_FORMAT).toDate()
        : null;

      const photoDoc = {
        imei: imeiValue,
        tipo: GTPHD,
        tipo_evento: 'foto',
        photo_time_fc: photoTimeDate,
        photo_time: photoTimeValue,
        fecha_gps: stNow.fechaGps || new Date(),
        latitud: stNow.latitud,
        longitud: stNow.longitud,
        imagen: storagePath,
        fecha: new Date(),
        js: true,
        frames_recibidos: framesReported,
        frames_esperados: stNow.totalFrames || null,
        ensamblaje_parcial: !isComplete,
        log: gtpHdBuildPhotoLog(stNow)
      };
      if (!isComplete && missingFrames.length) {
        photoDoc.frames_faltantes = missingFrames;
      }
      const photoFilter = { imei: imeiValue, tipo: GTPHD, photo_time: photoTimeValue };
      const photosCol = dbTrackingSystem.collection('photos_unidad');

      // Día local America/Guayaquil (UTC-5, sin DST): num_img reinicia cada día por IMEI
      const mGyq = moment().utcOffset(-5);
      const fechaBase = mGyq.format('YYYY-MM-DD');
      const hoyDesde = moment.parseZone(fechaBase + 'T00:00:00-05:00').toDate();
      const hoyHasta = moment.parseZone(fechaBase + 'T23:59:59.999-05:00').toDate();

      photosCol.findOne(photoFilter, function (errFind, existing) {
        // Si ya hay una versión más nueva en curso, o la foto ya se completó y se limpió el assembly,
        // no permitir que un parcial viejo pise el documento.
        const stLatest = photoAssemblies.get(assemblyKey);
        if (!persistIsComplete) {
          if (!stLatest || stLatest._persisted) return;
          if ((stLatest._persistGeneration || 0) !== persistGeneration) return;
          if (existing && existing.ensamblaje_parcial === false) return;
          if (existing && toInteger(existing.frames_recibidos) > framesReported) return;
        }

        function upsertPhoto(numImg) {
          const setDoc = Object.assign({}, photoDoc);
          if (numImg != null && numImg > 0) setDoc.num_img = numImg;
          const update = { $set: setDoc };
          if (persistIsComplete) {
            update.$unset = { frames_faltantes: '' };
            photosCol.updateOne(
              photoFilter,
              update,
              { upsert: true, writeConcern: { w: 0 } }
            );
            return;
          }
          if (!existing) {
            // Primera inserción de esta foto.
            photosCol.updateOne(
              photoFilter,
              update,
              { upsert: true, writeConcern: { w: 0 } }
            );
            return;
          }
          // Ya existe: actualizar solo si sigue parcial y no tiene más frames.
          photosCol.updateOne(
            {
              imei: imeiValue,
              tipo: GTPHD,
              photo_time: photoTimeValue,
              ensamblaje_parcial: { $ne: false },
              $or: [
                { frames_recibidos: { $exists: false } },
                { frames_recibidos: { $lte: framesReported } }
              ]
            },
            update,
            { upsert: false, writeConcern: { w: 0 } }
          );
        }

        // Misma foto (frames parciales / completar): conservar num_img ya asignado
        if (!errFind && existing && existing.num_img != null && toInteger(existing.num_img) > 0) {
          upsertPhoto(toInteger(existing.num_img));
          return;
        }

        // Nueva imagen del día: max(num_img) del IMEI hoy + 1 (al día siguiente vuelve a 1)
        photosCol
          .find({
            imei: imeiValue,
            fecha: { $gte: hoyDesde, $lte: hoyHasta },
            num_img: { $exists: true, $gt: 0 }
          })
          .project({ num_img: 1 })
          .sort({ num_img: -1 })
          .limit(1)
          .toArray(function (errMax, docs) {
            if (!persistIsComplete) {
              const stLatest2 = photoAssemblies.get(assemblyKey);
              if (!stLatest2 || stLatest2._persisted) return;
              if ((stLatest2._persistGeneration || 0) !== persistGeneration) return;
            }
            let maxNum = 0;
            if (!errMax && Array.isArray(docs) && docs.length > 0 && docs[0].num_img != null) {
              maxNum = toInteger(docs[0].num_img);
            }
            upsertPhoto(maxNum + 1);
          });
      });
    }

    console.log((isComplete ? '📸 Imagen guardada:' : '📸 Imagen parcial guardada:'), storagePath,
      '(' + framesReported + '/' + (stNow.totalFrames || '?') + ' frames, max#' + (stNow.maxFrameReceived || 0) +
      (isComplete ? '' : (', faltan:[' + missingFrames.join(',') + ']')) + ')');

    if (isComplete) {
      stNow._persisted = true;
      gtpHdCleanupAssembly(assemblyKey, locationCacheKey);
      return;
    }

    stNow._partialPersistPath = storagePath;
    stNow._partialPersistFilePath = filePath;
    stNow._partialPersistedFrames = framesReported;
    stNow._partialPersistedMaxFrame = stNow.maxFrameReceived || 0;
    gtpHdScheduleIdlePersist(assemblyKey);
  }

  // Coordenadas opcionales: nunca bloquear el guardado (completo ni parcial).
  if (gtpHdNeedCoords(st) && dbTrackingSystem && !st._gtpHdUnidadFetchAttempted) {
    st._gtpHdUnidadFetchAttempted = true;
    dbTrackingSystem.collection('unidads').findOne({ imei: imeiValue, estado: 'A' }, function (err, doc) {
      const st2 = photoAssemblies.get(assemblyKey);
      if (!st2 || st2._persisted) return;
      if (!err && doc) {
        st2.latitud = toFloat(doc.latitud);
        st2.longitud = toFloat(doc.longitud);
        if (!st2.fechaGps) st2.fechaGps = doc.fecha_gps || new Date();
      }
    });
  }

  writePhoto();
}

/** Programa guardado: completa si ya están todos; parcial solo tras silencio si faltan. */
function gtpHdScheduleIdlePersist(assemblyKey) {
  const st = photoAssemblies.get(assemblyKey);
  if (!st || st._persisted) return;
  gtpHdClearIdleTimer(st);

  const checkDelay = Math.min(5000, Math.max(1000, Math.round(gtpHdPartialIdleMs(st) / 4)));
  st.idlePersistTimer = setTimeout(function gtpHdIdlePersistTick() {
    const st2 = photoAssemblies.get(assemblyKey);
    if (!st2 || st2._persisted) return;

    // Prioridad: si ya están todos → imagen completa.
    if (gtpHdFramesComplete(st2)) {
      gtpHdTryPersistPhoto(assemblyKey, { forcePartial: false });
      return;
    }

    // Solo si siguen incompletos y hubo silencio real → imagen parcial.
    if (gtpHdReadyToPersistPartial(st2, { forcePartial: true })) {
      gtpHdTryPersistPhoto(assemblyKey, { forcePartial: true });
      if (photoAssemblies.has(assemblyKey) && !photoAssemblies.get(assemblyKey)._persisted) {
        gtpHdScheduleIdlePersist(assemblyKey);
      }
      return;
    }

    gtpHdScheduleIdlePersist(assemblyKey);
  }, checkDelay);
}

function flushTramas() {
  try {
    if (!dbTrackingSystem) return;
    if (TRAMAS_BUFFER.length === 0) return;

    const batch = TRAMAS_BUFFER.splice(0, TRAMAS_BATCH_MAX);
    dbTrackingSystem.collection('tramas').insertMany(batch, { ordered: false, writeConcern: { w: 0 } }, (err) => {
      if (err && debug) console.log('insertMany tramas error:', err.message || err);
    });
  } catch (e) {
    if (debug) console.log('flushTramas ex:', e.message || e);
  }
}
setInterval(flushTramas, TRAMAS_FLUSH_MS);

function limpiarBufferFotosParciales() {
  try {
    const now = Date.now();
    for (const [key, state] of photoAssemblies.entries()) {
      const lastUpdate = state.updatedAt || state.createdAt || now;
      const age = now - lastUpdate;
      const maxAgeMs = gtpHdMaxAssemblyAgeMs(state);
      const missingCount = gtpHdMissingFrameIndices(state).length;

      // Completa primero; parcial solo si sigue incompleta tras silencio.
      if (gtpHdFramesComplete(state)) {
        gtpHdTryPersistPhoto(key, { forcePartial: false });
      } else if (gtpHdReadyToPersistPartial(state, { forcePartial: true })) {
        gtpHdTryPersistPhoto(key, { forcePartial: true });
      }

      if (age > maxAgeMs) {
        if (photoAssemblies.has(key)) {
          if (gtpHdFramesComplete(photoAssemblies.get(key))) {
            gtpHdTryPersistPhoto(key, { forcePartial: false });
          } else {
            gtpHdTryPersistPhoto(key, { forcePartial: true, finalize: true });
          }
        }
        // Si solo faltan 1–3 frames, NO borrar el assembly todavía:
        // el frame puede llegar tarde (caso tramas sí / fotos no).
        // Extender espera hasta 3x maxAge antes de descartar memoria.
        if (missingCount > 0 && missingCount <= 3 && age < (maxAgeMs * 3)) {
          continue;
        }
        const locKey = state.imei && state.photoTime ? (state.imei + '_' + state.photoTime) : null;
        if (photoAssemblies.has(key)) photoAssemblies.delete(key);
        if (locKey) lastPhotoLocationByImei.delete(locKey);
      }
    }
    for (const [locKey, payload] of lastPhotoLocationByImei.entries()) {
      const storedAt = Number(payload && payload.storedAt) || 0;
      const ageLoc = storedAt ? (now - storedAt) : PHOTO_PARTIALS_MAX_AGE_MS + 1;
      if (ageLoc > PHOTO_LOCATION_CACHE_MAX_AGE_MS) {
        lastPhotoLocationByImei.delete(locKey);
      }
    }
  } catch (e) {
    if (debug) console.log('limpiarBufferFotosParciales ex:', e.message || e);
  }
}
setInterval(limpiarBufferFotosParciales, 10000);

// ===================== HELPERS =====================
function enviarALaravelPorWS(data, opts = {}) {
  try {
    if (!data) return;

    // Detectar si el mensaje raw es BUFF (trama en buffer del dispositivo).
    // Los BUFF SÍ se envían al front, pero con is_buff:true para que el front
    // pueda mostrar el parpadeo del ícono. Lo que NO hacemos con BUFF es:
    // - actualizar el throttle (skipThrottleUpdate implícito)
    // - forzar envío (no force)
    let isBuff = false;
    try {
      if (data._raw_message && String(data._raw_message).includes(BUFF)) {
        isBuff = true;
      }
    } catch (e) { }

    const now = Date.now();

    // 1) EVENTOS (NO TRACKING)
    if (data.type && data.type !== 'unidad.updated') {
      const coopEvt = String(data.cooperativa_id || '').trim();
      if (!coopEvt) return;

      const eventPayload = { ...data, is_buff: isBuff, _ts_sent: now };

      redisPub.publish('gps-channel', JSON.stringify(eventPayload), err => {
        if (err) console.error('❌ Redis event publish error:', err);
      });
      return;
    }

    // 2) TRACKING (unidad.updated)
    if (data.type === 'unidad.updated') {
      const key = String(data.imei || data._id || '').trim();
      if (!key) return;

      // 🔑 recuperar desde cache si no viene en el payload
      let coop = data.cooperativa_id;
      if (!coop) {
        const cached = unidadStateCache.get(key);
        if (cached && cached.cooperativa_id) {
          coop = cached.cooperativa_id;
        }
      }
      coop = String(coop || '').trim();
      if (!coop) return; // aquí sí, no hay forma

      // Throttle inteligente: solo limita si NO hay movimiento
      // Los BUFF nunca actualizan el throttle (no consumen el slot de tiempo)
      const prev = lastSentByUnit.get(key);
      const lat = Number(data.latitud);
      const lng = Number(data.longitud);

      const moved = !prev || prev.lat !== lat || prev.lng !== lng;

      if (!opts.force && !isBuff && !moved && prev && (now - prev.ts) < MIN_SEND_MS) return;

      // Solo actualizar el throttle para mensajes RESP (no BUFF)
      if (!isBuff && !opts.skipThrottleUpdate) {
        lastSentByUnit.set(key, { ts: now, lat, lng });
      }

      const trackingPayload = { ...data, cooperativa_id: coop, is_buff: isBuff, _ts_sent: now };

      redisPub.publish('gps-channel', JSON.stringify(trackingPayload), err => {
        if (err) console.error('❌ Redis tracking publish error:', err);
      });
    }

  } catch (err) {
    console.error('❌ Error en enviarALaravelPorWS:', err);
  }
}

/**
 * Dispara notificación push vía Laravel (FCM).
 * notificationTypeCode: código del tipo en notification_types; Laravel solo envía a usuarios con esa preferencia activa.
 * lat/lng opcionales: Laravel consulta LocationIQ solo si hay usuarios/tokens a los que enviar.
 */
function solicitarNotificacionPushPorImei(imei, bodyText, notificationTypeCode, lat, lng) {
  try {
    const attemptId = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);

    if (!LARAVEL_PUSH_URL || !LARAVEL_PUSH_SECRET) {
      pushDebugLog('skip', attemptId, 'faltan vars de entorno', {
        hasUrl: !!LARAVEL_PUSH_URL,
        hasSecret: !!LARAVEL_PUSH_SECRET
      });
      return;
    }
    if (imei == null || bodyText == null) {
      pushDebugLog('skip', attemptId, 'imei/body null', { imei: imei, bodyText: bodyText });
      return;
    }
    const imeiStr = String(imei).trim();
    const bodyStr = String(bodyText);
    if (!imeiStr || !bodyStr) {
      pushDebugLog('skip', attemptId, 'imei/body vacio', { imei: imeiStr, body: bodyStr });
      return;
    }

    const parsed = url.parse(LARAVEL_PUSH_URL);
    const typeCode = notificationTypeCode != null ? String(notificationTypeCode).trim() : '';
    const bodyPayload = {
      secret: LARAVEL_PUSH_SECRET,
      imei: imeiStr,
      body: bodyStr
    };
    if (typeCode) bodyPayload.notification_type_code = typeCode;
    const la = Number(lat);
    const lo = Number(lng);
    if (Number.isFinite(la) && Number.isFinite(lo) && Math.abs(la) <= 90 && Math.abs(lo) <= 180) {
      bodyPayload.lat = la;
      bodyPayload.lng = lo;
    }
    const payload = JSON.stringify(bodyPayload);
    const isHttps = parsed.protocol === 'https:';
    const mod = isHttps ? https : http;
    const port = parsed.port ? parseInt(parsed.port, 10) : (isHttps ? 443 : 80);
    const opts = {
      hostname: parsed.hostname,
      port: port,
      path: (parsed.path || '/') + (parsed.search || ''),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload, 'utf8')
      },
      timeout: 15000
    };
    pushDebugLog('send', attemptId, {
      url: LARAVEL_PUSH_URL,
      imei: imeiStr,
      type: typeCode || '(sin tipo)',
      secret_masked: maskSecret(LARAVEL_PUSH_SECRET),
      payload_preview: {
        imei: imeiStr,
        body: bodyStr,
        notification_type_code: typeCode || null
      }
    });

    const req = mod.request(opts, (res) => {
      let responseBody = '';
      res.on('data', (chunk) => {
        try { responseBody += chunk.toString('utf8'); } catch (e) { }
      });
      res.on('end', () => {
        pushDebugLog('response', attemptId, {
          statusCode: res.statusCode,
          statusMessage: res.statusMessage || '',
          body: responseBody ? responseBody.substring(0, 500) : '(sin body)'
        });
      });
    });
    req.on('timeout', () => {
      pushDebugLog('timeout', attemptId, 'timeout al enviar push');
      try { req.destroy(); } catch (e) { }
    });
    req.on('error', (err) => {
      pushDebugLog('error', attemptId, err && err.message ? err.message : err);
    });
    req.write(payload);
    req.end();
  } catch (e) {
    pushDebugLog('exception', e && e.message ? e.message : e);
    if (debug) console.log('solicitarNotificacionPushPorImei', e);
  }
}

/**
 * fecha_gps como Date (instante UTC interno) -> DD/MM/YYYY HH:mm:ss en zona configurada.
 * Las notificaciones FCM no renderizan HTML; usar este string para hora local correcta.
 */
function formatFechaGpsParaPush(fecha_gps) {
  if (fecha_gps == null || !(fecha_gps instanceof Date) || isNaN(fecha_gps.getTime())) return '';
  try {
    const fechaGpsDate = new Date(fecha_gps);
    if (isNaN(fechaGpsDate.getTime())) return '';
    fechaGpsDate.setHours(fechaGpsDate.getHours() + PUSH_GPS_HOUR_OFFSET);

    const fechaTxt = fechaGpsDate.toLocaleDateString('es-EC', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
    const horaTxt = fechaGpsDate.toLocaleTimeString('es-EC', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });
    return fechaTxt + ' ' + horaTxt;
  } catch (e) {
    return '';
  }
}

/**
 * Convierte el campo texto YYYYMMDDHHmmss del dispositivo a Date.
 * Por defecto UTC (ver PUSH_GPS_DATETIME_AS_UTC).
 */
function deviceDatetimeStringToDate(raw) {
  if (raw == null || raw === '') return null;
  const s = String(raw).replace(/\D/g, '');
  if (s.length < 14) return null;
  const piece = s.slice(0, 14);
  if (PUSH_GPS_DATETIME_AS_UTC) {
    const mUtc = moment.utc(piece, DEVICE_DATE_FORMAT, true);
    if (mUtc.isValid()) return mUtc.toDate();
  }
  const mLocal = moment(piece, DEVICE_DATE_FORMAT, true);
  return mLocal.isValid() ? mLocal.toDate() : null;
}

function fechaGpsFromGpsField(raw, fechaGPSInt) {
  if (!fechaGPSInt || fechaGPSInt === 0) return new Date();
  const d = deviceDatetimeStringToDate(raw);
  if (d) return d;
  return moment(raw, DEVICE_DATE_FORMAT).toDate();
}

function getSocket(imei) {
  for (let i = 0; i < socketArray.length; i++) {
    if (socketArray[i].imei === imei) return socketArray[i];
  }
  return null;
}

function sendLogsToAdminSockets(data) {
  let socketsToClear = [];
  for (let i = 0; i < logsAdmin.length; i++) {
    try {
      if (logsAdmin[i] && logsAdmin[i].writable) logsAdmin[i].write(`${data}\n`);
      else socketsToClear.push(logsAdmin[i]);
    } catch (e) {
      socketsToClear.push(logsAdmin[i]);
    }
  }
  logsAdmin = logsAdmin.filter(socket => !socketsToClear.includes(socket));
}

function getSpeed(speed) {
  if (speed !== '' && speed != null && !isNaN(speed)) return (parseFloat(speed) * 1.85);
  return 0;
}

function getCoordinates(value, orientation, latitud) {
  if (value !== '' && value != null && !isNaN(value)) {
    let sign = (orientation === 'N' || orientation === 'E') ? 1 : -1;
    let degrees = parseFloat(value.substring(0, ((latitud) ? 2 : 3)));
    let minutes = parseFloat(value.substring(((latitud) ? 2 : 3), value.length));
    let coord = (degrees + ((minutes) / 60)) * sign;
    return coord;
  }
  return 0;
}

function toInteger(value) {
  return (value === '' || isNaN(value)) ? 0 : parseInt(value);
}

function toFloat(value) {
  return (value === '' || isNaN(value)) ? 0 : parseFloat(value);
}

/** Misma regla que homev2: primeros 2 dígitos del voltaje (11000 → "11"). */
function voltajeDisplayUnidadParser(voltaje) {
  if (voltaje == null || voltaje === '') return null;
  return String(voltaje).substring(0, 2);
}

function voltajeEsAlertaUnidad(voltaje) {
  const disp = voltajeDisplayUnidadParser(voltaje);
  if (!disp) return false;
  const v = parseInt(disp, 10);
  return v === 11 || v === 10 || v === 9 || v === 8;
}

function estadoVehiculo(statusHex, velocidad, fechaGps, ahora = new Date(), isBuff = false, fechaGpsUnidadActual = null) {
  const LIMITE_SIN_SENAL = 30 * 60 * 1000; // 30 min
  const UMBRAL_MOVIMIENTO = 5; // km/h

  // Si es trama BUFF, validar antiguedad por fecha_gps:
  // si supera 30 min, marcar NS; si no, conservar estado actual.
  if (isBuff) {
    const fechaBaseBuff = fechaGpsUnidadActual;
    if (!fechaBaseBuff) return 'NS';
    const fechaBuff = new Date(fechaBaseBuff);
    if (isNaN(fechaBuff.getTime())) return 'NS';
    const fechagpsUA = new Date(fechaBuff.getTime() - (5 * 60 * 60 * 1000));
    const diffBuff = ahora - fechagpsUA;
    if (diffBuff > LIMITE_SIN_SENAL) return 'NS';
    return null;
  }

  // SIN SENAL
  if (!fechaGps) return 'NS';

  const fechaUtc = new Date(fechaGps);
  if (isNaN(fechaUtc.getTime())) return 'NS';

  // Igual que HistoricoController:
  // date_sub($f_gps, date_interval_create_from_date_string('10 hours'));
  const fecha = new Date(fechaUtc.getTime() - (5 * 60 * 60 * 1000));

  const diff = ahora - fecha;
  if (diff > LIMITE_SIN_SENAL) return 'NS';

  // Validacion basica
  if (!statusHex || String(statusHex).length < 2) return 'NS';

  const motion = String(statusHex).substring(0, 2).toUpperCase();

  const estadosMovimiento = ['16', '1A', '12', '22', '42'];
  const estadosDetenido = ['11', '21', '41'];

  // MOVIMIENTO REAL
  if (estadosMovimiento.includes(motion)) {
    if (velocidad && velocidad > UMBRAL_MOVIMIENTO) {
      return 'M';
    } else {
      return 'D'; // vibracion o falso movimiento
    }
  }

  // DETENIDO
  if (estadosDetenido.includes(motion)) {
    return 'D';
  }

  return 'D';
}

function toDecimalHex(value) {
  let dec = parseInt(value, 16);
  return isNaN(dec) ? 0 : dec;
}

function hexToBitPosition(hexString) {
  let decimal = (typeof hexString === 'string') ? parseInt(hexString, 16) : hexString;
  if (decimal === 0) return null;

  let position = 0;
  let value = decimal;
  while ((value & 1) === 0) {
    value >>= 1;
    position++;
  }
  return position;
}

// ===================== SENTIDO =====================
function actualizarSentidoUnidad(dbTrackingSystem, unidad, pdiActual, entrada, rawMessage, cb) {
  // Soporte para llamadas sin rawMessage: actualizarSentidoUnidad(db, unidad, pdi, entrada, cb)
  if (typeof rawMessage === 'function') { cb = rawMessage; rawMessage = null; }
  if (typeof cb !== 'function') cb = function () { };

  if (entrada !== 1) { cb(); return; } // Solo procesar entrada

  // OJO: moment-timezone no está importado en tu file original.
  // Mantengo tu lógica pero SIN tz() para no romperte si no está.
  const fechaBase = moment().format('YYYY-MM-DD');
  const hoyDesde = moment(`${fechaBase} 00:00:00`, 'YYYY-MM-DD HH:mm:ss').toDate();
  const hoyHasta = moment(`${fechaBase} 23:59:59`, 'YYYY-MM-DD HH:mm:ss').toDate();

  dbTrackingSystem.collection('despachos').aggregate([
    { $match: { unidad_id: unidad._id.toString(), estado: 'P', fecha: { $gte: hoyDesde, $lte: hoyHasta } } },
    { $lookup: { from: 'rutas', localField: 'ruta_id', foreignField: '_id', as: 'ruta' } }
  ]).toArray(function (err, despachos) {
    if (err) { console.error('❌ Error buscando despacho:', err); cb(); return; }

    if (!Array.isArray(despachos) || despachos.length === 0) {
      dbTrackingSystem.collection('unidads').updateOne(
        { _id: unidad._id },
        { $set: { sentido: null } },
        function (err) {
          if (err) console.error('❌ Error al resetear sentido:', err);
          cb();
        }
      );
      return;
    }

    if (!despachos[0].puntos_control || !Array.isArray(despachos[0].puntos_control) || despachos[0].puntos_control.length === 0) {
      cb(); return;
    }

    const puntosRuta = despachos[0].puntos_control || [];
    const puntoRetorno = puntosRuta.find(p => p.retorno === '1');
    if (!puntoRetorno) { cb(); return; }

    const ids = [];
    try { if (puntoRetorno.id) ids.push(ObjectId(puntoRetorno.id)); }
    catch (e) { console.error('❌ Error convirtiendo IDs:', e); cb(); return; }

    dbTrackingSystem.collection('punto_controls').find({ _id: { $in: ids } }).toArray(function (err, puntosReal) {
      if (err) { console.error('❌ Error buscando punto_controls:', err); cb(); return; }
      if (!Array.isArray(puntosReal) || puntosReal.length === 0) { cb(); return; }

      const ptoRet = puntosReal.find(p => p._id.equals(ObjectId(puntoRetorno.id)));
      const pdiRetorno = ptoRet ? ptoRet.pdi : null;

      let nuevoSentido = unidad.sentido || 'i';
      if (pdiRetorno && parseInt(pdiRetorno) === parseInt(pdiActual)) nuevoSentido = 'r';

      if (unidad.sentido === 'r' && nuevoSentido === 'i') { cb(); return; }

      if (nuevoSentido !== unidad.sentido) {
        dbTrackingSystem.collection('unidads').updateOne(
          { _id: unidad._id },
          { $set: { sentido: nuevoSentido } },
          function (err) {
            if (err) { console.error('❌ Error actualizando sentido:', err); cb(); return; }

            dbTrackingSystem.collection('historial_sentido').insertOne({
              unidad_id: unidad._id,
              imei: unidad.imei,
              nuevo_sentido: nuevoSentido,
              pdi: pdiActual,
              fecha: new Date()
            }, { writeConcern: { w: 0 } }, function (histErr) {
              if (histErr && debug) console.error('❌ Error insertando historial_sentido:', histErr);

              try {
                let sentidoEvent = {
                  type: 'unidad.sentido.changed',
                  unidad_id: unidad._id,
                  imei: unidad.imei || null,
                  nuevo_sentido: nuevoSentido,
                  cooperativa_id: (unidad.cooperativa_id ? String(unidad.cooperativa_id) : null),
                  _raw_message: rawMessage || null   // ✅ añadido
                };
                enviarALaravelPorWS(sentidoEvent);
              } catch (e) {
                console.error('❌ Error enviando evento de sentido:', e);
              }

              cb();
            });
          }
        );
      } else cb();
    });
  });
}

/**
 * Despacho en curso de la unidad entre varios pendientes del día.
 * Los despachos futuros (aún no llega su fecha de salida) se ignoran;
 * si varios ya iniciaron, se toma el de fecha de salida más reciente
 * cuya ventana cubra fecha_gps (o el más reciente si ya pasó el fin).
 */
function seleccionarDespachoActualUnidad(despachos, fechaGps) {
  if (!Array.isArray(despachos) || despachos.length === 0) return null;

  let refGps = null;
  if (fechaGps) {
    const fg = (fechaGps instanceof Date) ? fechaGps : new Date(fechaGps);
    if (!isNaN(fg.getTime())) refGps = fg;
  }
  if (!refGps) {
    // fecha_gps del dispositivo ~ local+offset; aproximar con ahora + 10h (misma escala del sistema)
    refGps = new Date(Date.now() + (10 * 60 * 60 * 1000));
  }

  const candidatos = [];
  for (let i = 0; i < despachos.length; i++) {
    const d = despachos[i];
    const puntos = Array.isArray(d.puntos_control) ? d.puntos_control : [];
    if (puntos.length === 0 || !d.fecha || !puntos[puntos.length - 1].tiempo_esperado) continue;

    const inicioGps = new Date(new Date(d.fecha).getTime() + (10 * 60 * 60 * 1000));
    const finGps = new Date(new Date(puntos[puntos.length - 1].tiempo_esperado).getTime() + (10 * 60 * 60 * 1000));
    // margen de atraso para aún considerar el viaje actual
    const finConMargen = new Date(finGps.getTime() + (2 * 60 * 60 * 1000));

    if (isNaN(inicioGps.getTime()) || isNaN(finGps.getTime())) continue;
    // Aún no empieza este despacho
    if (refGps < inicioGps) continue;

    candidatos.push({
      despacho: d,
      inicioGps: inicioGps,
      finConMargen: finConMargen,
      enVentana: refGps <= finConMargen
    });
  }

  if (candidatos.length === 0) return null;

  // Preferir los que aún están en su ventana horaria; entre ellos el de salida más reciente
  const enVentana = candidatos.filter(c => c.enVentana);
  const pool = enVentana.length > 0 ? enVentana : candidatos;
  pool.sort((a, b) => b.inicioGps.getTime() - a.inicioGps.getTime());
  return pool[0].despacho;
}

/**
 * Si la entrada (GTGIN/GTGEO entrada=1) es al PDI del último punto de control
 * del despacho EN CURSO de la unidad, solicita a Laravel DespachoController::end.
 */
function intentarFinalizarDespachoPorIngreso(unidad, pdi, fechaGps) {
  try {
    if (!unidad || !unidad._id) return;
    if (pdi == null || pdi === '') return;
    if (!LARAVEL_FINISH_DESPACHO_URL || !LARAVEL_PUSH_SECRET) return;

    const pdiNum = parseInt(pdi, 10);
    if (!Number.isFinite(pdiNum)) return;

    const fechaBase = moment().format('YYYY-MM-DD');
    // Misma ventana del día que FinalizarDespachosCommand (-5h)
    const hoyDesde = moment(`${fechaBase} 00:00:00`, 'YYYY-MM-DD HH:mm:ss').subtract(5, 'hours').toDate();
    const hoyHasta = moment(`${fechaBase} 23:59:59`, 'YYYY-MM-DD HH:mm:ss').subtract(5, 'hours').toDate();

    const unidadIdStr = String(unidad._id);
    const matchUnidad = {
      $or: [
        { unidad_id: unidadIdStr },
        { unidad_id: unidad._id }
      ]
    };

    dbTrackingSystem.collection('despachos').find({
      ...matchUnidad,
      estado: 'P',
      fecha: { $gte: hoyDesde, $lte: hoyHasta }
    }).sort({ fecha: 1 }).toArray(function (err, despachos) {
      if (err) {
        console.error('❌ Error buscando despacho para finalizar:', err);
        return;
      }
      if (!Array.isArray(despachos) || despachos.length === 0) return;

      // Con varios P del día (creados en la mañana), solo el despacho en curso
      const despacho = seleccionarDespachoActualUnidad(despachos, fechaGps);
      if (!despacho) return;

      const puntos = Array.isArray(despacho.puntos_control) ? despacho.puntos_control : [];
      if (puntos.length === 0) return;

      const ultimo = puntos[puntos.length - 1];
      if (!ultimo || !ultimo.id) return;

      // Evitar cerrar con la entrada al inicio si el PDI del primer y último punto coincide:
      // solo aceptar si fecha_gps >= tiempo_esperado del penúltimo (+10h, escala fecha_gps).
      if (puntos.length >= 2 && puntos[puntos.length - 2].tiempo_esperado && fechaGps) {
        const penultimoTe = new Date(puntos[puntos.length - 2].tiempo_esperado);
        if (!isNaN(penultimoTe.getTime())) {
          const desdeGps = new Date(penultimoTe.getTime() + (10 * 60 * 60 * 1000));
          const fg = (fechaGps instanceof Date) ? fechaGps : new Date(fechaGps);
          if (!isNaN(fg.getTime()) && fg < desdeGps) return;
        }
      }

      let ultimoId;
      try { ultimoId = ObjectId(String(ultimo.id)); }
      catch (e) { return; }

      dbTrackingSystem.collection('punto_controls').findOne({ _id: ultimoId }, function (errPc, puntoUltimo) {
        if (errPc || !puntoUltimo || puntoUltimo.pdi == null) return;
        if (parseInt(puntoUltimo.pdi, 10) !== pdiNum) return;

        const infoLog = {
          despacho_id: String(despacho._id),
          unidad_id: unidadIdStr,
          imei: unidad.imei || null,
          pdi_ultimo_punto: parseInt(puntoUltimo.pdi, 10),
          punto_control_id: String(ultimo.id),
          descripcion_punto: puntoUltimo.descripcion || null,
          fecha_despacho: despacho.fecha || null,
          fecha_gps_entrada: fechaGps || null
        };

        // Solo cooperativas con job de finalización automática
        const coopId = unidad.cooperativa_id;
        if (!coopId) {
          console.log('🚌 Finalizando despacho (ingreso último punto):', JSON.stringify(infoLog));
          solicitarFinalizarDespachoLaravel(String(despacho._id), infoLog);
          return;
        }

        let coopQuery;
        try {
          coopQuery = { _id: ObjectId(String(coopId)) };
        } catch (e) {
          coopQuery = { _id: coopId };
        }

        dbTrackingSystem.collection('cooperativas').findOne(coopQuery, function (errCoop, coop) {
          if (errCoop) {
            console.error('❌ Error buscando cooperativa para finalizar despacho:', errCoop);
            return;
          }
          if (coop && coop.despachos_job && String(coop.despachos_job).toUpperCase() !== 'S') {
            return;
          }
          infoLog.cooperativa = coop && coop.descripcion ? coop.descripcion : String(coopId);
          console.log('🚌 Finalizando despacho (ingreso último punto):', JSON.stringify(infoLog));
          solicitarFinalizarDespachoLaravel(String(despacho._id), infoLog);
        });
      });
    });
  } catch (e) {
    console.error('❌ intentarFinalizarDespachoPorIngreso:', e && e.message ? e.message : e);
  }
}

function solicitarFinalizarDespachoLaravel(despachoId, infoLog) {
  try {
    if (!despachoId || !LARAVEL_FINISH_DESPACHO_URL || !LARAVEL_PUSH_SECRET) return;
    if (finishingDespachoIds.has(despachoId)) return;
    finishingDespachoIds.add(despachoId);

    const parsed = url.parse(LARAVEL_FINISH_DESPACHO_URL);
    const payload = JSON.stringify({
      secret: LARAVEL_PUSH_SECRET,
      despacho_id: despachoId
    });
    const isHttps = parsed.protocol === 'https:';
    const mod = isHttps ? https : http;
    const port = parsed.port ? parseInt(parsed.port, 10) : (isHttps ? 443 : 80);
    const opts = {
      hostname: parsed.hostname,
      port: port,
      path: (parsed.path || '/') + (parsed.search || ''),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload, 'utf8')
      },
      timeout: 120000
    };

    console.log('➡️ Solicitando end() Laravel:', despachoId,
      '| pdi_ultimo:', infoLog && infoLog.pdi_ultimo_punto != null ? infoLog.pdi_ultimo_punto : '-');

    const req = mod.request(opts, (res) => {
      let responseBody = '';
      res.on('data', (chunk) => {
        try { responseBody += chunk.toString('utf8'); } catch (e) { }
      });
      res.on('end', () => {
        finishingDespachoIds.delete(despachoId);
        console.log('⬅️ Despacho finalizado:', despachoId,
          '| pdi_ultimo:', infoLog && infoLog.pdi_ultimo_punto != null ? infoLog.pdi_ultimo_punto : '-',
          '| status:', res.statusCode,
          '| body:', responseBody ? responseBody.substring(0, 300) : '');
      });
    });
    req.on('timeout', () => {
      finishingDespachoIds.delete(despachoId);
      console.error('⏱️ Timeout finish-despacho', despachoId,
        '| pdi_ultimo:', infoLog && infoLog.pdi_ultimo_punto != null ? infoLog.pdi_ultimo_punto : '-');
      try { req.destroy(); } catch (e) { }
    });
    req.on('error', (err) => {
      finishingDespachoIds.delete(despachoId);
      console.error('❌ Error finish-despacho', despachoId,
        '| pdi_ultimo:', infoLog && infoLog.pdi_ultimo_punto != null ? infoLog.pdi_ultimo_punto : '-',
        err && err.message ? err.message : err);
    });
    req.write(payload);
    req.end();
  } catch (e) {
    finishingDespachoIds.delete(despachoId);
    console.error('❌ solicitarFinalizarDespachoLaravel:', e && e.message ? e.message : e);
  }
}

// ===================== COUNTER RESTART =====================
function getTimeToRestartCounter() {
  const today = new Date();
  const tomorrow = new Date();
  tomorrow.setHours(restartHour);
  tomorrow.setMinutes(restartMinute);
  tomorrow.setSeconds(restartSecond);
  tomorrow.setMilliseconds(restartMilisecond);

  if (
    today.getHours() >= restartHour &&
    today.getMinutes() >= restartMinute &&
    today.getSeconds() >= restartSecond &&
    today.getMilliseconds() >= restartMilisecond
  ) {
    tomorrow.setDate(tomorrow.getDate() + 1);
  }
  return (tomorrow.getTime() - today.getTime());
}

function restartCounter() {
  dbTrackingSystem.collection('unidads').find({}).each(function (err, document) {
    if (err) console.log(err);
    else if (document != null) {
      dbTrackingSystem.collection('unidads').updateOne(
        { _id: document._id },
        {
          $set: {
            contador_diario: 0,
            contador_inicial: document.contador_total,
            contador_diario_sensor_2: 0,
            contador_inicial_sensor_2: document.contador_total_sensor_2,
            contador_diario_sensor_3: 0,
            contador_inicial_sensor_3: document.contador_total_sensor_3
          }
        },
        { writeConcern: { w: 0 } },
        function (err) { if (err && debug) console.log(err); }
      );
    }
  });
}

// ===================== BOOT =====================
MongoClient.connect(connection, { useUnifiedTopology: true }, function (error, client) {
  if (error) {
    console.log(error);
    process.exit(1);
  }

  dbTrackingSystem = client.db('dbtrackingsystem');

  // ===================== CARGA INICIAL CACHE UNIDADES =====================

  const server = net.createServer(onClientConnected);
  server.listen(PORT);
  dbTrackingSystem = client.db('dbtrackingsystem');

  schedule.scheduleJob("0 0 4 * * *", function () {
    restartCounter();
  });

  console.log('TCP server listening on', PORT);
});

// ===================== HEAVY ASYNC WORK =====================
// Se ejecuta en setImmediate() para NO bloquear SSE
function procesarRecorridosYAlertas_GTFRI(documentValue, data, message, indexes) {
  try {
    const { imei, voltage, speed, angle, height, longitude, latitude, datetime, battery, status, sentTime, mileage } = indexes;

    // ✅ Insert recorrido (liviano)
    try {
      dbTrackingSystem.collection('recorridos').insertOne({
        imei: data[imei],
        tipo: GTFRI,
        voltaje: documentValue.voltaje,
        fecha_gps: (documentValue.fecha_gps != null) ? new Date(documentValue.fecha_gps) : new Date(),
        latitud: documentValue.latitud,
        longitud: documentValue.longitud,
        velocidad: documentValue.velocidad_actual,
        mileage: documentValue.mileage,
        bateria: (documentValue.bateria),
        altura: toFloat(data[height]),
        angulo: documentValue.angulo,
        fecha_envio: (toInteger(data[sentTime]) != 0) ? (moment(data[sentTime], DEVICE_DATE_FORMAT).toDate()) : new Date(),
        unidad_id: documentValue._id,
        fecha: (documentValue.fecha != null) ? new Date(documentValue.fecha) : new Date(),
        estado_movil: documentValue.estado_movil,
        evento: documentValue.evento,
        contador_total: documentValue.contador_total,
        contador_diario: documentValue.contador_diario,
        js: true
      }, { writeConcern: { w: 0 } }, function (err) {
        if (err && debug) console.log(err);
      });
    } catch (e) { if (debug) console.log('insert recorridos ex', e); }

    // ⛔ Emails / alertas: mantenidas pero aquí ya NO bloquean SSE
    // (Dejé tu lógica intacta, solo la moví a este “worker” del mismo proceso)
    try {
      // --- EXCESO VELOCIDAD ---
      if (documentValue.velocidad != null && documentValue.velocidad !== '') {
        const velocidad_unidad_permitida = toFloat(documentValue.velocidad);
        if (documentValue.control_velocidad && documentValue.velocidad_actual > velocidad_unidad_permitida) {
          dbTrackingSystem.collection('cooperativas').findOne({ _id: new ObjectID(documentValue.cooperativa_id) }, function (err, document_coop) {
            if (err) return;

            let email_send = document_coop && document_coop.email ? document_coop.email : null;
            let cooperativa_descripcion = document_coop && document_coop.descripcion ? document_coop.descripcion : '';

            dbTrackingSystem.collection('users').find({
              estado: 'A',
              unidades_pertenecientes: { $in: [documentValue._id] }
            }).toArray(function (err, document_users) {
              if (err) return;

              if (Array.isArray(document_users)) {
                for (let i = 0; i < document_users.length; i++) {
                  if (email_send != null) email_send = email_send + "," + document_users[i].email;
                  else email_send = document_users[i].email;
                }
              }

              if (documentValue.email_alarma != null) {
                email_send = email_send ? (email_send + "," + documentValue.email_alarma) : documentValue.email_alarma;
              }
              email_send = email_send ? (email_send + "," + correoinfinty) : correoinfinty;

              let dategps = documentValue.fecha_gps;
              if (dategps != null) {
                let datetimeM = moment(dategps, DEVICE_DATE_FORMAT);
                let time = moment.duration("05:00:00");
                dategps = datetimeM.subtract(time).format(DATE_FORMAT);
              }

              let message_notification = "  Notificación Infinity Solutions\n";
              message_notification += "Fecha GPS: " + dategps;
              message_notification += "\nhttps://www.google.com.ec/maps/dir/" + documentValue.latitud + "," + documentValue.longitud + "//@" + documentValue.latitud + "," + documentValue.longitud + ",16z?hl=en";
              message_notification += " \nExceso de velocidad de: " + documentValue.velocidad_actual + " km/h \n\n\nInfinity Solutions";

              // Si quieres realmente enviar mails, descomenta sendMail.
              let transporter = nodemailer.createTransport({
                service: 'gmail',
                auth: { user: 'notificaciones.infinity@gmail.com', pass: 'qwertyuiop1' }
              });

              let options = {
                from: 'TRACKINGSYSTEM <notificaciones.infinity@gmail.com>',
                to: email_send,
                subject: "Notificaciones Infinity Exceso de Velocidad Disco " + documentValue.descripcion + " Placa " + documentValue.placa + " (" + cooperativa_descripcion + ")",
                text: message_notification
              };

              // transporter.sendMail(options, function (error, info) {});
            });
          });
        }
      }

      // --- VOLTAJE BAJO ---
      if (documentValue.voltaje != null && documentValue.voltaje !== '') {
        if (documentValue.sistema_energizado && documentValue.voltaje <= 11000) {
          dbTrackingSystem.collection('cooperativas').findOne({ _id: new ObjectID(documentValue.cooperativa_id) }, function (err, document_coop) {
            if (err) return;

            let email_send = document_coop && document_coop.email ? document_coop.email : null;
            let cooperativa_descripcion = document_coop && document_coop.descripcion ? document_coop.descripcion : '';

            dbTrackingSystem.collection('users').find({
              estado: 'A',
              unidades_pertenecientes: { $in: [documentValue._id] }
            }).toArray(function (err, document_users) {
              if (err) return;

              if (Array.isArray(document_users)) {
                for (let i = 0; i < document_users.length; i++) {
                  if (email_send != null) email_send = email_send + "," + document_users[i].email;
                  else email_send = document_users[i].email;
                }
              }

              if (documentValue.email_alarma != null) {
                email_send = email_send ? (email_send + "," + documentValue.email_alarma) : documentValue.email_alarma;
              }

              // en tu código ponías solo management.infinity...:
              email_send = correoinfinty;

              let dategps = documentValue.fecha_gps;
              if (dategps != null) {
                let datetimeM = moment(dategps, DEVICE_DATE_FORMAT);
                let time = moment.duration("05:00:00");
                dategps = datetimeM.subtract(time).format(DATE_FORMAT);
              }

              let message_notification = "  Notificación Infinity Solutions\n";
              message_notification += "Fecha GPS: " + dategps;
              message_notification += "\nhttps://www.google.com.ec/maps/dir/" + documentValue.latitud + "," + documentValue.longitud + "//@" + documentValue.latitud + "," + documentValue.longitud + ",16z?hl=en";
              message_notification += " \nUnidad con voltaje de dispositivo de:" + documentValue.voltaje + " \n\n\nInfinity Solutions";

              let transporter = nodemailer.createTransport({
                service: 'gmail',
                auth: { user: 'notificaciones.infinity@gmail.com', pass: 'qwertyuiop1' }
              });

              let options = {
                from: 'TRACKINGSYSTEM <notificaciones.infinity@gmail.com>',
                to: email_send,
                subject: "Notificaciones Infinity Voltaje 0 Disco " + documentValue.descripcion + " Placa " + documentValue.placa + " (" + cooperativa_descripcion + ")",
                text: message_notification
              };

              // transporter.sendMail(options, function (error, info) {});
            });
          });
        }
      }

    } catch (e) {
      if (debug) console.log('procesarRecorridosYAlertas_GTFRI ex:', e);
    }

  } catch (e) {
    if (debug) console.log('procesarRecorridosYAlertas_GTFRI outer ex:', e);
  }
}

/**
 * Igual que ComandosController::enviar_comando:
 * conecta al parseador y envía ADMIN;{imei};{cmd}\r\n (la rama ADMIN reenvía el AT al GPS).
 */
function escribirLineaComandoAlGps(imei, cmdLine) {
  const imeiKey = String(imei || '').trim();
  const cmd = String(cmdLine || CMD_VIGILANTE_AT_GTOUT).trim();
  if (!imeiKey || !cmd) return false;

  const host = String(process.env.TCP_HOST || '127.0.0.1').trim();
  const port = Number(process.env.TCP_PORT || PORT);
  const payload = 'ADMIN;' + imeiKey + ';' + cmd + '\r\n';

  try {
    const client = net.createConnection({ host: host, port: port });
    client.on('error', function (e) {
      console.error('escribirLineaComandoAlGps:', e && e.message ? e.message : e, { imei: imeiKey });
    });
    client.on('connect', function () {
      client.write(payload, function () {
        client.end();
      });
    });
    return true;
  } catch (e) {
    console.error('escribirLineaComandoAlGps:', e);
    return false;
  }
}

// ===================== TCP CLIENT HANDLER =====================
function onClientConnected(socket) {
  let clientName = `${socket.remoteAddress}:${socket.remotePort}`;

  socket.on('data', (dataRaw) => {
    const message = dataRaw.toString();
    sendLogsToAdminSockets(message);

    const imeiIndex = 2;

    try {
      if (!message.includes(ADMIN)) {
        const array = message.split(',');
        const currentSocket = getSocket(array[imeiIndex]);
        if (currentSocket === null) socketArray.push({ imei: array[imeiIndex], socket: socket });
        else currentSocket.socket = socket;
      }

      // ================== HEARTBEAT ==================
      if (message.includes(GTHBD) && !message.includes(ACK)) {
        const count = message.split(',')[5].split('$')[0];
        const heartbeat = `+SACK:GTHBD,,${count}$\n`;
        socket.write(heartbeat);
      }

      // ================== GTFRI (TRACKING) ==================
      // ================== GTFRI (REALTIME + ESTADO COMPLETO) ==================
      else if (message.includes(GTFRI) && !message.includes(ACK)) {

        const idx = {
          imei: 2,
          voltage: 4,
          speed: 8,
          angle: 9,
          height: 10,
          longitude: 11,
          latitude: 12,
          datetime: 13,
          mileage: 17,
          battery: 23,
          status: 24,
          sentTime: 28
        };

        const data = message.split(',');
        const now = new Date();
        const isBuffMessage = message.includes(BUFF);
        const fechaGPS = toInteger(data[idx.datetime]);

        // ===================== DATOS NUEVOS (GPS) =====================
        const fechaGpsDate = (fechaGPS !== 0)
          ? moment(data[idx.datetime], DEVICE_DATE_FORMAT).toDate()
          : now;
        const velocidadActual = toFloat(data[idx.speed]);

        const cachedUnidad = unidadStateCache.get(String(data[idx.imei] || '').trim()) || {};
        const fechaGpsUnidadActual = (cachedUnidad && cachedUnidad.fecha_gps != null)
          ? cachedUnidad.fecha_gps
          : null;
        const estadoMovilCalculado = estadoVehiculo(
          data[idx.status],
          velocidadActual,
          fechaGpsDate,
          now,
          isBuffMessage,
          fechaGpsUnidadActual
        );
        const estadoMovilFinal = (estadoMovilCalculado === null)
          ? (cachedUnidad.estado_movil || 'D')
          : estadoMovilCalculado;

        const gpsData = {
          type: 'unidad.updated',
          imei: data[idx.imei],
          latitud: toFloat(data[idx.latitude]),
          longitud: toFloat(data[idx.longitude]),
          voltaje: toFloat(data[idx.voltage]),
          velocidad_actual: velocidadActual,
          bateria: toFloat(data[idx.battery]),
          mileage: toDecimalHex(data[idx.mileage]),
          angulo: toInteger(data[idx.angle]),
          estado_movil: estadoMovilFinal,
          fecha_gps: fechaGpsDate,
          fecha: now,
          is_atm: (message.includes(ATM) ? 1 : 0),
          _raw_message: message
        };

        // tiempo_voltaje: 24h fijas si voltaje alerta (11,10,9,8); 0 si voltaje normal
        if (!isBuffMessage) {
          if (voltajeEsAlertaUnidad(gpsData.voltaje)) {
            gpsData.tiempo_voltaje = 24;
            gpsData.tiempo_voltaje_update = now;
          } else {
            gpsData.tiempo_voltaje = 0;
            gpsData.tiempo_voltaje_update = now;
          }
        }

        if(data[idx.imei]=='863457050082674' ){
          console.log("imei: "+data[idx.imei]);
          console.log("estado_movil_v2: "+estadoMovilFinal);
        }
       
        // ===================== PAYLOAD COMPLETO (CACHE + GPS) =====================
        const unidadPayload = buildUnidadPayloadRealtime(gpsData);

        // 🔥🔥🔥 ENVIAR AL FRONT INMEDIATO
        // Si es +RESP (no BUFF), forzar envío saltando throttle.
        // skipThrottleUpdate: no consume el slot de throttle aquí;
        // el post-BD (con datos completos) lo hace.
        const isRespMessage = !isBuffMessage;
        enviarALaravelPorWS(unidadPayload, { force: isRespMessage, skipThrottleUpdate: isRespMessage });

        // Solo tramas NO-BUFF actualizan unidads.
        if (!isBuffMessage) {
          // ===================== ACTUALIZAR BD (NO BLOQUEA) =====================
          dbTrackingSystem.collection('unidads').findOneAndUpdate(
            { imei: data[idx.imei], estado: 'A' },
            {
              $set: {
                estado_movil: gpsData.estado_movil,
                latitud: gpsData.latitud,
                longitud: gpsData.longitud,
                voltaje: gpsData.voltaje,
                velocidad_actual: gpsData.velocidad_actual,
                mileage: gpsData.mileage,
                bateria: gpsData.bateria,
                is_atm: gpsData.is_atm,
                angulo: gpsData.angulo,
                fecha_gps: gpsData.fecha_gps,
                fecha: now,
                tiempo_voltaje: gpsData.tiempo_voltaje,
                tiempo_voltaje_update: gpsData.tiempo_voltaje_update
              }
            },
            { returnDocument: 'after', writeConcern: { w: 0 } },
            function (err, result) {

              if (err || !result || !result.value) return;

              const unidad = result.value; // 🔥 unidad ya actualizada

              // Enviar una publicación autorizada basada en la unidad en BD
              // Esto garantiza que el campo `sentido` viene desde la tabla `unidads`
              // y fuerza el envío sin pasar por el throttle (opts.force = true).
              try {
                const unidadFromDbPayload = buildUnidadPayloadRealtime(Object.assign({ type: 'unidad.updated', _raw_message: gpsData._raw_message }, unidad));
                // Si es +RESP (no BUFF), forzar envío siempre (saltamos throttle)
                enviarALaravelPorWS(unidadFromDbPayload, { force: isRespMessage });
              } catch (e) {
                if (debug) console.error('Error publicando unidad desde BD:', e);
              }

              // ===================== TRABAJO PESADO =====================
              setImmediate(() => {
                try {

                  procesarRecorridosYAlertas_GTFRI(
                    unidad,
                    data,
                    message,
                    idx
                  );

                } catch (e) { }
              });

            }
          );
        }
      }

      // ================== GTDAT ==================
      else if (message.includes(GTDAT) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        // --- TU LÓGICA ORIGINAL (sin cambios funcionales) ---
        let imei = 2;
        let flag = 4;
        let deviceName = 5;
        let count = 6;
        let p2 = 7;
        let p3 = 8;
        let status = 7;
        let sentTime = 8;
        let fechaSend = 10;
        let data = message.split(',');
        const MAX_COUNT = 65535;
        const MAX_COUNT_C2 = 999999;
        // Techos de hardware: P1/C1 = 16 bits; P2/P3/C2 (y similares) = hasta MAX_COUNT_C2.
        const limiteHardwarePorTag = function (tag) {
          if (tag === 'P1' || tag === 'C1') return MAX_COUNT;
          return MAX_COUNT_C2;
        };
        const lecturaContadorInvalida = function (lectura, tag) {
          if (!Number.isFinite(lectura) || lectura < 0) return true;
          const lim = limiteHardwarePorTag(tag);
          if (lectura > lim) return true;
          // El equipo suele mandar el máximo “todo unos” como lectura inválida / sin dato.
          if (lectura === MAX_COUNT || lectura === MAX_COUNT_C2) return true;
          return false;
        };
        const contadorDiarioFueraDeRango = function (diario, tag) {
          if (!Number.isFinite(diario) || diario < 0) return true;
          const lim = limiteHardwarePorTag(tag);
          // Diario acumulado no puede superar el techo del contador; >= lim cubre 65535/999999 y rollover mal corregido.
          if (diario >= lim) return true;
          return false;
        };

        let puerta1, puerta2, puerta3;

        if (data[flag] === '>PC' || data[flag] === '>PC3') {
          if (data[deviceName] === 'P1' || data[deviceName] === 'P2' || data[deviceName] === 'P3') {
            dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
              if (err) console.log(err);
              else if (document) {
                const tag = data[deviceName];
                const lecturaContador = toInteger(data[count]);
                if (lecturaContadorInvalida(lecturaContador, tag)) {
                  return;
                }

                let contador_diario = 0;
                let contador_inicial = null;
                let contador_diario_anterior = 0;
                contador_diario_anterior = document.contador_diario;

                if (data[deviceName] === 'P1') { contador_diario_anterior = document.contador_diario; contador_inicial = document.contador_inicial; }
                if (data[deviceName] === 'P2') { contador_diario_anterior = document.contador_diario_sensor_2; contador_inicial = document.contador_inicial_sensor_2; }
                if (data[deviceName] === 'P3') { contador_diario_anterior = document.contador_diario_sensor_3; contador_inicial = document.contador_inicial_sensor_3; }

                if (contador_inicial != null) {
                  if (contador_inicial > 0) {
                    contador_diario = (toInteger(data[count])) - contador_inicial;
                    if (contador_diario < 0) {
                      if (data[deviceName] === 'P1') contador_diario = (toInteger(data[count])) + MAX_COUNT;
                      else contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;
                    }
                  } else contador_inicial = toInteger(data[count]);
                } else contador_inicial = toInteger(data[count]);

                if (contadorDiarioFueraDeRango(contador_diario, tag)) {
                  return;
                }

                if (contador_diario >= contador_diario_anterior && !isBuffMessage) {
                  if (data[deviceName] === 'P1') {
                    dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                      $set: { contador_total: toInteger(data[count]), contador_diario: contador_diario, contador_inicial: contador_inicial, is_atm: (message.includes(ATM) ? 1 : 0), evento: data[status] }
                    }, { writeConcern: { w: 0 } });
                  }
                  if (data[deviceName] === 'P2') {
                    dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                      $set: { contador_total_sensor_2: toInteger(data[count]), contador_diario_sensor_2: contador_diario, contador_inicial_sensor_2: contador_inicial, is_atm: (message.includes(ATM) ? 1 : 0), evento: data[status] }
                    }, { writeConcern: { w: 0 } });
                  }
                  if (data[deviceName] === 'P3') {
                    dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                      $set: { contador_total_sensor_3: toInteger(data[count]), contador_diario_sensor_3: contador_diario, contador_inicial_sensor_3: contador_inicial, is_atm: (message.includes(ATM) ? 1 : 0), evento: data[status] }
                    }, { writeConcern: { w: 0 } });
                  }
                }
              }
            });
          } else {
            if (data[deviceName] === 'PAC') {
              dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                if (err) console.log(err);
                else if (document) {
                  let puerta_1 = parseInt(data[count], 10);
                  let puerta_2 = parseInt(data[p2], 10);
                  let puerta_3 = parseInt(data[p3], 10);
                 

                  let fecha_gps = (toInteger(data[fechaSend]) != 0) ? moment(data[fechaSend], DEVICE_DATE_FORMAT).toDate() : new Date();
                  let fecha_servidor = new Date();

                  puerta1 = (puerta_1 == 0) ? 'PUERTA CERRADA (DELANTERA)' : 'PUERTA ABIERTA (DELANTERA)';
                  puerta2 = (puerta_2 == 0) ? 'PUERTA CERRADA (MEDIO)' : 'PUERTA ABIERTA (MEDIO)';
                  puerta3 = (puerta_3 == 0) ? 'PUERTA CERRADA (TRASERA)' : 'PUERTA ABIERTA (TRASERA)';

                  dbTrackingSystem.collection('recorridos').insertOne({
                    imei: data[imei], tipo: GTDIS, unidad_id: document._id, velocidad: document.velocidad, angulo: document.angulo,
                    longitud: document.longitud, latitud: document.latitud, fecha_gps: fecha_gps, fecha: fecha_servidor,
                    evento: puerta1, fecha_envio: fecha_gps, js: true
                  }, { writeConcern: { w: 0 } });
                  dbTrackingSystem.collection('recorridos').insertOne({
                    imei: data[imei], tipo: GTDIS, unidad_id: document._id, velocidad: document.velocidad, angulo: document.angulo,
                    longitud: document.longitud, latitud: document.latitud, fecha_gps: fecha_gps, fecha: fecha_servidor,
                    evento: puerta2, fecha_envio: fecha_gps, js: true
                  }, { writeConcern: { w: 0 } });
                  dbTrackingSystem.collection('recorridos').insertOne({
                    imei: data[imei], tipo: GTDIS, unidad_id: document._id, velocidad: document.velocidad, angulo: document.angulo,
                    longitud: document.longitud, latitud: document.latitud, fecha_gps: fecha_gps, fecha: fecha_servidor,
                    evento: puerta3, fecha_envio: fecha_gps, js: true
                  }, { writeConcern: { w: 0 } });
                }
              });
            } else {
              dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                if (err) console.log(err);
                else if (document) {
                  const tag = data[deviceName];
                  const lecturaContador = toInteger(data[count]);
                  if (lecturaContadorInvalida(lecturaContador, tag)) {
                    return;
                  }

                  let contador_diario = 0;
                  let contador_inicial = document.contador_inicial;

                  if (document.contador_inicial != null) {
                    if (document.contador_inicial > 0) {
                      contador_diario = (toInteger(data[count])) - document.contador_inicial;
                      if (contador_diario < 0) {
                        if (data[deviceName] === 'C1') contador_diario = (toInteger(data[count])) + MAX_COUNT;
                        else contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;
                      }
                    } else contador_inicial = toInteger(data[count]);
                  } else contador_inicial = toInteger(data[count]);

                  if (contadorDiarioFueraDeRango(contador_diario, tag)) {
                    return;
                  }

                  if (contador_diario >= document.contador_diario && !isBuffMessage) {
                    dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                      $set: { contador_total: toInteger(data[count]), contador_diario: contador_diario, contador_inicial: contador_inicial, is_atm: (message.includes(ATM) ? 1 : 0), evento: data[status] }
                    }, { writeConcern: { w: 0 } });
                  }
                }
              });
            }
          }
        }
      }

      // ================== GTPHL (PHOTO LOCATION PRE-REPORT) ==================
      else if (message.includes(GTPHL) && !message.includes(ACK)) {
        let imei = 2;
        let cameraId = 4;
        let photoTime = 6;
        let longitude = 11;
        let latitude = 12;
        let gpsUtcTime = 13;
        let sendTime = 19;
        let count = 20;
        let data = message.split(',');

        if (!data[imei]) return;

        const fecha_gps = (toInteger(data[gpsUtcTime]) != 0)
          ? moment(data[gpsUtcTime], DEVICE_DATE_FORMAT).toDate()
          : new Date();

        const locationPayload = {
          imei: String(data[imei] || '').trim(),
          cameraId: String(data[cameraId] || '').trim(),
          photoTime: String(data[photoTime] || '').trim(),
          latitud: toFloat(data[latitude]),
          longitud: toFloat(data[longitude]),
          fecha_gps: fecha_gps,
          send_time: String(data[sendTime] || '').trim(),
          count: String(data[count] || '').replace('$', '').trim(),
          storedAt: Date.now()
        };

        const locationCacheKey = locationPayload.imei + '_' + locationPayload.photoTime;

        function persistLocationCache() {
          lastPhotoLocationByImei.set(locationCacheKey, locationPayload);
        }

        // Si no viene coordenada valida, usar ultima posicion de la unidad.
        if (locationPayload.latitud === 0 || locationPayload.longitud === 0) {
          if (!dbTrackingSystem) {
            locationPayload.storedAt = Date.now();
            persistLocationCache();
            return;
          }
          dbTrackingSystem.collection('unidads').findOne({ imei: locationPayload.imei, estado: 'A' }, function (err, document) {
            if (err) console.log(err);
            else if (document) {
              locationPayload.latitud = toFloat(document.latitud);
              locationPayload.longitud = toFloat(document.longitud);
              if (!locationPayload.fecha_gps || Number.isNaN(locationPayload.fecha_gps.getTime())) {
                locationPayload.fecha_gps = document.fecha_gps || new Date();
              }
            }
            locationPayload.storedAt = Date.now();
            persistLocationCache();
          });
        } else {
          persistLocationCache();
        }
      }

      // ================== GTPHD (PHOTO DATA FRAMES) ==================
      else if (message.includes(GTPHD) && !message.includes(ACK)) {
        let imei = 2;
        let cameraId = 4;
        let photoTime = 6;
        let totalFramesIdx = 7;
        let currentFrameIdx = 8;
        let photoDataLengthIdx = 9;
        let photoDataStartIdx = 10;
        let data = message.split(',');

        let imeiValue = String(data[imei] || '').trim();
        let cameraIdValue = String(data[cameraId] || '').trim();
        let photoTimeValue = String(data[photoTime] || '').trim();
        let totalFramesValue = toInteger(data[totalFramesIdx]);
        let currentFrameValue = toInteger(data[currentFrameIdx]);
        let photoDataLengthValue = toInteger(data[photoDataLengthIdx]);

        // Photo Data no contiene comas (base64). Usar campo 10 + Photo Data Length;
        // slice().join(',') mezclaba reserved/sendTime o el siguiente GTPHD coalescido.
        let base64Payload = String(data[photoDataStartIdx] || '').trim();
        if (photoDataLengthValue > 0 && base64Payload.length > photoDataLengthValue) {
          base64Payload = base64Payload.slice(0, photoDataLengthValue);
        }
        let sendTimeValue = data[data.length - 2];
        let countTail = String(data[data.length - 1] || '').replace('$', '').trim();

        if (!imeiValue || !photoTimeValue || !base64Payload) return;
        if (data.length < 13) return;
        if (base64Payload.length < 50) return;
        if (totalFramesValue <= 0 || currentFrameValue <= 0) return;

        if (photoDataLengthValue > 0 && base64Payload.length !== photoDataLengthValue) {
          console.warn('⚠️ GTPHD length mismatch, frame descartado', {
            imei: imeiValue,
            frame: currentFrameValue,
            expected: photoDataLengthValue,
            actual: base64Payload.length,
            sendTime: sendTimeValue,
            count: countTail
          });
          // Registrar en el log del ensamblaje aunque no se use el payload
          const discardKey = imeiValue + '_' + photoTimeValue + '_' + cameraIdValue;
          let discardState = photoAssemblies.get(discardKey);
          if (!discardState && photoAssemblies.size < MAX_ACTIVE_PHOTO_ASSEMBLIES) {
            discardState = {
              imei: imeiValue,
              photoTime: photoTimeValue,
              cameraId: cameraIdValue,
              totalFrames: totalFramesValue,
              frames: new Map(),
              arrivalLog: [],
              createdAt: Date.now(),
              updatedAt: Date.now(),
              latitud: 0,
              longitud: 0,
              fechaGps: null
            };
            photoAssemblies.set(discardKey, discardState);
          }
          if (discardState) {
            if (totalFramesValue > 0) discardState.totalFrames = totalFramesValue;
            if (!discardState.arrivalLog) discardState.arrivalLog = [];
            discardState.arrivalLog.push({
              seq: discardState.arrivalLog.length + 1,
              frame: currentFrameValue,
              len: base64Payload.length,
              declaredLen: photoDataLengthValue,
              descartado: true,
              motivo: 'length_mismatch',
              at: new Date().toISOString()
            });
            discardState.updatedAt = Date.now();
            gtpHdScheduleIdlePersist(discardKey);
          }
          return;
        }

        const key = imeiValue + '_' + photoTimeValue + '_' + cameraIdValue;
        const locationCacheKey = imeiValue + '_' + photoTimeValue;

        let state = photoAssemblies.get(key);

        if (!state) {
          if (photoAssemblies.size >= MAX_ACTIVE_PHOTO_ASSEMBLIES) {
            console.warn('⚠️ demasiadas fotos en proceso (max ' + MAX_ACTIVE_PHOTO_ASSEMBLIES + '), se ignora trama GTPHD imei=' + imeiValue);
            return;
          }
          state = {
            imei: imeiValue,
            photoTime: photoTimeValue,
            cameraId: cameraIdValue,
            totalFrames: totalFramesValue,
            frames: new Map(),
            arrivalLog: [],
            createdAt: Date.now(),
            updatedAt: Date.now(),
            latitud: 0,
            longitud: 0,
            fechaGps: null
          };
          photoAssemblies.set(key, state);
        }

        if (totalFramesValue > 0) {
          state.totalFrames = totalFramesValue;
        }

        let wasDuplicate = false;
        if (state.frames.has(currentFrameValue)) {
          // Reemplazar solo si el nuevo chunk tiene el tamaño declarado y el anterior no
          const prev = state.frames.get(currentFrameValue);
          const prevOk = photoDataLengthValue <= 0 || (prev && prev.length === photoDataLengthValue);
          if (!prevOk) {
            state.frames.set(currentFrameValue, base64Payload);
          } else {
            wasDuplicate = true;
          }
        } else {
          state.frames.set(currentFrameValue, base64Payload);
        }
        gtpHdTrackFrameArrival(state, currentFrameValue, {
          len: base64Payload.length,
          declaredLen: photoDataLengthValue,
          duplicado: wasDuplicate
        });

        const locationInfo = lastPhotoLocationByImei.get(locationCacheKey);
        if (locationInfo) {
          state.latitud = toFloat(locationInfo.latitud);
          state.longitud = toFloat(locationInfo.longitud);
          state.fechaGps = locationInfo.fecha_gps;
        }

        // 1) Si ya están todos los frames → imagen COMPLETA ahora.
        //    No hacer return: debe registrarse también en TRAMAS_BUFFER.
        if (gtpHdFramesComplete(state)) {
          gtpHdTryPersistPhoto(key, { forcePartial: false });
        } else {
          // 2) Incompleta: si ya existía un parcial, actualizarlo con frames nuevos;
          //    si no, esperar silencio para construir parcial.
          if (state._partialPersistFilePath && !state._persisted) {
            gtpHdTryPersistPhoto(key, { forcePartial: true, finalize: true });
          }
          gtpHdScheduleIdlePersist(key);
        }
      }
      // ================== GTGOT / GTGIN ==================
      else if (!message.includes(ADMIN) && (message.includes(GTGOT) || message.includes(GTGIN)) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let speed = 14;
        let angle = 15;
        let height = 16;
        let longitude = 17;
        let latitude = 18;
        let datetime = 19;
        let sentTime = 26;
        let infoControlPoint = 7;
        let data = message.split(',');
        const indiceval = 20;

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let pdi = indiceval + hexToBitPosition(data[infoControlPoint]);

            let estado_movil = document.estado_movil;
            let latitud = toFloat(data[latitude]);
            let longitudV = toFloat(data[longitude]);

            let fecha_servidor = new Date();
            let fecha_gps = (toInteger(data[datetime]) != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date();

            if (latitud === 0 || longitudV === 0) {
              estado_movil = 'E';
              latitud = document.latitud;
              longitudV = document.longitud;
            }

            enviarUnidadUpdatedEstiloGTFRI({
              imei: data[imei],
              unidad_id: document._id,
              _id: document._id,
              latitud,
              longitud: longitudV,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              estado_movil,
              fecha_gps,
              is_atm: (message.includes(ATM) ? 1 : 0),
              cooperativa_id: document.cooperativa_id ? String(document.cooperativa_id).trim() : null
            }, message);

            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: { latitud: latitud, longitud: longitudV, estado_movil, velocidad_actual: toFloat(data[speed]), angulo: toInteger(data[angle]), fecha_gps, is_atm: (message.includes(ATM) ? 1 : 0), fecha: fecha_servidor }
              }, { writeConcern: { w: 0 } });
            }

            let entrada = message.includes("GTGIN") ? 1 : 0;
            let origen = message.includes("GTGIN") ? "GTGIN" : "GTGOT";

            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: "GTGEO",
              origen: origen,
              unidad_id: document._id,
              pdi: pdi,
              entrada: entrada,
              latitud: latitud,
              longitud: longitudV,
              velocidad: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              altura: toFloat(data[height]),
              fecha_gps: fecha_gps,
              fecha: fecha_servidor,
              fecha_envio: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
              contador_diario: document.contador_diario,
              contador_total: document.contador_total,
              js: true
            }, { writeConcern: { w: 0 } }, function (err) {
              if (!err && !isBuffMessage) actualizarSentidoUnidad(dbTrackingSystem, document, pdi, entrada, message);  // ✅
              if (!err && !isBuffMessage && entrada === 1) {
                setImmediate(function () {
                  intentarFinalizarDespachoPorIngreso(document, pdi, fecha_gps);
                });
              }
              if (!err) {
                dbTrackingSystem.collection('punto_controls').findOne({ pdi: String(pdi), cooperativa_id: String(document.cooperativa_id) }, function (errPuntoControl, puntoControl) {
                  if (errPuntoControl) console.error('❌ Error buscando punto_controls (GTGIN/GTGOT):', errPuntoControl);

                  let descripcionPuntoControl = '';
                  if (puntoControl && puntoControl.descripcion) descripcionPuntoControl = String(puntoControl.descripcion).trim();
                  const lineaPuntoControl = descripcionPuntoControl ? '* 📍 P. Control:* ' + descripcionPuntoControl + '\n' : '';

                  if (entrada === 1) {
                    const fechaHoraTxtGeo = formatFechaGpsParaPush(fecha_gps);
                    const fechaPartesGeo = fechaHoraTxtGeo.split(' ');
                    const fechaTxtGeo = fechaPartesGeo.length > 0 ? fechaPartesGeo[0] : fechaHoraTxtGeo;
                    const horaTxtGeo = fechaPartesGeo.length > 1 ? fechaPartesGeo[1] : '';
                    const unidadTxtGeo = String(document.descripcion).trim();
                    const txtGeofenceEntrada =
                      unidadTxtGeo + ':🚩🟢 Entrada a punto de control\n' +
                      '* 🚍 Vehiculo:* ' + unidadTxtGeo + '\n' +
                      lineaPuntoControl +
                      '* 📅 Fecha:* ' + fechaTxtGeo + '\n' +
                      '* ⏰ Hora:* ' + horaTxtGeo;
                      solicitarNotificacionPushPorImei(data[imei], txtGeofenceEntrada, PUSH_TYPE_GEOFENCE, latitud, longitudV);
                  } else if (entrada === 0) {
                    const fechaHoraTxtGeo = formatFechaGpsParaPush(fecha_gps);
                    const fechaPartesGeo = fechaHoraTxtGeo.split(' ');
                    const fechaTxtGeo = fechaPartesGeo.length > 0 ? fechaPartesGeo[0] : fechaHoraTxtGeo;
                    const horaTxtGeo = fechaPartesGeo.length > 1 ? fechaPartesGeo[1] : '';
                    const unidadTxtGeo = String(document.descripcion).trim();
                    const txtGeofenceSalida =
                      unidadTxtGeo + ':🚩🔴 Salida del punto de control\n' +
                      '* 🚍 Vehiculo:* ' + unidadTxtGeo + '\n' +
                      lineaPuntoControl +
                      '* 📅 Fecha:* ' + fechaTxtGeo + '\n' +
                      '* ⏰ Hora:* ' + horaTxtGeo;
                      solicitarNotificacionPushPorImei(data[imei], txtGeofenceSalida, PUSH_TYPE_GEOFENCE, latitud, longitudV);
                  }
                });
              }
            });
          }
        });
      }

      // ================== GTGEO ==================
      else if (!message.includes(ADMIN) && message.includes(GTGEO) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let infoControlPoint = 5;
        let speed = 8;
        let angle = 9;
        let height = 10;
        let longitude = 11;
        let latitude = 12;
        let datetime = 13;
        let sentTime = 20;
        let data = message.split(',');

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let pdi, inout;
            if (data[infoControlPoint].length === 3) {
              pdi = parseInt(data[infoControlPoint].substring(0, 2), 16);
              inout = parseInt(data[infoControlPoint].substring(2, 3));
            } else if (data[infoControlPoint].length === 2) {
              pdi = parseInt(data[infoControlPoint].charAt(0), 16);
              inout = parseInt(data[infoControlPoint].charAt(1));
            }

            let estado_movil = document.estado_movil;
            let latitud = toFloat(data[latitude]);
            let longitudV = toFloat(data[longitude]);
            let fecha_servidor = new Date();
            let fecha_gps = (toInteger(data[datetime]) != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date();

            if (latitud === 0 || longitudV === 0) {
              estado_movil = 'E';
              latitud = document.latitud;
              longitudV = document.longitud;
            }

            enviarUnidadUpdatedEstiloGTFRI({
              imei: data[imei],
              unidad_id: document._id,
              _id: document._id,
              latitud,
              longitud: longitudV,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              estado_movil,
              fecha_gps,
              is_atm: (message.includes(ATM) ? 1 : 0),
              cooperativa_id: document.cooperativa_id ? String(document.cooperativa_id).trim() : null
            }, message);

            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: { latitud, longitud: longitudV, estado_movil, velocidad_actual: toFloat(data[speed]), angulo: toInteger(data[angle]), fecha_gps, is_atm: (message.includes(ATM) ? 1 : 0), fecha: fecha_servidor }
              }, { writeConcern: { w: 0 } });
            }

            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTGEO,
              unidad_id: document._id,
              pdi: pdi,
              entrada: inout,
              latitud: latitud,
              longitud: longitudV,
              velocidad: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              altura: toFloat(data[height]),
              fecha_gps: fecha_gps,
              fecha: fecha_servidor,
              fecha_envio: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
              contador_diario: document.contador_diario,
              contador_total: document.contador_total,
              js: true
            }, { writeConcern: { w: 0 } }, function (err) {
              if (!err && !isBuffMessage) actualizarSentidoUnidad(dbTrackingSystem, document, pdi, inout, message);  // ✅
              if (!err && !isBuffMessage && inout === 1) {
                setImmediate(function () {
                  intentarFinalizarDespachoPorIngreso(document, pdi, fecha_gps);
                });
              }
              if (!err) {

                dbTrackingSystem.collection('punto_controls').findOne({ pdi: String(pdi), cooperativa_id: String(document.cooperativa_id) }, function (errPuntoControl, puntoControl) {
                  if (errPuntoControl) console.error('❌ Error buscando punto_controls (GTGEO):', errPuntoControl);
                  const descripcionPuntoControl = (puntoControl && puntoControl.descripcion) ? String(puntoControl.descripcion).trim() : '';
                  const lineaPuntoControl = descripcionPuntoControl ? '* 📍 P. Control:* ' + descripcionPuntoControl + '\n' : '';

                  if (inout === 1) {
                    const fechaHoraTxtGeo = formatFechaGpsParaPush(fecha_gps);
                    const fechaPartesGeo = fechaHoraTxtGeo.split(' ');
                    const fechaTxtGeo = fechaPartesGeo.length > 0 ? fechaPartesGeo[0] : fechaHoraTxtGeo;
                    const horaTxtGeo = fechaPartesGeo.length > 1 ? fechaPartesGeo[1] : '';
                    const unidadTxtGeo = String(document.descripcion).trim();
                    const txtGeofenceEntrada =
                      unidadTxtGeo + ':🚩🟢 Entrada a punto de control\n' +
                      '* 🚍 Vehiculo:* ' + unidadTxtGeo + '\n' +
                      lineaPuntoControl +
                      '* 📅 Fecha:* ' + fechaTxtGeo + '\n' +
                      '* ⏰ Hora:* ' + horaTxtGeo;
                    solicitarNotificacionPushPorImei(data[imei], txtGeofenceEntrada, PUSH_TYPE_GEOFENCE, latitud, longitudV);
                  } else if (inout === 0) {
                    const fechaHoraTxtGeo = formatFechaGpsParaPush(fecha_gps);
                    const fechaPartesGeo = fechaHoraTxtGeo.split(' ');
                    const fechaTxtGeo = fechaPartesGeo.length > 0 ? fechaPartesGeo[0] : fechaHoraTxtGeo;
                    const horaTxtGeo = fechaPartesGeo.length > 1 ? fechaPartesGeo[1] : '';
                    const unidadTxtGeo = String(document.descripcion).trim();
                    const txtGeofenceSalida =
                      unidadTxtGeo + ':🚩🔴 Salida del punto de control\n' +
                      '* 🚍 Vehiculo:* ' + unidadTxtGeo + '\n' +
                      lineaPuntoControl +
                      '* 📅 Fecha:* ' + fechaTxtGeo + '\n' +
                      '* ⏰ Hora:* ' + horaTxtGeo;
                    solicitarNotificacionPushPorImei(data[imei], txtGeofenceSalida, PUSH_TYPE_GEOFENCE, latitud, longitudV);
                  }
                });
              }
            });
          }
        });
      }

      // ================== GPRMC ==================
      else if (message.includes(GPRMC) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let arrayFromEmpty = message.split(' ');
        let arrayGPRMC = message.split('$');
        let deviceEvent = parseInt(arrayFromEmpty[8]);
        let deviceIMEI = arrayFromEmpty[15];
        let mainData = arrayGPRMC[1];
        let mainArray = mainData.split(',');

        let deviceTime = 1;
        let navigationReceiver = 2;
        let latitude = 3;
        let cLatitude = 4;
        let longitude = 5;
        let cLongitude = 6;
        let speed = 7;
        let direction = 8;
        let deviceDate = 9;

        let serverDate = new Date();
        let deviceDatetime = mainArray[deviceDate] + mainArray[deviceTime];

        let socketObject = getSocket(deviceIMEI);
        if (socketObject == null) socketArray.push({ imei: deviceIMEI, socket: socket });
        else socketObject.socket = socket;

        dbTrackingSystem.collection('unidads').findOne({ imei: deviceIMEI, estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else {
            let entrada = 0;
            if (deviceEvent >= 38) {
              let res = deviceEvent % 2;
              if (res === 0) entrada = 1;
            }

            dbTrackingSystem.collection('recorridos').insertOne({
              tipo: GPRMC,
              unidad_id: document._id,
              imei: deviceIMEI,
              evento: deviceEvent,
              entrada: entrada,
              fecha: serverDate,
              fecha_gps: moment(deviceDatetime, GPRMC_DATE_FORMAT).toDate(),
              latitud: getCoordinates(mainArray[latitude], mainArray[cLatitude], true),
              longitud: getCoordinates(mainArray[longitude], mainArray[cLongitude], false),
              velocidad: getSpeed(mainArray[speed]),
              angulo: toFloat(mainArray[direction]),
              estado: mainArray[navigationReceiver]
            }, { writeConcern: { w: 0 } }, function () {
              if (!isBuffMessage) {
                dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                  $set: {
                    latitud: getCoordinates(mainArray[latitude], mainArray[cLatitude], true),
                    longitud: getCoordinates(mainArray[longitude], mainArray[cLongitude], false),
                    velocidad_actual: getSpeed(mainArray[speed]),
                    angulo: toFloat(mainArray[direction]),
                    fecha_gps: moment(deviceDatetime, GPRMC_DATE_FORMAT).toDate(),
                    fecha: serverDate,
                    is_atm: (message.includes(ATM) ? 1 : 0),
                    estado_movil: (getSpeed(mainArray[speed]) > 0) ? 'M' : 'D'
                  }
                }, { writeConcern: { w: 0 } });
              }
            });
          }
        });
      }

      // ================== ADMIN ==================
      else if (message.includes(ADMIN) && !message.includes(ACK)) {
        if (message.includes(GTLOG)) logsAdmin.push(socket);
        else {
          let commandArray = message.split(';');
          let imei = 1;
          let command = 2;
          let clientImei = commandArray[imei];
          let socketObject = getSocket(clientImei);
          let response = `${commandArray[command]}\n`;
          if (socketObject != null && socketObject.socket.writable) {
            socketObject.socket.write(response);
          }
        }
      }

      // ================== GTDIS (PUERTAS) ==================
      else if (message.includes(GTDIS) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);

        let imei = 2;
        let speed = 8;
        let angle = 9;
        let longitude = 11;
        let latitude = 12;
        let datetime = 13;
        let sentTime = 20;
        let indexdoor = 5;

        let data = message.split(',');

        let puerta = '';
        if (toInteger(data[indexdoor]) === 10) puerta = 'PUERTA CERRADA (DELANTERAPR)';
        if (toInteger(data[indexdoor]) === 11) puerta = 'PUERTA ABIERTA (DELANTERAPR)';
        if (toInteger(data[indexdoor]) === 20) puerta = 'PUERTA ABIERTA (DELANTERA)';
        if (toInteger(data[indexdoor]) === 21) puerta = 'PUERTA CERRADA (DELANTERA)';
        if (toInteger(data[indexdoor]) === 30) puerta = 'PUERTA ABIERTA (TRASERA)';
        if (toInteger(data[indexdoor]) === 31) puerta = 'PUERTA CERRADA (TRASERA)';

        dbTrackingSystem.collection('unidads').findOne(
          { imei: data[imei], estado: 'A' },
          function (err, document) {
            if (err || !document) return;

            let latitud = toFloat(data[latitude]);
            let longitudV = toFloat(data[longitude]);
            let fecha_servidor = new Date();
            let fecha_gps = (toInteger(data[datetime]) !== 0)
              ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
              : new Date();

            if (latitud === 0 || longitudV === 0) {
              latitud = document.latitud;
              longitudV = document.longitud;
            }

            if (document.tipo_in1==='bp') {
              const fechaHoraTxtBp = formatFechaGpsParaPush(fecha_gps);
              const fechaPartesBp = fechaHoraTxtBp.split(' ');
              const fechaTxtBp = fechaPartesBp.length > 0 ? fechaPartesBp[0] : fechaHoraTxtBp;
              const horaTxtBp = fechaPartesBp.length > 1 ? fechaPartesBp[1] : '';
              const unidadTxtBp = String(document.descripcion).trim();
              const txtBpanico =
                unidadTxtBp + ':🚨 Botón de pánico\n' +
                '* 🚍 Vehiculo:* ' + unidadTxtBp + '\n' +
                '* ⚡ Evento:* ' + puerta + '\n' +
                '* 📅 Fecha:* ' + fechaTxtBp + '\n' +
                '* ⏰ Hora:* ' + horaTxtBp;
              solicitarNotificacionPushPorImei(data[imei], txtBpanico, PUSH_TYPE_BPANICO, latitud, longitudV);
              enviarALaravelPorWS({
                type: 'unidad.alerta.bpanico',
                unidad_id: document._id,
                _id: document._id,
                imei: document.imei,
                mensaje: unidadTxtBp + ' — ' + document.placa,
                fecha_gps: fecha_gps,
                cooperativa_id: document.cooperativa_id
                  ? String(document.cooperativa_id).trim()
                  : null,
                _raw_message: message
              });
            }

            // Registrar recorrido
            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTDIS,
              unidad_id: document._id,
              velocidad: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              longitud: longitudV,
              latitud: latitud,
              fecha_gps: fecha_gps,
              fecha: fecha_servidor,
              evento: puerta,
              fecha_envio: (toInteger(data[sentTime]) !== 0)
                ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate()
                : new Date(),
              js: true
            }, { writeConcern: { w: 0 } });

            // ================= PUERTA DELANTERA =================
            if (toInteger(data[indexdoor]) === 20 || toInteger(data[indexdoor]) === 21) {

              let fechaPuertaAbierta = null;
              let fechaPuertaCerrada = null;

              if (puerta === 'PUERTA ABIERTA (DELANTERA)') {
                fechaPuertaAbierta = fecha_gps;
              } else if (document.fecha_puerta_abierta !== undefined && document.fecha_puerta_abierta !== null) {
                fechaPuertaAbierta = document.fecha_puerta_abierta;
              }

              if (puerta === 'PUERTA CERRADA (DELANTERA)') {
                fechaPuertaCerrada = fecha_gps;
              } else if (document.fecha_puerta_cerrada !== undefined && document.fecha_puerta_cerrada !== null) {
                fechaPuertaCerrada = document.fecha_puerta_cerrada;
              }

              if (!isBuffMessage) {
                dbTrackingSystem.collection('unidads').updateOne(
                  { _id: document._id },
                  {
                    $set: {
                      puerta: puerta,
                      alerta_puerta_message: puerta,
                      alerta_puerta_fecha: fecha_gps,
                      fecha_puerta_abierta: fechaPuertaAbierta,
                      fecha_puerta_cerrada: fechaPuertaCerrada,
                      is_atm: 0
                    }
                  },
                  { writeConcern: { w: 0 } }
                );
              }

            }
            // ================= PUERTA DELANTERA PR =================
            else if (toInteger(data[indexdoor]) === 10 || toInteger(data[indexdoor]) === 11) {

              let fechaPuertaAbiertaPr = null;
              let fechaPuertaCerradaPr = null;
              let contPdAbierta = toInteger(document.contpdabierta) || 0;
              const esPuertaAbiertaPr = (puerta === 'PUERTA ABIERTA (DELANTERAPR)');

              if (esPuertaAbiertaPr) {
                fechaPuertaAbiertaPr = fecha_gps;
              } else if (document.fecha_puerta_abierta_delanterapr !== undefined && document.fecha_puerta_abierta_delanterapr !== null) {
                fechaPuertaAbiertaPr = document.fecha_puerta_abierta_delanterapr;
              }

              if (puerta === 'PUERTA CERRADA (DELANTERAPR)') {
                fechaPuertaCerradaPr = fecha_gps;
              } else if (document.fecha_puerta_cerrada_delanterapr !== undefined && document.fecha_puerta_cerrada_delanterapr !== null) {
                fechaPuertaCerradaPr = document.fecha_puerta_cerrada_delanterapr;
              }

              if (esPuertaAbiertaPr) {
                contPdAbierta = contPdAbierta + 1;
                dbTrackingSystem.collection('unidads').updateOne(
                  { _id: document._id },
                  { $inc: { contpdabierta: 1 } },
                  { writeConcern: { w: 0 } }
                );
              }

              if (!isBuffMessage) {
                dbTrackingSystem.collection('unidads').updateOne(
                  { _id: document._id },
                  {
                    $set: {
                      puerta_delanterapr: puerta,
                      alerta_puerta_message_delanterapr: puerta,
                      alerta_puerta_fecha_delanterapr: fecha_gps,
                      fecha_puerta_abierta_delanterapr: fechaPuertaAbiertaPr,
                      fecha_puerta_cerrada_delanterapr: fechaPuertaCerradaPr,
                      is_atm: 0
                    }
                  },
                  { writeConcern: { w: 0 } }
                );
              }

              enviarALaravelPorWS({
                type: 'unidad.alerta.puerta',
                unidad_id: document._id,
                imei: document.imei,
                puerta: 'DELANTERAPR',
                estado: puerta,
                fecha: fecha_gps,
                contpdabierta: contPdAbierta,
                cooperativa_id: document.cooperativa_id
                  ? String(document.cooperativa_id).trim()
                  : null,
                _raw_message: message
              });

            }
            // ================= PUERTA TRASERA =================
            else if (toInteger(data[indexdoor]) === 30 || toInteger(data[indexdoor]) === 31) {

              let fechaPuertaAbiertaT = null;
              let fechaPuertaCerradaT = null;

              if (puerta === 'PUERTA ABIERTA (TRASERA)') {
                fechaPuertaAbiertaT = fecha_gps;
              } else if (document.fecha_puerta_abierta_trasera !== undefined && document.fecha_puerta_abierta_trasera !== null) {
                fechaPuertaAbiertaT = document.fecha_puerta_abierta_trasera;
              }

              if (puerta === 'PUERTA CERRADA (TRASERA)') {
                fechaPuertaCerradaT = fecha_gps;
              } else if (document.fecha_puerta_cerrada_trasera !== undefined && document.fecha_puerta_cerrada_trasera !== null) {
                fechaPuertaCerradaT = document.fecha_puerta_cerrada_trasera;
              }

              if (!isBuffMessage) {
                dbTrackingSystem.collection('unidads').updateOne(
                  { _id: document._id },
                  {
                    $set: {
                      puerta_trasera: puerta,
                      alerta_puerta_message_trasera: puerta,
                      alerta_puerta_fecha_trasera: fecha_gps,
                      fecha_puerta_abierta_trasera: fechaPuertaAbiertaT,
                      fecha_puerta_cerrada_trasera: fechaPuertaCerradaT,
                      is_atm: 0
                    }
                  },
                  { writeConcern: { w: 0 } }
                );
              }

            }
          }
        );
      }
      // ================== GTIGN / GTIGF ==================

      else if (message.includes(GTIGN) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let data = message.split(',');
        let speed = 6;
        let angle = 7;
        let height = 8;
        let longitude = 9;
        let latitude = 10;
        let datetime = 11;
        let fechaGPS = toInteger(data[datetime]);
        let fecha_gps = (fechaGPS != 0)
        ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
        : new Date();
        dbTrackingSystem.collection('unidads').findOne(
          { imei: data[imei], estado: 'A' },
          function (err, unidad) {
            if (err || !unidad) return;

            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne(
                { _id: unidad._id },
                {
                  $set: {
                    ignicionf: 'on',
                    fecha_gps: fecha_gps,
                    latitud: toFloat(data[latitude]),
                    longitud: toFloat(data[longitude]),
                    velocidad_actual: toFloat(data[speed]),
                    altura: toFloat(data[height]),
                    angulo: toInteger(data[angle]),
                    fecha: new Date()
                  }
                },
                { writeConcern: { w: 0 } },
                function (uErr) { if (uErr) console.log(uErr); }
              );

              // Persistir ignicionf='on' en el cache para que los GTFRI posteriores lo incluyan
              buildUnidadPayloadRealtime({ imei: unidad.imei, _id: unidad._id, ignicionf: 'on' });
            }

            

            enviarALaravelPorWS({
              type: 'unidad.ignicion',
              unidad_id: unidad._id,
              _id: unidad._id,
              imei: unidad.imei,
              ignicionf: 'on',
              latitud: toFloat(data[latitude]),
              longitud: toFloat(data[longitude]),
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              fecha_gps: fecha_gps,
              fecha: new Date(),
              cooperativa_id: unidad.cooperativa_id ? String(unidad.cooperativa_id).trim() : null,
              _raw_message: message
            });

            if (!isBuffMessage && String(unidad.vigilante || '').trim() === 'on') {
              escribirLineaComandoAlGps(String(data[imei] || unidad.imei || '').trim(), CMD_VIGILANTE_AT_GTOUT);
            }
            

            const lat = toFloat(data[latitude]);
            const lng = toFloat(data[longitude]);
            const fechaHoraTxtEnc = formatFechaGpsParaPush(fecha_gps);
            const fechaPartesEnc = fechaHoraTxtEnc.split(' ');
            const fechaTxtEnc = fechaPartesEnc.length > 0 ? fechaPartesEnc[0] : fechaHoraTxtEnc;
            const horaTxtEnc = fechaPartesEnc.length > 1 ? fechaPartesEnc[1] : '';
            const unidadTxtEnc = String(unidad.descripcion).trim();
            const txtIgnEncendida =
              unidadTxtEnc + ':🟢 Ignicion ON\n' +
              '* 🚍 Vehiculo:* ' + unidadTxtEnc + '\n' +
              '* 📅 Fecha:* ' + fechaTxtEnc + '\n' +
              '* ⏰ Hora:* ' + horaTxtEnc;
            solicitarNotificacionPushPorImei(data[imei], txtIgnEncendida, PUSH_TYPE_IGN, lat, lng);
          }
        );
      }

      else if (message.includes(GTIGF) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let data = message.split(',');
        let speed = 6;
        let angle = 7;
        let height = 8;
        let longitude = 9;
        let latitude = 10;
        let datetime = 11;
        let fechaGPS = toInteger(data[datetime]);
        let fecha_gps = (fechaGPS != 0)
        ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
        : new Date();
        dbTrackingSystem.collection('unidads').findOne(
          { imei: data[imei], estado: 'A' },
          function (err, unidad) {
            if (err || !unidad) return;

            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne(
                { _id: unidad._id },
                {
                  $set: {
                    ignicionf: 'off',
                    fecha_gps: fecha_gps,
                    latitud: toFloat(data[latitude]),
                    longitud: toFloat(data[longitude]),
                    velocidad_actual: toFloat(data[speed]),
                    altura: toFloat(data[height]),
                    angulo: toInteger(data[angle]),
                    fecha: new Date()
                  }
                },
                { writeConcern: { w: 0 } },
                function (uErr) { if (uErr) console.log(uErr); }
              );

              // Persistir ignicionf='off' en el cache para que los GTFRI posteriores lo incluyan
              buildUnidadPayloadRealtime({ imei: unidad.imei, _id: unidad._id, ignicionf: 'off' });
            }

            enviarALaravelPorWS({
              type: 'unidad.ignicion',
              unidad_id: unidad._id,
              _id: unidad._id,
              imei: unidad.imei,
              ignicionf: 'off',
              latitud: toFloat(data[latitude]),
              longitud: toFloat(data[longitude]),
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              fecha_gps: fecha_gps,
              fecha: new Date(),
              cooperativa_id: unidad.cooperativa_id ? String(unidad.cooperativa_id).trim() : null,
              _raw_message: message
            });

            const lat = toFloat(data[latitude]);
            const lng = toFloat(data[longitude]);
            const fechaHoraTxtApag = formatFechaGpsParaPush(fecha_gps);
            const fechaPartesApag = fechaHoraTxtApag.split(' ');
            const fechaTxtApag = fechaPartesApag.length > 0 ? fechaPartesApag[0] : fechaHoraTxtApag;
            const horaTxtApag = fechaPartesApag.length > 1 ? fechaPartesApag[1] : '';
            const unidadTxtApag = String(unidad.descripcion).trim();
            const txtIgnApagada =
              unidadTxtApag + ':🔴 Ignicion OFF\n' +
              '* 🚍 Vehiculo:* ' + unidadTxtApag + '\n' +
              '* 📅 Fecha:* ' + fechaTxtApag + '\n' +
              '* ⏰ Hora:* ' + horaTxtApag;
            solicitarNotificacionPushPorImei(data[imei], txtIgnApagada, PUSH_TYPE_IGN, lat, lng);
          }
        );
      }


      // ================== GTMPF / GTMPN ==================
      else if (message.includes(GTMPF) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let data = message.split(',');
        let speed = 5;
        let angle = 6;
        let height = 7;
        let longitude = 8;
        let latitude = 9;
        let datetime = 10;
        let fechaGPS = toInteger(data[datetime]);

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let fecha_gps = (fechaGPS != 0)
            ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
            : new Date();

            const now = new Date();
            const lat = toFloat(data[latitude]);
            const lng = toFloat(data[longitude]);

            // ── Resetear tiempo_power a 24h fijas ──
            // Cada GTMPF reinicia el contador a 24 horas (no acumula)
            const nuevoTiempoPower = 24;

            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTIGF,
              fecha_gps: fecha_gps,
              latitud: lat,
              longitud: lng,
              velocidad: toFloat(data[speed]),
              altura: toFloat(data[height]),
              angulo: toInteger(data[angle]),
              unidad_id: document._id,
              fecha: now,
              js: true
            }, { writeConcern: { w: 0 } });

            // Actualizar coordenadas, fechas y alerta en la unidad
            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: {
                  latitud: lat,
                  longitud: lng,
                  velocidad_actual: toFloat(data[speed]),
                  angulo: toInteger(data[angle]),
                  fecha_gps: fecha_gps,
                  fecha: now,
                  power: 'off',
                  tiempo_power: nuevoTiempoPower,
                  tiempo_power_update: now,
                  alerta_desconx_message: 'Dispositivo GPS apagado',
                  alerta_desconx_fecha: fecha_gps
                }
              }, { writeConcern: { w: 0 } });

              // Persistir en el cache
              buildUnidadPayloadRealtime({ imei: document.imei, _id: document._id, power: 'off', tiempo_power: nuevoTiempoPower, tiempo_power_update: now });
            }

            // Publicar evento SSE
            enviarALaravelPorWS({
              type: 'unidad.power',
              unidad_id: document._id,
              _id: document._id,
              imei: document.imei,
              power: 'off',
              tiempo_power: nuevoTiempoPower,
              tiempo_power_update: now,
              latitud: lat,
              longitud: lng,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              fecha_gps: fecha_gps,
              fecha: now,
              cooperativa_id: document.cooperativa_id ? String(document.cooperativa_id).trim() : null,
              _raw_message: message
            });
            const fechaHoraTxtApag = formatFechaGpsParaPush(fecha_gps);
            const fechaPartesApag = fechaHoraTxtApag.split(' ');
            const fechaTxtApag = fechaPartesApag.length > 0 ? fechaPartesApag[0] : fechaHoraTxtApag;
            const horaTxtApag = fechaPartesApag.length > 1 ? fechaPartesApag[1] : '';
            const unidadTxtApag = String(document.descripcion).trim();
            const txtApagada =
              unidadTxtApag + ':🔴🔌 Dispositivo GPS sin conexion de energia\n' +
              '* 🚍 Vehiculo:* ' + unidadTxtApag + '\n' +
              '* 📅 Fecha:* ' + fechaTxtApag + '\n' +
              '* ⏰ Hora:* ' + horaTxtApag;
            solicitarNotificacionPushPorImei(data[imei], txtApagada, PUSH_TYPE_ONOFF, lat, lng);
          }
        });
      }

      else if (message.includes(GTMPN) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let data = message.split(',');
        let speed = 5;
        let angle = 6;
        let height = 7;
        let longitude = 8;
        let latitude = 9;
        let datetime = 10;
        let fechaGPS = toInteger(data[datetime]);

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let fecha_gps = (fechaGPS != 0)
            ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
            : new Date();
            const now = new Date();
            const lat = toFloat(data[latitude]);
            const lng = toFloat(data[longitude]);

            // ── Resetear tiempo_power a 24h fijas ──
            // Cada GTMPN reinicia el contador a 24 horas (no acumula)
            const nuevoTiempoPower = 24;

            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTIGN,
              fecha_gps: fecha_gps,
              latitud: lat,
              longitud: lng,
              velocidad: toFloat(data[speed]),
              altura: toFloat(data[height]),
              angulo: toInteger(data[angle]),
              unidad_id: document._id,
              fecha: now,
              js: true
            }, { writeConcern: { w: 0 } });

            // Actualizar coordenadas, fechas y alerta en la unidad
            if (!isBuffMessage) {
              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: {
                  latitud: lat,
                  longitud: lng,
                  velocidad_actual: toFloat(data[speed]),
                  angulo: toInteger(data[angle]),
                  fecha_gps: fecha_gps,
                  fecha: now,
                  power: 'on',
                  tiempo_power: nuevoTiempoPower,
                  tiempo_power_update: now,
                  alerta_desconx_message: 'Dispositivo GPS encendido',
                  alerta_desconx_fecha: fecha_gps
                }
              }, { writeConcern: { w: 0 } });

              // Persistir en el cache
              buildUnidadPayloadRealtime({ imei: document.imei, _id: document._id, power: 'on', tiempo_power: nuevoTiempoPower, tiempo_power_update: now });
            }

            // Publicar evento SSE
            enviarALaravelPorWS({
              type: 'unidad.power',
              unidad_id: document._id,
              _id: document._id,
              imei: document.imei,
              power: 'on',
              tiempo_power: nuevoTiempoPower,
              tiempo_power_update: now,
              latitud: lat,
              longitud: lng,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              fecha_gps: fecha_gps,
              fecha: now,
              cooperativa_id: document.cooperativa_id ? String(document.cooperativa_id).trim() : null,
              _raw_message: message
            });

            const fechaHoraTxtEnc = formatFechaGpsParaPush(fecha_gps);
            const fechaPartesEnc = fechaHoraTxtEnc.split(' ');
            const fechaTxtEnc = fechaPartesEnc.length > 0 ? fechaPartesEnc[0] : fechaHoraTxtEnc;
            const horaTxtEnc = fechaPartesEnc.length > 1 ? fechaPartesEnc[1] : '';
            const unidadTxtEnc = String(document.descripcion).trim();
            const txtEncendida =
              unidadTxtEnc + ':🟢🔌 Dispositivo GPS con conexion de energia\n' +
              '* 🚍 Vehiculo:* ' + unidadTxtEnc + '\n' +
              '* 📅 Fecha:* ' + fechaTxtEnc + '\n' +
              '* ⏰ Hora:* ' + horaTxtEnc;
            solicitarNotificacionPushPorImei(data[imei], txtEncendida, PUSH_TYPE_ONOFF, lat, lng);
          }
        });
      }

      // ================== GTDTT (contadores) ==================
      else if (message.includes(GTDTT) && !message.includes(ACK)) {
        const isBuffMessage = message.includes(BUFF);
        let imei = 2;
        let count = 8;
        let sentTime = 9;
        let type = 7;
        let data = message.split(',');

        const eventType = toInteger(data[type]);
        // IGNORAR evento 17: viene invertido y genera inconsistencias
        if (eventType == 17) {
          return;
        }

        // Techo 7 dígitos y módulo de rollover en este mensaje (distinto a GTDAT 65535/999999).
        const MAX_COUNT = message.includes(GTDTTDGT)? 99999999: 9999999;
        const MAX_INCREMENTO = 100;

        const lecturaGtdttInvalida = function (v) {
          if (!Number.isFinite(v) || v < 0) return true;
          if (v > MAX_COUNT) return true;
          if (v === MAX_COUNT) return true;
          return false;
        };
        const diarioGtdttFueraDeRango = function (d) {
          if (!Number.isFinite(d) || d < 0) return true;
          if (d >= MAX_COUNT) return true;
          return false;
        };

        /**
         * Reinicio manual desde panel (resetConteo): total, inicial y diario quedan en 0.
         * También cubre unidad/sensor sin baseline (inicial null) con total en 0.
         */
        const reinicioManualSensor = function (total, inicial, diario) {
          const t = toInteger(total);
          const d = toInteger(diario);
          if (t !== 0 || d !== 0) return false;
          if (inicial == null || inicial === '') return true;
          return toInteger(inicial) === 0;
        };

        /**
         * Salto imposible entre dos lecturas consecutivas del total GPS.
         * Se omite tras reinicio manual o cuando el total anterior es 0 (primera lectura).
         * Si current < prev se interpreta como rollover/reinicio del equipo.
         */
        const saltoImposible = function (prev, current, esReinicioManual) {
          if (esReinicioManual) return false;
          if (!Number.isFinite(prev) || prev <= 0) return false;
          if (!Number.isFinite(current) || current < 0) return true;
          if (current < prev) return false;
          return (current - prev) > MAX_INCREMENTO;
        };

        /** Calcula contador_inicial y contador_diario para un sensor GTDTT. */
        const calcularContadoresSensorGtdtt = function (countActual, contadorInicialBd, esReinicioManual) {
          let contador_inicial = contadorInicialBd;
          let contador_diario = 0;

          if (esReinicioManual) {
            return { contador_inicial: countActual, contador_diario: 0 };
          }

          if (contador_inicial != null && contador_inicial !== '') {
            if (toInteger(contador_inicial) > 0) {
              if (countActual < toInteger(contador_inicial)) {
                // Rollover o reinicio del equipo: total GPS menor que baseline del día.
                contador_inicial = countActual;
                contador_diario = 0;
              } else {
                contador_diario = countActual - toInteger(contador_inicial);
                if (contador_diario < 0) contador_diario = countActual + MAX_COUNT;
              }
            } else {
              contador_inicial = countActual;
            }
          } else {
            contador_inicial = countActual;
          }

          return { contador_inicial, contador_diario };
        };

        /**
         * Resuelve el estado final (total/inicial/diario) de UN sensor GTDTT sin abortar el mensaje.
         * Cada sensor es independiente: lo que le pase a uno no bloquea a los otros.
         * - Lectura fuera de rango: se conserva el valor anterior de ese sensor.
         * - Reinicio manual / salto imposible / diario incoherente: se re-basa al valor nuevo de la trama.
         * - Normal: total = valor nuevo, inicial se mantiene, diario = valor nuevo - inicial.
         */
        const resolverSensorGtdtt = function (countActual, prevTotal, prevInicial, prevDiario, esReinicioManual) {
          const anteriorTotal = toInteger(prevTotal);

          // Lectura fuera de rango: no escribimos basura, mantenemos lo que ya había en este sensor.
          if (lecturaGtdttInvalida(countActual)) {
            return { total: anteriorTotal, inicial: toInteger(prevInicial), diario: toInteger(prevDiario) };
          }

          // Reinicio manual o salto imposible: re-basar al valor que trae la trama.
          if (esReinicioManual || saltoImposible(anteriorTotal, countActual, esReinicioManual)) {
            return { total: countActual, inicial: countActual, diario: 0 };
          }

          const calc = calcularContadoresSensorGtdtt(countActual, prevInicial, false);
          if (diarioGtdttFueraDeRango(calc.contador_diario)) {
            // Diario incoherente respecto al baseline previo: re-basar.
            return { total: countActual, inicial: countActual, diario: 0 };
          }
          return { total: countActual, inicial: calc.contador_inicial, diario: calc.contador_diario };
        };

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let count_sensor_1 = 0;
            let count_sensor_2 = 0;
            let count_sensor_3 = 0;

            if (message.includes(GTDTTDGT)) {
              count = 9;
              sentTime = 10;
              const count_parse = String(data[count] || '')
                .replace(/\n/g, "")
                .replace(/\r/g, "")
                .replace(/ /g, "")
                .trim();
              const m = /^(\d{8})(\d{3})(\d{5})(?:\d{8})?$/.exec(count_parse);
              if (m) {
                count_sensor_1 = toInteger(m[1]);
                count_sensor_2 = toInteger(m[3]);
              } else {
                return;
              }
            } else {
              let count_parse = String(data[count] || '')
                .replace("RSC", "")
                .replace(/\n/g, "")
                .replace(/\r/g, "")
                .replace(/ /g, "")
                .trim();
              if (count_parse.length < 14) {
                return;
              }
              const s1 = count_parse.slice(0, 7);
              const s2 = count_parse.slice(7, 14);
              let s3 = "0";
              if (count_parse.length >= 21) {
                s3 = count_parse.slice(14, 21);
              }
              if (!s1 || !s2 || !s3 || s1.length < 7 || s2.length < 7) {
                return;
              }
              if (!/^\d+$/.test(s1) || !/^\d+$/.test(s2) || !/^\d+$/.test(s3)) {
                return;
              }
              count_sensor_1 = toInteger(s1);
              count_sensor_2 = toInteger(s2);
              count_sensor_3 = toInteger(s3);
            }


            if (!isBuffMessage) {

              const reinicioS1 = reinicioManualSensor(document.contador_total, document.contador_inicial, document.contador_diario);
              const reinicioS2 = reinicioManualSensor(document.contador_total_sensor_2, document.contador_inicial_sensor_2, document.contador_diario_sensor_2);
              const reinicioS3 = reinicioManualSensor(document.contador_total_sensor_3, document.contador_inicial_sensor_3, document.contador_diario_sensor_3);

              // Cada sensor se resuelve por separado: un problema en uno no bloquea a los otros
              // ni descarta el mensaje. En salto imposible se re-basa al valor nuevo de la trama.
              const s1 = resolverSensorGtdtt(count_sensor_1, document.contador_total, document.contador_inicial, document.contador_diario, reinicioS1);
              const s2 = resolverSensorGtdtt(count_sensor_2, document.contador_total_sensor_2, document.contador_inicial_sensor_2, document.contador_diario_sensor_2, reinicioS2);
              const s3 = resolverSensorGtdtt(count_sensor_3, document.contador_total_sensor_3, document.contador_inicial_sensor_3, document.contador_diario_sensor_3, reinicioS3);

             

              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: {
                  contador_total: s1.total,
                  contador_diario: s1.diario,
                  contador_inicial: s1.inicial,
                  contador_total_sensor_2: s2.total,
                  contador_diario_sensor_2: s2.diario,
                  contador_inicial_sensor_2: s2.inicial,
                  contador_total_sensor_3: s3.total,
                  contador_diario_sensor_3: s3.diario,
                  contador_inicial_sensor_3: s3.inicial,
                  is_atm: (message.includes(ATM) ? 1 : 0)
                }
              }, { writeConcern: { w: 0 } });
            }

            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTDAT,
              unidad_id: document._id,
              velocidad: document.velocidad_actual,
              angulo: document.angulo,
              longitud: document.longitud,
              latitud: document.latitud,
              fecha_gps: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
              fecha: new Date(),
              contador_total: count_sensor_1,
              contador_total_sensor_2: count_sensor_2,
              contador_total_sensor_3: count_sensor_3,
              trama: message
            }, { writeConcern: { w: 0 } });
          }
        });
      }

      // ================== GTRTL ==================

      else if (message.includes(GTRTL) && !message.includes(ACK)) {

        let imei = 2;
        let data = message.split(',');
        let speed = 8;
        let angle = 9;
        let height = 10;
        let longitude = 11;
        let latitude = 12;
        let datetime = 13;

        let fechaGPS = toInteger(data[datetime]);

        let lat = toFloat(data[latitude]);
        let lng = toFloat(data[longitude]);

        let fecha_gps = fechaGpsFromGpsField(data[datetime], fechaGPS);

        dbTrackingSystem.collection('unidads').findOne(
          { imei: data[imei], estado: 'A' },
          function (err, unidad) {

            if (err || !unidad) return;

            // ================= ENVIAR AL FRONT =================
            const gpsDataLocation = {
              type: 'unidad.location',
              unidad_id: unidad._id,
              _id: unidad._id,
              imei: data[imei],
              cooperativa_id: unidad.cooperativa_id ? String(unidad.cooperativa_id) : null,
              latitud: lat,
              longitud: lng,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              altura: toFloat(data[height]),
              fecha_gps: fecha_gps,
              _raw_message: message
            };

            const unidadLocation = buildUnidadPayloadRealtime(gpsDataLocation);
            enviarALaravelPorWS(unidadLocation);

          }
        );
      }

      // ================== GTSPD ==================

      else if (message.includes(GTSPD) && !message.includes(ACK)) {

        let imei = 2;
        let data = message.split(',');
        let speed = 8;
        let angle = 9;
        let height = 10;
        let longitude = 11;
        let latitude = 12;
        let datetime = 13;

        let fechaGPS = toInteger(data[datetime]);

        let lat = toFloat(data[latitude]);
        let lng = toFloat(data[longitude]);

        let fecha_gps = (fechaGPS != 0)
          ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
          : new Date();

        dbTrackingSystem.collection('unidads').findOne(
          { imei: data[imei], estado: 'A' },
          function (err, unidad) {

            if (err || !unidad) return;

            enviarUnidadUpdatedEstiloGTFRI({
              imei: data[imei],
              unidad_id: unidad._id,
              _id: unidad._id,
              latitud: lat,
              longitud: lng,
              velocidad_actual: toFloat(data[speed]),
              angulo: toInteger(data[angle]),
              estado_movil: unidad.estado_movil,
              fecha_gps,
              is_atm: (message.includes(ATM) ? 1 : 0),
              cooperativa_id: unidad.cooperativa_id ? String(unidad.cooperativa_id).trim() : null
            }, message);

            const fechaHoraTxtEV = formatFechaGpsParaPush(fecha_gps);
            const fechaPartesEV = fechaHoraTxtEV.split(' ');
            const fechaTxtEV = fechaPartesEV.length > 0 ? fechaPartesEV[0] : fechaHoraTxtEV;
            const horaTxtEV = fechaPartesEV.length > 1 ? fechaPartesEV[1] : '';
            const unidadTxtEV = String(unidad.descripcion).trim();
            const velocidadTxtEV = toFloat(data[speed]);
            const txtExcesoVelocidad =
              unidadTxtEV + ':⚠️ Exceso de velocidad \n' +
              '* 🚍 Vehiculo:* ' + unidadTxtEV + '\n' +
              '* 🏎️ Velocidad:* ' + velocidadTxtEV + ' km/h\n' +
              '* 📅 Fecha:* ' + fechaTxtEV + '\n' +
              '* ⏰ Hora:* ' + horaTxtEV;
            solicitarNotificacionPushPorImei(data[imei], txtExcesoVelocidad, PUSH_TYPE_OVERSPEED, lat, lng);

          }
        );
      }



      // ================== DEFAULT: siempre registrar TRAMA (BATCH) ==================
      // 🔥 Esto antes era insertOne por cada mensaje, ahora buffer + insertMany.
      TRAMAS_BUFFER.push({
        contenido: message,
        visto: false,
        created_at: new Date(),
        updated_at: new Date()
      });

      // flush rápido si se llenó
      if (TRAMAS_BUFFER.length >= TRAMAS_BATCH_MAX) flushTramas();

    } catch (err) {
      // log error
      try {
        dbTrackingSystem.collection('LogError').insertOne({
          Trama: message,
          Error: err.message
        }, { writeConcern: { w: 0 } });
      } catch (e) { }
      console.log(err);
    }
  });

  socket.on('error', (error) => {
    if (debug) console.log(error);
  });
}