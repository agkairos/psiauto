# syntax=docker/dockerfile:1

# ---- Stage 1: assets (Vite build) ----
FROM oven/bun:1-alpine AS assets
WORKDIR /app
COPY package.json bun.lock ./
RUN bun install --frozen-lockfile
COPY . .
RUN bun run build

# ---- Stage 2: dependências PHP (composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 3: imagem final (FrankenPHP) ----
FROM dunglas/frankenphp:1-php8.3

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_mysql bcmath pcntl posix opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN php artisan config:clear \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENV SERVER_NAME=":80"
EXPOSE 80

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
