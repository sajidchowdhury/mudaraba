# Mudaraba — Docker Local Development

A complete Docker-based development environment for the Mudaraba Laravel project.
No code modification needed — all existing files are mounted into containers.

---

## Quick Start

```bash
# 1. Build images + start all services
docker compose up -d --build

# 2. Wait for Postgres + migrations (≈30s)
docker compose logs -f app | grep "✅ Migrations done"

# 3. Open the app
open http://localhost:8080
```

That's it. The entrypoint automatically:
- Waits for Postgres to be ready
- Runs `composer install` (if `vendor/` is missing)
- Generates `APP_KEY` (if empty)
- Fixes `storage/` permissions
- Runs `php artisan migrate`
- Starts php-fpm

---

## Services

| Service    | URL / Port           | Purpose                          |
|------------|----------------------|----------------------------------|
| **nginx**  | http://localhost:8080 | Serves the Laravel app           |
| **Vite**   | http://localhost:5173 | HMR for React/TS during dev      |
| **Postgres** | localhost:5432    | Primary database                  |
| **PHP-FPM** | internal :9000      | FastCGI for nginx                |

---

## Architecture

```
┌──────────────────────────────────────────────────────┐
│                   docker-compose.yml                  │
├──────────────────────────────────────────────────────┤
│                                                       │
│  ┌─────────────┐     ┌──────────────┐                  │
│  │  app        │◄───►│  nginx       │   :8080 → host   │
│  │  PHP 8.3    │     │  Alpine      │                  │
│  │  FPM        │     └──────────────┘                  │
│  │  + composer │                                       │
│  │  + artisan  │     ┌──────────────┐                  │
│  └──────┬──────┘◄───►│  postgres    │   :5432 → host   │
│         │             │  16-alpine   │                  │
│  ┌──────▼──────┐      │  + volume    │                  │
│  │  node       │      └──────────────┘                  │
│  │  Node 22    │                                        │
│  │  Vite HMR   │      :5173 → host                     │
│  └─────────────┘                                        │
│                                                         │
└──────────────────────────────────────────────────────┘
```

All services share a bind-mounted volume (the project root) at `/var/www/html`,
so file changes on the host are instantly reflected in containers.

---

## Common Commands

### Artisan (run in the `app` container)

```bash
# Tinker
docker compose exec app php artisan tinker

# Run migrations
docker compose exec app php artisan migrate

# Fresh migrate (wipes DB — use with caution!)
docker compose exec app php artisan migrate:fresh

# Seed
docker compose exec app php artisan db:seed

# Clear caches
docker compose exec app php artisan optimize:clear

# Generate key (usually done automatically by entrypoint)
docker compose exec app php artisan key:generate
```

### Composer

```bash
# Install dependencies
docker compose exec app composer install

# Add a package
docker compose exec app composer require package/name

# Update
docker compose exec app composer update
```

### Testing (Pest / PHPUnit)

```bash
# Run all tests
docker compose exec app php artisan test

# Run with Pest directly
docker compose exec app vendor/bin/pest

# Run a specific test
docker compose exec app vendor/bin/pest --filter=ExampleTest
```

### Code Quality

```bash
# Pint (formatting)
docker compose exec app vendor/bin/pint

# Pint check (no changes)
docker compose exec app vendor/bin/pint --test

# Larastan (static analysis)
docker compose exec app vendor/bin/phpstan analyse
```

### Frontend (Vite / npm)

```bash
# Vite dev server (HMR) — runs automatically on `docker compose up`
# To restart manually:
docker compose restart node

# Production build
docker compose exec node sh -c "npm run build"

# TypeScript check
docker compose exec node sh -c "npx tsc --noEmit"

# Install a new npm package
docker compose exec node npm install package-name
```

### Postgres

```bash
# Connect via psql
docker compose exec postgres psql -U mudaraba -d mudaraba

# Or from host (port 5432 is forwarded):
psql -h localhost -U mudaraba -d mudaraba

# View tables
docker compose exec postgres psql -U mudaraba -d mudaraba -c "\dt"

# Backup
docker compose exec postgres pg_dump -U mudaraba mudaraba > backup.sql

# Restore
cat backup.sql | docker compose exec -T postgres psql -U mudaraba -d mudaraba
```

---

## Environment Variables

The `docker-compose.yml` reads from a `.env` file in the project root (the same one Laravel uses). Docker-specific overrides are set directly in the `environment:` section of `docker-compose.yml`.

Key variables (defaults shown):

| Variable             | Default      | Description                        |
|----------------------|--------------|------------------------------------|
| `APP_NAME`           | Mudaraba     | Application name                   |
| `APP_PORT`           | 8080         | Host port for nginx                |
| `DB_DATABASE`        | mudaraba     | Postgres database name             |
| `DB_USERNAME`        | mudaraba     | Postgres username                  |
| `DB_PASSWORD`        | secret       | Postgres password                  |
| `FORWARD_DB_PORT`    | 5432         | Host port for Postgres             |
| `VITE_PORT`          | 5173         | Host port for Vite HMR             |
| `XDEBUG_MODE`        | off          | Set to `debug` to enable Xdebug    |

To customize, create a `.env` file (if not already present) or export variables before `docker compose up`:

```bash
export DB_PASSWORD=my-secure-password
docker compose up -d
```

---

## File Permissions

On Linux, Docker runs containers as root. Files created by `composer install` inside the container (e.g., `vendor/`) will be owned by root. If you need to modify them on the host:

```bash
sudo chown -R $USER:$USER vendor node_modules storage bootstrap/cache
```

---

## Vite HMR in Docker

The Vite dev server runs in the `node` container on `0.0.0.0:5173`. The `laravel-vite-plugin` writes a `public/hot` file that tells Laravel to serve assets from the Vite dev server instead of built files.

**How it works:**
1. `node` container starts `npm run dev -- --host 0.0.0.0`
2. Vite creates `public/hot` with the dev server URL
3. Laravel's `@vite` directive reads `public/hot` and serves from `http://localhost:5173`
4. Your browser connects to `localhost:5173` for HMR

If HMR doesn't connect (e.g., on Docker Desktop for Windows/WSL2), check:
- The `node` container is running: `docker compose ps node`
- Port 5173 is not blocked: `docker compose logs node`
- Your browser allows WebSocket connections to `localhost:5173`

---

## Resetting the Database

```bash
# Stop containers + delete volumes (wipes Postgres data)
docker compose down -v

# Start fresh
docker compose up -d --build
```

---

## Troubleshooting

### Postgres won't start

```bash
# Check logs
docker compose logs postgres

# Common fix: remove the volume and restart
docker compose down -v
docker compose up -d
```

### Migration fails

```bash
# Check if Postgres is healthy
docker compose ps

# Check the app container's DB connection
docker compose exec app php artisan tinker --execute="echo DB::connection()->getPdo()->query('SELECT version()')->fetchColumn();"
```

### `vendor/` or `node_modules/` missing

The entrypoint should install them automatically. If not:

```bash
docker compose exec app composer install
docker compose exec node npm install
```

### Port already in use

Change the host port in `.env`:

```bash
APP_PORT=8081
FORWARD_DB_PORT=5433
VITE_PORT=5174
docker compose up -d
```

---

## Production Notes

This setup is for **local development only**. For production:

1. Use a multi-stage Dockerfile (build assets, then copy to slim runtime image)
2. Set `APP_ENV=production`, `APP_DEBUG=false`
3. Use Redis for cache/session/queue (add a `redis` service)
4. Run `composer install --no-dev --optimize-autoloader`
5. Run `npm run build` and serve from `public/build/`
6. Set up SSL/TLS in nginx
7. Use secrets management (Docker secrets / Vault)

---

## File Structure

```
mudaraba-app/
├── docker-compose.yml           ← orchestration
├── docker-compose.override.yml  ← dev overrides (auto-loaded)
├── .dockerignore
├── docker/
│   ├── Dockerfile               ← PHP 8.3-FPM image
│   ├── node.Dockerfile          ← Node 22 image
│   ├── README.md                ← this file
│   ├── .env.docker.example      ← env template
│   ├── nginx/
│   │   └── default.conf         ← nginx server config
│   ├── php/
│   │   ├── php.ini              ← PHP overrides
│   │   └── entrypoint.sh        ← app startup script
│   └── node/
│       └── entrypoint.sh        ← node startup script
├── composer.json
├── package.json
└── ... (Laravel app files)
```
