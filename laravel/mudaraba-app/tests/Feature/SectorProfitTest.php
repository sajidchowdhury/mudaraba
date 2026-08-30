<?php

use App\Models\Menu;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\InvestorSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\SectorSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->seed(SectorSeeder::class);
    $this->seed(InvestorSeeder::class);

    $this->superadmin = User::factory()->create([
        'username' => 'superadmin_sp',
        'role' => 'superadmin',
    ]);

    $this->regularUser = User::factory()->create([
        'username' => 'regular_sp',
        'role' => 'user',
    ]);

    $this->sector1 = Sector::firstWhere('name', 'China House BD');
    $this->sector2 = Sector::firstWhere('name', 'Bike X');
});

it('allows superadmin to view the sector profit page', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/sector?month=2026-07-01');

    $response->assertStatus(200);
});

it('shows the correct month label', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/sector?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('month', '2026-07-01')
        ->where('monthLabel', 'July, 2026')
    );
});

it('returns all active sectors in the grid', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/sector?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->has('grid')
        ->where('grid', fn ($grid) => count($grid) > 0)
    );
});

it('allows superadmin to save sector profits as draft', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                [
                    'sector_id' => $this->sector1->id,
                    'estimated_profit' => 750000,
                    'actual_profit' => 750000,
                ],
                [
                    'sector_id' => $this->sector2->id,
                    'estimated_profit' => 150000,
                    'actual_profit' => 120000,
                ],
            ],
            'finalize' => false,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify records were created with 'draft' status
    $entry1 = MonthlySectorProfit::where('sector_id', $this->sector1->id)
        ->where('profit_month', '2026-07-01')
        ->first();
    expect($entry1)->not->toBeNull();
    expect($entry1->estimated_profit)->toBe('750000.00');
    expect($entry1->actual_profit)->toBe('750000.00');
    expect($entry1->status->value)->toBe('draft');
});

it('allows superadmin to finalize sector profits', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                [
                    'sector_id' => $this->sector1->id,
                    'estimated_profit' => 750000,
                    'actual_profit' => 750000,
                ],
            ],
            'finalize' => true,
        ]);

    $response->assertRedirect();

    $entry = MonthlySectorProfit::where('sector_id', $this->sector1->id)
        ->where('profit_month', '2026-07-01')
        ->first();
    expect($entry->status->value)->toBe('finalized');
    expect($entry->finalized_at)->not->toBeNull();
    expect($entry->finalized_by)->toBe($this->superadmin->id);
});

it('updates existing entries instead of creating duplicates', function () {
    // First save
    $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sector1->id, 'estimated_profit' => 500000, 'actual_profit' => 500000],
            ],
            'finalize' => false,
        ]);

    // Second save (update)
    $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sector1->id, 'estimated_profit' => 750000, 'actual_profit' => 750000],
            ],
            'finalize' => false,
        ]);

    // Should have only 1 record, with updated values
    $count = MonthlySectorProfit::where('sector_id', $this->sector1->id)
        ->where('profit_month', '2026-07-01')
        ->count();
    expect($count)->toBe(1);

    $entry = MonthlySectorProfit::where('sector_id', $this->sector1->id)
        ->where('profit_month', '2026-07-01')
        ->first();
    expect($entry->estimated_profit)->toBe('750000.00');
});

it('skips entries with zero estimated and zero actual', function () {
    $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sector1->id, 'estimated_profit' => 750000, 'actual_profit' => 750000],
                ['sector_id' => $this->sector2->id, 'estimated_profit' => 0, 'actual_profit' => 0],
            ],
            'finalize' => false,
        ]);

    // sector2 should NOT have a record (both values are 0)
    $entry2 = MonthlySectorProfit::where('sector_id', $this->sector2->id)
        ->where('profit_month', '2026-07-01')
        ->first();
    expect($entry2)->toBeNull();
});

it('validates required fields', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', []);

    $response->assertSessionHasErrors(['profit_month', 'items']);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/profit/sector');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission', function () {
    $response = $this->actingAs($this->regularUser)->get('/profit/sector');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission', function () {
    $menu = Menu::where('route', 'profit.sector.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => true,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/profit/sector');
    $response->assertStatus(200);
});

it('computes totals correctly', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/profit/sector', [
            'profit_month' => '2026-07-01',
            'items' => [
                ['sector_id' => $this->sector1->id, 'estimated_profit' => 750000, 'actual_profit' => 750000],
                ['sector_id' => $this->sector2->id, 'estimated_profit' => 150000, 'actual_profit' => 120000],
            ],
            'finalize' => false,
        ]);

    // Now GET the page — totals should be Z2=900000, X2=870000, Y2=30000
    $response = $this->actingAs($this->superadmin)
        ->get('/profit/sector?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('totals.estimated', fn ($v) => $v == 900000)  // Z2
        ->where('totals.actual', fn ($v) => $v == 870000)      // X2
        ->where('totals.variance', fn ($v) => $v == 30000)     // Y2 = Z2 - X2
    );
});
