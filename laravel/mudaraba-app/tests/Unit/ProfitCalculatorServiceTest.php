<?php

use App\Models\Director;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\InvestorProfitDueLedger;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use App\Services\ProfitCalculatorService;

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    Director::factory()->create(['is_my' => true]);

    $this->sector = Sector::create(['name' => 'Test Sector', 'status' => 'active']);
    SectorDueLedger::create(['sector_id' => $this->sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $this->sector->id, 'due' => 0]);

    $this->service = app(ProfitCalculatorService::class);
});

it('throws when no sector profits exist for the month', function () {
    $this->service->calculate('1999-01-01', $this->superadmin->id);
})->throws(RuntimeException::class, 'No sector profits found');

it('throws when no active investors exist', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);
})->throws(RuntimeException::class, 'No active investors found');

it('throws when total investment is zero', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $inv = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 0]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);
})->throws(RuntimeException::class, 'Total investment is zero');

it('computes ratio correctly (E = D / D181)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 100000, 'actual_profit' => 100000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $inv1 = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv1->id, 'due' => 750000]);

    $inv2 = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv2->id, 'due' => 250000]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $d1 = InvestorMonthlyProfitDetail::where('investor_id', $inv1->id)->first();
    $d2 = InvestorMonthlyProfitDetail::where('investor_id', $inv2->id)->first();

    // D181 = 1M, inv1 ratio = 750K/1M = 0.75, inv2 ratio = 250K/1M = 0.25
    expect(round((float) $d1->investment_ratio, 2))->toBe(0.75);
    expect(round((float) $d2->investment_ratio, 2))->toBe(0.25);
});

it('applies deed ratio correctly (AG = N × AF/100)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 100000, 'actual_profit' => 100000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    // Single investor with 100% tier, 1M investment
    $inv = Investor::factory()->create(['deed_ratio' => '60', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 1000000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv->id, 'due' => 0]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $d = InvestorMonthlyProfitDetail::where('investor_id', $inv->id)->first();
    // N = 1.0 × 100K = 100K, AF = 60%, AG = 100K × 0.6 = 60K
    expect((float) $d->actual_profit_at_full)->toBe(100000.0);
    expect((float) $d->actual_profit_due)->toBe(60000.0);
});

it('computes advance difference correctly (AH = Q - AG)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 150000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $inv = Investor::factory()->create(['deed_ratio' => '100', 'status' => 'active', 'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 1000000]);
    InvestorProfitDueLedger::create(['investor_id' => $inv->id, 'due' => 0]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $d = InvestorMonthlyProfitDetail::where('investor_id', $inv->id)->first();
    // Q = 1.0 × 200K = 200K, AG = 1.0 × 150K = 150K, AH = 200K - 150K = 50K
    expect((float) $d->primary_profit_share)->toBe(200000.0);
    expect((float) $d->actual_profit_due)->toBe(150000.0);
    expect((float) $d->advance_difference)->toBe(50000.0);
});

it('excludes investors whose start_profit_month is after the target month', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id, 'profit_month' => '2026-07-01',
        'estimated_profit' => 100000, 'actual_profit' => 100000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    // Investor starts AFTER the profit month — should be excluded
    $inv = Investor::factory()->create([
        'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2026-08-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 500000]);

    // With no eligible investors, the service throws
    expect(fn () => $this->service->calculate('2026-07-01', $this->superadmin->id))
        ->toThrow(RuntimeException::class, 'No active investors found');
});
