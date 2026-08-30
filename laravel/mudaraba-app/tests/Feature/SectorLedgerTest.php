<?php

use App\Models\MonthlySectorProfit;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorInvestment;
use App\Models\User;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);

    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);
    SectorDueLedger::create(['sector_id' => $this->sector->id, 'due' => 1000000]);
});

it('allows superadmin to view the sector ledger page', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/sector-ledger');
    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/reports/sector-ledger');
    $response->assertRedirect('/login');
});

it('returns sectors list for the selector', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/sector-ledger');

    $response->assertInertia(fn ($page) => $page
        ->has('sectors')
        ->where('sectors.0.name', 'Test Sector')
    );
});

it('returns ledger entries when a sector is selected', function () {
    SectorInvestment::create([
        'sector_id' => $this->sector->id,
        'amount' => 200000,
        'type' => 'add',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('ledger')
        ->where('ledger.0.type', 'capital')
        ->where('ledger.0.subtype', 'add')
        ->where('ledger.0.amount', fn ($v) => $v == 200000)
        ->where('ledger.0.running_balance', fn ($v) => $v == 200000)
    );
});

it('computes running balance across multiple entries', function () {
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 300000, 'type' => 'add',
        'transaction_date' => '2026-07-15', 'created_by' => $this->superadmin->id,
    ]);
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 100000, 'type' => 'withdraw',
        'transaction_date' => '2026-08-10', 'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('ledger.0.running_balance', fn ($v) => $v == 300000)
        ->where('ledger.1.running_balance', fn ($v) => $v == 200000)  // 300K - 100K
    );
});

it('includes monthly sector profit entries', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 750000,
        'actual_profit' => 700000,
        'status' => 'finalized',
        'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    // Should have: estimated (750K), actual (700K), variance (50K)
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', fn ($v) => $v >= 3)
    );
});

it('includes profit adjustments for the sector', function () {
    ProfitAdjustment::create([
        'type' => 'fund_a',
        'target_type' => 'sector',
        'sector_id' => $this->sector->id,
        'amount' => 5000,
        'transaction_date' => '2026-07-20',
        'profit_month' => '2026-07-01',
        'batch_uuid' => 'test',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('ledger.0.type', 'adjustment')
        ->where('ledger.0.subtype', 'fund_a')
    );
});

it('returns summary with inflow/outflow/net', function () {
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 300000, 'type' => 'add',
        'transaction_date' => '2026-07-15', 'created_by' => $this->superadmin->id,
    ]);
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 100000, 'type' => 'withdraw',
        'transaction_date' => '2026-08-10', 'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_inflow', fn ($v) => $v == 300000)
        ->where('summary.total_outflow', fn ($v) => $v == 100000)
        ->where('summary.net_balance', fn ($v) => $v == 200000)
    );
});

it('filters by date range', function () {
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 200000, 'type' => 'add',
        'transaction_date' => '2026-06-15', 'created_by' => $this->superadmin->id,
    ]);
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 100000, 'type' => 'add',
        'transaction_date' => '2026-08-15', 'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}&date_from=2026-07-01");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', 1)
    );
});

it('shows empty state when no sector selected', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/sector-ledger');

    $response->assertInertia(fn ($page) => $page
        ->where('sector', null)
        ->where('ledger', [])
    );
});

it('returns sector opening balances', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/reports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('sector')
        ->where('sector.opening_balance', fn ($v) => $v == 1000000)
    );
});
