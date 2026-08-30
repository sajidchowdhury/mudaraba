<?php

use App\Models\Director;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlySectorProfit;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->sector = Sector::factory()->create(['status' => 'active']);
    Director::factory()->create(['is_my' => true]);

    $this->investor = Investor::factory()->create([
        'name' => 'Test Investor', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->investor->id, 'due' => 500000]);
});

it('allows superadmin to view the investor ledger page', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investor-ledger');
    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/reports/investor-ledger');
    $response->assertRedirect('/login');
});

it('returns investors list for the selector', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investor-ledger');

    $response->assertInertia(fn ($page) => $page
        ->has('investors')
        ->where('investors.0.name', 'Test Investor')
    );
});

it('returns ledger entries when an investor is selected', function () {
    // Create a capital transaction
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000,
        'type' => 'add',
        'transaction_month' => '2026-07-01',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('ledger')
        ->where('ledger.0.type', 'capital')
        ->where('ledger.0.subtype', 'add')
        ->where('ledger.0.amount', fn ($v) => $v == 100000)
        ->where('ledger.0.running_balance', fn ($v) => $v == 100000)
    );
});

it('computes running balance correctly across multiple entries', function () {
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000,
        'type' => 'add',
        'transaction_month' => '2026-07-01',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 30000,
        'type' => 'withdraw',
        'transaction_month' => '2026-08-01',
        'transaction_date' => '2026-08-10',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('ledger.0.running_balance', fn ($v) => $v == 100000)  // +100K
        ->where('ledger.1.running_balance', fn ($v) => $v == 70000)    // +100K - 30K = 70K
    );
});

it('includes profit distribution entries in the ledger', function () {
    // Create sector profit + run calculation
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 200000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    // Should have at least 1 profit entry
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', fn ($v) => $v >= 1)
    );
});

it('includes profit adjustments in the ledger', function () {
    ProfitAdjustment::create([
        'type' => 'direct',
        'target_type' => 'investor',
        'investor_id' => $this->investor->id,
        'amount' => 5000,
        'transaction_date' => '2026-07-20',
        'profit_month' => '2026-07-01',
        'batch_uuid' => 'test-batch',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', 1)
        ->where('ledger.0.type', 'adjustment')
        ->where('ledger.0.subtype', 'direct')
    );
});

it('returns summary with inflow/outflow/net', function () {
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000,
        'type' => 'add',
        'transaction_month' => '2026-07-01',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 30000,
        'type' => 'withdraw',
        'transaction_month' => '2026-08-01',
        'transaction_date' => '2026-08-10',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    // Inflow = 100K, Outflow = 30K, Net = 70K
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_inflow', fn ($v) => $v == 100000)
        ->where('summary.total_outflow', fn ($v) => $v == 30000)
        ->where('summary.net_balance', fn ($v) => $v == 70000)
    );
});

it('filters by date range', function () {
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000,
        'type' => 'add',
        'transaction_month' => '2026-06-01',
        'transaction_date' => '2026-06-15',
        'created_by' => $this->superadmin->id,
    ]);
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 50000,
        'type' => 'add',
        'transaction_month' => '2026-08-01',
        'transaction_date' => '2026-08-15',
        'created_by' => $this->superadmin->id,
    ]);

    // Filter to only July+
    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}&date_from=2026-07-01");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', 1)  // Only Aug entry
    );
});

it('shows empty state when no investor selected', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investor-ledger');

    $response->assertInertia(fn ($page) => $page
        ->where('investor', null)
        ->where('ledger', [])
    );
});

it('returns investor opening balances', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/reports/investor-ledger?investor_id={$this->investor->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('investor')
        ->where('investor.opening_balance', fn ($v) => $v == 500000)
    );
});
