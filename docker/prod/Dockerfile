# syntax=docker/dockerfile:1.7
# ─── Composer vendor (deps only, no autoload yet) ────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

# ─── Frontend assets ─────────────────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci || npm install

COPY . .
RUN npm run build

# ─── Optimised autoload with the full source tree present ────────────────
FROM composer:2 AS build

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload \
    --optimize \
    --no-dev \
    --classmap-authoritative \
    --no-interaction

# ─── Runtime: FrankenPHP (Caddy + PHP, single binary) ────────────────────
#
# Why FrankenPHP rather than php-fpm + nginx: the edge `caddy-docker-proxy`
# already terminates TLS and reverse-proxies plain HTTP into this container,
# so we just need a process that speaks HTTP and serves Laravel. FrankenPHP
# is the smallest correct fit — one binary, no fastcgi handshake, no static-
# file volume to share with the edge caddy.
FROM dunglas/frankenphp:1-php8.3-alpine

RUN install-php-extensions \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        redis \
        zip

ENV TZ=Europe/Rome \
    SERVER_NAME=:80

WORKDIR /app

# App + vendor + built assets — all baked into the image. Production code
# never lives on a shared volume; container restart is the redeploy unit.
COPY --from=build --chown=www-data:www-data /app /app

# Bake a pristine copy of the storage skeleton so the entrypoint can seed
# an empty mounted volume on first boot (logs/, framework/cache, …). The
# `storage/` mount in compose is what persists across restarts.
RUN cp -a /app/storage /app/storage-init \
 && chown -R www-data:www-data /app/storage /app/storage-init \
 && chmod -R 775 /app/storage /app/storage-init bootstrap/cache

COPY docker/prod/entrypoint.sh /usr/local/bin/hive-entrypoint
RUN chmod +x /usr/local/bin/hive-entrypoint

EXPOSE 80

ENTRYPOINT ["hive-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
