# Enjoythetrip runs on Laravel 9 / PHP ^8.0.2 (see composer.json).

# ---- Stage 1: install PHP dependencies with Composer -----------------------
FROM php:8.2-cli AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Dev dependencies are kept (no --no-dev): database/seeds/*.php (the
# demo data every seeder relies on) requires fakerphp/faker, which is
# only declared in require-dev. This is a local/demo image, not a
# hardened production build.
RUN composer install \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-scripts

# ---- Stage 2: runtime image (php-fpm + nginx in a single container) --------
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libmemcached-dev \
        zlib1g-dev \
        unzip \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

COPY --from=vendor /app .

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
