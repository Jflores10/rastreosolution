// websocket.js
const WebSocket = require('ws');
const redis = require('redis');

// =========================
// Redis
// =========================

// Publicador (GPS → Laravel)
const redisClient = redis.createClient();

// Suscriptor (Laravel → Frontend)
const redisSub = redis.createClient();

redisClient.on('error', (err) => {
  console.error('Redis Error:', err);
});

redisSub.on('error', (err) => {
  console.error('Redis SUB Error:', err);
});

// =========================
// WebSocket Server
// =========================
const wss = new WebSocket.Server({ port: 6001 }, () => {
  console.log('WebSocket escuchando en ws://127.0.0.1:6001');
});

// =========================
// Frontends conectados
// ws => cooperativa_id
// =========================
const frontendClients = new Map();

// =========================
// Escuchar Redis realtime
// =========================
redisSub.subscribe('gps-realtime');

redisSub.on('message', (channel, message) => {
  try {
    console.log('gps-realtime recibido:', message);
    
    const data = JSON.parse(message);

    // Enviar SOLO a los frontends de la cooperativa
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
    console.error('Error procesando gps-realtime:', err);
  }
});

// =========================
// Conexiones WebSocket
// =========================
wss.on('connection', (ws) => {
  console.log('Nuevo cliente WebSocket conectado');

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);

      // =========================
      // 1️⃣ Registro de frontend
      // =========================
      if (data.type === 'frontend' && data.cooperativa_id) {
        frontendClients.set(ws, data.cooperativa_id);
        console.log('Frontend registrado:', data.cooperativa_id);
        return;
      }

      // =========================
      // 2️⃣ Datos GPS
      // =========================
      if (
        data.imei &&
        data.latitud !== undefined &&
        data.longitud !== undefined
      ) {
        redisClient.publish('gps-channel', JSON.stringify(data));
        console.log(`Datos enviados a Redis: IMEI ${data.imei}`);
        return;
      }

      // =========================
      // Mensajes no reconocidos
      // =========================
      console.warn('Mensaje WebSocket ignorado:', data);

    } catch (err) {
      console.error('Error procesando mensaje WebSocket:', err);
    }
  });

  ws.on('close', () => {
    frontendClients.delete(ws);
    console.log('Cliente WebSocket desconectado');
  });

  ws.on('error', (err) => {
    frontendClients.delete(ws);
    console.error('Error en WebSocket:', err);
  });
});
