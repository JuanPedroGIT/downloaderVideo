# ── Stage 1: Build PHP deps ────────────────────────────────────────────────────
FROM php:8.3-cli AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git unzip libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install zip intl pdo pdo_pgsql \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --optimize-autoloader \
        --no-scripts

COPY . .
RUN composer run-script post-install-cmd --no-interaction || true


# ── Stage 2: Runtime base (ffmpeg, yt-dlp, LibreOffice, Ghostscript) ──────────
FROM php:8.3-cli AS runtime-base

RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-pip ffmpeg curl libzip-dev libicu-dev gnupg libpq-dev \
        libreoffice-writer libreoffice-common ghostscript \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install zip intl pdo pdo_pgsql \
    && pecl install redis && docker-php-ext-enable redis \
    && pip3 install --break-system-packages yt-dlp \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app /app
COPY --from=builder /usr/bin/composer /usr/bin/composer

RUN mkdir -p /tmp/downloads && chmod 777 /tmp/downloads

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080


# ── Stage 3: Development (xdebug habilitado) ───────────────────────────────────
FROM runtime-base AS dev

RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

CMD ["/usr/local/bin/start.sh"]


# ── Stage 4: Production (sin xdebug, opcache optimizado) ──────────────────────
FROM runtime-base AS prod

RUN echo "opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0" \
    > /usr/local/etc/php/conf.d/zz-opcache-prod.ini

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

CMD ["/usr/local/bin/start.sh"]
