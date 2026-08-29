# Mudaraba Profit Management System
## Laravel Project Plan — Phase & Session Breakdown

> **Document purpose**: A complete, phase-by-phase implementation roadmap for rebuilding the Mudaraba money-management & profit-distribution system as a modern Laravel application with a premium, Excel-rivaling UI.
>
> **Scope**: Mudaraba engine only (MuRabaha parallel system is out of scope for now).
> **Source of business truth**: `Modaraba_System_Analysis_Report_2026-08-29.docx` (8-phase calculation engine, tier system, retained earnings, M/Y residual profit).
> **Implementation reference**: existing raw PHP project at `github.com/sajidchowdhury/mudaraba` (due-ledger pattern, master calc engine, RBAC).

---

## Table of Contents

1. [Project Vision & Goals](#1-project-vision--goals)
2. [Technology Stack Decisions](#2-technology-stack-decisions)
3. [UI/UX Design Principles](#3-uiux-design-principles)
4. [Database Design](#4-database-design)
5. [Business Logic Specification](#5-business-logic-specification)
6. [Phase & Session Roadmap (Overview)](#6-phase--session-roadmap-overview)
7. [Phase Details](#7-phase-details)
   - [Phase 0 — Foundation & Design System](#phase-0--foundation--design-system)
   - [Phase 1 — Database Design & Migrations](#phase-1--database-design--migrations)
   - [Phase 2 — Authentication & RBAC](#phase-2--authentication--rbac)
   - [Phase 3 — Master Data Management](#phase-3--master-data-management)
   - [Phase 4 — The Profit Engine](#phase-4--the-profit-engine)
   - [Phase 5 — Advance Profit Adjustments](#phase-5--advance-profit-adjustments)
   - [Phase 6 — Opening Balances](#phase-6--opening-balances)
   - [Phase 7 — Reports & Dashboards](#phase-7--reports--dashboards)
   - [Phase 8 — Polish & Quality Assurance](#phase-8--polish--quality-assurance)
8. [Testing Strategy](#8-testing-strategy)
9. [Risk Register & Mitigation](#9-risk-register--mitigation)
10. [Deployment Notes](#10-deployment-notes)
11. [Convention & Naming Standards](#11-convention--naming-standards)

---

## 1. Project Vision & Goals

### 1.1 Vision
A **premium, clean, mobile-friendly** Laravel web application that replaces the manual Excel workbook ("Investors Report 2026 New.xlsx" → "July, 2026 For Sajid" sheet) for managing a Mudaraba profit-sharing pool. The system must feel as fast and tactile as Excel for the M/Y's daily workflow, while enforcing data integrity, audit trails, and proper business logic that the spreadsheet cannot guarantee.

### 1.2 Goals

| # | Goal | Success Metric |
|---|------|----------------|
| G1 | Accurate replication of the Excel 8-phase calculation engine | Monthly reconciliation matches Excel to ±BDT 1.00 |
| G2 | Premium UI that an Excel power-user enjoys | First-time user can complete a monthly run without training |
| G3 | Mobile-friendly responsive design | 100% of workflows usable on a 375px-wide phone |
| G4 | Proper relational database with enforced integrity | Zero orphaned transactions; all FKs enforced |
| G5 | Audit-grade transaction history | Every financial mutation is traceable to user + timestamp |
| G6 | Add the missing Retained Earnings mechanism | 71/29 split of BDT 200K automated monthly |
| G7 | Role-based access control | Operators see only permitted modules |

### 1.3 Non-Goals (this project)
- MuRabaha parallel system (separate project later)
- Public-facing investor portal (operator-only for now)
- Real-time currency conversion / multi-currency
- Automated bank integration

### 1.4 Key Stakeholders

| Role | Who | Uses For |
|------|-----|----------|
| **M/Y (Modaraba Owner)** | Sajid / managing partner | Daily operation, profit entry, reports |
| **Operators** | Mohammad and team | Data entry, investor management |
| **Super Admin** | IT executive | User management, permissions, opening balances |
| **Auditors** (future) | External | Read-only ledger review |

---

## 2. Technology Stack Decisions

### 2.1 Backend
| Component | Choice | Rationale |
|-----------|--------|-----------|
| Framework | **Laravel 11** (latest LTS) | Mature, excellent ORM, queue, auth, validation |
| Language | **PHP 8.3+** | Typed properties, enums, readonly — fits financial domain |
| Database | **PostgreSQL 16** (primary) | True `DECIMAL` arithmetic, `CHECK` constraints, window functions, partial indexes — essential for finance. MySQL 8 is an acceptable fallback. |
| Cache | Redis | Session, cache, queue |
| Queue | Laravel Queue (Redis driver) | Async PDF generation, bulk imports |
| Search | Laravel Scout + database driver (for now) | Investor/sector quick search |

### 2.2 Frontend
| Component | Choice | Rationale |
|-----------|--------|-----------|
| Rendering | **Inertia.js + React 18** | SPA feel without building a separate API; Laravel routes serve React pages directly |
| Styling | **Tailwind CSS 4** | Utility-first, responsive, fast iteration |
| Components | **shadcn/ui** (New York style) | Premium, accessible, customizable, not a "template look" |
| Icons | **Lucide React** | Consistent, modern, lightweight |
| Charts | **Recharts** | React-native, responsive, composable |
| Tables | **TanStack Table v8** | Headless, supports Excel-like inline editing, virtualization for 150+ investor grids |
| Forms | **React Hook Form + Zod** | Performant, schema-validated |
| Notifications | **Sonner** (toasts) | Premium toast UX |
| Animations | **Framer Motion** | Subtle micro-interactions |

> **Why not Livewire/Filament?** Filament is excellent for admin CRUD but produces a recognisable "admin panel" aesthetic. The M/Y currently lives in Excel — we need a bespoke, premium financial UI, not a generic admin skin. Inertia + React + shadcn gives full creative control.

### 2.3 DevOps & Tooling
| Tool | Purpose |
|------|---------|
| Composer / npm | Package management |
| Laravel Sail OR Laravel Herd | Local dev |
| Git + GitHub | Version control (private repo) |
| Laravel Pint | PHP formatting |
| Larastan (PHPStan) | Static analysis — critical for financial code |
| Pest PHP | Testing (modern, expressive) |
| Playwright | E2E browser testing |
| GitHub Actions | CI: lint, static-analysis, tests |

---

## 3. UI/UX Design Principles

### 3.1 Design Philosophy
> *"Make the M/Y forget Excel — but feel at home."*

The system must combine **spreadsheet ergonomics** (fast bulk entry, inline editing, keyboard navigation, live formula previews) with **premium SaaS aesthetics** (clean whitespace, refined typography, subtle motion, considered color).

### 3.2 Visual Design System

**Color palette** (light + dark mode, NO indigo/blue per house style):

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `background` | `#FFFFFF` | `#0A0A0B` | App canvas |
| `surface` | `#FAFAFA` | `#141416` | Cards, panels |
| `surface-2` | `#F4F4F5` | `#1C1C1F` | Nested sections, table headers |
| `border` | `#E4E4E7` | `#27272A` | Dividers |
| `foreground` | `#18181B` | `#FAFAFA` | Primary text |
| `muted` | `#71717A` | `#A1A1AA` | Secondary text |
| `primary` | **Emerald `#10B981`** | same | Action, positive (growth/profit) |
| `accent` | **Amber `#F59E0B`** | same | Highlight, retained earnings |
| `success` | `#22C55E` | same | Receivable to M/Y |
| `danger` | `#EF4444` | same | Payable / over-paid / loss |
| `warning` | `#F59E0B` | same | Variance flag |
| `info` | `#06B6D4` | same | Neutral info |

> Emerald as primary signals growth & Islamic-finance green-adjacent connotation without being literal. Amber accents tie to retained-earnings gold.

**Typography**:
- Sans: **Inter** (UI, tables, forms)
- Mono: **JetBrains Mono** (numbers, monetary values, cell refs) — monospace numbers align in columns like Excel
- Display: **Inter Tight** (headings, dashboard KPIs)

**Spacing & radius**: 4px grid; `rounded-xl` (12px) cards; `rounded-lg` (8px) inputs; `rounded-md` (6px) buttons.

**Shadows**: Layered, soft — `shadow-sm` default, `shadow-md` on hover, `shadow-lg` on modals.

### 3.3 Interaction Principles

| Principle | Implementation |
|-----------|---------------|
| **Spreadsheet-grade data entry** | TanStack Table with inline-editable cells; Tab/Enter navigation; paste from Excel; "fill down" |
| **Live calculation preview** | As M/Y types sector actuals, investor profit columns recompute in real-time before save (mirrors Excel formula feel) |
| **Color-coded financials** | Over-payment (receivable) = green tint; under-payment (payable) = red tint; zero = neutral |
| **Month switcher always visible** | Top-bar persistent month selector — jump between months instantly |
| **Sticky summary row** | Totals (X2, Y2, Z2, M/Y profit) pinned at top of investor grid, updating live |
| **Keyboard-first** | `Cmd/Ctrl+K` command palette; `J/K` to navigate tables; `E` to edit; `S` to save |
| **Subtle motion** | Framer Motion: 150ms ease-out for hovers; 200ms for panels; number count-up animations on KPIs |
| **Empty states** | Every empty view has an illustration + CTA (never blank) |
| **Toast feedback** | Every save/update/delete confirms with Sonner toast (success/danger variants) |
| **Optimistic + skeleton loading** | Skeleton rows on fetch; optimistic update on inline edit |

### 3.4 Layout Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  TopBar: [☰] [Month▼ Jul 2026]  [Search⌘K]  [Bell] [User▼]   │  ← sticky
├────────────┬─────────────────────────────────────────────────┤
│            │  Breadcrumb: Home / Profit / Investor Profit     │
│  Sidebar   │ ┌─────────────────────────────────────────────┐  │
│  (collap-  │ │  Page Title + contextual actions             │  │
│  sible on  │ ├─────────────────────────────────────────────┤  │
│  mobile →  │ │                                             │  │
│  bottom    │ │       Main content area                     │  │
│  tab bar)  │ │                                             │  │
│            │ │                                             │  │
│            │ └─────────────────────────────────────────────┘  │
├────────────┴─────────────────────────────────────────────────┤
│  Footer (sticky): © 2026 Mudaraba • v1.0 • Last sync 2m ago   │
└──────────────────────────────────────────────────────────────┘
```

**Mobile**: sidebar collapses to a bottom tab bar (Dashboard, Profit, Reports, More). Month switcher becomes a prominent card at top of every screen.

### 3.5 Key Page Mockups (described)

1. **Dashboard** — 4 KPI cards (Total Investment, This Month Profit, M/Y Profit YTD, Active Investors) + 2 charts (monthly profit trend line, sector allocation donut) + recent activity feed.

2. **Investor Profit page** — Split view:
   - Left: editable sector profit table (17 sectors × estimated/actual, like Excel V5:Z20)
   - Right: live investor grid (151 rows × investment/ratio/primary/actual/tier/advance-diff/retained/net) with sticky totals
   - Top: month selector + "Save & Reconcile" CTA + variance badge

3. **Investor Ledger** — Filterable transaction timeline per investor with running balance, color-coded entries, PDF/Excel export.

---

## 4. Database Design

### 4.1 Design Improvements over the PHP Version

| Issue in PHP version | Fix in Laravel version |
|----------------------|------------------------|
| No foreign keys (only logical) | All FKs enforced at DB level |
| Mixed `float(20,2)` / `double` / `decimal` | Uniform `DECIMAL(20,2)` everywhere money |
| `advance_paid` column actually stored the *difference* | Renamed `advance_difference`; separate `primary_profit_share` column |
| No Retained Earnings tables | New `retained_earnings` + `retained_earnings_distributions` |
| No audit trail | `audit_logs` table + model observations |
| No soft deletes | Soft deletes on financial records (never hard-delete) |
| `created_by` only on some tables | Universal `created_by`, `updated_by`, `deleted_by` |
| Month stored as `varchar(7)` `'2026-07'` | Native `DATE` (1st of month) + generated `YYYY-MM` column |
| No transaction batching | `transaction_batch` UUID for atomic month-reconcile ops |

### 4.2 Full Schema (PostgreSQL DDL — condensed)

```sql
-- ============================================================
-- AUDIT & USERS
-- ============================================================

CREATE TABLE users (
    id              BIGSERIAL PRIMARY KEY,
    employee_id     BIGINT,
    username        VARCHAR(50) UNIQUE NOT NULL,
    email           VARCHAR(120) UNIQUE,
    password_hash   TEXT NOT NULL,
    role            VARCHAR(20) NOT NULL DEFAULT 'user'
                    CHECK (role IN ('user','admin','superadmin')),
    status          VARCHAR(10) NOT NULL DEFAULT 'Active'
                    CHECK (status IN ('Active','Inactive','Suspended')),
    login_start     TIME,
    login_end       TIME,
    two_factor_secret TEXT,                  -- TOTP secret
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

CREATE TABLE employees (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    designation     VARCHAR(100),
    phone           VARCHAR(20),
    status          VARCHAR(20) DEFAULT 'Active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

CREATE TABLE menus (
    id              BIGSERIAL PRIMARY KEY,
    parent_id       BIGINT REFERENCES menus(id) ON DELETE CASCADE,
    name            VARCHAR(120) NOT NULL,
    route           VARCHAR(120),
    icon            VARCHAR(100),
    sort_order      INT DEFAULT 0,
    is_parent       BOOLEAN DEFAULT FALSE
);

CREATE TABLE user_permissions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    menu_id         BIGINT NOT NULL REFERENCES menus(id) ON DELETE CASCADE,
    can_view        BOOLEAN DEFAULT FALSE,
    can_backdate    BOOLEAN DEFAULT FALSE,
    can_edit        BOOLEAN DEFAULT FALSE,
    can_delete      BOOLEAN DEFAULT FALSE,
    UNIQUE (user_id, menu_id)
);

CREATE TABLE audit_logs (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    action          VARCHAR(50) NOT NULL,    -- create/update/delete/reconcile
    entity_type     VARCHAR(60) NOT NULL,
    entity_id       BIGINT,
    before_data     JSONB,
    after_data      JSONB,
    ip_address      INET,
    user_agent      TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- CORE ENTITIES
-- ============================================================

CREATE TABLE directors (           -- The M/Y (managing owner)
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    mobile          VARCHAR(20),
    address         TEXT,
    is_my           BOOLEAN DEFAULT FALSE,   -- flag the primary M/Y
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

CREATE TABLE investors (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    reference       VARCHAR(120),           -- "MD", "German", etc.
    mobile          VARCHAR(20),
    address         TEXT,
    deed_ratio      NUMERIC(5,2) NOT NULL DEFAULT 100.00
                    CHECK (deed_ratio IN (100, 80, 60)),  -- tier %
    start_profit_month DATE,                 -- when they begin earning
    end_profit_month   DATE,                 -- when they stop
    status          VARCHAR(20) DEFAULT 'active'
                    CHECK (status IN ('active','inactive','closed')),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
CREATE INDEX idx_investors_status ON investors(status) WHERE deleted_at IS NULL;

CREATE TABLE sectors (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    mobile          VARCHAR(20),
    address         TEXT,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

-- ============================================================
-- CAPITAL TRANSACTIONS (add / withdraw)
-- ============================================================

CREATE TABLE investment_transactions (
    id              BIGSERIAL PRIMARY KEY,
    investor_id     BIGINT NOT NULL REFERENCES investors(id),
    amount          DECIMAL(20,2) NOT NULL CHECK (amount > 0),
    type            VARCHAR(10) NOT NULL CHECK (type IN ('add','withdraw')),
    transaction_month DATE NOT NULL,        -- always 1st of month
    transaction_date DATE NOT NULL,
    remarks         TEXT,
    batch_uuid      UUID,                   -- groups atomic ops
    created_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
CREATE INDEX idx_inv_tx_investor_month ON investment_transactions(investor_id, transaction_month);

CREATE TABLE sector_investments (
    id              BIGSERIAL PRIMARY KEY,
    sector_id       BIGINT NOT NULL REFERENCES sectors(id),
    amount          DECIMAL(20,2) NOT NULL CHECK (amount > 0),
    type            VARCHAR(10) NOT NULL CHECK (type IN ('add','withdraw')),
    transaction_date DATE NOT NULL,
    remarks         TEXT,
    batch_uuid      UUID,
    created_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

CREATE TABLE director_transactions (
    id              BIGSERIAL PRIMARY KEY,
    director_id     BIGINT NOT NULL REFERENCES directors(id),
    amount          DECIMAL(20,2) NOT NULL CHECK (amount > 0),
    type            VARCHAR(10) NOT NULL CHECK (type IN ('withdraw','return')),
    transaction_month DATE NOT NULL,
    transaction_date DATE NOT NULL,
    remarks         TEXT,
    batch_uuid      UUID,
    created_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

-- ============================================================
-- PROFIT ENGINE
-- ============================================================

-- Per-sector monthly estimated + actual profit
CREATE TABLE monthly_sector_profit (
    id              BIGSERIAL PRIMARY KEY,
    sector_id       BIGINT NOT NULL REFERENCES sectors(id),
    profit_month    DATE NOT NULL,          -- 1st of month
    transaction_date DATE,
    estimated_profit DECIMAL(15,2) DEFAULT 0,   -- Excel Z column (Primary)
    actual_profit    DECIMAL(15,2) DEFAULT 0,    -- Excel X column
    profit_adjustment DECIMAL(15,2) DEFAULT 0,
    is_estimate     BOOLEAN DEFAULT FALSE,
    status          VARCHAR(20) DEFAULT 'draft'
                    CHECK (status IN ('draft','finalized')),
    created_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ,
    UNIQUE (sector_id, profit_month)
);

-- Per-investor monthly computed profit (the 8-phase engine output)
CREATE TABLE investor_monthly_profit_details (
    id                          BIGSERIAL PRIMARY KEY,
    profit_month                DATE NOT NULL,
    transaction_date            DATE,
    investor_id                 BIGINT NOT NULL REFERENCES investors(id),
    investment                  DECIMAL(20,2) DEFAULT 0,   -- Excel D
    investment_ratio            NUMERIC(10,6) DEFAULT 0,   -- Excel E
    primary_profit_share        DECIMAL(20,2) DEFAULT 0,   -- Excel Q/F (the advance)
    actual_profit_at_full       DECIMAL(20,2) DEFAULT 0,   -- Excel N (100% share)
    deed_ratio                  NUMERIC(5,2) DEFAULT 100,  -- Excel AF as %
    actual_profit_due           DECIMAL(20,2) DEFAULT 0,   -- Excel AG (after tier)
    advance_difference          DECIMAL(20,2) DEFAULT 0,   -- Excel AH (over/under)
    retained_earnings_credit    DECIMAL(20,2) DEFAULT 0,   -- Excel AJ
    net_settlement              DECIMAL(20,2) DEFAULT 0,    -- Excel AK
    batch_uuid                  UUID,
    created_by                  BIGINT REFERENCES users(id),
    created_at                  TIMESTAMPTZ DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ,
    UNIQUE (profit_month, investor_id)
);

-- Month-level totals + M/Y profit
CREATE TABLE monthly_profit_summary (
    profit_month                DATE PRIMARY KEY,
    transaction_date            DATE,
    total_estimated_profit      DECIMAL(18,2) DEFAULT 0,   -- Excel Z2
    total_actual_profit         DECIMAL(18,2) DEFAULT 0,   -- Excel X2
    total_advance_difference    DECIMAL(18,2) DEFAULT 0,   -- Excel Y2 (sector-level)
    total_investor_advance_diff  DECIMAL(18,2) DEFAULT 0,  -- Excel AH182
    total_investor_profit_due   DECIMAL(18,2) DEFAULT 0,   -- Excel AG182
    total_investor_retained     DECIMAL(18,2) DEFAULT 0,   -- Excel AJ182
    my_profit                   DECIMAL(18,2) DEFAULT 0,   -- Excel AG184
    my_profit_ratio             NUMERIC(6,2) DEFAULT 0,    -- Excel AG186 %
    total_mudaraba_investment   DECIMAL(20,2) DEFAULT 0,   -- Excel D181
    active_investor_count       INT DEFAULT 0,
    status                      VARCHAR(20) DEFAULT 'open'
                                CHECK (status IN ('open','finalized','locked')),
    finalized_by                BIGINT REFERENCES users(id),
    finalized_at                TIMESTAMPTZ,
    updated_at                  TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- RETAINED EARNINGS (NEW — was missing in PHP version)
-- ============================================================

CREATE TABLE retained_earnings (
    id              BIGSERIAL PRIMARY KEY,
    profit_month    DATE NOT NULL UNIQUE,
    total_amount    DECIMAL(18,2) NOT NULL DEFAULT 200000,  -- BDT 200K target
    investor_portion_pct NUMERIC(5,2) DEFAULT 71.00,
    my_portion_pct       NUMERIC(5,2) DEFAULT 29.00,
    investor_amount DECIMAL(18,2) GENERATED ALWAYS AS (total_amount * investor_portion_pct / 100) STORED,
    my_amount        DECIMAL(18,2) GENERATED ALWAYS AS (total_amount * my_portion_pct / 100) STORED,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ
);

CREATE TABLE retained_earnings_distributions (
    id              BIGSERIAL PRIMARY KEY,
    profit_month    DATE NOT NULL,
    investor_id     BIGINT NOT NULL REFERENCES investors(id),
    investment_ratio NUMERIC(10,6) NOT NULL,
    amount          DECIMAL(18,2) NOT NULL,    -- = investor_portion × ratio
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (profit_month, investor_id)
);

-- ============================================================
-- DUE LEDGERS (cumulative + monthly, per entity)
-- Pattern preserved from PHP version — proven design
-- ============================================================

-- Investor capital due (cumulative)
CREATE TABLE investor_due_ledger (
    investor_id     BIGINT PRIMARY KEY REFERENCES investors(id),
    due             DECIMAL(20,2) DEFAULT 0,
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE TABLE investor_monthly_due (
    investor_id     BIGINT NOT NULL REFERENCES investors(id),
    due_month       DATE NOT NULL,
    due             DECIMAL(20,2) DEFAULT 0,
    PRIMARY KEY (investor_id, due_month)
);

-- Investor *profit* due (separate from capital)
CREATE TABLE investor_profit_due_ledger (
    investor_id     BIGINT PRIMARY KEY REFERENCES investors(id),
    due             DECIMAL(20,2) DEFAULT 0,
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE TABLE investor_profit_monthly_due (
    investor_id     BIGINT NOT NULL REFERENCES investors(id),
    due_month       DATE NOT NULL,
    due             DECIMAL(20,2) DEFAULT 0,
    PRIMARY KEY (investor_id, due_month)
);

-- Sector due
CREATE TABLE sector_due_ledger (
    sector_id       BIGINT PRIMARY KEY REFERENCES sectors(id),
    due             DECIMAL(20,2) DEFAULT 0
);
CREATE TABLE sector_monthly_due (
    sector_id       BIGINT NOT NULL REFERENCES sectors(id),
    due_month       DATE NOT NULL,
    due             DECIMAL(20,2) DEFAULT 0,
    PRIMARY KEY (sector_id, due_month)
);
CREATE TABLE sector_profit_due_ledger (
    sector_id       BIGINT PRIMARY KEY REFERENCES sectors(id),
    due             DECIMAL(20,2) DEFAULT 0
);
CREATE TABLE sector_profit_monthly_due (
    sector_id       BIGINT NOT NULL REFERENCES sectors(id),
    due_month       DATE NOT NULL,
    due             DECIMAL(20,2) DEFAULT 0,
    PRIMARY KEY (sector_id, due_month)
);

-- Director (M/Y) due
CREATE TABLE director_due_ledger (
    director_id     BIGINT PRIMARY KEY REFERENCES directors(id),
    due             DECIMAL(20,2) DEFAULT 0
);
CREATE TABLE director_monthly_due (
    director_id     BIGINT NOT NULL REFERENCES directors(id),
    due_month       DATE NOT NULL,
    due             DECIMAL(20,2) DEFAULT 0,
    PRIMARY KEY (director_id, due_month)
);

-- ============================================================
-- ADVANCE PROFIT ADJUSTMENTS (Type A / B / General)
-- ============================================================

CREATE TABLE advance_profit_adjustments (        -- Type C (general)
    id              BIGSERIAL PRIMARY KEY,
    sector_id       BIGINT REFERENCES sectors(id),
    investor_id     BIGINT REFERENCES investors(id),
    amount          DECIMAL(20,2) NOT NULL,
    transaction_date DATE NOT NULL,
    profit_month    DATE NOT NULL,
    remarks         TEXT,
    created_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE advance_profit_adjustments_type_a (
    id              BIGSERIAL PRIMARY KEY,
    transaction_date DATE NOT NULL UNIQUE,
    amount          DECIMAL(20,2) NOT NULL,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE advance_profit_adjustments_type_b (
    id              BIGSERIAL PRIMARY KEY,
    transaction_date DATE NOT NULL UNIQUE,
    amount          DECIMAL(20,2) NOT NULL,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- OPENING BALANCES
-- ============================================================

CREATE TABLE opening_director_due (
    id              BIGSERIAL PRIMARY KEY,
    director_id     BIGINT NOT NULL REFERENCES directors(id),
    amount          DECIMAL(20,2),
    transaction_date DATE,
    profit_month    DATE,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE TABLE opening_investor_profit_due (
    id              BIGSERIAL PRIMARY KEY,
    investor_id     BIGINT NOT NULL REFERENCES investors(id),
    amount          DECIMAL(20,2),
    transaction_date DATE,
    profit_month    DATE,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE TABLE opening_sector_profit_due (
    id              BIGSERIAL PRIMARY KEY,
    sector_id       BIGINT NOT NULL REFERENCES sectors(id),
    amount          DECIMAL(20,2),
    transaction_date DATE,
    profit_month    DATE,
    remarks         TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 4.3 Entity-Relationship Summary

```
users ──< user_permissions >── menus
users >── employees
investors ──< investment_transactions
investors ──< investor_monthly_profit_details >── monthly_profit_summary
investors ──< investor_due_ledger / investor_monthly_due
investors ──< investor_profit_due_ledger / investor_profit_monthly_due
investors ──< retained_earnings_distributions >── retained_earnings
sectors ──< sector_investments
sectors ──< monthly_sector_profit
sectors ──< sector_due_ledger / sector_monthly_due
sectors ──< sector_profit_due_ledger / sector_profit_monthly_due
directors ──< director_transactions
directors ──< director_due_ledger / director_monthly_due
audit_logs >── users (everywhere)
```

---

## 5. Business Logic Specification

### 5.1 The 8-Phase Calculation Engine (canonical, from DOCX)

```
INPUT: 17 sectors × (estimated_profit, actual_profit) for month M
       151 investors × (investment balance, deed_ratio) as of month M

PHASE 1 — Totals
  Z2 = Σ sector.estimated_profit         (total primary / advance pool)
  X2 = Σ sector.actual_profit            (total realized)
  Y2 = Z2 − X2                           (sector advance difference)
  D181 = Σ investor.investment           (total mudaraba pool)

PHASE 2 — Per-investor ratio & primary share
  FOR each investor i:
    ratio[i]      = investment[i] / D181
    primary[i]    = ratio[i] × Z2         (paid as advance, start of month)
    actual_full[i] = ratio[i] × X2       (their 100% entitlement)

PHASE 3 — Tier application
  actual_due[i] = actual_full[i] × deed_ratio[i] / 100
  (deed_ratio ∈ {100, 80, 60})

PHASE 4 — Advance difference (receivable/payable)
  advance_diff[i] = primary[i] − actual_due[i]
    positive → investor was over-paid, owes M/Y
    negative → investor under-paid, M/Y owes them

PHASE 5 — Retained earnings distribution (NEW vs PHP version)
  RE_total = 200,000      (configurable per month)
  RE_investors = RE_total × 71%  = 142,000
  RE_my        = RE_total × 29%  = 58,000
  retained_credit[i] = RE_investors × ratio[i]

PHASE 6 — Net settlement
  net[i] = advance_diff[i] − retained_credit[i]
    positive → investor owes M/Y (after retained credit)
    negative → M/Y owes investor

PHASE 7 — Aggregates
  AH182 = Σ advance_diff[i]                 (total investor over-payment)
  AG182 = Σ actual_due[i]                  (total investor profit)
  AJ182 = Σ retained_credit[i]             (total investor retained credit)

PHASE 8 — M/Y profit
  my_profit = AH182 − Y2                   (Excel AG184)
            = (Z2 − AG182) − (Z2 − X2)
            = X2 − AG182                    (algebraically identical)
  my_ratio  = my_profit / X2 × 100         (target ≈ 29.13%)
```

### 5.2 Month Reconciliation Flow

```
1. M/Y opens month M (status = 'draft')
2. Enters estimated sector profits → Z column (start of month)
3. System computes primary_profit_share for all active investors → distributed as advance
4. Month elapses...
5. M/Y enters actual sector profits → X column (end of month)
6. System reconciles:
   a. Computes actual_due, advance_diff, retained_credit, net per investor
   b. Updates investor_profit_due_ledger (+/- advance_diff)
   c. Updates investor_profit_monthly_due
   d. Updates sector_profit_due_ledger (Y2 differences)
   e. Updates director_due_ledger (my_profit)
   f. Writes monthly_profit_summary
   g. Status → 'finalized'
7. Lock month (status = 'locked') — no further edits without admin override
```

### 5.3 Due-Ledger Rollback-on-Edit Pattern (preserved from PHP)

When re-saving a finalized month, the system must NOT double-count. The pattern:

```
FOR each investor i with prior saved values:
  old_advance_diff = stored advance_diff
  new_advance_diff = recomputed advance_diff
  delta = new_advance_diff − old_advance_diff
  investor_profit_monthly_due[i][M] += delta
  investor_profit_due_ledger[i]     += delta
```

This is implemented as a DB transaction wrapped in a `batch_uuid` for atomicity.

### 5.4 Investment Balance Computation

```
investment_till_month(investor, M) =
  Σ (add amounts) − Σ (withdraw amounts)
  WHERE transaction_month <= M
```

Stored in `investor_due_ledger` as a running balance, updated on each `investment_transactions` insert.

---

## 6. Phase & Session Roadmap (Overview)

| Phase | Title | Sessions | Est. Effort | Status Gate |
|-------|-------|----------|-------------|-------------|
| 0 | Foundation & Design System | 3 | Medium | App boots, design tokens applied |
| 1 | Database Design & Migrations | 6 | Medium | `php artisan migrate:fresh` clean |
| 2 | Authentication & RBAC | 4 | Medium | Login works, permissions enforced |
| 3 | Master Data Management | 4 | Medium | CRUD for investors/sectors/directors |
| 4 | The Profit Engine | 6 | **High** | Monthly reconciliation matches Excel |
| 5 | Advance Profit Adjustments | 4 | Medium | Type A/B/C adjustments working |
| 6 | Opening Balances | 3 | Low | Opening entries migrate PHP data |
| 7 | Reports & Dashboards | 6 | Medium | All ledgers + dashboard + exports |
| 8 | Polish & QA | 5 | Medium | Mobile pass, E2E tests, deploy-ready |

**Total: 41 sessions** across 9 phases.

---

## 7. Phase Details

### Phase 0 — Foundation & Design System

**Goal**: A bootable Laravel app with the premium design system in place, ready to receive features.

#### Session 0.1 — Project Scaffolding
- Install Laravel 11 via `composer create-project laravel/laravel mudaraba`
- Configure PostgreSQL connection in `.env`
- Install Inertia.js + React + Vite
- Install Tailwind CSS 4 + shadcn/ui CLI
- Install Lucide, Recharts, TanStack Table, React Hook Form, Zod, Sonner, Framer Motion
- Configure Pint + Larastan + Pest
- Set up Git repo + initial commit
- **Deliverable**: App boots at `/`, shows "Mudaraba" placeholder with design tokens

#### Session 0.2 — Design System & Theme
- Define Tailwind theme tokens (colors from §3.2, typography, spacing)
- Build base shadcn/ui components: Button, Input, Card, Badge, Dialog, Sheet, Table, Tabs, Toast
- Light + dark mode via `next-themes` equivalent (`laravel-dark-mode` package or custom)
- Monospace numeric font for monetary cells
- **Deliverable**: Storybook-like showcase page rendering all components

#### Session 0.3 — Layout Shell (App Chroma)
- Build `AuthenticatedLayout` with sticky TopBar (month switcher, search, user menu)
- Build collapsible Sidebar with menu structure from §3.4
- Mobile bottom-tab-bar responsive behavior
- Breadcrumb component
- Footer (sticky to bottom, pushes down on overflow per house rule)
- Command palette (`Cmd+K`) scaffold
- **Deliverable**: Logged-in shell renders responsively on mobile + desktop

---

### Phase 1 — Database Design & Migrations

**Goal**: Clean, enforced, audited schema matching §4.2.

#### Session 1.1 — Core Entity Migrations
- `users`, `employees`, `menus`, `user_permissions`, `audit_logs`, `directors`, `investors`, `sectors`
- All FKs, CHECKs, indexes
- Soft-delete columns
- **Deliverable**: `php artisan migrate` runs clean

#### Session 1.2 — Transaction Tables
- `investment_transactions`, `sector_investments`, `director_transactions`
- `batch_uuid` columns
- Indexes on `(entity_id, transaction_month)`
- **Deliverable**: Migration + Eloquent models with relationships

#### Session 1.3 — Profit Engine Tables
- `monthly_sector_profit`, `investor_monthly_profit_details`, `monthly_profit_summary`
- Unique constraints on `(profit_month, investor_id)` etc.
- **Deliverable**: Models + a `ProfitCalculator` service class scaffold

#### Session 1.4 — Due Ledger Tables
- All 6 entity due-ledger + monthly-due pairs
- Postgres `ON CONFLICT DO UPDATE` (or MySQL `ON DUPLICATE KEY UPDATE`) upserts
- **Deliverable**: `DueManager` trait / service implementing `updateDue`, `rollbackDue`, `updateDueAfterRollback`

#### Session 1.5 — Retained Earnings & Adjustments
- `retained_earnings` (with generated columns for split amounts)
- `retained_earnings_distributions`
- `advance_profit_adjustments` + `_type_a` + `_type_b`
- **Deliverable**: Migration + RetainedEarningsService

#### Session 1.6 — Seeders & Reference Data
- Seed: default superadmin user, menu tree (matching PHP `menus` table), sample 4 sectors, 6 investors (mimic PHP seed data for parity testing)
- **Deliverable**: `php artisan db:seed` populates minimal working dataset

---

### Phase 2 — Authentication & RBAC

**Goal**: Premium login experience + granular permissions.

#### Session 2.1 — Login UI (Premium & Creative)
- Split-screen design: left = brand panel with subtle gradient + Mudaraba illustration; right = login form
- Floating-label inputs, password visibility toggle, "remember me", forgot password link
- Micro-interaction: logo pulse, form slide-in
- Mobile: stacked, brand panel collapses to a header band
- Error states with inline validation messages
- **Deliverable**: `/login` renders premium, responsive, accessible (WCAG AA)

#### Session 2.2 — Auth Backend
- Laravel Fortify (session-based, no Sanctum since operator-only)
- Login attempt throttling
- Password hashing (bcrypt)
- "Remember me" via encrypted cookie
- Session timeout (30 min idle, matching PHP `config.inc.php`)
- Login time-window enforcement (`login_start` / `login_end`)
- **Deliverable**: Login → redirect to dashboard; logout works

#### Session 2.3 — Two-Factor Auth (TOTP)
- Implement TOTP using `spomky-labs/otphp` (same lib as PHP version)
- Setup flow: QR code → verify 6-digit code → enable
- Login flow: after password, prompt for 6-digit code
- Backup recovery codes
- **Deliverable**: 2FA enrollable + enforced for superadmin role

#### Session 2.4 — RBAC & Menu Permissions
- Middleware `permission:view:menu-slug` checks `user_permissions`
- Sidebar renders only permitted menus
- Route-level permission enforcement
- Admin UI to manage permissions per user × menu
- **Deliverable**: Different roles see different sidebars; direct URL access blocked

---

### Phase 3 — Master Data Management

**Goal**: Full CRUD for investors, sectors, directors with premium list + form UX.

#### Session 3.1 — Investors Module
- List page: TanStack Table with search, sort, filter (by tier, status), pagination, bulk actions
- Create/Edit: side-sheet form with validation (Zod), deed_ratio selector (100/80/60 segmented control)
- Detail page: tabs (Profile, Investments, Profit History, Ledger)
- Inline activation/deactivation
- **Deliverable**: Investor CRUD end-to-end, mobile-friendly

#### Session 3.2 — Sectors Module
- List + Create/Edit similar pattern
- Show current investment balance + active status
- **Deliverable**: Sector CRUD

#### Session 3.3 — Directors / M/Y Module
- List + Create/Edit
- Flag primary M/Y (`is_my = true`)
- **Deliverable**: Director CRUD

#### Session 3.4 — Investment Transactions
- Add/Withdraw form per investor with date picker (restricted by permission `can_backdate`)
- Real-time running balance preview
- Transaction history table with color-coded add/withdraw
- Bulk import via CSV (paste from Excel)
- **Deliverable**: Investment transaction entry + history

---

### Phase 4 — The Profit Engine

**Goal**: The beating heart — accurate replication of Excel's 8-phase engine with retained earnings.

#### Session 4.1 — Sector Profit Entry UI
- Monthly sector grid (17 rows × estimated/actual) resembling Excel V5:Z20
- Inline editable cells with Tab navigation
- Live totals row (Z2, X2, Y2) updating as you type
- "Save as Draft" vs "Finalize" buttons
- Month selector prominent at top
- **Deliverable**: M/Y can enter sector profits like Excel

#### Session 4.2 — Investor Profit Calculation Engine
- `ProfitCalculatorService` implementing §5.1 eight phases
- Triggered on sector profit save
- Computes per-investor: ratio, primary, actual_full, actual_due, advance_diff
- Bulk upsert into `investor_monthly_profit_details` within DB transaction
- **Deliverable**: Saving sector profits auto-computes investor profits

#### Session 4.3 — Retained Earnings Mechanism (NEW)
- `RetainedEarningsService`: for finalized month, allocate BDT 200K (configurable)
- Split 71% investors / 29% M/Y automatically
- Distribute investor portion by ratio → `retained_earnings_distributions`
- Subtract retained credit from advance_diff → net settlement
- **Deliverable**: Retained earnings automated per month

#### Session 4.4 — Due Ledger Updates & M/Y Profit
- After profit calc: update investor profit due ledgers (with rollback-on-edit pattern)
- Update sector profit due ledgers (Y2 differences)
- Compute M/Y profit = X2 − Σ actual_due
- Update director (M/Y) due ledger
- Write `monthly_profit_summary` totals
- **Deliverable**: All ledgers consistent after reconciliation

#### Session 4.5 — Investor Profit View (the "For Sajid" page)
- Premium spreadsheet-like grid: 151 investors × all 8-phase columns
- Sticky header + sticky totals row (matching Excel AG182, AH182, AG184, AG186)
- Color-coded advance_diff (green over-paid, red under-paid)
- Per-investor expandable row showing retained earnings breakdown
- "Reconcile" CTA finalizes the month
- Export to Excel (preserves the familiar format)
- **Deliverable**: Page visually + numerically matches "July, 2026 For Sajid" sheet

#### Session 4.6 — Month Closing & Lock
- Status workflow: `draft → finalized → locked`
- Lock action requires admin permission
- Locked months cannot be edited without admin override (with audit log)
- Month-end checklist UI: "All 17 sectors entered? All investors calculated? Retained earnings applied?"
- **Deliverable**: Month lifecycle complete

---

### Phase 5 — Advance Profit Adjustments

**Goal**: Handle the three adjustment types from the PHP system.

#### Session 5.1 — Type A Adjustment
- Re-implement `AdvanceProfitAdjustmentTypeA` (per-date single amount into `adv_profit_adjusting_fund_type_A`)
- UI: date picker + amount + remarks
- Updates relevant due ledger
- **Deliverable**: Type A entry + list view

#### Session 5.2 — Type B Adjustment
- Same pattern as Type A into `adv_profit_adjusting_fund_type_B`
- **Deliverable**: Type B entry + list view

#### Session 5.3 — Type C (General Adjustment)
- Per sector or per investor adjustment into `advance_profit_adjustments`
- Links to either sector_id or investor_id
- **Deliverable**: General adjustment entry

#### Session 5.4 — Adjustment Report
- Combined view of all adjustments in a date range
- Filter by type, sector, investor
- Export to PDF/Excel
- **Deliverable**: Reconciliation report

---

### Phase 6 — Opening Balances

**Goal**: Carry forward pre-system balances (migrate from PHP/Excel).

#### Session 6.1 — M/Y Opening
- Form: select director + amount + as-of date + remarks
- Writes to `opening_director_due` + initializes `director_due_ledger`
- **Deliverable**: M/Y opening balance set

#### Session 6.2 — Investor Advance Opening
- Bulk entry: paste investor_id + amount rows
- Writes to `opening_investor_profit_due` + `investor_profit_due_ledger`
- **Deliverable**: Investor opening balances imported

#### Session 6.3 — Sector Advance Opening
- Same pattern for sectors
- **Deliverable**: Sector opening balances imported

---

### Phase 7 — Reports & Dashboards

**Goal**: Decision-grade visibility + audit-ready exports.

#### Session 7.1 — Dashboard
- 4 KPI cards (count-up animation): Total Investment, This Month Profit, M/Y Profit YTD, Active Investors
- Recharts: monthly profit trend (line), sector allocation (donut), investor tier distribution (stacked bar)
- Recent activity feed (last 10 audit logs)
- Quick-action buttons
- **Deliverable**: Dashboard loads < 1s

#### Session 7.2 — Investor Ledger Report
- Per-investor transaction timeline: capital adds/withdraws + profit distributions + adjustments
- Running balance column
- Date-range filter, export to PDF + Excel
- **Deliverable**: PDF export matches PHP `InvestorLedgerReport` output

#### Session 7.3 — Sector Ledger Report
- Per-sector: investments + profit history + due
- Same export pattern
- **Deliverable**: Sector ledger report

#### Session 7.4 — M/Y Ledger Report
- M/Y withdrawals + profit accruals + retained earnings portion
- **Deliverable**: M/Y ledger report

#### Session 7.5 — Investment Profit Report
- Cross-investor comparative view: investment / ratio / profit / ratio-over-time
- **Deliverable**: Investment profit report

#### Session 7.6 — Exports (PDF + Excel)
- Use `barryvdh/laravel-dompdf` for PDF (replaces PHP's dompdf/mpdf)
- Use `maatwebsite/excel` for Excel exports — especially the "For Sajid" sheet replica
- Number-to-words (Bangla taka format) via `kwn/number-to-words` (same as PHP)
- **Deliverable**: All reports exportable

---

### Phase 8 — Polish & Quality Assurance

**Goal**: Production-ready, mobile-perfect, audited.

#### Session 8.1 — Mobile Responsiveness Audit
- Test every page at 375px / 414px / 768px / 1024px / 1440px
- Fix: table horizontal scroll, modal full-screen on mobile, bottom tab bar coverage
- **Deliverable**: No horizontal scroll on iPhone SE

#### Session 8.2 — Premium UI Touches
- Framer Motion page transitions
- Skeleton loaders everywhere
- Number count-up animations on KPIs
- Subtle hover states, focus rings
- Empty states with illustrations (use unDraw or similar)
- **Deliverable**: Feels like a premium SaaS

#### Session 8.3 — Performance Optimization
- Eager-load relationships (N+1 elimination)
- Index audit on slow queries via Laravel Telescope
- Cache dashboard aggregates (5-min TTL)
- Virtualize the 151-row investor grid (TanStack virtual)
- **Deliverable**: Lighthouse score > 90

#### Session 8.4 — Testing
- Pest feature tests for every calculation phase (verify against Excel "For Sajid" numbers)
- Pest unit tests for `ProfitCalculatorService`, `RetainedEarningsService`, `DueManager`
- Playwright E2E: login → enter sector profits → reconcile → verify M/Y profit = 476,220.07
- **Deliverable**: Test suite green, parity with Excel confirmed

#### Session 8.5 — Documentation & Deployment
- README with setup steps
- `php artisan` deploy checklist
- Database backup strategy (pg_dump nightly)
- **Deliverable**: Deploy-ready, documented

---

## 8. Testing Strategy

### 8.1 Parity Testing (critical)
The system MUST reproduce the "July, 2026 For Sajid" sheet numbers:

| Metric | Expected (Excel) | Source |
|--------|------------------|--------|
| Total Mudaraba Investment (D181) | 157,475,000 | investors + transactions |
| Total Primary Profit (Z2) | 1,765,000 | sector estimated Σ |
| Total Actual Profit (X2) | 1,635,000 | sector actual Σ |
| Sector Advance Diff (Y2) | 130,000 | Z2 − X2 |
| Total Investor Profit Due (AG182) | 1,110,024.58 | Σ actual_due |
| Total Investor Advance Diff (AH182) | 606,220.07 | Σ advance_diff |
| M/Y Profit (AG184) | 476,220.07 | X2 − AG182 |
| M/Y Profit Ratio (AG186) | 29.13% | AG184 / X2 |
| Retained Earnings Total | 200,000 | config |
| Investor Retained Portion (AJ4) | 142,000 | 71% of 200K |
| M/Y Retained Portion (AK4) | 58,000 | 29% of 200K |

A Pest test seeds July 2026 data (17 sectors + 151 investors with their balances) and asserts every above number matches to ±1.00 BDT.

### 8.2 Test Pyramid
- **Unit**: every Service class method (`ProfitCalculator`, `RetainedEarnings`, `DueManager`)
- **Feature**: every HTTP route (CRUD + reconciliation)
- **Browser (Playwright)**: golden-path workflows (login, monthly run, export)
- **Parity**: the Excel-matching test above

### 8.3 Static Analysis
- Larastan level 6+ on all `app/Services` (financial logic)
- Pint formatting enforced in CI

---

## 9. Risk Register & Mitigation

| # | Risk | Impact | Mitigation |
|---|------|--------|-----------|
| R1 | Floating-point drift in profit calcs | Pennies off across 151 investors | Use `DECIMAL(20,2)` everywhere; round only at display layer |
| R2 | Double-counting on month re-save | Ledger corruption | Rollback-on-edit pattern (preserved from PHP) + DB transactions + `batch_uuid` |
| R3 | Excel user rejects new UI | Adoption failure | Build the "For Sajid" view to mirror Excel layout; offer Excel export |
| R4 | Retained earnings logic wrong | M/Y profit mismatch | Parity test asserts AG184 = 476,220.07 for July 2026 |
| R5 | Performance on 151-row grid | Sluggish UI | TanStack Table virtualization; defer non-visible rows |
| R6 | Permission bypass via direct URL | Unauthorized access | Route-level middleware + policy classes on every financial route |
| R7 | Audit log grows unbounded | DB bloat | Partition `audit_logs` by month; archive > 2 years |
| R8 | Concurrent month edits | Race condition | Row-level lock on `monthly_profit_summary` during reconcile |
| R9 | Postgres vs MySQL divergence | Migration issues | Stick to ANSI SQL; avoid Postgres-only JSONB in hot paths |
| R10 | Dark mode contrast issues | Readability | Test all monetary cells in both modes; WCAG AA contrast ≥ 4.5:1 |

---

## 10. Deployment Notes

### 10.1 Environments
- **Local**: Laravel Herd / Sail + Postgres in Docker
- **Staging**: VPS (Ubuntu) + Nginx + PHP-FPM + Postgres + Redis
- **Production**: Same as staging, scaled

### 10.2 CI/CD (GitHub Actions)
```
on: push
jobs:
  lint:        pint --test
  static:      phpstan analyse
  test:        pest --parallel
  parity:      php artisan test --filter=ParityTest
  deploy:     (on main push) ssh deploy script
```

### 10.3 Backup
- Nightly `pg_dump` of production DB → encrypted offsite
- Daily audit log archive to cold storage

---

## 11. Convention & Naming Standards

### 11.1 Database
- Tables: `snake_case`, plural (`investors`, `monthly_sector_profit`)
- Columns: `snake_case`, `id` as BIGSERIAL PK
- Foreign keys: `{entity}_id` (`investor_id`, `sector_id`)
- Timestamps: `created_at`, `updated_at`, `deleted_at` (TIMESTAMPTZ)
- Money: `DECIMAL(20,2)` — never FLOAT/DOUBLE
- Months: stored as `DATE` (1st of month), displayed as `'YYYY-MM'`
- Booleans: native `BOOLEAN`

### 11.2 Laravel / PHP
- Models: Singular StudlyCase (`Investor`, `MonthlySectorProfit`)
- Controllers: Resource controllers (`InvestorController`, `SectorProfitController`)
- Services: `*Service` suffix (`ProfitCalculatorService`)
- Form Requests: `*Request` suffix (`StoreInvestorRequest`)
- Policies: `*Policy` (`InvestorPolicy`)
- Enums: PHP 8.1 enums for types (`InvestmentType::Add`, `MonthStatus::Finalized`)

### 11.3 React / TypeScript
- Components: PascalCase (`InvestorProfitGrid.tsx`)
- Hooks: `use*` prefix (`useMonthReconciliation`)
- Pages: route-segment files under `resources/js/pages/`
- API types: Zod schemas → inferred TS types

### 11.4 Git
- Branches: `feature/phase-4-investor-profit-grid`, `fix/retained-earnings-rounding`
- Commits: Conventional Commits (`feat:`, `fix:`, `chore:`, `test:`, `docs:`)
- PRs: squash-merge to `main`

---

## Appendix A — Source-to-Target Traceability

| Excel (For Sajid) | PHP (mudaraba repo) | Laravel (this plan) |
|-------------------|---------------------|---------------------|
| Sheet "July, 2026 For Sajid" | `dynamic-page.php?page=Investor-Profit` | `/profit/investor-profit?month=2026-07` |
| V5:V20 sectors | `sectors` table | `sectors` table |
| X5:X20 actual profit | `monthly_sector_profit.actual_profit` | `monthly_sector_profit.actual_profit` |
| Z5:Z20 primary profit | `monthly_sector_profit.estimated_profit` | `monthly_sector_profit.estimated_profit` |
| D181 total inv | `Investments::TotalInvestment()` | `InvestmentTransaction::totalBalance()` |
| E ratio | `investment_ratio` col | `investment_ratio` col |
| Q primary share | `estimated_profit` col | `primary_profit_share` col |
| N actual @ 100% | `actual_profit_before_deed` col | `actual_profit_at_full` col |
| AG actual due | `final_profit` col | `actual_profit_due` col |
| AH advance diff | `advance_paid` col ⚠️ | `advance_difference` col ✓ |
| AI3 retained 200K | ❌ not implemented | `retained_earnings.total_amount` ✓ |
| AJ4 investor 71% | ❌ not implemented | `retained_earnings.investor_amount` ✓ |
| AK4 M/Y 29% | ❌ not implemented | `retained_earnings.my_amount` ✓ |
| AG184 M/Y profit | `monthly_profit_summary.my_amount` | `monthly_profit_summary.my_profit` |
| AG186 M/Y ratio | computed | `monthly_profit_summary.my_profit_ratio` |

---

## Appendix B — Session Checklist Template

Use this checklist at the end of every session:

```
[ ] Code committed to feature branch
[ ] Pint formatting passes
[ ] Larastan passes (level 6 for Services)
[ ] Relevant Pest tests added / updated
[ ] Parity test still green (if financial logic touched)
[ ] Mobile view verified at 375px
[ ] Dark mode verified
[ ] Sticky footer verified
[ ] Worklog appended to /home/z/my-project/worklog.md
```

---

**End of Plan.** This document is the single source of truth for the Laravel rebuild. Any deviation must be recorded as an addendum with rationale.
