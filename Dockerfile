# syntax=docker/dockerfile:1.7

# ---------- Stage 1: composer dependencies (no dev) ----------
FROM composer:2 AS vendor

WORKDIR /app

# Copy only what composer needs first to maximize layer cache
COPY composer.json composer.lock ./

# Install PHP deps without dev / scripts (artisan not yet present)
RUN composer install \
        --no-interaction \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

# Copy the rest of the app and finish autoload
COPY . ./

RUN composer dump-autoload --optimize --classmap-authoritative \
    && composer install \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --classmap-authoritative


# ---------- Stage 2: front-end assets ----------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./

RUN npm ci --no-audit --no-fund

# Need vendor + source for tailwind/vite to discover blade classes
COPY --from=vendor /app /app

RUN npm run build


# ---------- Stage 3: runtime (php-fpm + nginx + supervisord) ----------
FROM php:8.2-fpm-alpine AS runtime

# Build deps installed only for compilation, removed after
RUN set -eux; \
    apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        tini \
        icu-libs \
        libzip \
        oniguruma \
        libpng \
        libjpeg-turbo \
        freetype \
        libwebp \
        libxml2 \
    ; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libwebp-dev \
        libxml2-dev \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    ; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/cache/apk/*

# Production-tuned php.ini overrides
RUN { \
        echo 'expose_php=Off'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=32M'; \
        echo 'post_max_size=32M'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.preload_user=www-data'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

# Composer (optional, kept slim) for runtime artisan tasks if user shell into the container
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Application files (vendor + assets baked in)
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Runtime configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Filesystem permissions for Laravel writable dirs
RUN set -eux; \
    mkdir -p storage/framework/{cache,sessions,testing,views} \
             storage/logs \
             bootstrap/cache; \
    chown -R www-data:www-data /var/www/html storage bootstrap/cache; \
    chmod -R ug+rwX storage bootstrap/cache

# Healthcheck: php-fpm ping endpoint via nginx /up
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

EXPOSE 8080

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
