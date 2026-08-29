# =============================================================================
# Mudaraba — Node 22 image for Vite dev server + frontend builds
# Vite 8 requires Node 20.19+ or 22.12+
# =============================================================================

FROM node:22-alpine

LABEL maintainer="Mudaraba Project"
LABEL description="Node 22 for Vite dev server + npm build"

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
# Entrypoint: npm install if needed, then start Vite dev server
# ---------------------------------------------------------------------------
COPY docker/node/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
