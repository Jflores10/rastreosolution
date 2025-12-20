// websocket.js
const WebSocket = require('ws');
const redis = require('redis');

// Configura Redis
const redisClient = redis.createClient(); // Configuración por defecto: localhost:6379

redisClient.on('error', (err) => {
  console.error('Redis Error:', err);
});

// Servidor WebSocket
const wss = new WebSocket.Server({ port: 6001 }, () => {
  console.log('WebSocket escuchando en ws://127.0.0.1:6001');
});

wss.on('connection', (ws) => {
  console.log('Nuevo cliente WebSocket conectado');

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);

      if (!data.imei || !data.latitud || !data.longitud) {
        console.warn('Datos incompletos recibidos:', data);
        return;
      }

      // Publica los datos en Redis
      redisClient.publish('gps-channel', JSON.stringify(data));

      console.log(`Datos enviados a Redis: IMEI ${data.imei}`);
    } catch (err) {
      console.error('Error procesando mensaje WebSocket:', err);
    }
  });

  ws.on('close', () => {
    console.log('Cliente WebSocket desconectado');
  });
});