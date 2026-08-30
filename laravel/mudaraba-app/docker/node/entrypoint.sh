#!/bin/bash
set -e

WORKDIR="/var/www/html"
cd "$WORKDIR"

echo "🟢 Mudaraba Node container starting..."
echo "   Node version: $(node -v)"
echo "   npm version:  $(npm -v)"
echo "   Platform:     $(uname -s)/$(uname -m)"

# ---------------------------------------------------------------------------
# Install npm dependencies if the named volume is empty or incomplete.
# Uses `npm ci` for reproducible installs from package-lock.json.
# npm ci also fetches the correct platform-specific optional dependencies
# for this container (Linux/musl), which is critical for Vite 8 / Rolldown.
# ---------------------------------------------------------------------------
if [ ! -f node_modules/.package-lock.json ] || [ ! -d node_modules/vite ]; then
    echo "📦 Installing npm dependencies (npm ci)..."
    npm ci --no-audit --no-fund
    echo "✅ npm dependencies installed"
else
    echo "✅ node_modules already initialized"
fi

# ---------------------------------------------------------------------------
# Mode selection
# ---------------------------------------------------------------------------
if [ "$1" = "build" ]; then
    echo "🏗️ Building frontend assets..."
    npm run build
    echo "✅ Build complete"

elif [ "$1" = "tsc" ]; then
    echo "🔍 Running TypeScript type check..."
    npx tsc --noEmit
    echo "✅ Type check complete"

else
    echo "🚀 Starting Vite dev server..."
    echo "   Listening on 0.0.0.0:5173"
    exec npm run dev -- --host 0.0.0.0 --port 5173
fi
