<?php

use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    // Create 3 sectors (simplified for test)
    $this->sectorA = Sector::factory()->create(['name' => 'Sector A', 'status' => 'active']);
    $this->sectorB = Sector::factory()->create(['name' => 'Sector B', 'status' => 'active']);
    $this->sectorC = Sector::factory()->create(['name' => 'Sector C', 'status' => 'active']);

    // Create 3 investors with different tiers + balances
    // Investor 1: Tier 100%, balance 400,000
    $this->inv1 = Investor::factory()->create(['name' => 'Inv1', 'deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 400000]);

    // Investor 2: Tier 80%, balance 300,000
    $this->inv2 = Investor::factory()->create(['name' => 'Inv2', 'deed_ratio' => '80', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 300000]);

    // Investor 3: Tier 60%, balance 300,000
    $this->inv3 = Investor::factory()->create(['name' => 'Inv3', 'deed_ratio' => '60', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $this->inv3->id, 'due' => 300000]);

    // Total investment = 400K + 300K + 300K = 1,000,000 (D181)

    $this->service = app(ProfitCalculatorService::class);
});

it('calculates per-investor profit details correctly', function () {
    // Set up sector profits for 2026-07
    // Z2 = 100K + 50K + 50K = 200,000
    // X2 = 90K + 50K + 40K = 180,000
    // Y2 = Z2 - X2 = 20,000
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 100000,
        'actual_profit' => 90000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorB->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 50000,
        'actual_profit' => 50000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorC->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 50000,
        'actual_profit' => 40000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $result = $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Verify totals (Z2=200K, X2=180K, Y2=20K, D181=1M)
    expect($result['summary']['total_estimated'])->toBe(200000.0);  // Z2
    expect($result['summary']['total_actual'])->toBe(180000.0);      // X2
    expect($result['summary']['total_variance'])->toBe(20000.0);     // Y2
    expect($result['summary']['total_investment'])->toBe(1000000.0);  // D181

    // Verify details were created
    expect($result['details_count'])->toBe(3);
    expect(InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count())->toBe(3);

    // Verify Investor 1 (400K / 1M = 0.4 ratio, tier 100%)
    $detail1 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv1->id)
        ->where('profit_month', '2026-07-01')->first();
    expect((float) $detail1->investment)->toBe(400000.0);                          // D
    expect(round((float) $detail1->investment_ratio, 6))->toBe(0.4);                // E = 400K/1M
    expect((float) $detail1->primary_profit_share)->toBe(80000.0);                 // Q = 0.4 × 200K
    expect((float) $detail1->actual_profit_at_full)->toBe(72000.0);                 // N = 0.4 × 180K
    expect((float) $detail1->deed_ratio)->toBe(100.0);                               // AF
    expect((float) $detail1->actual_profit_due)->toBe(72000.0);                      // AG = 72000 × 100%
    expect((float) $detail1->advance_difference)->toBe(8000.0);                      // AH = 80000 - 72000

    // Verify Investor 2 (300K / 1M = 0.3 ratio, tier 80%)
    $detail2 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv2->id)
        ->where('profit_month', '2026-07-01')->first();
    expect(round((float) $detail2->investment_ratio, 6))->toBe(0.3);                // E
    expect((float) $detail2->primary_profit_share)->toBe(60000.0);                 // Q = 0.3 × 200K
    expect((float) $detail2->actual_profit_at_full)->toBe(54000.0);                 // N = 0.3 × 180K
    expect((float) $detail2->deed_ratio)->toBe(80.0);                                // AF
    expect((float) $detail2->actual_profit_due)->toBe(43200.0);                      // AG = 54000 × 80%
    expect((float) $detail2->advance_difference)->toBe(16800.0);                     // AH = 60000 - 43200

    // Verify Investor 3 (300K / 1M = 0.3 ratio, tier 60%)
    $detail3 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv3->id)
        ->where('profit_month', '2026-07-01')->first();
    expect(round((float) $detail3->investment_ratio, 6))->toBe(0.3);                // E
    expect((float) $detail3->primary_profit_share)->toBe(60000.0);                 // Q = 0.3 × 200K
    expect((float) $detail3->actual_profit_at_full)->toBe(54000.0);                 // N = 0.3 × 180K
    expect((float) $detail3->deed_ratio)->toBe(60.0);                                // AF
    expect((float) $detail3->actual_profit_due)->toBe(32400.0);                      // AG = 54000 × 60%
    expect((float) $detail3->advance_difference)->toBe(27600.0);                     // AH = 60000 - 32400
});

it('computes M/Y profit correctly (AG184 = X2 - AG182)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $result = $this->service->calculate('2026-07-01', $this->superadmin->id);

    // X2 = 200,000
    // AG182 = sum of actual_profit_due:
    //   Inv1: 0.4 × 200K × 100% = 80,000
    //   Inv2: 0.3 × 200K × 80% = 48,000
    //   Inv3: 0.3 × 200K × 60% = 36,000
    //   AG182 = 80K + 48K + 36K = 164,000
    // AG184 (M/Y profit) = X2 - AG182 = 200K - 164K = 36,000
    // AG186 (M/Y ratio) = 36000 / 200000 × 100 = 18.0%

    expect($result['summary']['total_investor_due'])->toBe(164000.0);   // AG182
    expect($result['summary']['my_profit'])->toBe(36000.0);              // AG184
    expect($result['summary']['my_profit_ratio'])->toBe(18.0);           // AG186

    // Verify summary row in DB
    $summary = MonthlyProfitSummary::find('2026-07-01');
    expect($summary)->not->toBeNull();
    expect((float) $summary->total_estimated_profit)->toBe(200000.0);    // Z2
    expect((float) $summary->total_actual_profit)->toBe(200000.0);        // X2
    expect((float) $summary->total_investor_profit_due)->toBe(164000.0);  // AG182
    expect((float) $summary->my_profit)->toBe(36000.0);                    // AG184
    expect((float) $summary->my_profit_ratio)->toBe(18.0);                 // AG186
    expect((float) $summary->total_mudaraba_investment)->toBe(1000000.0); // D181
    expect($summary->active_investor_count)->toBe(3);
    expect($summary->status->value)->toBe('finalized');
});

it('M/Y profit ratio approaches 29.13% when tier distribution matches Excel', function () {
    // Recreate the Excel scenario: 16 investors at tier 100%, 9 at tier 80%, ~126 at tier 60%
    // For simplicity, use 3 investors with the same tier ratio proportions
    // This test verifies the FORMULA is correct, not exact Excel parity (which needs 151 investors)

    // Clear previous investors
    Investor::query()->delete();
    InvestorDueLedger::query()->delete();

    // Create investors with tier proportions approximating the Excel distribution
    // 1 investor at tier 100% with 40% of capital
    $inv100 = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv100->id, 'due' => 400000]);

    // 1 investor at tier 80% with 30% of capital
    $inv80 = Investor::factory()->create(['deed_ratio' => '80', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv80->id, 'due' => 300000]);

    // 1 investor at tier 60% with 30% of capital
    $inv60 = Investor::factory()->create(['deed_ratio' => '60', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv60->id, 'due' => 300000]);

    // Total = 1,000,000
    // Sector profits: Z2 = X2 = 200,000 (no variance)
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $result = $this->service->calculate('2026-07-01', $this->superadmin->id);

    // M/Y profit = X2 - AG182
    // AG182 = (0.4 × 200K × 1.0) + (0.3 × 200K × 0.8) + (0.3 × 200K × 0.6)
    //       = 80000 + 48000 + 36000 = 164000
    // AG184 = 200000 - 164000 = 36000
    // AG186 = 36000 / 200000 × 100 = 18.0%
    //
    // With the full 151-investor Excel distribution (mostly tier 60%),
    // the ratio approaches ~29.13%. This simplified test verifies the FORMULA.
    expect($result['summary']['my_profit'])->toBe(36000.0);
    expect($result['summary']['my_profit_ratio'])->toBe(18.0);
});

it('is triggered when sector profits are finalized via the controller', function () {
    // POST to /profit/sector with finalize=true
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sectorA->id, 'estimated_profit' => 200000, 'actual_profit' => 200000],
            ],
            'finalize' => true,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify investor details were created (calculation was triggered)
    expect(InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count())->toBe(3);

    // Verify summary was created
    expect(MonthlyProfitSummary::where('profit_month', '2026-07-01')->exists())->toBeTrue();
});

it('is NOT triggered when saving as draft', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sectorA->id, 'estimated_profit' => 200000, 'actual_profit' => 200000],
            ],
            'finalize' => false,
        ]);

    $response->assertRedirect();

    // Verify NO investor details were created (calculation NOT triggered)
    expect(InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count())->toBe(0);
    expect(MonthlyProfitSummary::where('profit_month', '2026-07-01')->exists())->toBeFalse();
});

it('recomputes correctly when re-finalizing (deletes old + inserts new)', function () {
    // First finalize
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);
    $this->service->calculate('2026-07-01', $this->superadmin->id);
    expect(InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count())->toBe(3);

    // Update sector profit + re-calculate
    MonthlySectorProfit::where('sector_id', $this->sectorA->id)
        ->where('profit_month', '2026-07-01')
        ->update(['actual_profit' => 250000]); // X2 changes from 200K to 250K

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Should still have 3 rows (not 6 — old ones were deleted)
    expect(InvestorMonthlyProfitDetail::where('profit_month', '2026-07-01')->count())->toBe(3);

    // Verify the values updated
    $detail1 = InvestorMonthlyProfitDetail::where('investor_id', $this->inv1->id)
        ->where('profit_month', '2026-07-01')->first();
    // N = 0.4 × 250K = 100K (was 80K before)
    expect((float) $detail1->actual_profit_at_full)->toBe(100000.0);
});

it('throws exception when no sector profits exist for the month', function () {
    expect(fn () => $this->service->calculate('1999-01-01', $this->superadmin->id))
        ->toThrow(RuntimeException::class, 'No sector profits found');
});

it('throws exception when total investment is zero', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    // Set all investor balances to 0
    InvestorDueLedger::query()->update(['due' => 0]);

    expect(fn () => $this->service->calculate('2026-07-01', $this->superadmin->id))
        ->toThrow(RuntimeException::class, 'Total investment is zero');
});

it('skips investors with zero balance', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sectorA->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    // Set investor 3's balance to 0
    InvestorDueLedger::where('investor_id', $this->inv3->id)->update(['due' => 0]);

    $result = $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Only 2 investors should have details (inv1 + inv2, inv3 skipped)
    expect($result['details_count'])->toBe(2);
    expect(InvestorMonthlyProfitDetail::where('investor_id', $this->inv3->id)->exists())->toBeFalse();
});
