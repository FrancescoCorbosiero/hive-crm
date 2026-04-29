# ─── Build stage ──────────────────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
RUN npm run build

# ─── Runtime stage ────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libpq-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-client \
        tzdata \
        zip \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

ENV TZ=Europe/Rome

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && php artisan storage:link || true

EXPOSE 9000
CMD ["php-fpm"]
