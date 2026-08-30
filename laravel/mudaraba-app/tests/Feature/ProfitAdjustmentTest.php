<?php

use App\Enums\AdjustmentType;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->inv1 = Investor::factory()->create(['name' => 'Inv1', 'deed_ratio' => '100', 'status' => 'active']);
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 500000]);
    InvestorProfitDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 0]);

    $this->inv2 = Investor::factory()->create(['name' => 'Inv2', 'deed_ratio' => '60', 'status' => 'active']);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 300000]);
    InvestorProfitDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 0]);

    $this->sector1 = Sector::factory()->create(['name' => 'Sector A', 'status' => 'active']);
    $this->sector2 = Sector::factory()->create(['name' => 'Sector B', 'status' => 'active']);
    SectorProfitDueLedger::create(['sector_id' => $this->sector1->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $this->sector2->id, 'due' => 0]);
});

it('allows superadmin to view the adjustments page', function () {
    $response = $this->actingAs($this->superadmin)->get('/adjustments');
    $response->assertStatus(200);
});

it('stores a Fund A batch adjustment with investor + sector items', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [
                ['investor_id' => $this->inv1->id, 'amount' => 5000],
                ['investor_id' => $this->inv2->id, 'amount' => 3000],
            ],
            'sector_items' => [
                ['sector_id' => $this->sector1->id, 'amount' => 4000],
            ],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify 3 adjustment records created (2 investor + 1 sector)
    expect(ProfitAdjustment::count())->toBe(3);
    expect(ProfitAdjustment::where('type', 'fund_a')->count())->toBe(3);
});

it('updates investor profit due ledger on batch adjustment', function () {
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [
                ['investor_id' => $this->inv1->id, 'amount' => 5000],
            ],
            'sector_items' => [],
        ]);

    // Inv1's profit due should be -5000 (decreased by 5000)
    $due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $due->due)->toBe(-5000.0);
});

it('updates sector profit due ledger on batch adjustment', function () {
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [],
            'sector_items' => [
                ['sector_id' => $this->sector1->id, 'amount' => 4000],
            ],
        ]);

    $due = SectorProfitDueLedger::where('sector_id', $this->sector1->id)->first();
    expect((float) $due->due)->toBe(-4000.0);
});

it('computes Fund A balance correctly (investors - sectors)', function () {
    // Fund A: investor adjustments = 5000 + 3000 = 8000
    //         sector adjustments = 4000
    //         Fund A balance = 8000 - 4000 = 4000
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [
                ['investor_id' => $this->inv1->id, 'amount' => 5000],
                ['investor_id' => $this->inv2->id, 'amount' => 3000],
            ],
            'sector_items' => [
                ['sector_id' => $this->sector1->id, 'amount' => 4000],
            ],
        ]);

    $balance = ProfitAdjustment::fundBalance(AdjustmentType::FundA);
    expect($balance)->toBe(4000.0);  // 8000 - 4000 = 4000
});

it('keeps Fund A and Fund B balances separate', function () {
    // Fund A: investor 5000, sector 0 → balance = 5000
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [['investor_id' => $this->inv1->id, 'amount' => 5000]],
            'sector_items' => [],
        ]);

    // Fund B: investor 3000, sector 1000 → balance = 2000
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_b',
            'transaction_date' => '2026-07-16',
            'profit_month' => '2026-07-01',
            'investor_items' => [['investor_id' => $this->inv2->id, 'amount' => 3000]],
            'sector_items' => [['sector_id' => $this->sector1->id, 'amount' => 1000]],
        ]);

    expect(ProfitAdjustment::fundBalance(AdjustmentType::FundA))->toBe(5000.0);
    expect(ProfitAdjustment::fundBalance(AdjustmentType::FundB))->toBe(2000.0);
});

it('stores a direct (single investor) adjustment', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/adjustments/direct', [
            'investor_id' => $this->inv1->id,
            'amount' => 10000,
            'transaction_date' => '2026-07-20',
            'profit_month' => '2026-07-01',
        ]);

    $response->assertRedirect();

    $adj = ProfitAdjustment::where('type', 'direct')->first();
    expect($adj)->not->toBeNull();
    expect((float) $adj->amount)->toBe(10000.0);
    expect($adj->target_type->value)->toBe('investor');

    // Inv1's profit due should be -10000
    $due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $due->due)->toBe(-10000.0);
});

it('does not affect fund balances for direct adjustments', function () {
    $this->actingAs($this->superadmin)
        ->post('/adjustments/direct', [
            'investor_id' => $this->inv1->id,
            'amount' => 10000,
            'transaction_date' => '2026-07-20',
            'profit_month' => '2026-07-01',
        ]);

    expect(ProfitAdjustment::fundBalance(AdjustmentType::FundA))->toBe(0.0);
    expect(ProfitAdjustment::fundBalance(AdjustmentType::FundB))->toBe(0.0);
});

it('rolls back ledger on delete', function () {
    // Create a direct adjustment
    $this->actingAs($this->superadmin)
        ->post('/adjustments/direct', [
            'investor_id' => $this->inv1->id,
            'amount' => 10000,
            'transaction_date' => '2026-07-20',
            'profit_month' => '2026-07-01',
        ]);

    // Verify due was decreased
    $due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $due->due)->toBe(-10000.0);

    // Delete the adjustment
    $adj = ProfitAdjustment::first();
    $response = $this->actingAs($this->superadmin)
        ->delete("/adjustments/{$adj->id}");

    $response->assertRedirect();

    // Due should be back to 0 (rolled back)
    $due = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $due->due)->toBe(0.0);

    // Record should be soft-deleted
    expect(ProfitAdjustment::withTrashed()->where('id', $adj->id)->first()->deleted_at)->not->toBeNull();
});

it('returns fund balances in the index response', function () {
    $response = $this->actingAs($this->superadmin)->get('/adjustments');

    $response->assertInertia(fn ($page) => $page
        ->has('fundBalances')
        ->where('fundBalances.fund_a', fn ($v) => $v == 0)
        ->where('fundBalances.fund_b', fn ($v) => $v == 0)
    );
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/adjustments');
    $response->assertRedirect('/login');
});

it('validates required fields for batch', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', []);

    $response->assertSessionHasErrors(['type', 'transaction_date', 'profit_month']);
});

it('validates required fields for direct', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/adjustments/direct', []);

    $response->assertSessionHasErrors(['investor_id', 'amount', 'transaction_date', 'profit_month']);
});

it('skips zero-amount entries in batch', function () {
    $this->actingAs($this->superadmin)
        ->post('/adjustments/batch', [
            'type' => 'fund_a',
            'transaction_date' => '2026-07-15',
            'profit_month' => '2026-07-01',
            'investor_items' => [
                ['investor_id' => $this->inv1->id, 'amount' => 5000],
                ['investor_id' => $this->inv2->id, 'amount' => 0],
            ],
            'sector_items' => [],
        ]);

    // Only 1 record should be created (inv2 with 0 is skipped)
    expect(ProfitAdjustment::count())->toBe(1);
});

it('supports filtering by type', function () {
    ProfitAdjustment::create([
        'type' => 'fund_a', 'target_type' => 'investor',
        'investor_id' => $this->inv1->id, 'amount' => 1000,
        'transaction_date' => '2026-07-01', 'profit_month' => '2026-07-01',
        'batch_uuid' => 'test-1', 'created_by' => $this->superadmin->id,
    ]);
    ProfitAdjustment::create([
        'type' => 'direct', 'target_type' => 'investor',
        'investor_id' => $this->inv2->id, 'amount' => 2000,
        'transaction_date' => '2026-07-02', 'profit_month' => '2026-07-01',
        'batch_uuid' => 'test-2', 'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get('/adjustments?type=fund_a');

    $response->assertInertia(fn ($page) => $page
        ->where('adjustments.total', 1)
    );
});
