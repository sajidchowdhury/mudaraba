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

## Current Status
**Phase 0 · Session 0.1** — Scaffolding complete. Design tokens applied. App boots.
