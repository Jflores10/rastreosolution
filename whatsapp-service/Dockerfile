# Imagen opcional del microservicio de WhatsApp.
# Uso:
#   docker build -t whatsapp-service .
#   docker run -d --name whatsapp-service --env-file .env -e HOST=0.0.0.0 \
#     -p 127.0.0.1:3100:3100 -v wa_sesion:/app/.wwebjs_auth --shm-size=1g whatsapp-service
#   docker logs -f whatsapp-service    # para ver el QR en consola
FROM node:20-bookworm-slim

# Chromium del sistema y fuentes (incluye emojis y acentos)
RUN apt-get update \
    && apt-get install -y --no-install-recommends chromium fonts-liberation fonts-noto-color-emoji ca-certificates \
    && rm -rf /var/lib/apt/lists/*

ENV PUPPETEER_SKIP_DOWNLOAD=true \
    PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    CHROME_PATH=/usr/bin/chromium \
    NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3100 \
    SESSION_DIR=/app/.wwebjs_auth

WORKDIR /app
COPY package*.json ./
RUN npm install --omit=dev && npm cache clean --force
COPY . .

# Usuario sin privilegios; la carpeta de sesión debe ser escribible
RUN mkdir -p /app/.wwebjs_auth /app/.wwebjs_cache && chown -R node:node /app
USER node

EXPOSE 3100
VOLUME ["/app/.wwebjs_auth"]
CMD ["node", "server.js"]
