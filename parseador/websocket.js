// websocket.js
const WebSocket = require('ws');
const redis = require('redis');
const fs = require('fs');
const https = require('https');

/* =========================
 * Redis
 * ========================= */
const redisClient = redis.createClient();
const redisSub = redis.createClient();

redisClient.on('error', err => {
  console.error('Redis PUB Error:', err);
});

redisSub.on('error', err => {
  console.error('Redis SUB Error:', err);
});

/* =========================
 * WebSocket Server (optional TLS)
 * If SSL_KEY_PATH and SSL_CERT_PATH env vars are set and files exist,
 * create an HTTPS server and attach the WebSocket server to it (wss).
 * Otherwise start plain ws server on port 6001.
 * ========================= */
let wss;
try {
  const keyPath = process.env.SSL_KEY_PATH;
  const certPath = process.env.SSL_CERT_PATH;
  const useTLS = keyPath && certPath && fs.existsSync(keyPath) && fs.existsSync(certPath);

  if (useTLS) {
    const server = https.createServer({
      key: fs.readFileSync(keyPath),
      cert: fs.readFileSync(certPath)
    });
    wss = new WebSocket.Server({ server });
    server.listen(6001, () => {
      console.log(`✅ WSS escuchando en wss://127.0.0.1:6001 (certs from env)`);
    });
  } else {
    wss = new WebSocket.Server({ port: 6001 }, () => {
      console.log('✅ WebSocket escuchando en ws://127.0.0.1:6001');
      if (process.env.FORCE_TLS === '1') {
        console.warn('⚠️ FORCE_TLS=1 está activo pero no se encontraron claves/certs en SSL_KEY_PATH/SSL_CERT_PATH');
      }
    });
  }
} catch (err) {
  console.error('❌ Error inicializando WebSocket server:', err);
  process.exit(1);
}

/* =========================
 * Frontends conectados
 * Map<ws, cooperativa_id>
 * ========================= */
const frontendClients = new Map();

/* =========================
 * Escuchar Redis realtime
 * Suscribirse a ambos canales que podemos usar: 'gps-realtime' y 'gps-channel'
 * ========================= */
redisSub.subscribe('gps-realtime', 'gps-channel');

// Buffer incoming Redis messages and coalesce by unidad (or imei) to avoid burst forwarding.
const pendingMessages = new Map();
let pendingFlushTimer = null;
const FLUSH_MS = 40; // coalesce window

function schedulePendingFlush() {
  if (pendingFlushTimer) return;
  pendingFlushTimer = setTimeout(() => {
    pendingFlushTimer = null;
    const items = Array.from(pendingMessages.values());
    pendingMessages.clear();
    // Broadcast each latest item to matching frontends
    items.forEach((data) => {
      try {
        const coopMsg = String(data.cooperativa_id || '').trim();
        const sendType = data.type || 'unidad.updated';
        frontendClients.forEach((coopId, ws) => {
          if (ws.readyState !== WebSocket.OPEN) return;
          const coopFront = String(coopId || '').trim();
          const shouldSend = (coopMsg === '' || coopMsg === null) ? true : (coopFront === coopMsg);
          if (shouldSend) {
            ws.send(JSON.stringify({ type: sendType, payload: data }));
          }
        });
      } catch (err) {
        console.error('❌ Error al enviar item coalescido:', err);
      }
    });
  }, FLUSH_MS);
}

redisSub.on('message', (channel, message) => {
  try {
    const data = JSON.parse(message);
    // Key by provided _id, unidad_id, imei or fallback to unique id
    const key = String(data._id || data.unidad_id || data.imei || (Date.now() + Math.random()));
    // Keep the latest message for that key (prelim/final ordering: later messages overwrite earlier)
    pendingMessages.set(key, data);
    schedulePendingFlush();
  } catch (err) {
    console.error('❌ Error procesando mensaje Redis (parse):', err);
  }
});

/* =========================
 * Conexiones WebSocket
 * ========================= */
wss.on('connection', (ws) => {
  console.log('🟢 Cliente WS conectado');

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);

      /* =========================
       * Registro de frontend
       * ========================= */
      if (data.type === 'frontend' && data.cooperativa_id) {
        frontendClients.set(ws, String(data.cooperativa_id));
        console.log('👤 Frontend registrado:', data.cooperativa_id);
        return;
      }

      /* =========================
       * Datos GPS (opcional)
       * ========================= */
      if (data.imei && data.latitud !== undefined) {
        redisClient.publish('gps-channel', JSON.stringify(data));
        return;
      }

    } catch (err) {
      console.error('❌ WS mensaje inválido:', err);
    }
  });

  ws.on('close', () => {
    frontendClients.delete(ws);
    console.log('🔴 Cliente WS desconectado');
  });

  ws.on('error', (err) => {
    frontendClients.delete(ws);
    console.error('❌ Error WS:', err);
  });
});