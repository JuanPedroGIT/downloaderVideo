# ── Stage 1: Build ────────────────────────────────────────────────────────────
FROM php:8.3-cli AS builder

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git unzip libzip-dev libicu-dev \
    && docker-php-ext-install zip intl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps (production only, no dev)
COPY composer.json composer.lock* ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --optimize-autoloader \
        --no-scripts

COPY . .

# Run post-install scripts (cache:clear etc.)
RUN composer run-script post-install-cmd --no-interaction || true


# ── Stage 2: Runtime ──────────────────────────────────────────────────────────
FROM php:8.3-cli AS runtime

# Install ffmpeg + yt-dlp + nodejs (for JS runtime) + runtime system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-pip ffmpeg curl libzip-dev libicu-dev gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install zip intl \
    && pecl install redis && docker-php-ext-enable redis \
    && pip3 install --break-system-packages yt-dlp \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy built application and composer from builder stage
COPY --from=builder /app /app
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Create writable tmp dir
RUN mkdir -p /tmp/downloads && chmod 777 /tmp/downloads

EXPOSE 8080

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
