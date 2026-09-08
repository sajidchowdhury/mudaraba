# =============================================================================
# Mudaraba — Node 22 image for Vite dev server + frontend builds
#
# Vite 8 requires Node 20.19+ or 22.12+. Node 22-alpine satisfies this.
#
# IMPORTANT — node_modules platform compatibility:
#   Vite 8 uses Rolldown, which ships platform-specific native bindings as
#   optional dependencies (@rolldown/binding-linux-x64-musl for Alpine,
#   @rolldown/binding-win32-x64-msvc for Windows, etc.).
#
#   The host's node_modules/ (installed on Windows/macOS) CANNOT be shared
#   with the Alpine container — the wrong native binding would be present.
#
#   Solution: docker-compose.yml mounts a NAMED VOLUME at
#   /var/www/html/node_modules that SHADOWS the host's node_modules. The
#   container therefore has its own Linux/musl-native node_modules while
#   source code remains bind-mounted for HMR.
#
#   The entrypoint verifies the binding is loadable before starting Vite,
#   and reinstalls if missing (e.g. on first run or after volume wipe).
# =============================================================================

FROM node:22-alpine

LABEL maintainer="Mudaraba Project"
LABEL description="Node 22 for Vite dev server + npm build (Linux/musl native)"

# ---------------------------------------------------------------------------
# System packages
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        bash \
        git \
        wget \
        curl \
    && rm -rf /var/cache/apk/*

# ---------------------------------------------------------------------------
# Working directory (matches the app container)
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Entrypoint: verifies native bindings, installs if missing, starts Vite
# ---------------------------------------------------------------------------
COPY docker/node/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
