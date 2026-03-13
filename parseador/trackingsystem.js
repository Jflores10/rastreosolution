#!/usr/bin/env node
'use strict';

/**
 * trackingsystem.js (OPTIMIZADO para SSE rápido y fluido)
 *
 * ✅ Cambios clave:
 * 1) SSE/Redis publish se hace INMEDIATO tras actualizar unidad (GTFRI)
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
const ObjectID = require('mongodb').ObjectID;
const { ObjectId } = require('mongodb');
const MongoClient = require('mongodb').MongoClient;

const WebSocket = require('ws');

// Redis publisher
const redis = require('redis');
const redisPub = redis.createClient();
redisPub.on('error', err => console.error('Redis PUB Error (parser):', err));

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
const GTLOG = 'GTLOG';
const GTGOT = 'GTGOT';
const GTGIN = 'GTGIN';
const GTRTL = 'GTRTL';

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
  try { delete cacheCopy._raw_message; } catch (e) {}
  unidadStateCache.set(key, cacheCopy);

  return payload;
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

// ===================== HELPERS =====================
function enviarALaravelPorWS(data, opts = {}) {
  try {
    if (!data) return;
    // If the original raw TCP message included the BUFF marker, avoid
    // publishing to Redis / XADD. Callers inside onClientConnected attach
    // the original raw message as `data._raw_message` so we can make the
    // decision here without changing behavior for other callers that don't
    // supply that field (eg. actualizarSentidoUnidad).
    try {
      if (data._raw_message && String(data._raw_message).includes(BUFF)) {
        return;
      }
    } catch (e) {
    }

    const now = Date.now();

    // 1) EVENTOS (NO TRACKING)
    if (data.type && data.type !== 'unidad.updated') {
      const coopEvt = String(data.cooperativa_id || '').trim();
      if (!coopEvt) return;

      const eventPayload = { ...data, _ts_sent: now };

      redisPub.publish('gps-channel', JSON.stringify(eventPayload), err => {
        if (err) console.error('❌ Redis event publish error:', err);
      });

      // opcional durable stream
      try {
        redisPub.send_command('XADD', ['gps-stream', '*', 'data', JSON.stringify(eventPayload)], (sxErr) => {
          if (sxErr) console.error('❌ Error XADD gps-stream (event):', sxErr);
        });
      } catch (e) {
        console.error('❌ Excepción XADD event:', e);
      }
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
      const prev = lastSentByUnit.get(key);
      const lat = Number(data.latitud);
      const lng = Number(data.longitud);

      const moved = !prev || prev.lat !== lat || prev.lng !== lng;

      if (!opts.force && !moved && prev && (now - prev.ts) < MIN_SEND_MS) return;

      lastSentByUnit.set(key, { ts: now, lat, lng });

      const trackingPayload = { ...data, cooperativa_id: coop, _ts_sent: now };

      redisPub.publish('gps-channel', JSON.stringify(trackingPayload), err => {
        if (err) console.error('❌ Redis tracking publish error:', err);
      });

      // opcional durable stream
      try {
        redisPub.send_command('XADD', ['gps-stream', '*', 'data', JSON.stringify(trackingPayload)], (sxErr) => {
          if (sxErr) console.error('❌ Error XADD gps-stream (tracking):', sxErr);
        });
      } catch (e) {
        console.error('❌ Excepción XADD tracking:', e);
      }
    }

  } catch (err) {
    console.error('❌ Error en enviarALaravelPorWS:', err);
  }
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
function actualizarSentidoUnidad(dbTrackingSystem, unidad, pdiActual, entrada, cb) {
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
                  cooperativa_id: (unidad.cooperativa_id ? String(unidad.cooperativa_id) : null)
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
            const fechaGPS = toInteger(data[idx.datetime]);
           
            // ===================== DATOS NUEVOS (GPS) =====================
            const gpsData = {
                type: 'unidad.updated',
                imei: data[idx.imei],
                latitud: toFloat(data[idx.latitude]),
                longitud: toFloat(data[idx.longitude]),
                voltaje: toFloat(data[idx.voltage]),
                velocidad_actual: toFloat(data[idx.speed]),
                bateria: toFloat(data[idx.battery]),
                mileage: toDecimalHex(data[idx.mileage]),
                angulo: toInteger(data[idx.angle]),
                estado_movil: (toInteger(data[idx.status]) >= 420000) ? 'M' : 'D',
                fecha_gps: (fechaGPS !== 0)
                    ? moment(data[idx.datetime], DEVICE_DATE_FORMAT).toDate()
                    : now,
                fecha: now,
                is_atm: (message.includes(ATM) ? 1 : 0),
                _raw_message:message 
            };

            // ===================== PAYLOAD COMPLETO (CACHE + GPS) =====================
            const unidadPayload = buildUnidadPayloadRealtime(gpsData);

            // 🔥🔥🔥 ENVIAR AL FRONT INMEDIATO
            enviarALaravelPorWS(unidadPayload);

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
                        fecha: now
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
                      enviarALaravelPorWS(unidadFromDbPayload, { force: true });
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

                        } catch (e) {}
                    });

                }
            );
        }

      // ================== GTDAT ==================
      else if (message.includes(GTDAT) && !message.includes(ACK)) {
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

        let puerta1, puerta2, puerta3;

        if (data[flag] === '>PC' || data[flag] === '>PC3') {
          if (data[deviceName] === 'P1' || data[deviceName] === 'P2' || data[deviceName] === 'P3') {
            dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
              if (err) console.log(err);
              else if (document) {
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
                      if (deviceName === 'C1') contador_diario = (toInteger(data[count])) + MAX_COUNT;
                      else contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;
                    }
                  } else contador_inicial = toInteger(data[count]);
                } else contador_inicial = toInteger(data[count]);

                if (contador_diario >= contador_diario_anterior) {
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
                  let fecha_gps = (toInteger(data[fechaSend]) != 0) ? moment(data[fechaSend], DEVICE_DATE_FORMAT).toDate() : new Date();
                  let puerta_1 = parseInt(data[count]);
                  let puerta_2 = parseInt(data[p2]);
                  let puerta_3 = parseInt(data[p3]);
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
                  let contador_diario = 0;
                  let contador_inicial = document.contador_inicial;

                  if (document.contador_inicial != null) {
                    if (document.contador_inicial > 0) {
                      contador_diario = (toInteger(data[count])) - document.contador_inicial;
                      if (contador_diario < 0) {
                        if (deviceName === 'C1') contador_diario = (toInteger(data[count])) + MAX_COUNT;
                        else contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;
                      }
                    } else contador_inicial = toInteger(data[count]);
                  } else contador_inicial = toInteger(data[count]);

                  if (contador_diario >= document.contador_diario) {
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

      // ================== GTGOT / GTGIN ==================
      else if (!message.includes(ADMIN) && (message.includes(GTGOT) || message.includes(GTGIN)) && !message.includes(ACK)) {
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

            dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
              $set: { latitud: latitud, longitud: longitudV, estado_movil, velocidad_actual: toFloat(data[speed]), angulo: toInteger(data[angle]), fecha_gps, is_atm: (message.includes(ATM) ? 1 : 0), fecha: fecha_servidor }
            }, { writeConcern: { w: 0 } });

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
              if (!err) actualizarSentidoUnidad(dbTrackingSystem, document, pdi, entrada);
            });
          }
        });
      }

      // ================== GTGEO ==================
      else if (!message.includes(ADMIN) && message.includes(GTGEO) && !message.includes(ACK)) {
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

            dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
              $set: { latitud, longitud: longitudV, estado_movil, velocidad_actual: toFloat(data[speed]), angulo: toInteger(data[angle]), fecha_gps, is_atm: (message.includes(ATM) ? 1 : 0), fecha: fecha_servidor }
            }, { writeConcern: { w: 0 } });

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
              if (!err) actualizarSentidoUnidad(dbTrackingSystem, document, pdi, inout);
            });
          }
        });
      }

      // ================== GPRMC ==================
      else if (message.includes(GPRMC) && !message.includes(ACK)) {
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

                        enviarALaravelPorWS({
                            type: 'unidad.alerta.puerta',
                            unidad_id: document._id,
                            imei: document.imei,
                            puerta: 'DELANTERA',
                            estado: puerta,
                            fecha: fecha_gps,
                            cooperativa_id: document.cooperativa_id
                                ? String(document.cooperativa_id).trim()
                                : null,
                            _raw_message:message
                        });

                    }
                    // ================= PUERTA TRASERA =================
                    else {

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

                        enviarALaravelPorWS({
                            type: 'unidad.alerta.puerta',
                            unidad_id: document._id,
                            imei: document.imei,
                            puerta: 'TRASERA',
                            estado: puerta,
                            fecha: fecha_gps,
                            cooperativa_id: document.cooperativa_id
                                ? String(document.cooperativa_id).trim()
                                : null,
                            _raw_message:message
                        });
                    }
                }
            );
        }
      // ================== GTIGN / GTIGF ==================

        else if (message.includes(GTIGN) && !message.includes(ACK)) {
            let imei = 2;
            let data = message.split(',');
            let speed = 6;
            let angle = 7;
            let height = 8;
            let longitude = 9;
            let latitude = 10;
            let datetime = 11;
            let fechaGPS = toInteger(data[datetime]);


            dbTrackingSystem.collection('unidads').updateOne(
                { imei: data[imei], estado: 'A' },
                { $set: { 
                    ignicionf: 'on',
                    fecha_gps: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date(),
                    latitud: toFloat(data[latitude]),
                    longitud: toFloat(data[longitude]),
                    velocidad: toFloat(data[speed]),
                    altura: toFloat(data[height]),
                    angulo: toInteger(data[angle]),
                    fecha: new Date(),
                } },
                { writeConcern: { w: 0 } },
                function(err, result) {
                    if (err) console.log(err);
                }
            );
        }

        else if (message.includes(GTIGF) && !message.includes(ACK)) {
            let imei = 2;
            let data = message.split(',');
            let speed = 6;
            let angle = 7;
            let height = 8;
            let longitude = 9;
            let latitude = 10;
            let datetime = 11;
            let fechaGPS = toInteger(data[datetime]);
           

            dbTrackingSystem.collection('unidads').updateOne(
                { imei: data[imei], estado: 'A' },
                { $set: { 
                    ignicionf: 'off',
                    fecha_gps: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date(),
                    latitud: toFloat(data[latitude]),
                    longitud: toFloat(data[longitude]),
                    velocidad: toFloat(data[speed]),
                    altura: toFloat(data[height]),
                    angulo: toInteger(data[angle]),
                    fecha: new Date(),
                } },
                { writeConcern: { w: 0 } },
                function(err, result) {
                    if (err) console.log(err);
                }
            );
        }


      // ================== GTMPF / GTMPN ==================
      else if (message.includes(GTMPF) && !message.includes(ACK)) {
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
            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTIGF,
              fecha_gps: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date(),
              latitud: toFloat(data[latitude]),
              longitud: toFloat(data[longitude]),
              velocidad: toFloat(data[speed]),
              altura: toFloat(data[height]),
              angulo: toInteger(data[angle]),
              unidad_id: document._id,
              fecha: (document.fecha != null) ? new Date(document.fecha) : new Date(),
              js: true
            }, { writeConcern: { w: 0 } });

            dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
              $set: { alerta_desconx_message: 'Dispositivo GPS apagado ', alerta_desconx_fecha: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date() }
            }, { writeConcern: { w: 0 } });
          }
        });
      }

      else if (message.includes(GTMPN) && !message.includes(ACK)) {
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
            dbTrackingSystem.collection('recorridos').insertOne({
              imei: data[imei],
              tipo: GTIGN,
              fecha_gps: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date(),
              latitud: toFloat(data[latitude]),
              longitud: toFloat(data[longitude]),
              velocidad: toFloat(data[speed]),
              altura: toFloat(data[height]),
              angulo: toInteger(data[angle]),
              unidad_id: document._id,
              fecha: (document.fecha != null) ? new Date(document.fecha) : new Date(),
              js: true
            }, { writeConcern: { w: 0 } });

            dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
              $set: { alerta_desconx_message: 'Dispositivo GPS encendido ', alerta_desconx_fecha: (fechaGPS != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date() }
            }, { writeConcern: { w: 0 } });
          }
        });
      }

      // ================== GTDTT (contadores) ==================
      else if (message.includes(GTDTT) && !message.includes(ACK)) {
        // Mantengo tu lógica, solo w:0 en writes (ya ayuda bastante)
        let imei = 2;
        let count = 8;
        let sentTime = 9;
        let data = message.split(',');
        const MAX_COUNT = 9999999;

        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
          if (err) console.log(err);
          else if (document) {
            let contador_diario = 0;
            let contador_diario_sensor_2 = 0;
            let contador_diario_sensor_3 = 0;

            let contador_inicial = document.contador_inicial;
            let contador_inicial_sensor_2 = document.contador_inicial_sensor_2;
            let contador_inicial_sensor_3 = document.contador_inicial_sensor_3;

            let count_sensor_1 = 0;
            let count_sensor_2 = 0;
            let count_sensor_3 = 0;

            if (message.includes(GTDTTDGT)) {
              count = 9;
              sentTime = 10;
              const count_parse = data[count];
              const m = /^(\d{8})(\d{4})(\d{5})$/.exec(count_parse);
              if (m) {
                count_sensor_1 = toInteger(m[1]);
                count_sensor_2 = toInteger(m[3]);
              }
            } else {
              let count_parse = data[count].replace("RSC", "").replace("\n", "").replace(" ", "");
              count_sensor_1 = toInteger(count_parse.substr(0, 7));
              count_sensor_2 = toInteger(count_parse.substr(7, 7));
              count_sensor_3 = toInteger(count_parse.substr(14, 7));
            }

            if (count_sensor_1 != 9999999) {
              if (contador_inicial != null) {
                if (contador_inicial > 0) {
                  contador_diario = count_sensor_1 - contador_inicial;
                  if (contador_diario < 0) contador_diario = count_sensor_1 + MAX_COUNT;
                } else contador_inicial = count_sensor_1;
              } else contador_inicial = count_sensor_1;

              if (contador_inicial_sensor_2 != null) {
                if (contador_inicial_sensor_2 > 0) {
                  contador_diario_sensor_2 = count_sensor_2 - contador_inicial_sensor_2;
                  if (contador_diario_sensor_2 < 0) contador_diario_sensor_2 = count_sensor_2 + MAX_COUNT;
                } else contador_inicial_sensor_2 = count_sensor_2;
              } else contador_inicial_sensor_2 = count_sensor_2;

              if (contador_inicial_sensor_3 != null) {
                if (contador_inicial_sensor_3 > 0) {
                  contador_diario_sensor_3 = count_sensor_3 - contador_inicial_sensor_3;
                  if (contador_diario_sensor_3 < 0) contador_diario_sensor_3 = count_sensor_3 + MAX_COUNT;
                } else contador_inicial_sensor_3 = count_sensor_3;
              } else contador_inicial_sensor_3 = count_sensor_3;

              if (contador_diario < document.contador_diario) contador_diario = document.contador_diario;
              if (contador_diario_sensor_2 < document.contador_diario_sensor_2) contador_diario_sensor_2 = document.contador_diario_sensor_2;
              if (contador_diario_sensor_3 < document.contador_diario_sensor_3) contador_diario_sensor_3 = document.contador_diario_sensor_3;

              dbTrackingSystem.collection('unidads').updateOne({ _id: document._id }, {
                $set: {
                  contador_total: count_sensor_1,
                  contador_diario: contador_diario,
                  contador_inicial: contador_inicial,
                  contador_total_sensor_2: count_sensor_2,
                  contador_diario_sensor_2: contador_diario_sensor_2,
                  contador_inicial_sensor_2: contador_inicial_sensor_2,
                  contador_total_sensor_3: count_sensor_3,
                  contador_diario_sensor_3: contador_diario_sensor_3,
                  contador_inicial_sensor_3: contador_inicial_sensor_3,
                  is_atm: (message.includes(ATM) ? 1 : 0)
                }
              }, { writeConcern: { w: 0 } });

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

          let fecha_gps = (fechaGPS != 0)
              ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate()
              : new Date();

          dbTrackingSystem.collection('unidads').findOne(
              { imei: data[imei], estado: 'A' },
              function(err, unidad) {

                  if (err || !unidad) return;

                  // ================= ENVIAR AL FRONT =================
                    const gpsDataLocation = {
                      type: 'unidad.location',
                      unidad_id: unidad._id,
                      _id:unidad._id,
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