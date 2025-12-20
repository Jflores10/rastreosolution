// websocket.js
const WebSocket = require('ws');
const redis = require('redis');

// =========================
// Configura Redis (EXISTENTE)
// =========================
const redisClient = redis.createClient(); // publish (YA EXISTE)

// 👉 NUEVO: Redis subscriber
const redisSub = redis.createClient();

redisClient.on('error', (err) => {
  console.log('Redis Error:', err);
});

redisSub.on('error', (err) => {
  console.log('Redis SUB Error:', err);
});

// =========================
// Servidor WebSocket (EXISTENTE)
// =========================
const wss = new WebSocket.Server({ port: 6001 }, () => {
  console.log('WebSocket escuchando en ws://127.0.0.1:6001');
});

// 👉 NUEVO: clientes frontend registrados
const frontendClients = new Map(); // ws => cooperativa_id

// =========================
// 👉 NUEVO: escuchar Redis realtime
// =========================
redisSub.subscribe('gps-realtime');

redisSub.on('message', (channel, message) => {
  try {
    const data = JSON.parse(message);

    // Enviar SOLO a frontends de la cooperativa
    frontendClients.forEach((coopId, ws) => {
      if (
        ws.readyState === WebSocket.OPEN &&
        coopId === data.cooperativa_id
      ) {
        ws.send(JSON.stringify({
          type: 'unidad.updated',
          payload: data
        }));
      }
    });

  } catch (err) {
    console.error('Error enviando a frontend:', err);
  }
});

// =========================
// Conexiones WebSocket (EXISTENTE + EXTENSIÓN)
// =========================
wss.on('connection', (ws) => {
  console.log('Nuevo cliente WebSocket conectado');

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);

      // =========================
      // CASO 1️⃣ GPS (EXISTENTE)
      // =========================
      if (!data.type && data.imei && data.latitud && data.longitud) {

        // Publica los datos en Redis (EXISTENTE)
        redisClient.publish('gps-channel', JSON.stringify(data));

        console.log(`Datos enviados a Redis: IMEI ${data.imei}`);
        return;
      }

      // =========================
      // 👉 CASO 2️⃣ Frontend (NUEVO)
      // =========================
      if (data.type === 'frontend' && data.cooperativa_id) {
        frontendClients.set(ws, data.cooperativa_id);
        console.log('Frontend registrado:', data.cooperativa_id);
        return;
      }

      // =========================
      // Validación original (EXISTENTE)
      // =========================
      if (!data.imei || !data.latitud || !data.longitud) {
        console.log('Datos incompletos recibidos:', data);
        return;
      }

    } catch (err) {
      console.log('Error procesando mensaje WebSocket:', err);
    }
  });

  ws.on('close', () => {
    // 👉 NUEVO: limpiar frontend
    frontendClients.delete(ws);
    console.log('Cliente WebSocket desconectado');
  });
});
