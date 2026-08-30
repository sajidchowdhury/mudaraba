<?php

use App\Models\Director;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    Director::factory()->create(['is_my' => true]);
    $this->sector = Sector::factory()->create(['status' => 'active']);

    $this->inv1 = Investor::factory()->create([
        'name' => 'Inv1', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 600000]);

    $this->inv2 = Investor::factory()->create([
        'name' => 'Inv2', 'deed_ratio' => '60', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 400000]);

    // Finalize sector profits + run calculation
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);
});

it('allows superadmin to view the investment profit report', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');
    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/reports/investment-profit');
    $response->assertRedirect('/login');
});

it('returns grid with all investors for the month', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('grid')
        ->where('grid', fn ($grid) => count($grid) === 2)
    );
});

it('returns correct month label', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('month', '2026-07-01')
        ->where('monthLabel', 'July, 2026')
    );
});

it('returns totals with M/Y profit + ratio', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('totals')
        ->where('totals.investment', fn ($v) => $v == 1000000)
        ->where('totals.total_actual', fn ($v) => $v == 200000)
        ->where('totals.my_profit', fn ($v) => $v > 0)
    );
});

it('returns hasData=true when calculation exists', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page->where('hasData', true));
});

it('returns hasData=false for a month with no calculation', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2025-01-01');

    $response->assertInertia(fn ($page) => $page->where('grid', fn ($g) => collect($g)->every(fn ($i) => ! $i['has_detail'])));
});

it('filters by tier', function () {
    // Tier 100% → only inv1
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01&tier=100');

    $response->assertInertia(fn ($page) => $page
        ->where('grid', fn ($grid) => count($grid) === 1 && $grid[0]['name'] === 'Inv1')
    );
});

it('sorts investors by investment descending', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    // inv1 (600K) should be first, inv2 (400K) second
    $response->assertInertia(fn ($page) => $page
        ->where('grid.0.name', 'Inv1')
        ->where('grid.1.name', 'Inv2')
    );
});

it('returns profit details for each investor in the grid', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/reports/investment-profit?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('grid.0.has_detail', true)
        ->where('grid.0.investment_ratio', fn ($v) => $v > 0)
        ->where('grid.0.actual_profit_due', fn ($v) => $v > 0)
    );
});
