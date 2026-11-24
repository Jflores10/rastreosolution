# whatsapp-service

Microservicio Node.js que envía imágenes por WhatsApp usando
[whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) (cliente no oficial de WhatsApp Web).
Lo usa el panel de **Despachos** para enviar el ticket al conductor con el botón **Crear y Enviar**.

> ⚠️ whatsapp-web.js no es la API oficial de Meta. Su uso va contra los términos de WhatsApp y el número
> puede ser bloqueado. Use un número dedicado, nunca el personal ni el principal de la empresa.

## Estructura

```
whatsapp-service/
├── server.js             # Express: endpoints, autenticación, validaciones
├── src/
│   ├── whatsapp.js       # Cliente whatsapp-web.js, LocalAuth, QR, reconexión
│   ├── cola.js           # Cola en serie con pausa aleatoria entre mensajes
│   ├── numeros.js        # Normalización de números (Ecuador 593 por defecto)
│   └── logger.js         # Logs de cada envío
├── ecosystem.config.js   # PM2
├── Dockerfile            # Opcional
├── package.json
└── .env.example
```

## Endpoints

Todos requieren el header `x-api-key: <API_KEY>`. Respuestas: `{ ok, error?, id? }`.

| Método | Ruta              | Cuerpo                                              | Notas |
|--------|-------------------|-----------------------------------------------------|-------|
| GET    | `/estado`         | —                                                   | `{ ok, conectado, enCola }` |
| GET    | `/qr`             | —                                                   | PNG del QR. Desde el navegador: `/qr?key=<API_KEY>`. 204 si ya está vinculado |
| POST   | `/enviar-url`     | JSON `{ numero, url, texto? }`                      | Descarga la imagen de la URL |
| POST   | `/enviar-archivo` | multipart `numero`, `texto?`, `imagen`              | Máx 15 MB, jpg/png/webp |
| POST   | `/enviar-base64`  | JSON `{ numero, base64, mimetype, texto? }`         | Acepta también `data:image/png;base64,...` |

Códigos: `400` datos inválidos · `401` API key · `404` el número no tiene WhatsApp · `413` muy grande ·
`415` tipo no permitido · `429` cola llena · `503` WhatsApp no está listo.

Números aceptados: `0991234567`, `991234567`, `+593 99 123 4567`, `593991234567`.

Cada envío espera en cola y entre mensajes hay una pausa aleatoria de 3 a 8 s, por lo que la respuesta
HTTP puede tardar (cola × ~8 s). Configure el timeout del cliente en consecuencia (Laravel usa 120 s).

## Instalación en Ubuntu 22.04

```bash
# 1. Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 2. Librerías que necesita el Chromium de Puppeteer
sudo apt-get install -y ca-certificates fonts-liberation fonts-noto-color-emoji libasound2 \
  libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 \
  libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 \
  libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release wget xdg-utils

# 3. Proyecto
cd /opt && sudo git clone <repo> rastreosolution   # o copie la carpeta whatsapp-service
cd /opt/rastreosolution/whatsapp-service
npm install --omit=dev
cp .env.example .env
nano .env        # defina API_KEY (openssl rand -hex 32)
```

## Vincular el QR (una sola vez)

```bash
node server.js
```

- El QR aparece en la consola, **o** abra `http://<servidor>:3100/qr?key=<API_KEY>` en el navegador
  (si `HOST=127.0.0.1`, use un túnel: `ssh -L 3100:127.0.0.1:3100 usuario@servidor` y abra `http://localhost:3100/qr?key=...`).
- En el teléfono: WhatsApp → **Dispositivos vinculados** → **Vincular un dispositivo** → escanear.
- Cuando el log muestre `WhatsApp listo`, detenga con `Ctrl+C`. La sesión queda en `.wwebjs_auth/`.

## Producción con PM2

```bash
sudo npm install -g pm2
pm2 start ecosystem.config.js
pm2 save
pm2 startup          # ejecute el comando que imprime para arrancar al reiniciar el servidor
pm2 logs whatsapp-service
```

Siempre **una sola instancia**: la sesión de WhatsApp no se comparte entre procesos.

## Docker (opcional)

```bash
docker build -t whatsapp-service .
docker run -d --name whatsapp-service --restart unless-stopped --env-file .env -e HOST=0.0.0.0 \
  -p 127.0.0.1:3100:3100 -v wa_sesion:/app/.wwebjs_auth --shm-size=1g whatsapp-service
docker logs -f whatsapp-service   # QR en consola
```

## Integración con Laravel (trackingsystemwebapp)

En el `.env` de Laravel:

```
WHATSAPP_SERVICE_URL=http://127.0.0.1:3100
WHATSAPP_SERVICE_KEY=<la misma API_KEY>
```

El panel usa `POST /despachos/{id}/whatsapp` (DespachoController@enviarTicketWhatsapp), que reenvía la
imagen del ticket a `/enviar-base64` con el celular del conductor. Ejemplos sueltos con Guzzle 6:

```php
$cliente = new \GuzzleHttp\Client([
    'base_uri' => config('services.whatsapp.url'),
    'timeout' => 120,
    'http_errors' => false,
    'headers' => ['x-api-key' => config('services.whatsapp.key')],
]);

// Enviar un archivo
$resp = $cliente->post('/enviar-archivo', [
    'multipart' => [
        ['name' => 'numero', 'contents' => '0991234567'],
        ['name' => 'texto', 'contents' => 'Su comprobante'],
        ['name' => 'imagen', 'contents' => fopen(storage_path('app/ticket.png'), 'r'), 'filename' => 'ticket.png'],
    ],
]);

// Enviar una URL
$resp = $cliente->post('/enviar-url', [
    'json' => ['numero' => '+593 99 123 4567', 'url' => 'https://ejemplo.com/ticket.png', 'texto' => 'Ticket'],
]);

$json = json_decode((string) $resp->getBody(), true); // ['ok' => true, 'id' => '...']
```

Con curl:

```bash
curl -H "x-api-key: $API_KEY" -F numero=0991234567 -F texto=Hola -F imagen=@ticket.png http://127.0.0.1:3100/enviar-archivo
```

## Buenas prácticas para reducir el riesgo de bloqueo

- Número dedicado, con foto y nombre de perfil, y que lleve semanas en uso normal antes de enviar volumen.
- Enviar solo a personas que esperan el mensaje (conductores de la cooperativa) y que idealmente tengan
  guardado el número. Pídales que respondan o lo guarden: las conversaciones bidireccionales bajan el riesgo.
- Mantener la pausa aleatoria y volúmenes bajos; no enviar ráfagas ni el mismo texto idéntico a muchos.
- Variar el texto (este sistema incluye unidad, ruta y hora en el pie de foto).
- No ejecutar dos sesiones con el mismo número ni dejar el teléfono sin conexión por semanas.
- No exponer el puerto a internet: `HOST=127.0.0.1` o firewall + API key larga.

## Cuando una actualización de WhatsApp Web rompe la librería

Síntomas: no llega a `ready`, `getNumberId`/`sendMessage` fallan con errores de `window.Store` o evaluación.

1. Revise los issues recientes de whatsapp-web.js en GitHub; normalmente sale un parche en días.
2. Actualice: `npm install whatsapp-web.js@latest` (o la versión/rama indicada en el issue) y `pm2 restart whatsapp-service`.
3. Mientras tanto, fije una versión de WhatsApp Web que funcione con `WWEBJS_WEB_VERSION` en `.env`
   (lista en https://github.com/wppconnect-team/wa-version) y reinicie.
4. Si la sesión quedó corrupta: detenga el servicio, borre `.wwebjs_auth/` y `.wwebjs_cache/` y vuelva a escanear el QR.
5. Mientras el servicio esté caído, el panel sigue creando despachos; solo el envío responde con error.
