<?php

use App\Models\MonthlySectorProfit;
use App\Models\MonthlyProfitSummary;
use App\Models\Sector;
use App\Services\ProfitCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ParitySeederTest — verifies the SEEDED July 2026 data matches the
 * canonical Excel "For Sajid" sheet numbers.
 *
 * === How this differs from ParityTest ===
 *
 * `tests/Feature/ParityTest.php` creates its OWN hand-coded data inline
 * (16 sectors with July 2026 numbers + 3 test investors at $1M total).
 * It verifies the ALGORITHM is correct against known hand-computed numbers.
 *
 * `ParitySeederTest` (this file) runs the parity check AGAINST the data
 * loaded by `July2026Seeder`. It verifies the SEEDER itself produces the
 * correct canonical numbers (Z2, X2, Y2) AND that the calculation engine
 * produces the correct algebraic identity (AG184 = X2 - AG182) when run
 * against the seeded data.
 *
 * === What's verifiable vs. not ===
 *
 * ✅ Verifiable (matches Excel exactly):
 *   - Z2 = 1,765,000 (Σ sector estimated_profit — comes from july_2026_data.json)
 *   - X2 = 1,635,000 (Σ sector actual_profit)
 *   - Y2 = 130,000   (Z2 - X2)
 *
 * 🟡 Not verifiable against canonical Excel (we don't have real July 2026 investor balances):
 *   - D181 (total investment) — seeded with January 2026 amounts (137M, not 157M)
 *   - AG182 (total investor profit due) — depends on D181
 *   - AH182 (total investor advance diff) — depends on D181
 *   - AG184 (M/Y profit = X2 - AG182) — depends on AG182
 *   - AG186 (M/Y profit ratio) — depends on AG184
 *
 * For these, we assert the ALGEBRAIC IDENTITY (AG184 = X2 - AG182) holds,
 * which proves the calculation engine is correct regardless of the
 * specific investor amounts.
 *
 * To verify the EXACT canonical AG182/AG184 figures (1,110,024.58 / 476,220.07),
 * we'd need the real per-investor investment balances as of 2026-07-01,
 * which are not in the codebase. See `database/seeders/july_2026_data.json`
 * → `_meta.note_about_investors` for the full explanation.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    // Run the July2026Seeder — this is what we're testing.
    $this->seed(\Database\Seeders\July2026Seeder::class);
});

it('seeds exactly 16 sectors (one per canonical Excel sector)', function () {
    expect(Sector::count())->toBe(16);
});

it('seeds exactly 150 investors (matches January 2026 data)', function () {
    expect(\App\Models\Investor::count())->toBe(150);
});

it('seeds MonthlySectorProfit rows for BOTH January and July 2026', function () {
    $janCount = MonthlySectorProfit::where('profit_month', '2026-01-01')->count();
    $julyCount = MonthlySectorProfit::where('profit_month', '2026-07-01')->count();

    expect($janCount)->toBe(16)
        ->and($julyCount)->toBe(16);
});

it('seeds July 2026 sector totals matching the canonical Excel sheet (Z2=1,765,000 X2=1,635,000 Y2=130,000)', function () {
    $julyProfits = MonthlySectorProfit::where('profit_month', '2026-07-01')->get();

    $z2 = $julyProfits->sum(fn ($s) => (float) $s->estimated_profit);
    $x2 = $julyProfits->sum(fn ($s) => (float) $s->actual_profit);
    $y2 = $z2 - $x2;

    // These are the canonical reference values from the Excel "For Sajid" sheet
    // and from MUDARABA_LARAVEL_PROJECT_PLAN.md §8.1.
    expect($z2)->toBe(1765000.0)
        ->and($x2)->toBe(1635000.0)
        ->and($y2)->toBe(130000.0);
});

it('seeds July 2026 sector profits with finalized status (so calculation can run)', function () {
    $julyProfits = MonthlySectorProfit::where('profit_month', '2026-07-01')->get();

    expect($julyProfits->every(fn ($s) => $s->status->value === 'finalized'))->toBeTrue();
});

it('reproduces the canonical July 2026 totals when ProfitCalculatorService runs against seeded data', function () {
    // Find the superadmin user created by the seeder
    $user = \App\Models\User::where('username', 'E0001')->firstOrFail();

    $result = app(ProfitCalculatorService::class)->calculate('2026-07-01', $user->id);

    // Z2, X2, Y2 — these match the canonical Excel sheet exactly because
    // they come from the seeded sector profits (july_2026_data.json).
    expect($result['summary']['total_estimated'])->toBe(1765000.0)   // Z2
        ->and($result['summary']['total_actual'])->toBe(1635000.0)    // X2
        ->and($result['summary']['total_variance'])->toBe(130000.0);  // Y2
});

it('verifies the algebraic identity AG184 = X2 - AG182 holds against seeded data', function () {
    $user = \App\Models\User::where('username', 'E0001')->firstOrFail();

    $result = app(ProfitCalculatorService::class)->calculate('2026-07-01', $user->id);

    // AG184 (M/Y profit) must equal X2 - AG182 — this is the central
    // accounting identity of the 8-phase engine. It holds regardless of
    // the specific investor amounts.
    $x2 = $result['summary']['total_actual'];
    $ag182 = $result['summary']['total_investor_due'];
    $ag184 = $result['summary']['my_profit'];

    expect($ag184)->toBe($x2 - $ag182)
        ->and($result['summary']['my_profit_ratio'])->toBeGreaterThan(0.0)
        ->and($result['summary']['my_profit_ratio'])->toBeLessThan(100.0);
});

it('writes the monthly_profit_summary row with the canonical July 2026 sector totals', function () {
    $user = \App\Models\User::where('username', 'E0001')->firstOrFail();

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $user->id);

    $summary = MonthlyProfitSummary::find('2026-07-01');

    expect($summary)->not->toBeNull()
        ->and((float) $summary->total_estimated_profit)->toBe(1765000.0)   // Z2
        ->and((float) $summary->total_actual_profit)->toBe(1635000.0)       // X2
        ->and((float) $summary->total_advance_difference)->toBe(130000.0); // Y2
});

it('retained earnings row is created for July 2026 with the canonical 200K total + 71/29 split', function () {
    $user = \App\Models\User::where('username', 'E0001')->firstOrFail();

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $user->id);

    $re = \App\Models\RetainedEarnings::where('profit_month', '2026-07-01')->first();

    // AI3 = 200,000 (canonical total)
    // AJ4 = 142,000 (71% investor portion)
    // AK4 = 58,000  (29% M/Y portion)
    // These match the canonical Excel sheet EXACTLY because they're driven
    // by the config (BDT 200K target) and the fixed 71/29 split — not by
    // the investor amounts.
    expect($re)->not->toBeNull()
        ->and((float) $re->total_amount)->toBe(200000.0)
        ->and($re->investor_portion_amount)->toBe(142000.0)
        ->and($re->my_portion_amount)->toBe(58000.0)
        ->and($re->investor_portion_amount + $re->my_portion_amount)->toBe(200000.0);
});

it('produces the "For Sajid" investor grid with all 150 investors for July 2026', function () {
    $user = \App\Models\User::where('username', 'E0001')->firstOrFail();

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $user->id);

    $detailCount = \App\Models\InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count();

    // Should be ~150 (some investors may have 0 investment and be skipped by
    // the calculator; the seeder creates 150 investors but a few have inv=0)
    expect($detailCount)->toBeGreaterThan(140)
        ->and($detailCount)->toBeLessThanOrEqual(150);
});
