#!/bin/bash
# =============================================================================
# Mudaraba PHP-FPM entrypoint
#   1. Waits for Postgres to be ready
#   2. Installs composer dependencies if missing
#   3. Generates APP_KEY if empty
#   4. Runs migrations
#   5. Fixes storage permissions
#   6. Starts php-fpm
# =============================================================================

set -e

WORKDIR="/var/www/html"
cd "$WORKDIR"

echo "🟢 Mudaraba app entrypoint starting..."
echo "   PHP version: $(php -v | head -1)"
echo "   Working dir: $WORKDIR"

# ---------------------------------------------------------------------------
# 1. Wait for Postgres
# ---------------------------------------------------------------------------
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    echo "⏳ Waiting for Postgres at $DB_HOST:$DB_PORT ..."
    timeout=60
    while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -q 2>/dev/null; do
        sleep 1
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "⚠️  Postgres not ready after 60s — continuing anyway (may fail later)"
            break
        fi
    done
    if [ $timeout -gt 0 ]; then
        echo "✅ Postgres is ready"
    fi
fi

# ---------------------------------------------------------------------------
# 2. Composer install (if vendor/ is missing or incomplete)
# ---------------------------------------------------------------------------
if [ ! -f vendor/autoload.php ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-interaction --no-progress --prefer-dist
    echo "✅ Composer install complete"
else
    echo "✅ vendor/ already populated — skipping composer install"
fi

# ---------------------------------------------------------------------------
# 3. APP_KEY — generate if empty
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi

# ---------------------------------------------------------------------------
# 4. Storage permissions
# ---------------------------------------------------------------------------
echo "🔧 Fixing storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "✅ Permissions fixed"

# ---------------------------------------------------------------------------
# 5. Run migrations (graceful — won't fail if DB is temporarily unavailable)
# ---------------------------------------------------------------------------
echo "🗃️  Running migrations..."
php artisan migrate --force --graceful 2>&1 || {
    echo "⚠️  Migration warning (non-fatal) — check DB connection"
}
echo "✅ Migrations done"

# ---------------------------------------------------------------------------
# 6. Start php-fpm
# ---------------------------------------------------------------------------
echo "🚀 Starting php-fpm..."
exec php-fpm
