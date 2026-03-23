# ── Stage 1: Build ────────────────────────────────────────────────────────────
FROM node:20-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

# VITE_API_URL will be injected at build time via --build-arg
ARG VITE_API_URL=""
ENV VITE_API_URL=${VITE_API_URL}

RUN npm run build


# ── Stage 2: Serve with Nginx ─────────────────────────────────────────────────
FROM nginx:1.25-alpine AS runtime

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Copy config as a template – nginx entrypoint runs envsubst on /etc/nginx/templates/
# and outputs the result to /etc/nginx/conf.d/ before starting nginx.
COPY nginx.conf /etc/nginx/templates/app.conf.template

# Default upstream (Docker Compose). Override in Railway with BACKEND_UPSTREAM env var.
ENV BACKEND_UPSTREAM=http://backend:8080

# Copy built assets
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD wget -qO- http://localhost/ || exit 1

CMD ["/bin/sh", "-c", "envsubst '${PORT} ${BACKEND_UPSTREAM}' < /etc/nginx/templates/app.conf.template > /etc/nginx/conf.d/app.conf && nginx -g 'daemon off;'"]

