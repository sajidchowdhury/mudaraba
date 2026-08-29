# =============================================================================
# Mudaraba Laravel App — PHP 8.4-FPM Alpine
#
# PHP version rationale:
#   composer.json says "php": "^8.3" (loose constraint)
#   BUT composer.lock resolved Symfony 8.x packages (symfony/console v8.1.5,
#   symfony/http-kernel v8.1.5, etc.) which require "php >=8.4.1".
#   Therefore the runtime MUST be PHP 8.4.x — using 8.3 would make
#   `composer install` fail with a platform requirement error.
#
# Extension rationale:
#   The base php:8.4-fpm-alpine image ALREADY includes:
#     pdo, pdo_sqlite, sqlite3, mbstring, curl, dom, xml, simplexml,
#     fileinfo, filter, hash, json, openssl, pcre, session, tokenizer,
#     ctype, date, libxml, phar, posix, random, reflection, sodium, spl
#   These cover everything composer.lock requires (ext-ctype, ext-dom,
#   ext-fileinfo, ext-filter, ext-hash, ext-json, ext-libxml, ext-mbstring,
#   ext-openssl, ext-pcre, ext-session, ext-simplexml, ext-tokenizer,
#   ext-xml, ext-xmlwriter).
#
#   We install ONLY 4 additional extensions the application actually needs:
#     1. pdo_pgsql — Postgres driver (docker-compose sets DB_CONNECTION=pgsql)
#     2. zip       — Composer needs it to extract package archives
#     3. intl      — Carbon/Laravel locale formatting (Asia/Dhaka, BDT)
#     4. opcache   — enables the compiled-in opcache (no compilation needed)
#
#   NOT installed (verified unused via grep on app/ and database/):
#     gd, bcmath, pcntl, exif — the application code does not call any
#     functions from these extensions.
# =============================================================================

FROM php:8.4-fpm-alpine

LABEL maintainer="Mudaraba Project"
LABEL description="PHP 8.4-FPM for Laravel 13 (Mudaraba)"

# ---------------------------------------------------------------------------
# Install build dependencies + runtime libraries, compile extensions, clean up
#
# Build deps are installed EXPLICITLY (not via $PHPIZE_DEPS env var) to avoid
# "Cannot find config.m4" errors caused by env-var expansion issues on
# specific Alpine versions where libc-dev doesn't exist (Alpine uses musl-dev).
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        # Runtime libraries (kept in final image)
        postgresql-libs \
        postgresql-client \
        libzip \
        icu-libs \
        # Build dependencies (removed after extensions are compiled)
        postgresql-dev \
        libzip-dev \
        icu-dev \
        autoconf \
        m4 \
        make \
        gcc \
        g++ \
        musl-dev \
        # Utilities
        bash \
        git \
        zip \
        unzip \
    # Compile only the 4 extensions the application needs
    # (pdo, pdo_sqlite, sqlite3, mbstring, curl, dom, xml are already in base image)
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        zip \
        intl \
        opcache \
    # Remove build dependencies (keep runtime libs + utilities)
    && apk del \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        autoconf \
        m4 \
        make \
        gcc \
        g++ \
        musl-dev \
    && rm -rf /var/cache/apk/*

# ---------------------------------------------------------------------------
# Composer (matching the version that built the lockfile: 2.10+)
# ---------------------------------------------------------------------------
COPY --from=composer:2.10 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# PHP configuration
# ---------------------------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-mudaraba.ini

# ---------------------------------------------------------------------------
# Working directory
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Entrypoint: wait for Postgres, composer install, key gen, migrate, FPM
# ---------------------------------------------------------------------------
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Run as root for dev (storage permissions); switch to www-data for production
USER root

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# Expose FPM
EXPOSE 9000
