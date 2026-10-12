#!/bin/sh
set -e

if [ "$1" = "php-fpm" ]; then
    if [ -z "$APP_KEY" ]; then
        APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        export APP_KEY
        echo "APP_KEY is not set: using a temporary key for this container. Set APP_KEY to keep one."
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan migrate --force

    if [ "${SEED_DEMO_DATA:-true}" = "true" ]; then
        php artisan db:seed --force
    fi

    chown -R www-data:www-data storage bootstrap/cache
fi

exec docker-php-entrypoint "$@"
