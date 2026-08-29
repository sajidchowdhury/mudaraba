#!/bin/bash
# =============================================================================
# Mudaraba PHP-FPM entrypoint
#   1. Waits for Postgres to be ready (pg_isready or PHP PDO fallback)
#   2. Installs composer dependencies if missing
#   3. Generates APP_KEY if empty
#   4. Fixes storage permissions
#   5. Runs migrations (graceful)
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
    while [ $timeout -gt 0 ]; do
        # Use pg_isready if available, otherwise fall back to PHP PDO check
        if command -v pg_isready >/dev/null 2>&1; then
            if pg_isready -h "$DB_HOST" -p "$DB_PORT" -q 2>/dev/null; then
                echo "✅ Postgres is ready (pg_isready)"
                break
            fi
        else
            # Fallback: try a PHP PDO connection
            if php -r "
                try {
                    \$pdo = new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
                    echo 'ok';
                } catch (Exception \$e) {
                    exit(1);
                }
            " >/dev/null 2>&1; then
                echo "✅ Postgres is ready (PDO check)"
                break
            fi
        fi
        sleep 1
        timeout=$((timeout - 1))
    done
    if [ $timeout -le 0 ]; then
        echo "⚠️  Postgres not ready after 60s — continuing anyway (migrations may fail)"
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
# 3. APP_KEY — generate if not set
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ]; then
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
