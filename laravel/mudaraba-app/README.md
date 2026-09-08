# Mudaraba — Laravel Profit Management System

Premium Laravel rebuild of the Mudaraba money-management & profit-distribution system.

## Tech Stack
- **Backend**: Laravel 13 (PHP 8.4) + PostgreSQL/MySQL/SQLite
- **Frontend**: Inertia.js + React 18 + TypeScript + Tailwind CSS 4 + shadcn/ui
- **Tooling**: Vite 8, Pint, Larastan, Pest

## Local Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Install frontend dependencies
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database (SQLite for dev — switch in .env for PostgreSQL/MySQL)
php artisan migrate

# 5. Build frontend
npm run build

# 6. Start dev server
php artisan serve
```

Visit http://localhost:8000 — you should see the Mudaraba welcome page with design-system preview.

## Project Plan
See [`../MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md) for the full 9-phase / 41-session roadmap.

## Current Status (updated 2026-09-08)

**All 9 phases (Phase 0 → Phase 8) have at least a first-pass implementation** committed to `main` (49 commits total).

| Layer | Status |
|-------|--------|
| Backend (controllers / services / models / migrations) | ✅ 17 controllers · 3 services · 23 models · 35 migrations · 3,548 LOC |
| Frontend (React + Inertia + shadcn/ui) | ✅ 27 UI components · 7 layout components · 28 pages · 7,477 LOC |
| Tests (Pest) | ✅ 22 feature tests + 1 unit test (incl. `ParityTest` against Excel math) |
| Seed data | ✅ January 2026 — 150 investors, 16 sectors, BDT 137M total investment |
| Docker dev env | ✅ 4-service docker-compose + Makefile + QUICKSTART.md |

**Known gaps** (prioritized in the plan's "Where to Start Next" section):
1. **2FA / TOTP** (Phase 2.3) — not yet implemented; `spomky-labs/otphp` not installed
2. **Seeder ≠ parity test** — seeder loads January 2026 data; `ParityTest.php` asserts July 2026 numbers
3. **"For Sajid" Excel export** — the investor-profit grid lacks a 1-click `.xlsx` export matching the Excel layout
4. **Playwright E2E** — only Pest tests exist; no browser E2E
5. **Audit log observers** — `AuditLog` model exists but not all mutations write to it
6. **Documentation** — README (this file) needs expansion; no `DEPLOYMENT.md` yet

See [`../MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md) → "📊 PROGRESS TRACKER" section for the full per-session breakdown and recommended next steps.
