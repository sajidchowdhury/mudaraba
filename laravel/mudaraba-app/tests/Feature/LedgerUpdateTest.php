<?php

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\InvestorProfitMonthlyDue;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    // Create sector
    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);

    // Create primary M/Y director
    $this->myDirector = Director::factory()->create(['name' => 'Primary M/Y', 'is_my' => true]);
    DirectorDueLedger::create(['director_id' => $this->myDirector->id, 'due' => 0]);

    // Create 2 investors
    $this->inv1 = Investor::factory()->create([
        'name' => 'Inv1', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 600000]);
    InvestorProfitDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 0]);

    $this->inv2 = Investor::factory()->create([
        'name' => 'Inv2', 'deed_ratio' => '60', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 400000]);
    InvestorProfitDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 0]);

    // Sector profit due ledger at 0
    SectorProfitDueLedger::create(['sector_id' => $this->sector->id, 'due' => 0]);

    // Total investment = 1,000,000

    $this->service = app(ProfitCalculatorService::class);
});

it('updates investor profit due ledger with advance_difference after finalize', function () {
    // Z2 = 200K, X2 = 180K → Y2 = 20K
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Inv1: ratio=0.6, Q=0.6×200K=120K, N=0.6×180K=108K, AG=108K×100%=108K, AH=120K-108K=12K
    $inv1Due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $inv1Due->due)->toBe(12000.0); // AH = 12K

    // Inv2: ratio=0.4, Q=0.4×200K=80K, N=0.4×180K=72K, AG=72K×60%=43.2K, AH=80K-43.2K=36.8K
    $inv2Due = InvestorProfitDueLedger::where('investor_id', $this->inv2->id)->first();
    expect((float) $inv2Due->due)->toBe(36800.0); // AH = 36,800
});

it('updates sector profit due ledger with variance (Y = Z - X)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Y = Z - X = 200K - 180K = 20K
    $sectorDue = SectorProfitDueLedger::where('sector_id', $this->sector->id)->first();
    expect((float) $sectorDue->due)->toBe(20000.0); // Y2 = 20K
});

it('updates director (M/Y) due ledger with M/Y profit', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $result = $this->service->calculate('2026-07-01', $this->superadmin->id);

    // M/Y profit = X2 - AG182
    // AG182 = 0.6×200K×100% + 0.4×200K×60% = 120K + 48K = 168K
    // AG184 = 200K - 168K = 32K
    $directorDue = DirectorDueLedger::where('director_id', $this->myDirector->id)->first();
    expect((float) $directorDue->due)->toBe(32000.0); // AG184 = 32K
    expect($result['summary']['my_profit'])->toBe(32000.0);
});

it('creates monthly due entries for investor profit', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $monthlyDue = InvestorProfitMonthlyDue::where('investor_id', $this->inv1->id)
        ->where('due_month', '2026-07-01')
        ->first();
    expect($monthlyDue)->not->toBeNull();
    expect((float) $monthlyDue->due)->toBe(12000.0);
});

it('rolls back ledgers on re-finalize (no double counting)', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    // First finalize
    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $inv1DueAfterFirst = (float) InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first()->due;
    $sectorDueAfterFirst = (float) SectorProfitDueLedger::where('sector_id', $this->sector->id)->first()->due;
    $directorDueAfterFirst = (float) DirectorDueLedger::where('director_id', $this->myDirector->id)->first()->due;

    // Re-finalize (same values → should get SAME results, not doubled)
    $this->service->calculate('2026-07-01', $this->superadmin->id);

    $inv1DueAfterSecond = (float) InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first()->due;
    $sectorDueAfterSecond = (float) SectorProfitDueLedger::where('sector_id', $this->sector->id)->first()->due;
    $directorDueAfterSecond = (float) DirectorDueLedger::where('director_id', $this->myDirector->id)->first()->due;

    // Values should be IDENTICAL (rollback + reapply = no net change)
    expect($inv1DueAfterSecond)->toBe($inv1DueAfterFirst);
    expect($sectorDueAfterSecond)->toBe($sectorDueAfterFirst);
    expect($directorDueAfterSecond)->toBe($directorDueAfterFirst);
});

it('updates ledgers correctly when sector profits change between finalizations', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000, // Y = 20K
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    // First finalize with Y=20K
    $this->service->calculate('2026-07-01', $this->superadmin->id);
    $sectorDueFirst = (float) SectorProfitDueLedger::where('sector_id', $this->sector->id)->first()->due;
    expect($sectorDueFirst)->toBe(20000.0);

    // Change actual profit to 190K (Y = 10K)
    MonthlySectorProfit::where('sector_id', $this->sector->id)
        ->where('profit_month', '2026-07-01')
        ->update(['actual_profit' => 190000]);

    // Re-finalize
    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Sector due should now be 10K (not 30K = 20K + 10K)
    $sectorDueSecond = (float) SectorProfitDueLedger::where('sector_id', $this->sector->id)->first()->due;
    expect($sectorDueSecond)->toBe(10000.0);
});

it('does not update ledgers when saving as draft', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'draft', // NOT finalized
        'transaction_date' => now(),
    ]);

    // POST with finalize=false
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sector->id, 'estimated_profit' => 200000, 'actual_profit' => 180000],
            ],
            'finalize' => false,
        ]);

    $response->assertRedirect();

    // Ledgers should NOT be updated
    $inv1Due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $inv1Due->due)->toBe(0.0);

    $sectorDue = SectorProfitDueLedger::where('sector_id', $this->sector->id)->first();
    expect((float) $sectorDue->due)->toBe(0.0);

    $directorDue = DirectorDueLedger::where('director_id', $this->myDirector->id)->first();
    expect((float) $directorDue->due)->toBe(0.0);
});

it('skips zero advance_difference entries (no ledger update needed)', function () {
    // Z2 = X2 = 200K → Y = 0, and for tier 100% investor: AH = 0
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000, // Y = 0
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    $this->service->calculate('2026-07-01', $this->superadmin->id);

    // Inv1 (tier 100%): AH = Q - AG = 120K - 120K = 0 → due stays 0
    $inv1Due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $inv1Due->due)->toBe(0.0);

    // Sector due: Y = Z - X = 0 → stays 0
    $sectorDue = SectorProfitDueLedger::where('sector_id', $this->sector->id)->first();
    expect((float) $sectorDue->due)->toBe(0.0);

    // But M/Y profit is NOT zero (tier discount from inv2)
    // AG182 = 120K + 48K = 168K, AG184 = 200K - 168K = 32K
    $directorDue = DirectorDueLedger::where('director_id', $this->myDirector->id)->first();
    expect((float) $directorDue->due)->toBe(32000.0);
});
