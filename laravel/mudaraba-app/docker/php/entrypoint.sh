#!/bin/bash
# =============================================================================
# Mudaraba PHP-FPM entrypoint — handles fresh clone perfectly
#   1. Creates .env from .env.example if missing
#   2. Waits for Postgres
#   3. Runs composer install if vendor/ missing
#   4. Generates APP_KEY if empty
#   5. Fixes storage permissions
#   6. Runs migrations + seeds (on first run only)
#   7. Starts php-fpm
# =============================================================================

set -e

WORKDIR="/var/www/html"
cd "$WORKDIR"

echo "🟢 Mudaraba app entrypoint starting..."
echo "   PHP version: $(php -v | head -1)"

# ---------------------------------------------------------------------------
# 1. Create .env from .env.example if it doesn't exist
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.example..."
    cp .env.example .env
    echo "✅ .env created"
else
    echo "✅ .env already exists"
fi

# ---------------------------------------------------------------------------
# 2. Wait for Postgres
# ---------------------------------------------------------------------------
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    echo "⏳ Waiting for Postgres at $DB_HOST:$DB_PORT ..."
    timeout=60
    while [ $timeout -gt 0 ]; do
        if command -v pg_isready >/dev/null 2>&1; then
            if pg_isready -h "$DB_HOST" -p "$DB_PORT" -q 2>/dev/null; then
                echo "✅ Postgres is ready"
                break
            fi
        else
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
        echo "⚠️  Postgres not ready after 60s — continuing anyway"
    fi
fi

# ---------------------------------------------------------------------------
# 3. Composer install (if vendor/ is missing)
# ---------------------------------------------------------------------------
if [ ! -f vendor/autoload.php ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-interaction --no-progress --prefer-dist
    echo "✅ Composer install complete"
else
    echo "✅ vendor/ already populated"
fi

# ---------------------------------------------------------------------------
# 4. APP_KEY — generate if empty
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ] || ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi

# ---------------------------------------------------------------------------
# 5. Storage permissions
# ---------------------------------------------------------------------------
echo "🔧 Fixing storage permissions..."
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "✅ Permissions fixed"

# ---------------------------------------------------------------------------
# 6. Run migrations + seed (if DB is fresh — no users table data)
# ---------------------------------------------------------------------------
echo "🗃️  Running migrations..."
php artisan migrate --force 2>&1 || {
    echo "⚠️  Migration warning — check DB connection"
}

# Check if we need to seed (no users = fresh DB)
USER_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "🌱 Fresh database detected — running seeders..."
    php artisan db:seed --force 2>&1 || echo "⚠️  Seed warning (non-fatal)"
    echo "✅ Database seeded"
    echo ""
    echo "═══════════════════════════════════════════════════"
    echo "  🎉 Mudaraba is ready!"
    echo ""
    echo "  Login: http://localhost:8080/login"
    echo "  Username: E0001"
    echo "  Password: Mudaraba@2026"
    echo "═══════════════════════════════════════════════════"
else
    echo "✅ Database already has data (skipping seed)"
fi

# ---------------------------------------------------------------------------
# 7. Start php-fpm
# ---------------------------------------------------------------------------
echo "🚀 Starting php-fpm..."
exec php-fpm
