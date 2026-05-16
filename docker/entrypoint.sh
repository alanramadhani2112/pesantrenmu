#!/usr/bin/env sh
set -eu

APP_ROOT=/var/www/html
cd "$APP_ROOT"

# Storage / cache must be writable by www-data even when mounted as volume
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Skip booted artisan calls during image build (no APP_KEY yet);
# always safe to run at container start.
if [ -n "${APP_KEY:-}" ] || [ -f .env ]; then
    php artisan config:cache  || true
    php artisan route:cache   || true
    php artisan view:cache    || true
    php artisan event:cache   || true

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        php artisan migrate --force --no-interaction
    fi
fi

exec "$@"
