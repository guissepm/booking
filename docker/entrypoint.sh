#!/usr/bin/env bash
set -e

cd /var/www/html

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    tries=0
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "Database never became available, starting anyway."
            break
        fi
        sleep 2
    done
fi

if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set. Generating a throwaway one for this container run only."
    echo "Existing encrypted cookies/sessions will be invalidated on every restart until you"
    echo "set a permanent APP_KEY (generate one with: php artisan key:generate --show)."
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

php artisan storage:link || true
php artisan migrate --force
php artisan config:cache

chown -R www-data:www-data storage bootstrap/cache

php-fpm -D
exec nginx -g "daemon off;"
