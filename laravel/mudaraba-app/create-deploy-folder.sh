#!/bin/bash
# =============================================================================
# create-deploy-folder.sh — creates a clean 'deploy/mudaraba/' folder
# containing ONLY the files needed to run the project in production.
#
# Usage:
#   cd /path/to/mudaraba/laravel/mudaraba-app
#   bash create-deploy-folder.sh
#
# Output:
#   deploy/mudaraba/          ← copy THIS folder to your VPS
#
# What's INCLUDED:
#   - app/ (controllers, models, services, traits, enums, exports, requests)
#   - bootstrap/ (app.php, providers)
#   - config/ (all config files)
#   - database/ (migrations, seeders, factories)
#   - public/ (index.php, .htaccess, favicon, build/ assets)
#   - resources/ (js/, css/, views/)
#   - routes/ (web.php, console.php)
#   - storage/ (empty dirs with .gitignore)
#   - composer.json, package.json
#   - .env.example
#   - vite.config.ts, tsconfig.json
#   - artisan
#   - README.md, USAGE_GUIDE.md, DEPLOYMENT.md
#
# What's EXCLUDED:
#   - vendor/ (install on VPS with composer install --no-dev)
#   - node_modules/ (install on VPS with npm install)
#   - tests/ (not needed in production)
#   - docker/ (Docker dev environment — not for VPS)
#   - docker-compose.yml, docker-compose.override.yml, Makefile (Docker-specific)
#   - playwright.config.ts, tests/e2e/ (E2E tests — not for production)
#   - phpunit.xml (testing config — not for production)
#   - AGENTS.md, CLAUDE.md, CHANGELOG.md, QUICKSTART.md (dev docs — keep README + DEPLOYMENT + USAGE_GUIDE)
#   - refresh.sh (dev helper)
#   - .env (contains local secrets — VPS uses its own .env)
#   - .env.docker (Docker-specific)
#   - package-lock.json (regenerate on VPS)
#   - .github/ (CI/CD — not needed on VPS)
# =============================================================================

set -e

SCRIPT_DIR="$(dirname "$0")"
DEPLOY_DIR="$SCRIPT_DIR/deploy/mudaraba"

echo "Creating clean deploy folder at: $DEPLOY_DIR"
echo ""

# Clean up old deploy folder if it exists
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# ── Directories to copy (recursive) ──────────────────────────────────
DIRS=(
    "app"
    "bootstrap"
    "config"
    "database"
    "public"
    "resources"
    "routes"
)

for dir in "${DIRS[@]}"; do
    echo "  Copying $dir/..."
    cp -r "$SCRIPT_DIR/$dir" "$DEPLOY_DIR/$dir"
done

# ── Create storage directory structure (empty, with .gitignore) ──────
echo "  Creating storage/ structure..."
mkdir -p "$DEPLOY_DIR/storage/framework/sessions"
mkdir -p "$DEPLOY_DIR/storage/framework/views"
mkdir -p "$DEPLOY_DIR/storage/framework/cache/data"
mkdir -p "$DEPLOY_DIR/storage/logs"
mkdir -p "$DEPLOY_DIR/storage/app/public"
mkdir -p "$DEPLOY_DIR/storage/app/private"
mkdir -p "$DEPLOY_DIR/storage/inertia-devtools"
echo "*" > "$DEPLOY_DIR/storage/framework/sessions/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/framework/views/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/framework/cache/data/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/logs/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/app/public/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/app/private/.gitignore"
echo "*" > "$DEPLOY_DIR/storage/inertia-devtools/.gitignore"

# ── Remove test files from database/ ────────────────────────────────
echo "  Removing test-related files..."
rm -rf "$DEPLOY_DIR/database/factories"

# ── Remove test files from resources/ (none, but clean up if any) ───
# resources/js/Pages has all pages — keep them all (needed for Inertia rendering)

# ── Files to copy (top-level) ────────────────────────────────────────
FILES=(
    "artisan"
    "composer.json"
    "package.json"
    "vite.config.ts"
    "tsconfig.json"
    ".env.example"
    "README.md"
    "USAGE_GUIDE.md"
    "DEPLOYMENT.md"
)

for file in "${FILES[@]}"; do
    if [ -f "$SCRIPT_DIR/$file" ]; then
        echo "  Copying $file..."
        cp "$SCRIPT_DIR/$file" "$DEPLOY_DIR/$file"
    fi
done

# ── Create a production .env.example ────────────────────────────────
echo "  Creating .env.production..."
cat > "$DEPLOY_DIR/.env.production" << 'ENVEOF'
APP_NAME=Mudaraba
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://inventoryos.com/mudaraba

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

# Database — use your VPS PostgreSQL credentials
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mudaraba
DB_USERNAME=YOUR_DB_USER
DB_PASSWORD=YOUR_DB_PASSWORD

# Session / Cache / Queue — use database (or Redis if available)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

# Redis (if available on VPS — otherwise use database)
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# Mail (configure if needed)
MAIL_MAILER=log

VITE_APP_NAME="${APP_NAME}"
ENVEOF

# ── Create a VPS-specific Nginx config for subfolder deployment ─────
echo "  Creating nginx-subfolder.conf..."
cat > "$DEPLOY_DIR/nginx-subfolder.conf" << 'NGINXEOF'
# =============================================================================
# Nginx config for Mudaraba deployed in a subfolder: /mudaraba
# Place this in /etc/nginx/sites-available/inventoryos.com
# (merge with the existing inventoryos.com config)
# =============================================================================

# Subfolder location block — add this INSIDE your existing server { } block
# for inventoryos.com, at the location /mudaraba { ... } level

location /mudaraba {
    alias /var/www/inventoryos.com/mudaraba/public;
    index index.php index.html;

    # Serve static files directly
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Vite build assets
    location /mudaraba/build/ {
        alias /var/www/inventoryos.com/mudaraba/public/build/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Storage (symlinked public/storage)
    location /mudaraba/storage/ {
        alias /var/www/inventoryos.com/mudaraba/storage/app/public/;
        expires 7d;
    }

    # Laravel routing — pass to PHP-FPM
    try_files $uri $uri/ /mudaraba/index.php?$query_string;

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_param QUERY_STRING $query_string;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXEOF

# ── Create a VPS deployment script ──────────────────────────────────
echo "  Creating deploy-to-vps.sh..."
cat > "$DEPLOY_DIR/deploy-to-vps.sh" << 'VPSEOF'
#!/bin/bash
# =============================================================================
# deploy-to-vps.sh — run this ON the VPS after uploading the deploy folder
#
# Usage:
#   cd /var/www/inventoryos.com/mudaraba
#   bash deploy-to-vps.sh
#
# This script:
#   1. Installs PHP dependencies (composer install --no-dev)
#   2. Installs Node dependencies (npm install)
#   3. Builds frontend assets (npm run build)
#   4. Creates .env from .env.production
#   5. Generates APP_KEY
#   6. Creates storage symlink
#   7. Runs migrations
#   8. Seeds the database (clean start — empty state)
#   9. Caches config + routes
#  10. Fixes permissions
# =============================================================================
set -e

echo "=== Mudaraba VPS Deployment ==="
echo ""

# Check we're in the right directory
if [ ! -f "artisan" ]; then
    echo "ERROR: artisan not found. Run this script from the mudaraba project root."
    exit 1
fi

# 1. Composer install (production, no dev dependencies)
echo "=== 1/10: Installing PHP dependencies ==="
composer install --no-dev --optimize-autoloader --no-interaction

# 2. npm install
echo "=== 2/10: Installing Node dependencies ==="
npm install --omit=dev

# 3. Build frontend assets
echo "=== 3/10: Building frontend assets ==="
npm run build

# 4. Create .env from production template
echo "=== 4/10: Setting up .env ==="
if [ ! -f ".env" ]; then
    cp .env.production .env
    echo "  .env created from .env.production — EDIT IT with your DB credentials!"
else
    echo "  .env already exists — keeping existing config"
fi

# 5. Generate APP_KEY
echo "=== 5/10: Generating APP_KEY ==="
php artisan key:generate --force

# 6. Storage symlink
echo "=== 6/10: Creating storage symlink ==="
php artisan storage:link

# 7. Run migrations
echo "=== 7/10: Running migrations ==="
php artisan migrate --force

# 8. Seed (clean start — empty state, 0 balances)
echo "=== 8/10: Seeding database (clean start) ==="
php artisan db:seed --force --class=CleanStartSeeder

# 9. Cache config + routes
echo "=== 9/10: Caching config + routes ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 10. Fix permissions
echo "=== 10/10: Fixing permissions ==="
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo ""
echo "═══════════════════════════════════════════════════"
echo "  ✅ Deployment complete!"
echo ""
echo "  Next steps:"
echo "  1. Edit .env with your database credentials:"
echo "     nano .env"
echo "  2. Re-run config cache after editing .env:"
echo "     php artisan config:cache"
echo "  3. Add the Nginx subfolder config (see nginx-subfolder.conf)"
echo "  4. Reload Nginx:"
echo "     sudo systemctl reload nginx"
echo ""
echo "  Login: E0001 / Mudaraba@2026"
echo "  URL: https://inventoryos.com/mudaraba"
echo "═══════════════════════════════════════════════════"
VPSEOF
chmod +x "$DEPLOY_DIR/deploy-to-vps.sh"

# ── Create .gitignore for the deploy folder ─────────────────────────
echo "*" > "$SCRIPT_DIR/deploy/.gitignore"

# ── Summary ─────────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════"
echo "  ✅ Deploy folder created: deploy/mudaraba/"
echo ""
echo "  Contents:"
find "$DEPLOY_DIR" -maxdepth 1 -type f | xargs -I{} basename {} | sort | while read f; do echo "    $f"; done
find "$DEPLOY_DIR" -maxdepth 1 -type d | xargs -I{} basename {} | sort | while read d; do echo "    $d/"; done
echo ""
echo "  To deploy to VPS:"
echo "  1. Upload deploy/mudaraba/ to /var/www/inventoryos.com/mudaraba/"
echo "  2. SSH to VPS: cd /var/www/inventoryos.com/mudaraba"
echo "  3. Edit .env.production with your DB credentials"
echo "  4. Run: bash deploy-to-vps.sh"
echo "  5. Add the Nginx subfolder config (nginx-subfolder.conf)"
echo "  6. Reload Nginx"
echo ""
echo "  The deploy/ folder is gitignored — it won't be committed."
echo "═══════════════════════════════════════════════════"
