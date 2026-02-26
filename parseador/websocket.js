// websocket.js (OPTIMIZADO Y LIMPIO)
const WebSocket = require('ws');
const redis = require('redis');
const http = require('http');
const url = require('url');

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

// SSE clients: Map<coopId, Set<res>>
const sseClients = new Map();

// Create HTTP server and attach WebSocket.Server to it so both WS and SSE share the same port
const server = http.createServer((req, res) => {
    try {
        const parsed = url.parse(req.url, true);
        if (parsed.pathname === '/sse' || parsed.pathname === '/sse/') {

    const coopRaw = parsed.query.coop || parsed.query.cooperativa_id || '*';
    const coop = String(coopRaw).trim() || '*';

    res.writeHead(200, {
        'Content-Type': 'text/event-stream; charset=utf-8',
        'Cache-Control': 'no-cache, no-transform',
        'Connection': 'keep-alive',
        'Access-Control-Allow-Origin': '*',
        'X-Accel-Buffering': 'no'
    });

    // 🔥 SSE INIT
    res.write('retry: 3000\n');
    res.write(': connected\n\n');

    let set = sseClients.get(coop);
    if (!set) {
        set = new Set();
        sseClients.set(coop, set);
    }
    set.add(res);

    console.log(`🔌 SSE CONNECT coop=${coop} total=${set.size}`);

    // 🔥 HEARTBEAT
    const ping = setInterval(() => {
        try { res.write(': ping\n\n'); } catch (e) {}
    }, 25000);

    req.on('close', () => {
        clearInterval(ping);
        set.delete(res);
        console.log(`🔌 SSE CLOSE coop=${coop} total=${set.size}`);
    });

    return;
}

        // For other paths, simple healthcheck
        if (req.method === 'GET' && req.url === '/_health') {
            res.writeHead(200, { 'Content-Type': 'text/plain' });
            res.end('ok');
            return;
        }

        res.writeHead(404, { 'Content-Type': 'text/plain' });
        res.end('Not found');
    } catch (e) {
        console.error('HTTP handling error:', e);
        res.writeHead(500);
        res.end('server error');
    }
});

const wss = new WebSocket.Server({ server });

server.listen(PORT, () => {
    console.log(`✅ WebSocket + SSE escuchando en http://127.0.0.1:${PORT}`);
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

        // Add receive timestamp for diagnostics
        try { data._ts_recv = Date.now(); } catch (e) {}

        frontendClients.forEach((coopFront, ws) => {
            if (ws.readyState !== WebSocket.OPEN) return;
            if (coopFront !== coopMsg) return;
            ws.send(JSON.stringify(data));
        });

        // Also send to SSE clients subscribed to this cooperativa
        const sset = sseClients.get(coopMsg);
        if (sset && sset.size > 0) {
           const eventName = data.type || 'message';

const payload =
    `event: ${eventName}\n` +
    `data: ${JSON.stringify(data)}\n\n`;
            sset.forEach((res) => {
                try { res.write(payload); } catch (e) { /* ignore closed sockets */ }
            });
        }

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

// === Redis Stream consumer (durable pipeline) ===
const redisStream = redis.createClient();
redisStream.on('error', err => { console.error('❌ Redis STREAM Error:', err); });

const STREAM_KEY = 'gps-stream';
const GROUP = 'ws-group';
const CONSUMER = `ws-${process.pid}`;

function ensureGroup(cb) {
    redisStream.send_command('XGROUP', ['CREATE', STREAM_KEY, GROUP, '$', 'MKSTREAM'], (err) => {
        // ignore BUSYGROUP error if group already exists
        if (err && String(err).includes('BUSYGROUP')) {
            if (DEBUG_WS) console.log('🔁 Redis group already exists');
            return cb && cb();
        }
        if (err) console.error('❌ Error creando consumer group:', err);
        return cb && cb();
    });
}

function readLoop() {
    // XREADGROUP GROUP <group> <consumer> BLOCK 5000 COUNT 100 STREAMS gps-stream >
    redisStream.send_command('XREADGROUP', ['GROUP', GROUP, CONSUMER, 'BLOCK', '5000', 'COUNT', '100', 'STREAMS', STREAM_KEY, '>'], (err, resp) => {
        if (err) {
            console.error('❌ Error XREADGROUP:', err);
            return setTimeout(readLoop, 1000);
        }

        if (!resp) {
            // timeout, no messages
            return setImmediate(readLoop);
        }

        // resp is [[streamKey, [[id, [field, value, ...]], ...]]]
        try {
            for (const streamResp of resp) {
                const entries = streamResp[1] || [];
                for (const entry of entries) {
                    const id = entry[0];
                    const fields = entry[1] || [];
                    // fields is an array [field, value, field, value...]
                    const obj = {};
                    for (let i = 0; i < fields.length; i += 2) {
                        obj[fields[i]] = fields[i+1];
                    }

                    // Expect obj.data to be JSON payload
                    let data = null;
                    try { data = JSON.parse(obj.data); } catch (e) { data = obj.data; }
                    try { if (data && typeof data === 'object') data._ts_recv = Date.now(); } catch (e) {}

                    // Forward to WS clients
                    try {
                        const coopMsg = String(data.cooperativa_id || '').trim();
                        if (coopMsg) {
                            frontendClients.forEach((coopFront, ws) => {
                                if (ws.readyState !== WebSocket.OPEN) return;
                                if (coopFront !== coopMsg) return;
                                ws.send(JSON.stringify(data));
                            });

                            // Send to SSE clients
                            const sset = sseClients.get(coopMsg);
                            if (sset && sset.size > 0) {
                                const eventName = data.type || 'message';

const payload =
    `event: ${eventName}\n` +
    `data: ${JSON.stringify(data)}\n\n`;
                                sset.forEach((res) => {
                                    try { res.write(payload); } catch (e) {}
                                });
                            }
                        }
                    } catch (e) { console.error('❌ Error forwarding stream message:', e); }

                    // ACK the entry
                    try {
                        redisStream.send_command('XACK', [STREAM_KEY, GROUP, id], (ackErr) => {
                            if (ackErr) console.error('❌ Error XACK:', ackErr);
                        });
                    } catch (e) { console.error('❌ Error XACK send_command:', e); }
                }
            }
        } catch (e) {
            console.error('❌ Error procesando entries de stream:', e);
        }

        // Continue loop
        setImmediate(readLoop);
    });
}

ensureGroup(() => { readLoop(); });