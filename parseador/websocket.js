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

redisSub.on('message', (channel, message) => {
  try {
    console.log(`📡 Mensaje Redis en canal ${channel}`);

    const data = JSON.parse(message);

    const coopMsg = String(data.cooperativa_id || '').trim();

    console.log('📦 Redis coop:', coopMsg || '<ninguna>');
    console.log('👥 Fronts registrados:', [...frontendClients.values()]);

    // marcar cuando el WS-server recibe el mensaje desde Redis (diagnóstico)
    try { data._ts_recv = Date.now(); } catch (e) {}

    frontendClients.forEach((coopId, ws) => {
      const coopFront = String(coopId || '').trim();

      // Si no hay coopMsg (o es vacío), hacemos broadcast a todos los frontends conectados
      const shouldSend = (coopMsg === '' || coopMsg === null) ? (ws.readyState === WebSocket.OPEN) : (ws.readyState === WebSocket.OPEN && coopFront === coopMsg);

      if (shouldSend) {
        console.log('✅ ENVIANDO DATA A FRONT ->', coopFront || '<broadcast>');

        // Si el mensaje recibido parece ser una unidad (tiene _id o imei),
        // lo envolvemos en payload.unidad para que el frontend siempre lo lea
        // como `payload.unidad`. Mantenemos _ts_recv a nivel de payload
        // para que el frontend lo pueda medir como antes.
        try {
          const isUnit = data && (data._id || data.imei);
          if (isUnit) {
            const payload = { unidad: data };
            try { payload._ts_recv = data._ts_recv; } catch (e) {}
            ws.send(JSON.stringify({ type: 'unidad.updated', payload }));
          } else {
            ws.send(JSON.stringify({ type: 'unidad.updated', payload: data }));
          }
        } catch (e) {
          console.error('❌ Error enviando data al front:', e);
        }
      }
    });

  } catch (err) {
    console.error('❌ Error procesando mensaje Redis:', err);
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