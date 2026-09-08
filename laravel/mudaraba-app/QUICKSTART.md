# Quick Start — Docker

## Prerequisites
- Docker Desktop (or Docker Engine + Docker Compose)
- No PHP, Node, or PostgreSQL needed on your host

## 1. Clone the repo
```bash
git clone https://github.com/sajidchowdhury/mudaraba.git
cd mudaraba/laravel/mudaraba-app
```

## 2. Start everything
```bash
docker compose up -d --build
```

That's it. On first run, the entrypoint automatically:
- Creates `.env` from `.env.example`
- Installs composer dependencies
- Generates APP_KEY
- Waits for PostgreSQL
- Runs all migrations
- Seeds the database (superadmin user, menus, permissions, investors, sectors, directors)

Wait ~60 seconds for everything to start up, then check:
```bash
docker compose ps
```
All 4 services should be running: `postgres`, `app`, `nginx`, `node`.

## 3. Open the app
- **App**: http://localhost:8080
- **Login**: http://localhost:8080/login
  - Username: `E0001`
  - Password: `Mudaraba@2026`

## Common commands

| Command | What it does |
|---------|-------------|
| `make start` | Build + start all services |
| `make stop` | Stop containers (keep data) |
| `make reset` | Full reset — wipes DB + node_modules |
| `make artisan migrate` | Run pending migrations |
| `make fresh` | Fresh migrate + seed (wipes DB) |
| `make test` | Run all Pest tests |
| `make pint` | Run Pint formatter |
| `make build` | Build frontend assets for production |
| `make logs` | Tail all logs |
| `make shell` | Shell into PHP container |
| `make shell-db` | Connect to PostgreSQL via psql |

## Vite HMR (hot reload)
Vite runs automatically in the `node` container. When you edit `.tsx`/`.css` files,
the browser auto-refreshes. HMR is available at http://localhost:5173 (used internally
by Laravel's Vite plugin — you don't need to visit this URL directly).

## Changing the database password
Edit `.env.docker` (or create `.env` from it), then:
```bash
docker compose down -v
docker compose up -d --build
```

## Troubleshooting

### Port already in use
Change the port in `.env.docker`:
```
APP_PORT=8081
```

### App shows "No application encryption key"
```bash
docker compose exec app php artisan key:generate
```

### Database connection error
```bash
docker compose logs postgres
docker compose restart app
```

### Node/Vite not working
```bash
docker compose logs node
docker compose restart node
```

### Full nuclear reset
```bash
make clean
make start
```
