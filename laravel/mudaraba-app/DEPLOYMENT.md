# Production Deployment Guide

This guide covers deploying the Mudaraba Laravel app to a production VPS running Ubuntu 22.04+ with Nginx + PHP-FPM + PostgreSQL. It assumes a single-server deployment (no load balancing) which is appropriate for ~10-50 concurrent operators.

For high-availability or multi-server deployments, see the "Scaling Notes" section at the bottom.

---

## Table of Contents

- [Server Requirements](#server-requirements)
- [Step 1 — System Dependencies](#step-1--system-dependencies)
- [Step 2 — PostgreSQL](#step-2--postgresql)
- [Step 3 — PHP 8.4 + Composer](#step-3--php-84--composer)
- [Step 4 — Node 18+ + npm](#step-4--node-18--npm)
- [Step 5 — Application Code](#step-5--application-code)
- [Step 6 — Environment Configuration](#step-6--environment-configuration)
- [Step 7 — Nginx](#step-7--nginx)
- [Step 8 — PHP-FPM](#step-8--php-fpm)
- [Step 9 — Systemd Services](#step-9--systemd-services)
- [Step 10 — SSL/TLS via Let's Encrypt](#step-10--ssltls-via-lets-encrypt)
- [Step 11 — Smoke Test](#step-11--smoke-test)
- [Backup Strategy](#backup-strategy)
- [Security Hardening Checklist](#security-hardening-checklist)
- [CI/CD via GitHub Actions](#cicd-via-github-actions)
- [Monitoring & Logs](#monitoring--logs)
- [Scaling Notes](#scaling-notes)
- [Troubleshooting](#troubleshooting)

---

## Server Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB | 50 GB (for DB growth + backups) |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| Database | PostgreSQL 16 | PostgreSQL 16 |
| PHP | 8.4 | 8.4 (the project requires this — Symfony 8.x components require PHP 8.4+) |
| Web server | Nginx 1.24+ | Nginx 1.26+ |

The app uses these PHP extensions (enforced by `composer.lock`):
`pdo_pgsql`, `zip`, `intl`, `opcache`, `gd` (with freetype, jpeg, webp, png),
`mbstring`, `curl`, `dom`, `xml` (these last 5 are bundled in the base PHP image).

---

## Step 1 — System Dependencies

SSH into your production server as a sudo-capable user, then:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y \
    curl wget git unzip zip \
    nginx \
    postgresql postgresql-contrib \
    redis-server \
    certbot python3-certbot-nginx \
    ufw fail2ban
```

Enable the firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## Step 2 — PostgreSQL

### Install PostgreSQL 16

Ubuntu 22.04 ships with PostgreSQL 14 by default. Add the official PostgreSQL repo for v16:

```bash
sudo sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
sudo apt update
sudo apt install -y postgresql-16
```

### Create the database + user

```bash
sudo -u postgres psql
```

Inside the psql prompt:

```sql
CREATE DATABASE mudaraba;
CREATE USER mudaraba_user WITH ENCRYPTED PASSWORD 'CHANGE_ME_TO_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE mudaraba TO mudaraba_user;
ALTER DATABASE mudaraba OWNER TO mudaraba_user;
\q
```

**Note the password** — you'll put it in `.env` in Step 6.

### Restrict connections (recommended)

Edit `/etc/postgresql/16/main/postgresql.conf`:
```
listen_addresses = 'localhost'
```

Edit `/etc/postgresql/16/main/pg_hba.conf` — ensure only local connections are allowed:
```
local   all   all   peer
host    all   all   127.0.0.1/32   scram-sha-256
host    all   all   ::1/128       scram-sha-256
```

Restart PostgreSQL:

```bash
sudo systemctl restart postgresql
sudo systemctl enable postgresql
```

---

## Step 3 — PHP 8.4 + Composer

### Add the PHP 8.4 PPA

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### Install PHP 8.4-FPM + extensions

```bash
sudo apt install -y \
    php8.4-fpm \
    php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-intl php8.4-gd php8.4-bcmath \
    php8.4-opcache php8.4-redis
```

### Configure PHP-FPM for production

Edit `/etc/php/8.4/fpm/php.ini`:

```ini
; Production settings
error_log = /var/log/php8.4-fpm/error.log
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors_max_len = 4096

; Memory (the investor grid loads 150 rows + relations — 256M is comfortable)
memory_limit = 256M

; Upload size — set higher than Nginx's client_max_body_size
upload_max_filesize = 64M
post_max_size = 64M

; Timezone — match your M/Y's locale
date.timezone = Asia/Dhaka

; OPcache — critical for performance
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.validate_timestamps = 0   ; set to 0 in prod (clear cache on deploy)

; Session hardening
session.cookie_httponly = 1
session.cookie_secure = 1         ; requires HTTPS
session.cookie_samesite = Strict
session.use_strict_mode = 1
```

### Configure the FPM pool

Edit `/etc/php/8.4/fpm/pool.d/www.conf`:

```ini
; Run as www-data (matches Nginx)
user = www-data
group = www-data

; Listen via socket (faster than TCP)
listen = /run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data

; Process management — tuned for a 2-4 vCPU server
pm = dynamic
pm.max_children = 30
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500   ; restart workers after 500 requests (prevents memory leaks)

; Slow log — catch queries that take > 5s
slowlog = /var/log/php8.4-fpm/slow.log
request_slowlog_timeout = 5s
```

### Install Composer

```bash
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl enable php8.4-fpm
```

---

## Step 4 — Node 18+ + npm

Required for building frontend assets (Vite + React).

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version    # should be v20+
npm --version
```

---

## Step 5 — Application Code

### Create the deploy directory

```bash
sudo mkdir -p /var/www/mudaraba
sudo chown -R $USER:www-data /var/www/mudaraba
```

### Clone the repo

```bash
cd /var/www
git clone https://github.com/sajidchowdhury/mudaraba.git
sudo mv mudaraba/laravel/mudaraba-app /var/www/mudaraba
sudo chown -R $USER:www-data /var/www/mudaraba
cd /var/www/mudaraba
```

### Install dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install --omit=dev
npm run build
```

### Storage + bootstrap/cache permissions

```bash
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

### Public/storage symlink

```bash
php artisan storage:link
```

---

## Step 6 — Environment Configuration

### Create the production .env

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with the production values:

```env
APP_NAME=Mudaraba
APP_ENV=production
APP_KEY=base64:...                    # generated above
APP_DEBUG=false                       # CRITICAL — never expose stack traces in prod
APP_URL=https://mudaraba.example.com  # your actual domain

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning                      # quieter than debug

# Database — match what you created in Step 2
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mudaraba
DB_USERNAME=mudaraba_user
DB_PASSWORD=CHANGE_ME_TO_A_STRONG_PASSWORD

# Session / Cache / Queue — use Redis in prod for multi-process performance
SESSION_DRIVER=redis
SESSION_LIFETIME=30                   # 30 min idle timeout (matches PHP original)
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (configure if you'll send password reset emails)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@mudaraba.example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

### Migrate + seed (initial deploy only)

```bash
php artisan migrate --force
php artisan db:seed --force
```

After seeding, verify the superadmin user exists:

```bash
php artisan tinker
>>> App\Models\User::count();
>>> App\Models\Investor::count();
>>> App\Models\Sector::count();
>>> exit
```

You should see `1` user (E0001), `150` investors, `16` sectors.

### Cache the config + routes (production optimization)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Note**: after every deploy, run `php artisan optimize:clear && php artisan config:cache && php artisan route:cache` to refresh caches.

---

## Step 7 — Nginx

### Create the site config

`/etc/nginx/sites-available/mudaraba`:

```nginx
server {
    listen 80;
    server_name mudaraba.example.com;
    root /var/www/mudaraba/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 64M;

    access_log /var/log/nginx/mudaraba-access.log;
    error_log  /var/log/nginx/mudaraba-error.log warn;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Main routing — Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static assets — long cache (Vite fingerprinting makes this safe)
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Storage — public path
    location /storage/ {
        alias /var/www/mudaraba/storage/app/public/;
        expires 7d;
        add_header Cache-Control "public";
    }

    # PHP handler
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Buffers
        fastcgi_buffer_size 16k;
        fastcgi_buffers 8 16k;
        fastcgi_read_timeout 120s;

        # Important for Inertia/React
        fastcgi_param HTTPS $https if_not_empty;
    }

    # Deny hidden files (.env, .git, etc.)
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Favicon
    location = /favicon.ico {
        try_files $uri =204;
        access_log off;
        log_not_found off;
    }

    # Health check endpoint (Laravel 11+)
    location = /up {
        try_files $uri /index.php?$query_string;
        access_log off;
    }
}
```

### Enable + test

```bash
sudo ln -s /etc/nginx/sites-available/mudaraba /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t        # should print "syntax is ok, test is successful"
sudo systemctl reload nginx
```

---

## Step 8 — PHP-FPM

Confirm the FPM pool is running as `www-data` and listening on the socket Nginx expects:

```bash
sudo systemctl status php8.4-fpm
ls -la /run/php/php8.4-fpm.sock
# Should show: srw-rw---- 1 www-data www-data ... /run/php/php8.4-fpm.sock
```

If the socket is missing, restart FPM:

```bash
sudo systemctl restart php8.4-fpm
```

---

## Step 9 — Systemd Services

### Laravel queue worker (for async PDF generation, bulk imports)

Create `/etc/systemd/system/mudaraba-queue.service`:

```ini
[Unit]
Description=Mudaraba Laravel Queue Worker
After=network.target postgresql.service redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/mudaraba
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/mudaraba/queue.log
StandardError=append:/var/log/mudaraba/queue-error.log

[Install]
WantedBy=multi-user.target
```

Create the log directory:

```bash
sudo mkdir -p /var/log/mudaraba
sudo chown www-data:www-data /var/log/mudaraba
```

Enable + start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable mudaraba-queue
sudo systemctl start mudaraba-queue
sudo systemctl status mudaraba-queue
```

### Laravel scheduler (if you add scheduled commands later)

Create `/etc/systemd/system/mudaraba-scheduler.service`:

```ini
[Unit]
Description=Laravel Scheduler Daemon
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/mudaraba
ExecStart=/usr/bin/php artisan schedule:work
StandardOutput=append:/var/log/mudaraba/scheduler.log
StandardError=append:/var/log/mudaraba/scheduler-error.log

[Install]
WantedBy=multi-user.target
```

Enable + start:

```bash
sudo systemctl enable mudaraba-scheduler
sudo systemctl start mudaraba-scheduler
```

### Redis (for session + cache + queue)

```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

Verify:

```bash
redis-cli ping   # should return PONG
```

### PostgreSQL

Already enabled + started in Step 2.

---

## Step 10 — SSL/TLS via Let's Encrypt

```bash
sudo certbot --nginx -d mudaraba.example.com -d www.mudaraba.example.com \
    --redirect --agree-tos --no-eff-email --email admin@mudaraba.example.com
```

Certbot will modify the Nginx config to:
- Listen on :443 with the SSL certificate
- Redirect :80 to :443

Test auto-renewal:

```bash
sudo certbot renew --dry-run
```

After SSL is set up, verify cookies are secure:
- Visit `https://mudaraba.example.com/login`
- Inspect cookies in browser dev tools → the `mudaraba_session` cookie should have `Secure: true` and `HttpOnly: true`

---

## Step 11 — Smoke Test

1. **App loads over HTTPS** — visit `https://mudaraba.example.com` → should redirect to `/login`
2. **Login works** — `E0001 / Mudaraba@2026` → should redirect to `/dashboard`
3. **DB connection** — Dashboard should show KPIs (Total Investment, etc.) — if it 500s, check `storage/logs/laravel.log`
4. **Redis** — log in, refresh page, verify session persists (if it doesn't, Redis isn't connected)
5. **Queue** — trigger an Excel export → check `sudo journalctl -u mudaraba-queue -f` shows the job processing
6. **Static assets** — view source → CSS/JS files should load (check Nginx access log for 200s, not 404s)
7. **Health endpoint** — `curl https://mudaraba.example.com/up` should return 200

---

## Backup Strategy

### Nightly PostgreSQL backup

Create `/etc/cron.daily/mudaraba-backup`:

```bash
#!/bin/bash
set -e

BACKUP_DIR=/var/backups/mudaraba
DATE=$(date +%Y%m%d)
BACKUP_FILE="$BACKUP_DIR/mudaraba-$DATE.sql.gz"

mkdir -p $BACKUP_DIR

# Dump the database (compressed)
PGPASSWORD="CHANGE_ME_TO_A_STRONG_PASSWORD" \
    pg_dump -U mudaraba_user -h 127.0.0.1 -d mudaraba \
    | gzip > $BACKUP_FILE

# Keep last 30 days of backups locally
find $BACKUP_DIR -name "mudaraba-*.sql.gz" -mtime +30 -delete

# Optional: offsite copy (uncomment after configuring S3 / B2 / etc.)
# aws s3 cp $BACKUP_FILE s3://mudaraba-backups/$(date +%Y/%m/%d)/
```

Make it executable + add a log:

```bash
sudo chmod +x /etc/cron.daily/mudaraba-backup
sudo mkdir -p /var/backups/mudaraba
sudo chown postgres:postgres /var/backups/mudaraba
```

Test it manually:

```bash
sudo /etc/cron.daily/mudaraba-backup
ls -lh /var/backups/mudaraba/
```

### Audit log archival (annual)

The `audit_logs` table grows unbounded. Archive rows older than 2 years to a cold-storage table (or just drop them if not legally required):

```bash
# Add to /etc/cron.monthly/mudaraba-audit-archive
#!/bin/bash
set -e
PGPASSWORD="..." psql -U mudaraba_user -h 127.0.0.1 -d mudaraba <<EOF
CREATE TABLE IF NOT EXISTS audit_logs_archive (LIKE audit_logs INCLUDING ALL);
INSERT INTO audit_logs_archive SELECT * FROM audit_logs WHERE created_at < NOW() - INTERVAL '2 years';
DELETE FROM audit_logs WHERE created_at < NOW() - INTERVAL '2 years';
VACUUM ANALYZE audit_logs;
EOF
```

### Restore test (quarterly)

Backup is useless if you can't restore. Test quarterly:

```bash
# On a staging server:
gunzip -c /var/backups/mudaraba/mudaraba-20260908.sql.gz | psql -U mudaraba_user -h 127.0.0.1 -d mudaraba_test
php artisan tinker
>>> App\Models\Investor::count();   # should be 150
```

---

## Security Hardening Checklist

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] Strong `DB_PASSWORD` (≥ 32 chars random)
- [ ] `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true` in `.env`
- [ ] HTTPS enforced via Let's Encrypt + Nginx redirect
- [ ] UFW firewall allows only 22, 80, 443
- [ ] fail2ban enabled for SSH + Nginx
- [ ] `display_errors = Off` in `php.ini`
- [ ] `.env` file owned by `www-data`, chmod `600` (only readable by the FPM user)
- [ ] `php artisan config:cache` run (env vars are then compiled, `.env` file no longer read at runtime)
- [ ] Storage directory not web-accessible (Nginx config serves `/storage/` only via the public symlink)
- [ ] `audit_logs` table being written to (run `php artisan tinker` → `App\Models\AuditLog::count()`)
- [ ] Default superadmin password (`Mudaraba@2026`) **changed** before going live
- [ ] Rate limiting enabled (Laravel Fortify-style throttle on `/login` POST)

---

## CI/CD via GitHub Actions

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: mudaraba
          POSTGRES_USER: mudaraba
          POSTGRES_PASSWORD: secret
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 5s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, zip, intl, gd, opcache
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install PHP dependencies
        run: composer install --no-interaction --no-progress --prefer-dist
        working-directory: laravel/mudaraba-app

      - name: Install Node dependencies
        run: npm install --ignore-scripts
        working-directory: laravel/mudaraba-app

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate
        working-directory: laravel/mudaraba-app

      - name: Migrate
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_DATABASE: mudaraba
          DB_USERNAME: mudaraba
          DB_PASSWORD: secret
        run: php artisan migrate --force
        working-directory: laravel/mudaraba-app

      - name: Run Pint
        run: vendor/bin/pint --test
        working-directory: laravel/mudaraba-app

      - name: Run Larastan
        run: vendor/bin/phpstan analyse --no-progress
        working-directory: laravel/mudaraba-app

      - name: Run Pest tests
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_DATABASE: mudaraba
          DB_USERNAME: mudaraba
          DB_PASSWORD: secret
        run: php artisan test --parallel
        working-directory: laravel/mudaraba-app

      - name: Run Parity test specifically
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_DATABASE: mudaraba
          DB_USERNAME: mudaraba
          DB_PASSWORD: secret
        run: php artisan test --filter=ParityTest
        working-directory: laravel/mudaraba-app
```

### Auto-deploy on push to main

For automatic deployment to production on push to `main`, create `.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    needs: [tests]  # wait for CI to pass first
    if: github.ref == 'refs/heads/main'
    steps:
      - name: SSH deploy
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/mudaraba
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm install --omit=dev
            npm run build
            php artisan optimize:clear
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo systemctl restart php8.4-fpm
            sudo systemctl restart mudaraba-queue
            echo "Deployed at $(date)"
```

Required GitHub Actions secrets:
- `PROD_HOST` — production server IP
- `PROD_USER` — SSH user (e.g. `deploy`)
- `PROD_SSH_KEY` — SSH private key

---

## Monitoring & Logs

### Where logs live

| What | Path |
|------|------|
| Laravel app log | `/var/www/mudaraba/storage/logs/laravel.log` |
| Nginx access | `/var/log/nginx/mudaraba-access.log` |
| Nginx error | `/var/log/nginx/mudaraba-error.log` |
| PHP-FPM error | `/var/log/php8.4-fpm/error.log` |
| PHP-FPM slow log | `/var/log/php8.4-fpm/slow.log` |
| Queue worker | `/var/log/mudaraba/queue.log` |
| Queue errors | `/var/log/mudaraba/queue-error.log` |

### Log rotation

Create `/etc/logrotate.d/mudaraba`:

```
/var/www/mudaraba/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.4-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Quick log-check commands

```bash
# All Mudaraba errors in the last hour
sudo journalctl -u php8.4-fpm -u mudaraba-queue --since "1 hour ago" | grep -i error

# Slow PHP requests (> 5s)
sudo tail -f /var/log/php8.4-fpm/slow.log

# 404s in Nginx access log
sudo grep ' 404 ' /var/log/nginx/mudaraba-access.log | tail -50

# Failed logins (Laravel writes to laravel.log)
sudo grep -i "failed\|invalid" /var/www/mudaraba/storage/logs/laravel.log
```

### Laravel Telescope (optional — dev/staging only)

For deep request/SQL/queue introspection:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Do not enable Telescope in production** — it has a meaningful performance overhead. Restrict access to superadmin role via the `TeServiceProvider` config.

---

## Scaling Notes

For > 50 concurrent operators or > 1000 investors:

- **Multiple PHP-FPM servers** behind a load balancer. Switch `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` to `redis` (shared Redis instance) so sessions work across servers.
- **PostgreSQL read replicas** for report-heavy workloads. The ledger reports (Investor/Sector/MY Ledger) are read-heavy — point them at a read replica via Laravel's `Model::on('read-replica')` connection.
- **Object storage for exports** — instead of writing PDFs to `storage/app/`, write to S3 / B2 / R2 and serve via signed URLs.
- **PgBouncer** between PHP and PostgreSQL for connection pooling (default PHP-FPM pool of 30 workers = 30 simultaneous PG connections; with PgBouncer, that becomes 5-10 real connections multiplexed).
- **CDN for static assets** — Vite fingerprints all assets (e.g. `assets/index-AbCdEf123.css`), so they can be cached for 1 year at the CDN edge.

---

## Troubleshooting

### 502 Bad Gateway from Nginx

Most common cause: PHP-FPM isn't running or the socket path is wrong.

```bash
sudo systemctl status php8.4-fpm
ls -la /run/php/php8.4-fpm.sock
sudo tail -20 /var/log/nginx/mudaraba-error.log
```

### 500 Internal Server Error

```bash
sudo tail -50 /var/www/mudaraba/storage/logs/laravel.log
```

Most likely causes:
- `APP_KEY` not set → `php artisan key:generate`
- DB connection refused → check `DB_*` in `.env` and `pg_isready -h 127.0.0.1 -p 5432`
- Storage permissions → `sudo chgrp -R www-data storage bootstrap/cache && sudo chmod -R ug+rwx storage bootstrap/cache`
- Stale config cache → `php artisan optimize:clear`

### Login redirects back to /login forever

CSRF or session issue. Check:
- `SESSION_DRIVER` is `redis` (or `database`) — not `file` if you have multiple FPM workers
- `SESSION_SECURE_COOKIE=true` only works over HTTPS — set to `false` if testing over HTTP
- `.env` file is readable by `www-data`

### Excel/PDF export downloads an empty file

`phpoffice/phpspreadsheet` requires `ext-gd`. Confirm it's installed:

```bash
php -m | grep gd
# Should print: gd
```

If not, install it:

```bash
sudo apt install php8.4-gd
sudo systemctl restart php8.4-fpm
```

### Queue jobs stuck / not processing

```bash
sudo systemctl status mudaraba-queue
sudo journalctl -u mudaraba-queue -n 50
```

Most common cause: `QUEUE_CONNECTION` in `.env` is `database` but you want `redis` (or vice versa). After changing `.env`, run `php artisan config:cache` and `sudo systemctl restart mudaraba-queue`.

### `permission denied` on storage/

```bash
sudo chown -R www-data:www-data /var/www/mudaraba/storage
sudo chmod -R 775 /var/www/mudaraba/storage
```

### Database connection failed

```bash
sudo -u postgres psql -d mudaraba -c "SELECT 1;"   # should print 1
PGPASSWORD="..." psql -U mudaraba_user -h 127.0.0.1 -d mudaraba -c "SELECT 1;"
```

If the second command fails with `password authentication failed`:
- You set a different password in `Step 2` than what's in `.env`
- Or `pg_hba.conf` is requiring `scram-sha-256` but the password was set without scram encryption

Fix by resetting the password:
```sql
ALTER USER mudaraba_user WITH ENCRYPTED PASSWORD '...new password...';
```

---

**End of DEPLOYMENT.md**. For everything else, see [`README.md`](./README.md) and [`../MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md).
