// websocket.js (OPTIMIZADO Y LIMPIO)
const WebSocket = require('ws');
const redis = require('redis');

/* =========================
 * Redis
 * ========================= */
const redisSub = redis.createClient();

redisSub.on('error', err => {
    console.error('❌ Redis SUB Error:', err);
});

/* =========================
 * WebSocket Server
 * ========================= */
const PORT = 6001;
const wss = new WebSocket.Server({ port: PORT }, () => {
    console.log(`✅ WebSocket escuchando en ws://127.0.0.1:${PORT}`);
});

const DEBUG_WS = process.env.DEBUG_WS === '1';

/* =========================
 * Frontends conectados
 * Map<ws, cooperativa_id>
 * ========================= */
const frontendClients = new Map();

/* =========================
 * Keepalive (ping/pong)
 * ========================= */
setInterval(() => {
    wss.clients.forEach(ws => {
        if (ws.isAlive === false) {
            frontendClients.delete(ws);
            return ws.terminate();
        }
        ws.isAlive = false;
        try { ws.ping(); } catch (e) {}
    });
}, 30000);

/* =========================
 * Redis realtime
 * ========================= */
redisSub.subscribe('gps-channel');

redisSub.on('message', (channel, message) => {
    try {
        const data = JSON.parse(message);

        const coopMsg = String(data.cooperativa_id || '').trim();
        if (!coopMsg) return; // 🔥 evita broadcast accidental

        if (DEBUG_WS) {
            console.log(`📡 Redis → WS | type=${data.type} coop=${coopMsg}`);
        }

        frontendClients.forEach((coopFront, ws) => {
            if (ws.readyState !== WebSocket.OPEN) return;
            if (coopFront !== coopMsg) return;

            // 🔥 REENVIAR TAL CUAL, SIN ENVOLVER, SIN MODIFICAR
            ws.send(JSON.stringify(data));
        });

    } catch (err) {
        console.error('❌ Error procesando mensaje Redis:', err);
    }
});

/* =========================
 * Conexiones WebSocket
 * ========================= */
wss.on('connection', (ws) => {
    ws.isAlive = true;

    ws.on('pong', () => {
        ws.isAlive = true;
    });

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);

            // Registro del frontend
            if (data.type === 'frontend' && data.cooperativa_id) {
                const coopId = String(data.cooperativa_id).trim();
                if (!coopId) return;
                frontendClients.set(ws, coopId);
                if (DEBUG_WS) console.log('👤 Frontend registrado:', coopId);
                return;
            }

        } catch (err) {
            console.error('❌ WS mensaje inválido:', err);
        }
    });

    ws.on('close', () => {
        frontendClients.delete(ws);
        if (DEBUG_WS) console.log('🔴 Cliente WS desconectado');
    });

    ws.on('error', () => {
        frontendClients.delete(ws);
    });
});