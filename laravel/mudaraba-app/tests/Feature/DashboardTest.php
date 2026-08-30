<?php

use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);
});

it('allows authenticated user to view the dashboard', function () {
    $response = $this->actingAs($this->superadmin)->get('/dashboard');
    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

it('returns KPIs with real data', function () {
    $inv = Investor::factory()->create(['name' => 'Test Inv', 'status' => 'active', 'deed_ratio' => '100']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 500000]);

    $response = $this->actingAs($this->superadmin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('kpis')
        ->where('kpis.0.label', 'Total Mudaraba Investment')
        ->where('kpis.0.value', fn ($v) => $v == 500000)
        ->where('kpis.3.label', 'Active Investors')
        ->where('kpis.3.value', fn ($v) => $v >= 1)
    );
});

it('returns monthly trend data (6 months)', function () {
    $response = $this->actingAs($this->superadmin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('trend')
        ->where('trend', fn ($t) => count($t) === 6)
    );
});

it('returns sector allocation data', function () {
    $sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);
    SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 1000000]);

    $response = $this->actingAs($this->superadmin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('sectorAllocation')
        ->where('sectorAllocation.0.name', 'Test Sector')
        ->where('sectorAllocation.0.value', fn ($v) => $v == 1000000)
    );
});

it('returns investor tier distribution', function () {
    Investor::factory()->count(2)->create(['deed_ratio' => '100', 'status' => 'active']);
    Investor::factory()->create(['deed_ratio' => '80', 'status' => 'active']);
    Investor::factory()->count(3)->create(['deed_ratio' => '60', 'status' => 'active']);

    $response = $this->actingAs($this->superadmin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('tierDistribution')
        ->where('tierDistribution.0.name', 'Tier 100%')
        ->where('tierDistribution.0.value', fn ($v) => $v >= 2)
        ->where('tierDistribution.1.name', 'Tier 80%')
        ->where('tierDistribution.1.value', fn ($v) => $v >= 1)
        ->where('tierDistribution.2.name', 'Tier 60%')
        ->where('tierDistribution.2.value', fn ($v) => $v >= 3)
    );
});

it('returns recent activity', function () {
    $response = $this->actingAs($this->superadmin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->has('recentActivity'));
});

it('returns hasData flag based on investment + profit', function () {
    // Clear cache to ensure fresh data
    Cache::flush();

    // No data → hasData = false
    $response = $this->actingAs($this->superadmin)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('hasData', false));

    // Add investment → hasData = true
    $inv = Investor::factory()->create(['status' => 'active']);
    InvestorDueLedger::create(['investor_id' => $inv->id, 'due' => 100000]);

    // Clear cache to pick up the new investment
    Cache::flush();

    $response = $this->actingAs($this->superadmin)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('hasData', true));
});

it('allows regular users to view dashboard', function () {
    $response = $this->actingAs($this->regularUser)->get('/dashboard');
    $response->assertStatus(200);
});
