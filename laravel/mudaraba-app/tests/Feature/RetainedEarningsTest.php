<?php

use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\RetainedEarnings;
use App\Models\RetainedEarningsDistribution;
use App\Models\Sector;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    // Create sectors
    $this->sectorA = Sector::factory()->create(['name' => 'Sector A', 'status' => 'active']);

    // Create 3 investors with different tiers
    $this->inv1 = Investor::factory()->create([
        'name' => 'Inv1', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 400000]);

    $this->inv2 = Investor::factory()->create([
        'name' => 'Inv2', 'deed_ratio' => '80', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 300000]);

    $this->inv3 = Investor::factory()->create([
        'name' => 'Inv3', 'deed_ratio' => '60', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv3->id, 'due' => 300000]);

    // Total investment = 1,000,000

    // Create finalized sector profits
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    // Run the full calculation (Phases 1-8 including retained earnings)
    $this->service = app(ProfitCalculatorService::class);
    $this->result = $this->service->calculate('2026-07-01', $this->superadmin->id);
});

it('creates a retained_earnings row for the month', function () {
    $re = RetainedEarnings::where('profit_month', '2026-07-01')->first();
    expect($re)->not->toBeNull();
    expect((float) $re->total_amount)->toBe(200000.0);     // AI3
    expect((float) $re->investor_portion_pct)->toBe(71.0);
    expect((float) $re->my_portion_pct)->toBe(29.0);
});

it('computes the 71/29 split correctly (AJ4 + AK4)', function () {
    $re = RetainedEarnings::where('profit_month', '2026-07-01')->first();
    expect($re->investor_portion_amount)->toBe(142000.0);  // AJ4 = 200K × 71%
    expect($re->my_portion_amount)->toBe(58000.0);          // AK4 = 200K × 29%
    expect($re->investor_portion_amount + $re->my_portion_amount)->toBe(200000.0);
});

it('distributes retained earnings to each investor by ratio (AJ)', function () {
    // Inv1: ratio=0.4, AJ = 142000 × 0.4 = 56800
    $detail1 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv1->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail1->retained_earnings_credit)->toBe(56800.0);

    // Inv2: ratio=0.3, AJ = 142000 × 0.3 = 42600
    $detail2 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv2->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail2->retained_earnings_credit)->toBe(42600.0);

    // Inv3: ratio=0.3, AJ = 142000 × 0.3 = 42600
    $detail3 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv3->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail3->retained_earnings_credit)->toBe(42600.0);
});

it('computes net settlement as advance_difference - retained_credit (AK)', function () {
    // Inv1 (tier 100%): AH = 80000 - 80000 = 0, AJ = 56800
    //   AK = AH - AJ = 0 - 56800 = -56800 (M/Y owes investor)
    $detail1 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv1->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail1->net_settlement)->toBe(-56800.0);

    // Inv2 (tier 80%): AH = 60000 - 48000 = 12000, AJ = 42600
    //   AK = 12000 - 42600 = -30600 (M/Y owes investor)
    $detail2 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv2->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail2->net_settlement)->toBe(-30600.0);

    // Inv3 (tier 60%): AH = 60000 - 36000 = 24000, AJ = 42600
    //   AK = 24000 - 42600 = -18600 (M/Y owes investor)
    $detail3 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv3->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail3->net_settlement)->toBe(-18600.0);
});

it('creates retained_earnings_distributions for each investor', function () {
    $distributions = RetainedEarningsDistribution::where('profit_month', '2026-07-01')->get();
    expect($distributions->count())->toBe(3);

    // Verify Inv1's distribution
    $dist1 = $distributions->where('investor_id', $this->inv1->id)->first();
    expect($dist1)->not->toBeNull();
    expect((float) $dist1->amount)->toBe(56800.0);
    expect((float) $dist1->investment_ratio)->toBe(0.4);
});

it('updates monthly_profit_summary with AH182 + AJ182', function () {
    $summary = MonthlyProfitSummary::find('2026-07-01');
    expect($summary)->not->toBeNull();

    // AH182 = sum of all advance_difference values
    // Inv1: 0, Inv2: 12000, Inv3: 24000 → AH182 = 36000
    expect((float) $summary->total_investor_advance_diff)->toBe(36000.0); // AH182

    // AJ182 = sum of all retained_earnings_credit values
    // 56800 + 42600 + 42600 = 142000
    expect((float) $summary->total_investor_retained)->toBe(142000.0); // AJ182
});

it('verifies the complete 8-phase calculation returns correct summary', function () {
    $summary = $this->result['summary'];

    // Z2 = 200000, X2 = 200000, Y2 = 0
    expect($summary['total_estimated'])->toBe(200000.0);
    expect($summary['total_actual'])->toBe(200000.0);
    expect($summary['total_variance'])->toBe(0.0);

    // D181 = 1,000,000
    expect($summary['total_investment'])->toBe(1000000.0);

    // AG182 = 80000 + 48000 + 36000 = 164000
    expect($summary['total_investor_due'])->toBe(164000.0);

    // AH182 = 0 + 12000 + 24000 = 36000
    expect($summary['total_advance_diff'])->toBe(36000.0);

    // Retained: total=200000, investor=142000, my=58000
    expect($summary['retained_total'])->toBe(200000.0);
    expect($summary['retained_investor'])->toBe(142000.0);
    expect($summary['retained_my'])->toBe(58000.0);

    // AG184 (M/Y profit) = X2 - AG182 = 200000 - 164000 = 36000
    expect($summary['my_profit'])->toBe(36000.0);

    // AG186 = 36000 / 200000 × 100 = 18.0%
    expect($summary['my_profit_ratio'])->toBe(18.0);
});

it('recomputes retained earnings correctly on re-finalize', function () {
    // Re-run the calculation (simulates re-finalize)
    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Should still have 3 distributions (not 6 — old ones deleted)
    $distributions = RetainedEarningsDistribution::where('profit_month', '2026-07-01')->count();
    expect($distributions)->toBe(3);

    // Should still have 3 investor details (not 6 — old ones deleted)
    $details = InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count();
    expect($details)->toBe(3);
});

it('is triggered automatically when sector profits are finalized via controller', function () {
    // Reset: delete everything for a fresh month
    InvestorMonthlyProfitDetail::where('profit_month', '2026-08-01')->delete();
    MonthlySectorProfit::where('profit_month', '2026-08-01')->delete();

    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-08-01',
            'items' => [
                ['sector_id' => $this->sectorA->id, 'estimated_profit' => 200000, 'actual_profit' => 200000],
            ],
            'finalize' => true,
        ]);

    $response->assertRedirect();

    // Verify retained earnings were created automatically
    expect(RetainedEarnings::where('profit_month', '2026-08-01')->exists())->toBeTrue();
    expect(RetainedEarningsDistribution::where('profit_month', '2026-08-01')->count())->toBe(3);
});
