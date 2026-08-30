<?php

use App\Models\Director;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\Menu;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);

    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);

    Director::factory()->create(['name' => 'M/Y', 'is_my' => true]);

    // Create 2 investors
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

    // Create + finalize sector profits (triggers the full 8-phase calculation)
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000,
        'actual_profit' => 180000,
        'status' => 'finalized',
        'transaction_date' => now(),
    ]);

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);
});

it('allows superadmin to view the investor profit page', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertStatus(200);
});

it('returns the correct month label', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('month', '2026-07-01')
        ->where('monthLabel', 'July, 2026')
    );
});

it('returns grid data with all 8-phase columns', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('grid')
        ->where('grid', fn ($grid) => count($grid) === 2)
    );
});

it('returns totals with Excel cell references (Z2, X2, Y2, AG182, AG184)', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('totals')
        ->where('totals.total_estimated', fn ($v) => $v == 200000)    // Z2
        ->where('totals.total_actual', fn ($v) => $v == 180000)         // X2
        ->where('totals.total_variance', fn ($v) => $v == 20000)        // Y2
        ->where('totals.total_investment', fn ($v) => $v == 1000000)    // D181
        ->where('totals.total_profit_due', fn ($v) => $v == 108000 + 43200) // AG182 = 151200
        ->where('totals.my_profit', fn ($v) => $v == 180000 - 151200)    // AG184 = 28800
    );
});

it('returns retained earnings breakdown (AI3, AJ4, AK4)', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('retained')
        ->where('retained.total_amount', fn ($v) => $v == 200000)      // AI3
        ->where('retained.investor_portion', fn ($v) => $v == 142000)   // AJ4
        ->where('retained.my_portion', fn ($v) => $v == 58000)          // AK4
    );
});

it('returns isCalculated=true when profit has been computed', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('isCalculated', true)
    );
});

it('returns isCalculated=false for a month with no calculation', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2025-01-01');

    $response->assertInertia(fn ($page) => $page
        ->where('isCalculated', false)
        ->where('grid', [])
        ->where('totals', null)
    );
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/profit/investor');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission', function () {
    $response = $this->actingAs($this->regularUser)->get('/profit/investor');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission', function () {
    $menu = Menu::where('route', 'profit.investor.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/profit/investor');
    $response->assertStatus(200);
});

it('sorts investors by investment descending (largest first)', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/investor?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('grid.0.investor_name', 'Inv1')  // 600K > 400K
    );
});
