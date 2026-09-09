# Mudaraba — Step-by-Step Usage Guide

This guide walks through the complete monthly workflow of the Mudaraba Profit Management System — from creating investors to distributing profit and retained earnings.

> **Prerequisites**: The app is running (`docker compose up -d`) and seeded with the clean start seeder (`php artisan migrate:fresh --seed`). Login at `http://localhost:8080/login` with `E0001 / Mudaraba@2026`.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Step 1: Add Investment from Investors](#step-1-add-investment-from-investors)
3. [Step 2: Allocate Funds to Sectors](#step-2-allocate-funds-to-sectors)
4. [Step 3: Enter Sector Profits](#step-3-enter-sector-profits)
5. [Step 4: Set Retained Earnings](#step-4-set-retained-earnings)
6. [Step 5: Finalize the Month](#step-5-finalize-the-month)
7. [Step 6: Review Investor Profit (For Sajid)](#step-6-review-investor-profit-for-sajid)
8. [Step 7: Export to Excel](#step-7-export-to-excel)
9. [Step 8: Lock the Month](#step-8-lock-the-month)
10. [Step 9: Review Reports](#step-9-review-reports)
11. [Monthly Workflow Checklist](#monthly-workflow-checklist)
12. [When to Do What](#when-to-do-what)

---

## 1. System Overview

The Mudaraba system manages a profit-sharing pool. The M/Y (managing partner, Sajid) collects investment from investors, allocates those funds to business sectors, and distributes the monthly profit according to an 8-phase calculation engine.

### The 8-Phase Calculation Engine

```
Phase 1: Totals          Z2 = Σ sector estimated, X2 = Σ sector actual, D181 = Σ investor investment
Phase 2: Per-investor     ratio[i] = investment[i] / D181, primary[i] = ratio × Z2, actual_full[i] = ratio × X2
Phase 3: Tier             actual_due[i] = actual_full[i] × deed_ratio / 100   (deed_ratio = 100, 80, or 60)
Phase 4: Advance diff     advance_diff[i] = primary[i] − actual_due[i]
Phase 5: Retained earnings retained_credit[i] = RE_investor × ratio[i]       (RE_investor = RE_total × investor%)
Phase 6: Net settlement   net[i] = advance_diff[i] − retained_credit[i]
Phase 7: Aggregates       AH182 = Σ advance_diff, AG182 = Σ actual_due, AJ182 = Σ retained_credit
Phase 8: M/Y profit       my_profit = X2 − AG182,  my_ratio = my_profit / X2 × 100
```

### Key Entities

| Entity | What it represents | Excel column |
|--------|-------------------|--------------|
| Investor | A person who invests money in the pool | Row 5-155 |
| Sector | A business where the money is deployed | V5:V20 |
| Investment Transaction | Money added/withdrawn by an investor | D column |
| Sector Investment | Money allocated to/withdrawn from a sector | — |
| Monthly Sector Profit | Estimated + actual profit per sector per month | Z/X columns |
| Investor Monthly Profit Detail | Per-investor calculated profit (8-phase output) | D-AK columns |
| Retained Earnings | Monthly reserve amount, split 71/29 (configurable) | AI3/AJ4/AK4 |

### Tier System (Deed Ratio)

| Tier | Deed Ratio | What it means |
|------|-----------|---------------|
| 100% | 100 | Investor gets 100% of their entitled profit share |
| 80% | 80 | Investor gets 80% — M/Y keeps the remaining 20% |
| 60% | 60 | Investor gets 60% — M/Y keeps the remaining 40% |

---

## Step 1: Add Investment from Investors

**When**: Start of a new month, or whenever a new investor joins.

**Where**: Sidebar → **Investment** → **Investor** (`/investments`)

### What to do

1. On the **Investment** page, you'll see a form at the top:
   - **Investor dropdown**: Select the investor (e.g., "Afzal B")
   - **Action**: Choose "Add" (new investment) or "Withdraw" (return money to investor)
   - **Amount**: Enter the amount in BDT (e.g., `500000`)
   - **Month**: The transaction month (defaults to current month)
   - **Date**: The transaction date
   - **Remarks**: Optional note (e.g., "January investment")
2. Click **"Add to {Investor Name}"** (or "Withdraw from {Name}")

### What happens in the system

- An `InvestmentTransaction` record is created (audited)
- The investor's `InvestorDueLedger` is updated: `due += amount` (for Add) or `due -= amount` (for Withdraw)
- The dashboard's **Total Investment** and **Cash in Hand** update (cached for 5 min)

### Repeat

Add investments for all investors who are participating this month. The total of all investor balances = D181 (Total Mudaraba Investment).

---

## Step 2: Allocate Funds to Sectors

**When**: After collecting investment from investors — the M/Y decides how much goes to each sector.

**Where**: Sidebar → **Investment** → **Sector** (`/sector-investments`)

### What to do

1. On the **Sector Investments** page, fill in the form:
   - **Sector dropdown**: Select the sector (e.g., "China House BD")
   - **Action**: Choose "Add (Allocate)" or "Withdraw"
   - **Amount**: Enter the amount to allocate (e.g., `1000000`)
   - **Date**: Transaction date
   - **Remarks**: Optional note (e.g., "January allocation")
2. Click **"Allocate to Sector"**

### What happens in the system

- A `SectorInvestment` record is created (audited)
- The sector's `SectorDueLedger` is updated: `due += amount`
- The dashboard's **Cash in Hand** decreases (Cash in Hand = investor deposits − sector allocations)

### Repeat

Allocate funds to all sectors. The total allocated should be ≤ total investor investment (Cash in Hand should be ≥ 0).

### Verify on Dashboard

Go to **Dashboard** (`/dashboard`):
- **Total Mudaraba Investment**: Sum of all investor balances
- **Cash in Hand**: Total collected − total allocated (should be ≥ 0)
- **Total Allocated**: Sum of all sector balances

---

## Step 3: Enter Sector Profits

**When**: At the end of the month, when you know how much profit each sector made.

**Where**: Sidebar → **Profit** → **Sector Profit** (`/profit/sector`)

### What to do

1. Navigate to the correct month using the **◀ ▶** month switcher at the top
2. For each of the 16 sectors, enter:
   - **Estimated Profit (Z column)**: The budgeted/target profit for the month
   - **Actual Profit (X column)**: The realized profit for the month
3. The **live totals row** at the bottom shows:
   - **Z2**: Total estimated profit (Σ of all estimated)
   - **X2**: Total actual profit (Σ of all actual)
   - **Y2**: Variance = Z2 − X2

### How it relates to the Excel sheet

- **Z column** = Primary/advance profit (estimated at start of month)
- **X column** = Actual profit (known at end of month)
- The system computes `primary_profit_share[i] = ratio[i] × Z2` for each investor (paid as advance at the start of the month)
- At month end, the system reconciles using `actual_due[i] = ratio[i] × X2 × deed_ratio/100`

### Save as Draft vs Finalize

- **Save as Draft**: Saves the estimated/actual values without running the calculation engine
- **Finalize Month**: Saves + runs the 8-phase calculation engine (see Step 5)

> ⚠️ **Don't finalize yet** — set retained earnings first (Step 4).

---

## Step 4: Set Retained Earnings

**When**: Before finalizing the month — the retained earnings amount is used in the calculation.

**Where**: Same page as Step 3 (`/profit/sector`) — scroll down to the **"Retained Earnings (AI3)"** card.

### What to do

1. In the **Retained Earnings** card, enter:
   - **Total Amount (BDT)**: The amount to set aside as retained earnings this month (e.g., `200000`, or any amount)
   - **Investor Portion (%)**: Default 71% (investors get 71% of the retained earnings)
   - **M/Y Portion (%)**: Default 29% (M/Y gets 29%)
2. The **live preview** shows:
   - **AJ4**: Investor portion = total × investor% / 100 (e.g., 200,000 × 71% = 142,000)
   - **AK4**: M/Y portion = total × my% / 100 (e.g., 200,000 × 29% = 58,000)
3. Adjust the split if needed — changing one percentage auto-updates the other to always sum to 100%

### How it works in the calculation

- Phase 5: Each investor gets `retained_credit[i] = AJ4 × ratio[i]` (distributed by investment ratio)
- Phase 6: `net_settlement[i] = advance_diff[i] − retained_credit[i]`
  - Positive → investor owes M/Y (after retained credit)
  - Negative → M/Y owes investor

---

## Step 5: Finalize the Month

**When**: After entering all sector profits + setting retained earnings.

**Where**: Same page — click the **"Finalize Month"** button at the bottom.

### What happens when you finalize

The system runs the complete **8-phase calculation engine**:

1. Loads all sector profits for the month (Z2, X2, Y2)
2. Loads all active investors with their current investment balances (D181)
3. Computes per-investor: ratio, primary share, actual @ 100%, profit due (after tier), advance diff
4. Allocates retained earnings: investor portion distributed by ratio, M/Y portion tracked
5. Computes net settlement per investor
6. Writes all results to `investor_monthly_profit_details` (one row per investor)
7. Writes `monthly_profit_summary` with totals (Z2, X2, AG182, AH182, AJ182, AG184, AG186)
8. Updates due ledgers: investor profit due, sector profit due, director (M/Y) due
9. Writes an audit log entry: `reconcile` action

### After finalization

- The sector profit inputs become **read-only** (finalized status)
- The Investor Profit page (`/profit/investor`) now shows the full "For Sajid" grid
- The dashboard updates with the current month's profit figures

---

## Step 6: Review Investor Profit (For Sajid)

**When**: After finalizing the month.

**Where**: Sidebar → **Profit** → **Investor Profit** (`/profit/investor`)

### What you'll see

- **KPI cards**: Total Investment (D181), Total Actual Profit (X2), M/Y Profit (AG184), Retained Earnings (AI3)
- **Retained Earnings Breakdown**: Total (AI3), Investor 71% (AJ4), M/Y 29% (AK4)
- **Investor Profit Grid**: All investors × all 8-phase columns:
  - D = Investment, E = Ratio, Q = Primary Share, N = Actual @ 100%
  - AF = Tier, AG = Profit Due, AH = Advance Diff, AJ = Retained Credit, AK = Net Settlement
- **Sticky totals row**: D181, Z2, X2, AG182, AH182, AJ182, AG184 (M/Y profit), AG186 (M/Y ratio %)
- Click any row to expand the retained earnings breakdown for that investor

### Color coding

- **AH (Advance Diff)**: Red = over-paid (investor owes M/Y), Green = under-paid (M/Y owes investor)
- **AK (Net Settlement)**: Red = investor owes M/Y (after retained credit), Green = M/Y owes investor

### What to check

1. **Z2 and X2** match your sector profit totals ✓
2. **D181** matches your total investor investment ✓
3. **AG184 (M/Y Profit)** = X2 − AG182 ✓
4. **AG186 (M/Y Profit Ratio)** = AG184 / X2 × 100 (target ~29%) ✓
5. **AJ4 + AK4** = AI3 (retained earnings total) ✓
6. **AJ182** (total retained credit) = AJ4 (investor portion of retained earnings) ✓

---

## Step 7: Export to Excel

**When**: After reviewing the investor profit grid — to share with the M/Y or archive.

**Where**: On the Investor Profit page — click the **"Export to Excel"** button (top right, next to the month switcher).

### What you get

- A file named `For Sajid - {Month Year}.xlsx` (e.g., `For Sajid - July_2026.xlsx`)
- The sheet tab is named `For Sajid - {Month Year}`
- The grid has all investor rows with columns: Investor, Reference, Tier, Investment (D), Ratio (E), Primary Share (Q), Actual @ 100% (N), Deed Ratio (AF), Profit Due (AG), Advance Diff (AH), Retained Credit (AJ), Net Settlement (AK)
- Below the data: TOTALS row (D181, Z2, X2, AG182, AH182, AJ182, ΣAK)
- Below that: M/Y Profit block (AG184 + AG186 ratio) + Retained Earnings block (AI3, AJ4, AK4)

---

## Step 8: Lock the Month

**When**: After reviewing and exporting — to prevent further edits.

**Where**: Sidebar → **Month Close** (`/month-close`)

### What to do

1. The **Month Close** page shows a checklist:
   - ✅ All active sectors have profit entries
   - ✅ All sector profits are finalized
   - ✅ 8-phase calculation engine has run
   - ✅ Retained earnings allocated
2. When all items are green, click **"Lock Month"** (superadmin only)

### After locking

- The sector profit page becomes read-only (inputs disabled)
- The investor profit page shows a "locked" badge
- No further edits without admin override (unlock)

### Unlocking

To make edits after locking: go to **Month Close** → click **"Unlock Month"** (superadmin only). This allows re-finalizing the month. The rollback-on-edit pattern ensures no double-counting.

---

## Step 9: Review Reports

**When**: Anytime — for audit, reconciliation, or sharing with investors.

**Where**: Sidebar → **Reports**

### Available reports

| Report | URL | What it shows | Export |
|--------|-----|---------------|--------|
| Investor Ledger | `/reports/investor-ledger` | Per-investor transaction timeline (capital + profit + adjustments) with running balance | PDF |
| Sector Ledger | `/reports/sector-ledger` | Per-sector: investments + profit history + due | PDF |
| M/Y Ledger | `/reports/my-ledger` | M/Y withdrawals + profit accruals + retained earnings portion | PDF |
| Investment Profit | `/reports/investment-profit` | Cross-investor comparative view: investment / ratio / profit over time | Excel |

### Using reports

1. Select the investor/sector/director from the dropdown
2. Filter by date range (optional)
3. View the timeline with running balance
4. Click **Export** to download PDF or Excel

---

## Monthly Workflow Checklist

Here's the checklist the M/Y follows every month:

```
□ 1. Add investment from investors
     → Investment → Investor page
     → Add money for each investor (or skip if already invested)

□ 2. Allocate funds to sectors
     → Investment → Sector page
     → Allocate to each sector as needed
     → Check Dashboard: Cash in Hand ≥ 0

□ 3. Enter sector profits
     → Profit → Sector Profit page
     → Enter estimated (Z) + actual (X) for each sector
     → Check Z2 and X2 totals

□ 4. Set retained earnings
     → Same page, scroll down to "Retained Earnings (AI3)" card
     → Enter total amount (e.g., 200,000)
     → Adjust split if needed (default 71/29)
     → Check AJ4 and AK4 preview

□ 5. Finalize the month
     → Click "Finalize Month" button
     → System runs 8-phase calculation engine
     → All investor profit details computed

□ 6. Review investor profit
     → Profit → Investor Profit page
     → Check the "For Sajid" grid
     → Verify Z2, X2, AG184, AG186
     → Verify retained earnings (AI3, AJ4, AK4)

□ 7. Export to Excel
     → Click "Export to Excel" button
     → Download "For Sajid - {Month Year}.xlsx"

□ 8. Lock the month
     → Month Close page
     → Verify checklist is all green
     → Click "Lock Month"

□ 9. Repeat next month
     → Navigate to the next month using the month switcher
     → Start again from Step 1 (or Step 3 if investments are already set)
```

---

## When to Do What

| Task | When | Frequency |
|------|------|-----------|
| Add investor investment | Start of month, or when a new investor joins | As needed |
| Withdraw investor investment | When an investor wants to exit or reduce | As needed |
| Allocate to sectors | After collecting investor funds | Monthly (or as needed) |
| Withdraw from sectors | When capital is returned from a sector | As needed |
| Enter sector estimated profit | Start of month (budget) | Monthly |
| Enter sector actual profit | End of month (realized) | Monthly |
| Set retained earnings | Before finalizing | Monthly |
| Finalize month | After all sector profits + retained earnings are entered | Monthly (once per month) |
| Review investor profit | After finalizing | Monthly |
| Export to Excel | After reviewing, before locking | Monthly |
| Lock month | After review + export | Monthly |
| Review reports | Anytime for audit | As needed |
| Profit adjustments (Fund A/B/Direct) | When correcting errors or adjusting | As needed |
| Opening balances | Only once at system startup | One-time |

---

## Troubleshooting

### "No profit calculation for this month"

You see this on the Investor Profit page when you haven't finalized the month yet. Go to the Sector Profit page and click "Finalize Month".

### "Cannot lock — no profit calculation exists"

You need to finalize the month first (Step 5) before locking.

### Month is locked and I need to edit

Go to **Month Close** → click **"Unlock Month"** (superadmin only). Make your changes, then re-finalize and re-lock.

### Dashboard shows 0 for everything

Either:
- No investments have been added yet (Step 1)
- The cache hasn't expired (5-min TTL) — wait or run `php artisan cache:clear`

### Investor profit numbers don't match Excel

1. Check that Z2 and X2 match your sector profit totals
2. Check that D181 matches your total investor investment
3. Check that the retained earnings amount is correct (AI3)
4. The AG184 (M/Y profit) = X2 − AG182 (this is an algebraic identity — it always holds)
5. If AG182 doesn't match Excel, it's because the investor investment amounts differ from the Excel sheet

---

**End of Usage Guide.** For technical documentation, see [`README.md`](./README.md). For deployment, see [`DEPLOYMENT.md`](./DEPLOYMENT.md). For the full project plan, see [`../MUDARABA_LARAVEL_PROJECT_PLAN.md`](../MUDARABA_LARAVEL_PROJECT_PLAN.md).
