# Mudaraba — Laravel Profit Management System

A premium Laravel rebuild of the Mudaraba money-management & profit-distribution system. Replaces a manual Excel workbook ("Investors Report 2026 New.xlsx" → "July, 2026 For Sajid" sheet) for managing a Mudaraba profit-sharing pool, with an 8-phase calculation engine, retained-earnings automation, due-ledger tracking, and audit-grade transaction history.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Quick Start (Docker)](#quick-start-docker—recommended)
- [Quick Start (Bare Metal)](#quick-start-bare-metal)
- [Common Commands](#common-commands)
- [Environment Variables](#environment-variables)
- [First Run — Login & Smoke Test](#first-run--login--smoke-test)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Production Deployment](#production-deployment)
- [Project Plan & Status](#project-plan--status)
- [License](#license)

---

## Tech Stack

| Layer | Choice | Notes |
|-------|--------|-------|
| Backend | Laravel 13 (PHP 8.4) | Fortify-style custom auth, Inertia for SPA-feel routing |
| Database | PostgreSQL 16 (prod) / SQLite (local dev) | Switch via `DB_CONNECTION` in `.env` |
| Frontend | Inertia.js + React 18 + TypeScript | Tailwind CSS 4 + shadcn/ui (New York style) |
| Charts | Recharts | React-native, responsive |
| Tables | TanStack Table v8 | Excel-like inline editing |
| Forms | React Hook Form + Zod | Schema-validated |
| Notifications | Sonner | Premium toast UX |
| Animations | Framer Motion | Subtle micro-interactions |
| Excel export | `maatwebsite/excel` (phpspreadsheet) | Used for the "For Sajid" sheet export |
| PDF export | `barryvdh/laravel-dompdf` | Replaces PHP version's dompdf/mpdf |
| Build | Vite 8 | HMR in dev, production bundle in prod |
| Testing | Pest 4 + Larastan 3 | Modern, expressive PHP testing |
| Static analysis | Larastan (PHPStan level 6+) | Enforced on all `app/Services/*` (financial logic) |
| Formatting | Pint | PSR-12 + Laravel conventions |

---

## Quick Start (Docker — Recommended)

The Docker path is fully self-contained — no PHP, Node, or PostgreSQL install needed on your host. Just Docker Desktop (or Docker Engine + Compose).

### 1. Clone

```bash
git clone https://github.com/sajidchowdhury/mudaraba.git
cd mudaraba/laravel/mudaraba-app
```

### 2. Start everything

```bash
docker compose up -d --build
```

On first run, the entrypoint automatically:
- Creates `.env` from `.env.example`
- Installs composer dependencies
- Generates `APP_KEY`
- Waits for Postgres
- Runs all migrations
- Seeds the database (superadmin user, menus, permissions, 150 investors, 16 sectors, directors)

Wait ~60 seconds, then verify all 4 services are up:

```bash
docker compose ps
# Expected: postgres (healthy), app (Up), nginx (Up), node (Up)
```

### 3. Open the app

- App: http://localhost:8080
- Login: http://localhost:8080/login
  - Username: `E0001`
  - Password: `Mudaraba@2026`

You should land on the dashboard with KPI cards showing the January 2026 seed data (150 investors, BDT 137M total investment).

See [`QUICKSTART.md`](./QUICKSTART.md) for troubleshooting common issues (port conflicts, encryption key, DB connection, etc.).

---

## Quick Start (Bare Metal)

Use this if you already have PHP 8.3+, Composer, Node 18+, and (optionally) PostgreSQL installed natively and prefer not to use Docker.

### 1. Clone

```bash
git clone https://github.com/sajidchowdhury/mudaraba.git
cd mudaraba/laravel/mudaraba-app
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Default `.env.example` uses SQLite (zero-config — just needs the file to exist):

```bash
touch database/database.sqlite
```

If you want PostgreSQL instead, edit the `DB_*` lines in `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mudaraba
DB_USERNAME=mudaraba
DB_PASSWORD=secret
```

### 4. Migrate + seed

```bash
php artisan migrate:fresh --seed
```

You should see the seeder output:
```
🎉 January 2026 data loaded!
  Investors:              150
  Sectors:                16
  Total Investment:       137,022,000
  Login: E0001 / Mudaraba@2026
```

### 5. Build the frontend

```bash
npm run build        # production bundle
# OR
npm run dev          # HMR — keeps running; use this if you'll edit React code
```

### 6. Start the dev server

```bash
php artisan serve
```

App: http://localhost:8000 → login `E0001 / Mudaraba@2026`.

---

## Common Commands

### Docker (via Make)

| Command | What it does |
|---------|-------------|
| `make start` | Build + start all services |
| `make stop` | Stop containers (keep data) |
| `make down` | Stop + remove containers (keep DB) |
| `make reset` | Full reset — wipes DB + node_modules volumes |
| `make rebuild` | Rebuild images from scratch (no-cache) |
| `make migrate` | Run pending migrations |
| `make fresh` | Fresh migrate + seed (wipes DB) |
| `make test` | Run all Pest tests |
| `make pint` | Run Pint formatter |
| `make build` | Build frontend assets for production |
| `make logs` | Tail all logs |
| `make shell` | Shell into PHP container |
| `make shell-db` | Connect to PostgreSQL via psql |

### Bare Metal (artisan / npm)

| Command | What it does |
|---------|-------------|
| `php artisan serve` | Start dev server at :8000 |
| `php artisan migrate:fresh --seed` | Wipe + migrate + seed |
| `php artisan test` | Run all Pest tests |
| `php artisan test --filter=AuditLogTest` | Run a single test file |
| `vendor/bin/pint` | Run Pint formatter |
| `vendor/bin/phpstan analyse` | Run Larastan |
| `npm run dev` | Vite dev server (HMR) |
| `npm run build` | Production bundle |
| `npm run lint` | TypeScript check (`tsc --noEmit`) |

### Refresh after pulling latest code

```bash
# Docker
bash refresh.sh
# OR manually:
git pull origin main
docker compose exec app php artisan migrate:fresh --seed
docker compose exec node npm run build
```

---

## Environment Variables

The full list lives in [`.env.example`](./.env.example). The variables you'll most likely need to override:

### Application

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_NAME` | `Mudaraba` | App display name |
| `APP_ENV` | `local` | One of `local`, `staging`, `production` |
| `APP_DEBUG` | `true` | **Set to `false` in production** |
| `APP_URL` | `http://localhost:8080` | Canonical URL (used in emails, exports) |
| `APP_KEY` | (auto-generated) | Generate with `php artisan key:generate` |

### Database

| Variable | Default (Docker) | Default (bare metal) |
|----------|-------------------|---------------------|
| `DB_CONNECTION` | `pgsql` (forced by docker-compose) | `sqlite` |
| `DB_HOST` | `postgres` | `127.0.0.1` |
| `DB_PORT` | `5432` | `5432` |
| `DB_DATABASE` | `mudaraba` | `mudaraba` (or path to .sqlite file) |
| `DB_USERNAME` | `mudaraba` | `mudaraba` |
| `DB_PASSWORD` | `secret` | `secret` |

### Session / Cache / Queue

| Variable | Default | Purpose |
|----------|---------|---------|
| `SESSION_DRIVER` | `database` | Stores sessions in DB (works across multiple app servers) |
| `SESSION_LIFETIME` | `120` | Session idle timeout (minutes) |
| `CACHE_STORE` | `database` | Cache driver (switch to `redis` for production) |
| `QUEUE_CONNECTION` | `database` | Queue driver (switch to `redis` for production) |

### Docker overrides (only used by docker-compose)

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_PORT` | `8080` | Host port mapped to nginx :80 |
| `FORWARD_DB_PORT` | `5432` | Host port mapped to postgres :5432 |
| `VITE_PORT` | `5173` | Host port mapped to node :5173 (HMR) |
| `XDEBUG_MODE` | `off` | `on` enables Xdebug 3 in the PHP container |

---

## First Run — Login & Smoke Test

After booting the app, log in with `E0001 / Mudaraba@2026` and click through these to verify the system is working:

1. **Dashboard** (`/dashboard`) — 5 KPI cards (Total Investment, Cash in Hand, This Month Profit, M/Y Profit YTD, Active Investors) + 3 charts + recent activity feed
2. **Investors** (`/investors`) — list of 150 investors with tier badges (100/80/60)
3. **Sectors** (`/sectors`) — 16 sectors
4. **Sector Profit** (`/profit/sector`) — 16-row grid with January 2026 estimated/actual numbers
5. **Investor Profit** (`/profit/investor?month=2026-01-01`) — the "For Sajid" grid; should show 150 investors with computed profit shares, retained earnings column, M/Y profit at the bottom. Click "Export to Excel" to download `For Sajid - January_2026.xlsx`.
6. **Month Close** (`/month-close`) — January 2026 status (open / finalized / locked)
7. **Reports** → Investor Ledger / Sector Ledger / M/Y Ledger / Investment Profit — all four exports work (PDF for ledgers, Excel for investment profit)

If any of these crash or show empty data, check `docker compose logs app --tail 50` (Docker) or `storage/logs/laravel.log` (bare metal) for the actual error.

---

## Project Structure

```
mudaraba/
├── laravel/
│   ├── MUDARABA_LARAVEL_PROJECT_PLAN.md   # ← the master plan + progress tracker
│   └── mudaraba-app/                       # ← Laravel application root
│       ├── app/
│       │   ├── Controllers/   (17 controllers, ~3,000 LOC)
│       │   ├── Services/      ProfitCalculatorService, RetainedEarningsService,
│       │   │                  LedgerUpdateService, AuditService
│       │   ├── Models/        (23 Eloquent models)
│       │   ├── Enums/         (6 PHP 8.1 enums)
│       │   ├── Traits/        Auditable, DueManager, HasPermissions
│       │   ├── Http/
│       │   │   ├── Middleware/  PermissionMiddleware, SuperadminMiddleware
│       │   │   └── Requests/    (11 Form Request classes)
│       │   └── Exports/       InvestorProfitExport (Excel)
│       ├── bootstrap/app.php                # withMiddleware config (CSRF exemption in tests)
│       ├── config/                          # Standard Laravel config
│       ├── database/
│       │   ├── migrations/   (35 migration files)
│       │   ├── factories/    User, Investor, Sector, Director
│       │   └── seeders/      January2026Seeder (production-shape data)
│       ├── docker/                           # Docker dev environment
│       │   ├── Dockerfile     # PHP 8.4-FPM Alpine + 5 extensions
│       │   ├── node.Dockerfile
│       │   ├── nginx/default.conf
│       │   └── php/entrypoint.sh  # auto-migrate + auto-seed on first run
│       ├── resources/
│       │   ├── js/
│       │   │   ├── Pages/      (28 React pages)
│       │   │   ├── Components/  (27 shadcn/ui + 7 layout + 3 common)
│       │   │   └── lib/         utils, theme-init
│       │   └── views/exports/   PDF blade templates (investor/sector/MY ledger)
│       ├── routes/
│       │   └── web.php          # All routes (auth, permission-guarded)
│       ├── tests/
│       │   ├── Feature/        (22 Pest feature tests, incl. ParityTest + AuditLogTest)
│       │   └── Unit/           (1 unit test: ProfitCalculatorServiceTest)
│       ├── docker-compose.yml
│       ├── Makefile            # 12 helper targets (start/stop/test/pint/...)
│       ├── QUICKSTART.md       # Docker quick-start guide
│       ├── DEPLOYMENT.md       # Production deployment guide
│       └── CHANGELOG.md        # Commit history summary
└── (legacy PHP project files — to be removed once Laravel version is production)
```

---

## Testing

### Run the full suite

```bash
# Docker
make test

# Bare metal
php artisan test
```

### Test inventory

| File | What it covers |
|------|---------------|
| `tests/Feature/ParityTest.php` | Verifies the 8-phase engine reproduces Excel math (Z2, X2, Y2, AG182, AG184, AG186, retained 71/29 split, idempotency) |
| `tests/Unit/ProfitCalculatorServiceTest.php` | Unit tests for the calculation service |
| `tests/Feature/AuditLogTest.php` | 14 tests covering every financial mutation's audit trail |
| `tests/Feature/AuthTest.php` | Login, logout, invalid credentials, inactive users |
| `tests/Feature/PermissionTest.php` | RBAC enforcement at route + sidebar level |
| `tests/Feature/InvestorTest.php` | Investor CRUD + validation + soft deletes |
| `tests/Feature/SectorTest.php` | Sector CRUD |
| `tests/Feature/DirectorTest.php` | Director CRUD + is_my toggle |
| `tests/Feature/InvestmentTransactionTest.php` | Add/withdraw + DueManager integration |
| `tests/Feature/SectorProfitTest.php` | Sector profit entry + finalize transition |
| `tests/Feature/InvestorProfitViewTest.php` | The "For Sajid" page renders correctly |
| `tests/Feature/MonthCloseTest.php` | Lock/unlock workflow |
| `tests/Feature/ProfitAdjustmentTest.php` | Fund A / Fund B / Direct adjustments |
| `tests/Feature/RetainedEarningsTest.php` | 71/29 split + distribution |
| `tests/Feature/LedgerUpdateTest.php` | Due ledger updates + rollback-on-edit |
| `tests/Feature/ExportTest.php` | PDF + Excel exports |
| `tests/Feature/DashboardTest.php` | Dashboard loads + KPI aggregation |
| `tests/Feature/InvestorLedgerTest.php` | Investor ledger report |
| `tests/Feature/SectorLedgerTest.php` | Sector ledger report |
| `tests/Feature/MYLedgerTest.php` | M/Y ledger report |
| `tests/Feature/InvestmentProfitReportTest.php` | Investment profit report |
| `tests/Feature/OpeningBalanceTest.php` | Opening balance bulk update |

### E2E tests (Playwright)

Browser-level end-to-end tests for the golden path: login → dashboard → sector profit → finalize → investor profit → export to Excel → logout.

| File | What it covers |
|------|---------------|
| `tests/e2e/golden-path.spec.ts` | Login, dashboard KPIs, sector profit grid, canonical July 2026 totals (Z2, X2), investor profit grid, Excel export (`For Sajid - July_2026.xlsx`), logout + invalid-login + unauthenticated-redirect edge cases |

#### Running E2E tests

```bash
# Prerequisites (one-time):
#   1. Docker dev environment running: docker compose up -d --build
#   2. Database seeded: docker compose exec app php artisan migrate:fresh --seed
#   3. Playwright browser installed:
docker compose exec node npx playwright install --with-deps chromium

# Run all E2E tests (headless):
npm run e2e

# Interactive UI mode (great for debugging):
npm run e2e:ui

# Run a specific test by name:
npm run e2e -- --grep "login"

# View the HTML report after a run:
npm run e2e:report
```

The E2E tests run against `http://localhost:8080` (the Nginx container's mapped port). If you changed `APP_PORT` in `.env.docker`, update `playwright.config.ts → use.baseURL` to match.

### Static analysis

```bash
# Docker
docker compose exec app vendor/bin/phpstan analyse

# Bare metal
vendor/bin/phpstan analyse
```

Target: **Larastan level 6** on all `app/Services/*` (financial logic).

### Formatting

```bash
# Docker
make pint

# Bare metal
vendor/bin/pint
```

---

## Production Deployment

See [`DEPLOYMENT.md`](./DEPLOYMENT.md) for the full production deployment guide, including:

- Nginx + PHP-FPM + PostgreSQL production config
- Systemd service files
- Backup strategy (`pg_dump` nightly + audit log archival)
- SSL/TLS via Let's Encrypt
- GitHub Actions CI/CD pipeline (lint + static analysis + tests + parity test)
- Security hardening checklist

---

## Project Plan & Status

See [`../MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md) for:

- The full 9-phase / 41-session roadmap
- The "📊 PROGRESS TRACKER" section at the top — overall phase status table, codebase inventory, known gaps, and recommended next steps
- Per-phase and per-session status markers (✅ / 🟡 / ❌) with file/commit references

### Headline status (2026-09-08)

**All 9 phases (Phase 0 → Phase 8) have at least a first-pass implementation** committed to `main` (52 commits total). The app boots, the 8-phase calculation engine passes its parity tests, audit logging is in place for every financial mutation, and both January + July 2026 seed data is loaded (July 2026 is the canonical "For Sajid" reference).

Known gaps (excluding 2FA per project decision):
- **Playwright E2E** — only Pest tests exist; no browser E2E
- **Pest test suite has CSRF issues** — 65 tests failing with HTTP 419 (work in progress)
- **Performance / virtualization** — `@tanstack/react-virtual` not yet installed for the 150-row investor grid

See the plan's Progress Tracker for the most current state.

---

## License

The Mudaraba Laravel application is proprietary software developed for the Mudaraba project. All rights reserved.
