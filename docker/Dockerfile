FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    redis \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install pdo_pgsql bcmath intl zip opcache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# ------- Build stage -------
FROM base AS build

RUN apk add --no-cache nodejs npm

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build \
    && rm -rf node_modules \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# ------- Production stage -------
FROM base AS production

COPY --from=build /var/www/html /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php artisan env:validate || exit 1

EXPOSE 9000
CMD ["php-fpm"]
