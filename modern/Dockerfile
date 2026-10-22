FROM composer:2 AS vendor-production

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts
COPY src ./src
RUN composer dump-autoload --classmap-authoritative --no-dev --no-interaction

FROM composer:2 AS vendor-test

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-interaction \
    --no-progress \
    --no-scripts
COPY src ./src
COPY tests ./tests
RUN composer dump-autoload --classmap-authoritative --no-interaction

FROM node:24.22.1-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY assets ./assets
RUN npm run build

FROM nginx:1.30.4-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /app/public
COPY --from=frontend /app/public/css /app/public/css
COPY --from=frontend /app/public/js /app/public/js
COPY --from=frontend /app/public/assets /app/public/assets

HEALTHCHECK --interval=15s --timeout=3s --start-period=10s --retries=5 \
    CMD wget --quiet --spider http://127.0.0.1/healthz || exit 1

FROM php:8.5.12-fpm-alpine AS runtime

RUN apk add --no-cache icu-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        pdo_mysql \
    && apk del .build-deps

WORKDIR /app
COPY . .
COPY --from=frontend /app/public/css ./public/css
COPY --from=frontend /app/public/js ./public/js
COPY --from=frontend /app/public/assets ./public/assets

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=5 \
    CMD php-fpm -t || exit 1

CMD ["php-fpm", "-F"]

FROM runtime AS test
COPY --from=vendor-test /app/vendor ./vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
CMD ["php", "bin/phpunit"]

FROM runtime AS production
COPY --from=vendor-production /app/vendor ./vendor
