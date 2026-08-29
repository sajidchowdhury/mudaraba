#!/bin/bash
# =============================================================================
# Node entrypoint — installs Linux/musl-native deps + starts Vite dev server
#
# KEY DESIGN: This entrypoint NEVER trusts an existing node_modules directory.
# It verifies that the platform-native optional dependencies (e.g.
# @rolldown/binding-linux-x64-musl for Vite 8 on Alpine) are actually
# present and loadable. If not, it reinstalls inside the container so the
# correct musl-native bindings are fetched.
# =============================================================================

set -e

WORKDIR="/var/www/html"
cd "$WORKDIR"

echo "🟢 Mudaraba node entrypoint starting..."
echo "   Node version: $(node -v)"
echo "   npm version:  $(npm -v)"
echo "   Platform:     $(uname -s)/$(uname -m) ($(ldd --version 2>&1 | head -1))"

# ---------------------------------------------------------------------------
# 1. Verify that the platform-native optional dependency for Rolldown
#    (Vite 8's bundler) is actually present and loadable.
#
#    Why: Vite 8 uses Rolldown, which ships native bindings as optional
#    dependencies keyed to OS+arch+libc. If node_modules was installed on
#    a different platform (Windows/macOS) and then bind-mounted in, the
#    wrong binding is present and Vite crashes with:
#      "Cannot find module '@rolldown/binding-linux-x64-musl'"
#
#    A named volume in docker-compose.yml prevents the host's node_modules
#    from leaking in, but we still verify here as a safety net.
# ---------------------------------------------------------------------------
ROLLDOWN_OK=false
if [ -d node_modules/@rolldown/binding ]; then
    # Try to actually load the native binding via Node
    if node -e "require('@rolldown/binding')" >/dev/null 2>&1; then
        ROLLDOWN_OK=true
        echo "✅ Rolldown native binding is loadable"
    else
        echo "⚠️  @rolldown/binding present but failed to load — will reinstall"
    fi
else
    echo "⚠️  @rolldown/binding not found — will install"
fi

# ---------------------------------------------------------------------------
# 2. Also verify package-lock.json is in sync with package.json
#    (cheap integrity check — if npm ls fails, deps are out of date)
# ---------------------------------------------------------------------------
DEPS_IN_SYNC=false
if [ "$ROLLDOWN_OK" = "true" ]; then
    if npm ls --silent >/dev/null 2>&1; then
        DEPS_IN_SYNC=true
        echo "✅ Dependencies are in sync"
    else
        echo "⚠️  npm ls reports missing/changed dependencies — will reinstall"
    fi
fi

# ---------------------------------------------------------------------------
# 3. Install / reinstall if needed
#    Uses `npm install` (not `npm ci`) because the named volume may be empty
#    on first run and we want npm to respect both package-lock.json AND fetch
#    the correct platform optionalDependencies for this container.
# ---------------------------------------------------------------------------
if [ "$ROLLDOWN_OK" != "true" ] || [ "$DEPS_IN_SYNC" != "true" ]; then
    echo "📦 Installing npm dependencies for this platform (Linux/musl)..."
    # --no-audit --no-fund: skip non-essential network calls
    # npm install (not ci) will honor package-lock.json while also
    # resolving the correct platform-specific optional dependencies.
    npm install --no-audit --no-fund
    echo "✅ npm install complete"

    # Verify the install actually fixed the binding
    if ! node -e "require('@rolldown/binding')" >/dev/null 2>&1; then
        echo "❌ FATAL: @rolldown/binding still not loadable after npm install"
        echo "   Platform: $(uname -s)/$(uname -m)"
        echo "   This suggests the package-lock.json is missing the Linux/musl"
        echo "   optional dependency. Try removing the named volume:"
        echo "     docker compose down -v"
        echo "     docker compose up -d --build node"
        exit 1
    fi
    echo "✅ Rolldown native binding now loadable"
else
    echo "✅ Skipping npm install — bindings verified"
fi

# ---------------------------------------------------------------------------
# 4. Default: start Vite dev server with HMR accessible from host
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
