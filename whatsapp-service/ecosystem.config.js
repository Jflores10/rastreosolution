// Configuración de PM2: pm2 start ecosystem.config.js
module.exports = {
    apps: [
        {
            name: 'whatsapp-service',
            script: 'server.js',
            cwd: __dirname,
            // Node 20 aparte (el Node 12 del sistema no es compatible con whatsapp-web.js)
            interpreter: '/opt/node-v20.18.1-linux-x64/bin/node',
            // Una sola instancia: la sesión de WhatsApp no se puede compartir entre procesos
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '700M',
            kill_timeout: 10000,
            time: true,
            env: {
                NODE_ENV: 'production',
            },
        },
    ],
};
