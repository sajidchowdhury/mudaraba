# =============================================================================
# Mudaraba Laravel App — PHP 8.3-FPM image
# Matches the project's composer.json constraint: php ^8.3
# Does NOT downgrade Laravel or any locked package.
# =============================================================================

FROM php:8.3-fpm-alpine

LABEL maintainer="Mudaraba Project"
LABEL description="PHP 8.3-FPM for Laravel 13 (Mudaraba)"

# ---------------------------------------------------------------------------
# System dependencies (build + runtime)
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        # Build tools
        $PHPIZE_DEPS \
        # PostgreSQL client + dev headers
        postgresql-dev \
        postgresql-client \
        # SQLite
        sqlite-dev \
        sqlite-libs \
        # Image processing (GD)
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        # Zip
        libzip-dev \
        zip \
        unzip \
        # Intl (ICU)
        icu-dev \
        # Curl
        curl-dev \
        # Multibyte string
        oniguruma-dev \
        # Utilities
        bash \
        git \
        vim \
        nano \
        wget \
        # For composer + node interop
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        sqlite3 \
        bcmath \
        gd \
        zip \
        intl \
        curl \
        mbstring \
        opcache \
        pcntl \
        exif \
    && apk del $PHPIZE_DEPS \
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
# Entrypoint: composer install, key gen, migrate, fix perms, start FPM
# ---------------------------------------------------------------------------
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Use root for dev (storage perms); switch to www-data for production
USER root

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# Expose FPM
EXPOSE 9000
