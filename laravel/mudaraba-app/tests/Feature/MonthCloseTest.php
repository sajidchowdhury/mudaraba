<?php

use App\Models\Director;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\User;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);

    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);
    Director::factory()->create(['name' => 'M/Y', 'is_my' => true]);

    $this->inv = Investor::factory()->create([
        'name' => 'Test Inv', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->inv->id, 'due' => 1000000]);
});

it('allows superadmin to view the month close page', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/month-close?month=2026-07-01');
    $response->assertStatus(200);
});

it('shows open status when no calculation exists', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/month-close?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page->where('status', 'open'));
});

it('shows finalized status after calculation runs', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    $response = $this->actingAs($this->superadmin)
        ->get('/month-close?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page->where('status', 'finalized'));
});

it('allows superadmin to lock a finalized month', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    $response = $this->actingAs($this->superadmin)
        ->post('/month-close/lock', ['month' => '2026-07-01']);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $summary = MonthlyProfitSummary::find('2026-07-01');
    expect($summary->status->value)->toBe('locked');
    expect($summary->locked_at)->not->toBeNull();
    expect($summary->locked_by)->toBe($this->superadmin->id);
});

it('prevents locking a month with no calculation', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/month-close/lock', ['month' => '2026-07-01']);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('prevents locking an already locked month', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // Lock once
    $this->actingAs($this->superadmin)->post('/month-close/lock', ['month' => '2026-07-01']);

    // Try to lock again
    $response = $this->actingAs($this->superadmin)
        ->post('/month-close/lock', ['month' => '2026-07-01']);

    $response->assertSessionHas('error');
});

it('allows superadmin to unlock a locked month', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // Lock
    $this->actingAs($this->superadmin)->post('/month-close/lock', ['month' => '2026-07-01']);

    // Unlock
    $response = $this->actingAs($this->superadmin)
        ->post('/month-close/unlock', ['month' => '2026-07-01']);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $summary = MonthlyProfitSummary::find('2026-07-01');
    expect($summary->status->value)->toBe('finalized');
    expect($summary->locked_at)->toBeNull();
    expect($summary->locked_by)->toBeNull();
});

it('blocks regular users from locking', function () {
    $response = $this->actingAs($this->regularUser)
        ->post('/month-close/lock', ['month' => '2026-07-01']);

    $response->assertStatus(403);
});

it('blocks regular users from unlocking', function () {
    $response = $this->actingAs($this->regularUser)
        ->post('/month-close/unlock', ['month' => '2026-07-01']);

    $response->assertStatus(403);
});

it('returns checklist with correct completion states', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    $response = $this->actingAs($this->superadmin)
        ->get('/month-close?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('allDone', true)
        ->where('checklist', fn ($items) => collect($items)->every(fn ($i) => $i['done']))
    );
});

it('returns allDone=false when checklist is incomplete', function () {
    // No sector profits entered, no calculation run
    $response = $this->actingAs($this->superadmin)
        ->get('/month-close?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page
        ->where('allDone', false)
        ->where('checklist', fn ($items) => collect($items)->contains(fn ($i) => ! $i['done']))
    );
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/month-close');
    $response->assertRedirect('/login');
});

it('passes isLocked to sector profit page', function () {
    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
    ]);
    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);
    $this->actingAs($this->superadmin)->post('/month-close/lock', ['month' => '2026-07-01']);

    $response = $this->actingAs($this->superadmin)
        ->get('/profit/sector?month=2026-07-01');

    $response->assertInertia(fn ($page) => $page->where('isLocked', true));
});
