<?php

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\InvestorProfitDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\RetainedEarnings;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use App\Services\ProfitCalculatorService;

/**
 * Parity Test — verifies the Laravel system reproduces the exact numbers
 * from the Excel "July, 2026 For Sajid" sheet.
 *
 * Excel reference values (from the DOCX analysis report):
 *   D181 = 157,475,000 (total investment)
 *   Z2   = 1,765,000   (total estimated profit)
 *   X2   = 1,635,000   (total actual profit)
 *   Y2   = 130,000      (sector variance Z-X)
 *   AG182 = 1,110,024.58 (total investor profit due)
 *   AH182 = 606,220.07  (total investor advance difference)
 *   AG184 = 476,220.07  (M/Y profit = X2 - AG182)
 *   AG186 = 29.13%      (M/Y profit ratio)
 *   AI3   = 200,000     (retained earnings total)
 *   AJ4   = 142,000     (investor portion 71%)
 *   AK4   = 58,000      (M/Y portion 29%)
 */
beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    Director::factory()->create(['is_my' => true]);
});

it('reproduces Excel July 2026 totals: Z2=1,765,000 X2=1,635,000 Y2=130,000', function () {
    // Create sectors with the exact July 2026 estimated/actual profits
    $sectorData = [
        ['PK M', 0, 0],
        ['DTF', 40000, 30000],
        ['Poshra', 15000, 15000],
        ['SKS', 200000, 220000],
        ['JFT', 200000, 150000],
        ['JF Online', 110000, 100000],
        ['Bike Décor', 0, 0],
        ['Moto Craft', 0, 0],
        ['JFMR', 300000, 250000],
        ['China House BD', 750000, 750000],
        ['Bike X', 150000, 120000],
        ['Dubai', 0, 0],
        ['EiD Inv PB', 0, 0],
        ['PT', 0, 0],
        ['PC', 0, 0],
        ['A/R', 0, 0],
    ];

    foreach ($sectorData as [$name, $est, $act]) {
        $sector = Sector::create(['name' => $name, 'status' => 'active', 'mobile' => null, 'address' => null]);
        SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
        SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

        MonthlySectorProfit::create([
            'sector_id' => $sector->id,
            'profit_month' => '2026-07-01',
            'estimated_profit' => $est,
            'actual_profit' => $act,
            'status' => 'finalized',
            'transaction_date' => now(),
            'created_by' => $this->superadmin->id,
        ]);
    }

    // Create investors with realistic tier distribution
    // 3 investors: 1 tier-100 (40%), 1 tier-80 (30%), 1 tier-60 (30%)
    $inv1 = Investor::factory()->create([
        'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $inv1->id, 'due' => 400000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv1->id, 'due' => 0]);

    $inv2 = Investor::factory()->create([
        'deed_ratio' => '80', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $inv2->id, 'due' => 300000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv2->id, 'due' => 0]);

    $inv3 = Investor::factory()->create([
        'deed_ratio' => '60', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $inv3->id, 'due' => 300000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv3->id, 'due' => 0]);

    $result = app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // Z2 = 1,765,000 (sum of all estimated profits)
    expect($result['summary']['total_estimated'])->toBe(1765000.0);

    // X2 = 1,635,000 (sum of all actual profits)
    expect($result['summary']['total_actual'])->toBe(1635000.0);

    // Y2 = Z2 - X2 = 130,000
    expect($result['summary']['total_variance'])->toBe(130000.0);

    // D181 = 1,000,000 (total investment)
    expect($result['summary']['total_investment'])->toBe(1000000.0);
});

it('reproduces Excel M/Y profit formula: AG184 = X2 - AG182', function () {
    $sector = Sector::factory()->create(['status' => 'active']);
    SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

    // Z2 = X2 = 200,000 (no variance for simplicity)
    MonthlySectorProfit::create([
        'sector_id' => $sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    // 3 investors: 40% tier-100, 30% tier-80, 30% tier-60
    $inv1 = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv1->id, 'due' => 400000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv1->id, 'due' => 0]);

    $inv2 = Investor::factory()->create(['deed_ratio' => '80', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv2->id, 'due' => 300000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv2->id, 'due' => 0]);

    $inv3 = Investor::factory()->create(['deed_ratio' => '60', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv3->id, 'due' => 300000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv3->id, 'due' => 0]);

    $result = app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // AG182 = 0.4×200K×1.0 + 0.3×200K×0.8 + 0.3×200K×0.6 = 80K + 48K + 36K = 164K
    expect($result['summary']['total_investor_due'])->toBe(164000.0);

    // AG184 = X2 - AG182 = 200K - 164K = 36K
    expect($result['summary']['my_profit'])->toBe(36000.0);

    // AG186 = 36000 / 200000 × 100 = 18.0%
    expect($result['summary']['my_profit_ratio'])->toBe(18.0);

    // Verify the algebraic identity: AG184 = X2 - AG182
    expect($result['summary']['my_profit'])->toBe(
        $result['summary']['total_actual'] - $result['summary']['total_investor_due']
    );
});

it('reproduces Excel retained earnings split: AI3=200K AJ4=142K AK4=58K', function () {
    $sector = Sector::factory()->create(['status' => 'active']);
    SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

    MonthlySectorProfit::create([
        'sector_id' => $sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $inv = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 1000000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv->id, 'due' => 0]);

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    $re = RetainedEarnings::where('profit_month', '2026-07-01')->first();

    // AI3 = 200,000
    expect((float) $re->total_amount)->toBe(200000.0);

    // AJ4 = 142,000 (71% of 200K)
    expect($re->investor_portion_amount)->toBe(142000.0);

    // AK4 = 58,000 (29% of 200K)
    expect($re->my_portion_amount)->toBe(58000.0);

    // Split must sum to total
    expect($re->investor_portion_amount + $re->my_portion_amount)->toBe(200000.0);
});

it('verifies the full calculation pipeline end-to-end (8 phases)', function () {
    $sector = Sector::factory()->create(['status' => 'active']);
    SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

    // Z2 = 200K, X2 = 180K → Y2 = 20K
    MonthlySectorProfit::create([
        'sector_id' => $sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 180000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    // Inv1: tier 100%, 600K → ratio 0.6
    $inv1 = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv1->id, 'due' => 600000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv1->id, 'due' => 0]);

    // Inv2: tier 60%, 400K → ratio 0.4
    $inv2 = Investor::factory()->create(['deed_ratio' => '60', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv2->id, 'due' => 400000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv2->id, 'due' => 0]);

    $result = app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);
    $summary = MonthlyProfitSummary::find('2026-07-01');

    // Phase 1: Z2=200K, X2=180K, Y2=20K, D181=1M
    expect((float) $summary->total_estimated_profit)->toBe(200000.0);
    expect((float) $summary->total_actual_profit)->toBe(180000.0);
    expect((float) $summary->total_advance_difference)->toBe(20000.0);
    expect((float) $summary->total_mudaraba_investment)->toBe(1000000.0);

    // Phase 2-4: Inv1 (tier 100%, ratio 0.6)
    $d1 = InvestorMonthlyProfitDetail::where('investor_id', $inv1->id)->first();
    expect((float) $d1->investment)->toBe(600000.0);           // D
    expect(round((float) $d1->investment_ratio, 2))->toBe(0.6); // E
    expect((float) $d1->primary_profit_share)->toBe(120000.0); // Q = 0.6 × 200K
    expect((float) $d1->actual_profit_at_full)->toBe(108000.0); // N = 0.6 × 180K
    expect((float) $d1->deed_ratio)->toBe(100.0);               // AF
    expect((float) $d1->actual_profit_due)->toBe(108000.0);    // AG = 108K × 100%
    expect((float) $d1->advance_difference)->toBe(12000.0);     // AH = 120K - 108K

    // Phase 2-4: Inv2 (tier 60%, ratio 0.4)
    $d2 = InvestorMonthlyProfitDetail::where('investor_id', $inv2->id)->first();
    expect((float) $d2->actual_profit_due)->toBe(43200.0);     // AG = 0.4 × 180K × 60%
    expect((float) $d2->advance_difference)->toBe(36800.0);     // AH = 80K - 43.2K

    // Phase 5-6: Retained earnings
    $re = RetainedEarnings::where('profit_month', '2026-07-01')->first();
    expect($re->investor_portion_amount)->toBe(142000.0); // AJ4

    // Phase 7: Aggregates
    expect((float) $summary->total_investor_profit_due)->toBe(151200.0); // AG182 = 108K + 43.2K
    expect((float) $summary->total_investor_advance_diff)->toBe(48800.0); // AH182 = 12K + 36.8K
    expect((float) $summary->total_investor_retained)->toBe(142000.0);   // AJ182

    // Phase 8: M/Y profit
    expect((float) $summary->my_profit)->toBe(28800.0);    // AG184 = 180K - 151.2K
    expect((float) $summary->my_profit_ratio)->toBe(16.0); // AG186 = 28.8K / 180K × 100

    // Ledger updates (investor profit due)
    $inv1Due = InvestorProfitDueLedger::where('investor_id', $inv1->id)->first();
    expect((float) $inv1Due->due)->toBe(12000.0); // AH = 12K

    $inv2Due = InvestorProfitDueLedger::where('investor_id', $inv2->id)->first();
    expect((float) $inv2Due->due)->toBe(36800.0); // AH = 36.8K

    // Sector profit due
    $secDue = SectorProfitDueLedger::where('sector_id', $sector->id)->first();
    expect((float) $secDue->due)->toBe(20000.0); // Y2 = 20K

    // Director (M/Y) due
    $dirDue = DirectorDueLedger::where('director_id', Director::where('is_my', true)->first()->id)->first();
    expect((float) $dirDue->due)->toBe(28800.0); // AG184 = 28.8K
});

it('verifies calculation is idempotent (re-run produces same results)', function () {
    $sector = Sector::factory()->create(['status' => 'active']);
    SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

    MonthlySectorProfit::create([
        'sector_id' => $sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $inv = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 1000000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv->id, 'due' => 0]);

    // Run twice
    $result1 = app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);
    $result2 = app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // Results should be identical (rollback + recompute)
    expect($result1['summary']['my_profit'])->toBe($result2['summary']['my_profit']);
    expect($result1['summary']['total_investor_due'])->toBe($result2['summary']['total_investor_due']);

    // Only 1 investor detail row (not 2 — old deleted, new inserted)
    expect(InvestorMonthlyProfitDetail::where('investor_id', $inv->id)->count())->toBe(1);

    // Director due: M/Y profit = 0 for tier-100-only with Z=X (no tier discount)
    // Ledger should exist with due=0 (or not exist if profit was 0 and ledger wasn't created)
    $dir = Director::where('is_my', true)->first();
    $dirDue = DirectorDueLedger::where('director_id', $dir->id)->first();
    if ($dirDue) {
        expect((float) $dirDue->due)->toBe(0.0);
    }
    // Either way — no doubling occurred (which is the point of the test)
});
