#!/bin/bash
# =============================================================================
# Node entrypoint — installs deps + starts Vite dev server
# =============================================================================

set -e

WORKDIR="/var/www/html"
cd "$WORKDIR"

echo "🟢 Mudaraba node entrypoint starting..."
echo "   Node version: $(node -v)"
echo "   npm version:  $(npm -v)"

# ---------------------------------------------------------------------------
# 1. Install dependencies if node_modules is missing
# ---------------------------------------------------------------------------
if [ ! -d node_modules ]; then
    echo "📦 Installing npm dependencies..."
    npm install --no-audit --no-fund
    echo "✅ npm install complete"
else
    echo "✅ node_modules/ already populated — checking for updates..."
    # Only run if package-lock changed (quick check)
    if ! npm ls --silent >/dev/null 2>&1; then
        echo "📦 Dependencies changed — reinstalling..."
        npm install --no-audit --no-fund
    else
        echo "✅ Dependencies up to date"
    fi
fi

# ---------------------------------------------------------------------------
# 2. Default: start Vite dev server with HMR accessible from host
# ---------------------------------------------------------------------------
if [ "$1" = "build" ]; then
    echo "🏗️  Building frontend assets for production..."
    npm run build
    echo "✅ Build complete"
elif [ "$1" = "tsc" ]; then
    echo "🔍 Running TypeScript type check..."
    npx tsc --noEmit
    echo "✅ Type check complete"
else
    echo "🚀 Starting Vite dev server on 0.0.0.0:5173..."
    # --host 0.0.0.0 so the host browser can reach HMR
    exec npm run dev -- --host 0.0.0.0 --port 5173
fi
