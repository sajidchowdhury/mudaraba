<?php

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\DirectorTransaction;
use App\Models\MonthlyProfitSummary;
use App\Models\RetainedEarnings;
use App\Models\User;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->director = Director::factory()->create(['name' => 'Test M/Y', 'is_my' => true]);
    DirectorDueLedger::create(['director_id' => $this->director->id, 'due' => 136162]);
});

it('allows superadmin to view the M/Y ledger page', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/my-ledger');
    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/reports/my-ledger');
    $response->assertRedirect('/login');
});

it('returns directors list for the selector', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/my-ledger');

    $response->assertInertia(fn ($page) => $page
        ->has('directors')
        ->where('directors.0.name', 'Test M/Y')
        ->where('directors.0.is_my', true)
    );
});

it('returns ledger entries when a director is selected', function () {
    DirectorTransaction::create([
        'director_id' => $this->director->id,
        'amount' => 50000,
        'type' => 'withdraw',
        'transaction_month' => '2026-07-01',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('ledger')
        ->where('ledger.0.type', 'transaction')
        ->where('ledger.0.subtype', 'withdraw')
        ->where('ledger.0.amount', fn ($v) => $v == -50000)
    );
});

it('computes running balance across multiple entries', function () {
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 32000, 'type' => 'withdraw',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 10000, 'type' => 'return',
        'transaction_month' => '2026-08-01', 'transaction_date' => '2026-08-10',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    // Withdraw: -32000, Return: +10000
    $response->assertInertia(fn ($page) => $page
        ->where('ledger.0.running_balance', fn ($v) => $v == -32000)
        ->where('ledger.1.running_balance', fn ($v) => $v == -22000)
    );
});

it('includes M/Y profit from monthly summaries', function () {
    MonthlyProfitSummary::create([
        'profit_month' => '2026-07-01',
        'total_estimated_profit' => 200000,
        'total_actual_profit' => 200000,
        'my_profit' => 32000,
        'my_profit_ratio' => 16.0,
        'total_mudaraba_investment' => 1000000,
        'active_investor_count' => 1,
        'status' => 'finalized',
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', fn ($v) => $v >= 1)
    );
});

it('includes retained earnings M/Y portion', function () {
    MonthlyProfitSummary::create([
        'profit_month' => '2026-07-01',
        'total_estimated_profit' => 200000,
        'total_actual_profit' => 200000,
        'my_profit' => 32000,
        'my_profit_ratio' => 16.0,
        'total_mudaraba_investment' => 1000000,
        'active_investor_count' => 1,
        'status' => 'finalized',
    ]);
    RetainedEarnings::create([
        'profit_month' => '2026-07-01',
        'total_amount' => 200000,
        'investor_portion_pct' => 71,
        'my_portion_pct' => 29,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    // Should have entries: M/Y profit + retained portion
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', fn ($v) => $v >= 2)
    );
});

it('returns summary with inflow/outflow/net', function () {
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 32000, 'type' => 'withdraw',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 10000, 'type' => 'return',
        'transaction_month' => '2026-08-01', 'transaction_date' => '2026-08-10',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    // Outflow: 32000 (withdraw), Inflow: 10000 (return)
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_inflow', fn ($v) => $v == 10000)
        ->where('summary.total_outflow', fn ($v) => $v == 32000)
        ->where('summary.net_balance', fn ($v) => $v == -22000)
    );
});

it('filters by date range', function () {
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 20000, 'type' => 'withdraw',
        'transaction_month' => '2026-06-01', 'transaction_date' => '2026-06-15',
        'created_by' => $this->superadmin->id,
    ]);
    DirectorTransaction::create([
        'director_id' => $this->director->id, 'amount' => 10000, 'type' => 'withdraw',
        'transaction_month' => '2026-08-01', 'transaction_date' => '2026-08-15',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}&date_from=2026-07-01");

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_entries', 1)
    );
});

it('shows empty state when no director selected', function () {
    $response = $this->actingAs($this->superadmin)->get('/reports/my-ledger');

    $response->assertInertia(fn ($page) => $page
        ->where('director', null)
        ->where('ledger', [])
    );
});

it('returns director opening due', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/reports/my-ledger?director_id={$this->director->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('director')
        ->where('director.opening_due', fn ($v) => $v == 136162)
    );
});
