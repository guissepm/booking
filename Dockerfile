# Enjoythetrip runs on Laravel 5.6 / PHP ^7.1.3 (see composer.json). This
# image intentionally targets php:7.1-fpm to match that requirement rather
# than the newer PHP available on the host.

# ---- Stage 1: install PHP dependencies with Composer -----------------------
# Composer 2 rewrote vendor/composer/installed.json to a new schema that
# Laravel 5.6's PackageManifest can't parse ("Undefined index: name" at
# runtime, same issue fixed for CI in .github/workflows/laravel.yml), so we
# need Composer 1. The docker.io/library/composer:1 image turned out to
# bundle a modern PHP (8.4) that Composer 1's own code doesn't support
# (fatal TypeError in stream_context_create()), so install Composer 1
# ourselves on a PHP version it actually works with instead.
FROM php:7.3-cli AS vendor

RUN sed -i \
        -e 's|deb.debian.org|archive.debian.org|g' \
        -e '/security.debian.org/d' \
        /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until \
    && apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --version=1.10.27 --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /app

COPY . .

RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --ignore-platform-reqs \
        --prefer-dist \
        --no-interaction \
        --no-scripts

# ---- Stage 2: runtime image (php-fpm + nginx in a single container) --------
FROM php:7.1-fpm

# php:7.1-fpm's Debian release is EOL; its main mirrors are gone, so point
# apt at the archive (and stop requiring a live Release "Valid-Until",
# which archived snapshots don't refresh).
RUN sed -i \
        -e 's|deb.debian.org|archive.debian.org|g' \
        -e '/security.debian.org/d' \
        /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

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
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install memcached-3.1.5 \
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
