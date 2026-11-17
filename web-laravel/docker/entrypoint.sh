#!/bin/sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    key_file=/var/www/html/storage/app/.docker-app-key
    mkdir -p "$(dirname "$key_file")"
    if [ ! -s "$key_file" ]; then
        php artisan key:generate --show > "$key_file"
        chown www-data:www-data "$key_file"
        chmod 600 "$key_file"
    fi
    export APP_KEY="$(cat "$key_file")"
fi

exec "$@"
