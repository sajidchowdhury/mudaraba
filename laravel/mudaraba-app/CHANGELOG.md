# Changelog

All notable changes to the Mudaraba Laravel application are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) (MAJOR.MINOR.PATCH).

The Laravel rebuild lives at `laravel/mudaraba-app/` and was developed against the
9-phase / 41-session roadmap documented in
[`laravel/MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md).

The pre-Laravel raw PHP application is preserved at the repo root for traceability;
it is not part of the changelog below.

---

## [Unreleased]

No unreleased changes — `main` is the latest released version.

---

## [0.1.0] — 2026-09-08

The initial Laravel rebuild. All 9 phases (0 → 8) of the project plan have at least
a first-pass implementation. The app boots, the 8-phase calculation engine passes
its parity tests against the Excel "July, 2026 For Sajid" sheet numbers, audit
logging is in place for every financial mutation, and January 2026 production-shape
seed data is loaded (150 investors, 16 sectors, BDT 137M total investment).

**52 commits** across 11 days (Aug 29 → Sep 8, 2026).

### Added

#### Phase 0 — Foundation & Design System
- Laravel 13 scaffolded with Inertia.js + React 18 + TypeScript + Vite 8
- Tailwind CSS 4 + shadcn/ui (New York style) base component library (27 components: Button, Input, Card, Badge, Dialog, Sheet, Table, Tabs, Toaster, etc.)
- Custom ThemeProvider + ThemeToggle for light/dark mode
- AuthenticatedLayout shell with sticky TopBar (month switcher, search, user menu), collapsible Sidebar, MobileTabBar (responsive bottom-tab on mobile), Breadcrumb, sticky Footer
- `Cmd+K` command palette (uses `cmdk` package)
- Design-system showcase page at `/design-system`

#### Phase 1 — Database Design & Migrations
- 35 migration files covering the full schema:
  - Core: `users`, `employees`, `menus`, `user_permissions`, `audit_logs`, `directors`, `investors`, `sectors`
  - Transactions: `investment_transactions`, `sector_investments`, `director_transactions` (with `batch_uuid` for atomic ops)
  - Profit engine: `monthly_sector_profit`, `investor_monthly_profit_details`, `monthly_profit_summary`
  - Due ledgers (cumulative + monthly, capital + profit) for investor / sector / director entities
  - Retained earnings: `retained_earnings` (with generated columns for 71/29 split), `retained_earnings_distributions`
  - Adjustments: `advance_profit_adjustments` (Type C), `_type_a` (Fund A), `_type_b` (Fund B), `profit_adjustments` (unified)
  - Cache, jobs, sessions, password_reset_tokens
- 23 Eloquent models with relationships, scopes, casts, and PHP 8.1 enums
- `DueManager` trait implementing `updateDue`, `rollbackDue`, `updateDueAfterRollback`
- `January2026Seeder` + `january_2026_data.json` — production-shape seed data (150 investors, 16 sectors, BDT 137M total investment)
- User, menu, user-permission, sector, investor, director minimal seeders

#### Phase 2 — Authentication & RBAC
- Premium split-screen login UI (`/login`) — floating-label inputs, password visibility toggle, remember me, micro-interactions, mobile-responsive
- Custom `LoginController` (Fortify-style; no Sanctum — operator-only)
- Login throttling, bcrypt hashing, remember-me via encrypted cookie
- `PermissionMiddleware` for route-level `permission:{slug}` checks against `user_permissions` table
- `SuperadminMiddleware` for superadmin-only routes (lock/unlock month, opening balances, admin permissions)
- `HasPermissions` trait on `User` for permission helpers
- Admin UI to manage permissions per user × menu (`/admin/permissions`)
- Sidebar filters menu items by Inertia-shared user permissions

#### Phase 3 — Master Data Management
- Investors module — TanStack Table list (search, sort, filter, pagination), side-sheet form with Zod validation, deed_ratio segmented control (100/80/60), detail page with tabs (Profile, Investments, Profit History, Ledger), inline activation/deactivation
- Sectors module — same premium CRUD pattern
- Directors module — same pattern + `is_my` toggle (only one primary M/Y at a time)
- Investment Transactions — add/withdraw form with date picker, real-time running balance preview (`GET /investments/balance/{investor}`), color-coded history, `DueManager` integration

#### Phase 4 — The Profit Engine (the beating heart)
- Sector Profit Entry UI — 16-row Excel-like grid with inline-editable cells, Tab navigation, live totals row (Z2, X2, Y2)
- `ProfitCalculatorService` implementing the full 8-phase calculation engine from the Excel "For Sajid" sheet:
  - Phase 1: Totals (Z2 = Σ estimated, X2 = Σ actual, Y2 = Z2 − X2, D181 = Σ investment)
  - Phase 2: Per-investor ratio + primary share (Q = ratio × Z2, N = ratio × X2)
  - Phase 3: Tier application (AG = N × deed_ratio / 100)
  - Phase 4: Advance difference (AH = Q − AG)
  - Phase 5: Retained earnings allocation (RE_total = 200K, 71/29 split, retained_credit[i] = RE_investors × ratio)
  - Phase 6: Net settlement (AK = AH − retained_credit)
  - Phase 7: Aggregates (AH182, AG182, AJ182)
  - Phase 8: M/Y profit (AG184 = X2 − AG182, AG186 = AG184 / X2 × 100)
- `RetainedEarningsService` — 71/29 split + per-investor distribution
- `LedgerUpdateService` — rollback-on-edit pattern (preserved from PHP version), atomic within `batch_uuid` transaction
- Investor Profit View — the "For Sajid" page at `/profit/investor` — premium spreadsheet-like grid with sticky header + sticky totals row (matching Excel AG182, AH182, AG184, AG186), color-coded advance_diff (green/red), per-investor expandable row showing retained earnings breakdown, "Reconcile" CTA
- **"For Sajid" Excel export button** on the investor-profit page (commit `6d6b4c5`) — wires to `/exports/investment-profit?month=...`, filename `For Sajid - {Month Year}.xlsx`, sheet tab `For Sajid - {Month Year}`, totals row + M/Y profit + retained earnings blocks included below the data
- Month Closing & Lock — `draft → finalized → locked` status workflow, superadmin-only lock/unlock, month-end checklist UI (`/month-close`)

#### Phase 5 — Advance Profit Adjustments
- Unified "Fund A / Fund B / Direct" adjustments UI (`/adjustments`) — single page, single controller, all three types
- `AdjustmentType` enum (`FundA`, `FundB`, `Direct`)
- `AdjustmentTarget` enum (`Investor`, `Sector`)
- Fund B is sector-only (per PHP spec §5) — investor_items rejected at validation
- Fund A balance = Σ(investor amounts) − Σ(sector amounts) (computed on-the-fly from `profit_adjustments` records — no drift)
- Direct adjustment supports both `investor_wise` (per-investor) and `as_per_invest` (bulk by ratio) modes

#### Phase 6 — Opening Balances
- Single unified page (`/opening`) for M/Y + Investor + Sector opening balances (superadmin-only)
- `OpeningBalanceController` with `updateDirector`, `updateInvestors`, `updateSectors` bulk endpoints
- Initializes due ledgers (`director_due_ledger`, `investor_profit_due_ledger`, `sector_profit_due_ledger`)

#### Phase 7 — Reports & Dashboards
- Dashboard (`/dashboard`) — 5 KPI cards (Total Investment, Cash in Hand, This Month Profit, M/Y Profit YTD, Active Investors) with count-up animations, Recharts (monthly profit trend line, sector allocation donut, investor tier distribution stacked bar), recent activity feed (last 10 audit logs), quick-action buttons
- Investor Ledger Report (`/reports/investor-ledger`) — per-investor transaction timeline (capital adds/withdraws + profit distributions + adjustments), running balance column, date-range filter, PDF + Excel export
- Sector Ledger Report (`/reports/sector-ledger`) — per-sector: investments + profit history + due, PDF export
- M/Y Ledger Report (`/reports/my-ledger`) — M/Y withdrawals + profit accruals + retained earnings portion, PDF export
- Investment Profit Report (`/reports/investment-profit`) — cross-investor comparative view
- Exports — `barryvdh/laravel-dompdf` for PDF (replaces PHP's dompdf/mpdf), `maatwebsite/excel` for Excel

#### Phase 8 — Polish & Quality Assurance
- Mobile responsiveness audit — every page tested at 375 / 414 / 768 / 1024 / 1440px (commit `63feba3`)
- Premium UI touches — Framer Motion page transitions, skeleton loaders, number count-up animations on KPIs, subtle hover states + focus rings, empty-state component (commits `aec533f`)
- Performance — eager-load relationships (N+1 elimination), dashboard aggregate caching (5-min TTL via `Cache::remember`)
- Testing — `ParityTest.php` (5 tests verifying the 8-phase engine reproduces Excel math: Z2, X2, Y2, AG182, AG184, AG186, retained 71/29 split, idempotency), `ProfitCalculatorServiceTest.php` (unit), 22 Pest feature tests covering every module

#### Infrastructure — Docker Dev Environment
- Complete `docker-compose.yml` with 4 services:
  - `postgres` — PostgreSQL 16 Alpine with healthcheck
  - `app` — PHP 8.4-FPM Alpine with 5 extensions (pdo_pgsql, zip, intl, opcache, gd)
  - `nginx` — Nginx Alpine serving `/public` + proxying PHP to `app:9000`
  - `node` — Vite dev server (HMR) with named volume for Linux/musl-native node_modules
- Auto `.env` creation on first run, auto composer install, auto key:generate, auto-migrate + auto-seed
- `Makefile` with 12 helper targets (start/stop/reset/fresh/test/pint/build/logs/shell/...)
- `QUICKSTART.md` — Docker quick-start guide
- `refresh.sh` — pull + reseed helper

#### Infrastructure — Audit Log System
- `AuditService` — the audit writer (`log()`, `snapshot()`, `diff()`, IP + user-agent capture, non-numeric PK handling)
- `Auditable` trait — opt-in trait that auto-wires Eloquent `created`/`updating`/`updated`/`deleted` events (no AppServiceProvider registration needed; `bootAuditable()` is called by Laravel when the trait is used)
- Applied to 9 financial models: `InvestmentTransaction`, `SectorInvestment`, `DirectorTransaction`, `MonthlySectorProfit`, `InvestorMonthlyProfitDetail`, `AdvanceProfitAdjustment`, `AdvanceProfitAdjustmentTypeA`, `AdvanceProfitAdjustmentTypeB`, `ProfitAdjustment`, `MonthlyProfitSummary`
- Special-case overrides:
  - `MonthlySectorProfit` logs `finalize` action (not generic `update`) when status transitions to finalized
  - `MonthlyProfitSummary` logs `lock` and `unlock` for status transitions
  - `InvestorMonthlyProfitDetail` skips `delete` events (bulk-delete plumbing during re-finalize)
- `ProfitCalculatorService::calculate()` writes a single `reconcile` audit row representing the entire batch (regardless of investor count) — user-intent level audit record
- 14 Pest tests in `tests/Feature/AuditLogTest.php` covering every mutation type

#### Documentation
- `README.md` — full setup (Docker + bare metal), env vars, common commands, project structure, testing, project plan reference
- `DEPLOYMENT.md` — production deployment guide (Nginx + PHP-FPM + PostgreSQL + Redis + Systemd + Let's Encrypt + backup strategy + CI/CD + monitoring + scaling notes)
- `CHANGELOG.md` — this file
- `MUDARABA_LARAVEL_PROJECT_PLAN.md` — the master plan with a "📊 PROGRESS TRACKER" section at the top (overall phase status table, codebase inventory, known gaps, recommended next steps) and per-session status markers (✅/🟡/❌) throughout

### Changed

- (n/a — initial release)

### Deprecated

- (n/a — initial release)

### Removed

- (n/a — initial release)

### Fixed

#### Docker fixes
- `fix(docker): add ext-gd to PHP image` (`0d3f991`) — `maatwebsite/excel` → `phpoffice/phpspreadsheet` requires `ext-gd`; composer install was failing on Windows + Docker with "ext-gd is missing"
- `fix(docker): PHP 8.4 + minimal extensions` (`e138910`) — fixed "Cannot find config.m4" errors caused by env-var expansion issues on Alpine (musl-dev instead of libc-dev)
- `fix(docker): node_modules named volume` (`e3d8718`) — Vite 8 ships platform-specific native bindings; bind-mounting host's node_modules (Windows/macOS) into the Alpine container caused "Cannot find module '@rolldown/binding-linux-x64-musl'"
- `fix(docker): fresh-clone setup — auto .env, auto-seed, Makefile, Quickstart` (`ff82f80`)
- `fix(docker): simplify node entrypoint + fix Vite HMR for Docker Desktop` (`b6d8417`)
- `fix(docker): create storage/inertia-devtools + chmod 777 for Windows bind-mounts` (`0fe9aba`) — Laravel 13.29+ writes a .gitignore to `storage/inertia-devtools/` on first request; directory didn't exist in fresh clones + Windows bind-mounts ignore Unix chown, causing 500 Permission denied on login page

#### Test fixes
- `fix(tests): disable CSRF in tests via Pest beforeEach` (`8f6bf65`) — 65 Pest tests were failing with HTTP 419 (CSRF token mismatch) because tests submit POST/PUT/DELETE without a CSRF token; CSRF protection is the browser's job, not the test's
- `fix(bootstrap): use Env::get() instead of app()->environment()` (`7da3d9d`) — the previous CSRF fix used `app()->environment('testing')` which threw "Target class [env] does not exist" because the env repository isn't bound when `withMiddleware` runs during boot

#### Seeder fixes
- `fix(seeder): replace SQLite PRAGMA with DB-agnostic Schema::disableForeignKeyConstraints()` (`f020329`) — January2026Seeder used `PRAGMA foreign_keys=OFF` which is SQLite-only and broke on PostgreSQL
- `fix: migrations fail on PostgreSQL with 'Duplicate table' error` (`d0d7528`)

#### Sidebar / menu fixes
- `fix(sidebar): replace require() with static import for lucide-react` (`c366f47`)
- `fix: align menu route names with actual Laravel routes + crash-proof sidebar` (`67dcde9`)

### Security

- Default superadmin password (`Mudaraba@2026`) must be changed before going to production (documented in `DEPLOYMENT.md` security checklist)
- CSRF protection is enabled in production; disabled only in the testing environment via `tests/Pest.php`
- Session cookies set `HttpOnly`, `Secure` (when over HTTPS), `SameSite=Strict` (configurable via `.env`)
- `audit_logs` table tracks every financial mutation with acting user, IP, user agent, and before/after state

---

## Commit Index

The 52 commits, grouped by phase, oldest first within each group. Hashes are abbreviated (full hashes in `git log`).

### Project Planning & Documentation
- `5e7c3e6` — docs: add Laravel rebuild project plan (9 phases, 41 sessions)
- `8e84865` — docs(plan): add progress tracker + per-session status markers
- `2f0fca8` — fix(tests): disable CSRF verification in testing env
- `7da3d9d` — fix(bootstrap): use Env::get() instead of app()->environment() in withMiddleware
- `8f6bf65` — fix(tests): disable CSRF in tests via Pest beforeEach (bulletproof)

### Phase 0 — Foundation & Design System
- `4dc7640` — feat(phase-0): scaffold Laravel 13 + Inertia/React + design system
- `5e7449e` — feat(phase-0): expand design system + dark mode + showcase page
- `8d631c9` — feat(phase-0): build authenticated layout shell + Cmd+K palette

### Phase 1 — Database Design & Migrations
- `3853ba4` — feat(phase-1): core entity migrations + models
- `154f432` — feat(phase-1): transaction tables + enum types + signed amounts
- `7e4c59d` — feat(phase-1): profit engine tables (8-phase calc schema)
- `bac1627` — feat(phase-1): due ledger tables + DueManager trait
- `c078d68` — feat(phase-1): retained earnings + advance adjustments (completes Phase 1)

### Phase 2 — Authentication & RBAC
- `b47969c` — feat(phase-2): premium login UI (split-screen + floating labels)
- `9f2952f` — feat(phase-2): auth backend — login, logout, rate limiting, time window
- `43e82e3` — feat(phase-2): RBAC + menu permissions (full system)

### Phase 3 — Master Data Management
- `6d289a8` — feat(phase-3): investors module (full CRUD + premium UI)
- `9a727ad` — feat(phase-3): sectors module (full CRUD + premium UI)
- `d18b63b` — feat(phase-3): directors (M/Y) module (full CRUD + is_my toggle)
- `16c8ae8` — feat(phase-3): investment transactions (add/withdraw + DueManager integration)

### Phase 4 — The Profit Engine
- `6b0a803` — feat(phase-4): sector profit entry UI (Excel-like grid + live totals)
- `3afb18e` — feat(phase-4): profit calculation engine (8-phase algorithm)
- `068e29f` — feat(phase-4): retained earnings mechanism (Phases 5-7, 71/29 split)
- `6ecdc5d` — feat(phase-4): due ledger updates + M/Y profit (post-calculation sync)
- `88aecc7` — feat(phase-4): investor profit view — the 'For Sajid' page
- `e7e4d15` — feat(phase-4): month closing & lock (status workflow + checklist)
- `6d6b4c5` — feat(phase-4.5): For Sajid Excel export button + filename/sheet naming

### Phase 5 — Advance Profit Adjustments
- `b8176ac` — feat(phase-5): unified profit adjustments (Fund A + Fund B + Direct)

### Phase 6 — Opening Balances
- `d372321` — feat(phase-6): opening balances (M/Y + Investor + Sector in one page)

### Phase 7 — Reports & Dashboards
- `43968ff` — feat(phase-7): dashboard with real data + charts + KPI animations
- `c4719be` — feat(phase-7): investor ledger report (timeline + running balance)
- `03a4f9f` — feat(phase-7): sector ledger report (timeline + running balance)
- `80475df` — feat(phase-7): M/Y ledger report (director transactions + profit + retained)
- `9b5e202` — feat(phase-7): investment profit report (cross-investor comparison)
- `ae94520` — feat(phase-7): exports — PDF (investor/sector/MY ledger) + Excel (investment profit)

### Phase 8 — Polish & Quality Assurance
- `63feba3` — fix(phase-8): mobile responsiveness audit — all pages fixed
- `aec533f` — feat(phase-8): premium UI touches — page transitions + reusable components
- `823a396` — perf(phase-8): performance optimization — caching + query reduction
- `d546700` — test(phase-8): parity tests + ProfitCalculatorService unit tests

### Bonus (post-Phase 8 work)
- `251e62b` — feat: seed January 2026 data from Excel sheet (150 investors, 16 sectors)
- `63c0372` — feat: cash-in-hand dashboard + correct Jan 2026 data + fix profit adjustments (Fund A/B/Direct)
- `0e60f54` — chore: add refresh.sh helper script for quick pull + reseed
- `f020329` — fix(seeder): replace SQLite PRAGMA with DB-agnostic Schema::disableForeignKeyConstraints()
- `0d3f991` — fix(docker): add ext-gd to PHP image (required by maatwebsite/excel → phpspreadsheet)
- `0fe9aba` — fix(docker): create storage/inertia-devtools + chmod 777 for Windows bind-mounts
- `05ad0a5` — feat(audit): write audit_logs for every financial mutation (Gap #8)

### Docker / Infrastructure
- `2d26922` — infra(docker): add complete Docker dev environment
- `e138910` — fix(docker): PHP 8.4 + minimal extensions (fixes 'Cannot find config.m4')
- `e3d8718` — fix(docker): node_modules named volume for Linux/musl native bindings
- `ff82f80` — fix(docker): fresh-clone setup — auto .env, auto-seed, Makefile, Quickstart
- `b6d8417` — fix(docker): simplify node entrypoint + fix Vite HMR for Docker Desktop
- `c366f47` — fix(sidebar): replace require() with static import for lucide-react
- `67dcde9` — fix: align menu route names with actual Laravel routes + crash-proof sidebar
- `d0d7528` — fix: migrations fail on PostgreSQL with 'Duplicate table' error

### Initial
- `9e9bcb3`, `94e184f` — first commits
- `ec2413e` — DATABSE

---

## How to Update This Changelog

When you make a new release:

1. Add a new section at the top under `## [Unreleased]` with sections `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security` (only the relevant ones)
2. When you cut a release, rename `## [Unreleased]` to `## [X.Y.Z] — YYYY-MM-DD` and create a new empty `## [Unreleased]` above it
3. Reference GitHub issues / PRs where possible: `(#123)`, `(#456)`
4. Reference commit hashes for major refactors: `(\`abc1234\`)`
5. Group commits by phase (matching the project plan) — not by commit hash order

### Versioning

- **MAJOR** — breaking schema changes, API contract changes, business logic changes (e.g. changing the 71/29 split would be a major bump)
- **MINOR** — new features (new report, new export format, new audit action type)
- **PATCH** — bug fixes, dependency updates, documentation improvements

[Unreleased]: https://github.com/sajidchowdhury/mudaraba/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/sajidchowdhury/mudaraba/releases/tag/v0.1.0
