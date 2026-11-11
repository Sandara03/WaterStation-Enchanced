#!/bin/sh
set -eu
cd /var/www/html

# Render generates a base64-encoded 32-byte value. Do not regenerate it on restarts.
if [ -n "${RENDER_APP_KEY:-}" ]; then
    export APP_KEY="base64:$RENDER_APP_KEY"
fi
if [ -z "${APP_KEY:-}" ] || [ -z "${DB_URL:-}" ]; then
    echo 'APP_KEY/RENDER_APP_KEY and the PostgreSQL DB_URL must be configured.' >&2
    exit 1
fi
export APP_URL="${RENDER_EXTERNAL_URL:-${APP_URL:-http://localhost:10000}}"
port="${PORT:-10000}"
case "$port" in ''|*[!0-9]*) echo 'PORT must be numeric.' >&2; exit 1 ;; esac
sed -i "s/^Listen .*/Listen $port/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:10000>/<VirtualHost *:$port>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear
php artisan migrate --force --no-interaction
php artisan db:seed --class=DatabaseSeeder --force --no-interaction
php artisan db:seed --class=HostedAdminSeeder --force --no-interaction
php artisan config:cache
exec apache2-foreground
