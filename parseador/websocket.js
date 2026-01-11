// websocket.js
const WebSocket = require('ws');
const redis = require('redis');

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
 * WebSocket Server
 * ========================= */
const wss = new WebSocket.Server({ port: 6001 }, () => {
  console.log('✅ WebSocket escuchando en ws://127.0.0.1:6001');
});

/* =========================
 * Frontends conectados
 * Map<ws, cooperativa_id>
 * ========================= */
const frontendClients = new Map();

/* =========================
 * Escuchar Redis realtime
 * ========================= */
redisSub.subscribe('gps-realtime');

redisSub.on('message', (channel, message) => {
  try {
    console.log('📡 gps-realtime recibido');

   const data = JSON.parse(message);

    const coopMsg = String(data.cooperativa_id || '').trim();

    console.log('📦 Redis coop:', coopMsg);
    console.log('👥 Fronts registrados:', [...frontendClients.values()]);

    frontendClients.forEach((coopId, ws) => {
      const coopFront = String(coopId || '').trim();

      console.log('🔎 Comparando:', coopFront, '===', coopMsg);

      if (
        ws.readyState === WebSocket.OPEN &&
        coopFront === coopMsg
      ) {
        console.log('✅ ENVIANDO DATA A FRONT');

        ws.send(JSON.stringify({
          type: 'unidad.updated',
          payload: data
        }));
      }
    });

  } catch (err) {
    console.error('❌ Error procesando gps-realtime:', err);
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