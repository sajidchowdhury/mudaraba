<?php

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);

    $this->director = Director::factory()->create(['name' => 'Test M/Y', 'is_my' => true]);
    $this->inv1 = Investor::factory()->create(['name' => 'Inv1', 'status' => 'active']);
    $this->inv2 = Investor::factory()->create(['name' => 'Inv2', 'status' => 'active']);
    $this->sector1 = Sector::factory()->create(['name' => 'Sec1', 'status' => 'active']);
    $this->sector2 = Sector::factory()->create(['name' => 'Sec2', 'status' => 'active']);
});

it('allows superadmin to view the opening balances page', function () {
    $response = $this->actingAs($this->superadmin)->get('/opening');
    $response->assertStatus(200);
});

it('blocks regular users from viewing opening balances', function () {
    $response = $this->actingAs($this->regularUser)->get('/opening');
    $response->assertStatus(403);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/opening');
    $response->assertRedirect('/login');
});

it('updates director (M/Y) opening balance', function () {
    $response = $this->actingAs($this->superadmin)
        ->put("/opening/director/{$this->director->id}", ['due' => 136162]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $ledger = DirectorDueLedger::where('director_id', $this->director->id)->first();
    expect($ledger)->not->toBeNull();
    expect((float) $ledger->due)->toBe(136162.0);
});

it('creates director ledger if it does not exist', function () {
    expect(DirectorDueLedger::where('director_id', $this->director->id)->exists())->toBeFalse();

    $this->actingAs($this->superadmin)
        ->put("/opening/director/{$this->director->id}", ['due' => 50000]);

    expect(DirectorDueLedger::where('director_id', $this->director->id)->exists())->toBeTrue();
});

it('updates existing director ledger without creating duplicate', function () {
    DirectorDueLedger::create(['director_id' => $this->director->id, 'due' => 100000]);

    $this->actingAs($this->superadmin)
        ->put("/opening/director/{$this->director->id}", ['due' => 150000]);

    $count = DirectorDueLedger::where('director_id', $this->director->id)->count();
    expect($count)->toBe(1);

    $ledger = DirectorDueLedger::where('director_id', $this->director->id)->first();
    expect((float) $ledger->due)->toBe(150000.0);
});

it('bulk updates investor opening balances (capital + profit)', function () {
    $response = $this->actingAs($this->superadmin)
        ->put('/opening/investors', [
            'items' => [
                ['id' => $this->inv1->id, 'capital_due' => 400000, 'profit_due' => 5000],
                ['id' => $this->inv2->id, 'capital_due' => 300000, 'profit_due' => 0],
            ],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify capital ledgers
    $inv1Capital = InvestorDueLedger::where('investor_id', $this->inv1->id)->first();
    expect((float) $inv1Capital->due)->toBe(400000.0);

    $inv2Capital = InvestorDueLedger::where('investor_id', $this->inv2->id)->first();
    expect((float) $inv2Capital->due)->toBe(300000.0);

    // Verify profit ledger (only for inv1, inv2 has 0 so no profit ledger created)
    $inv1Profit = InvestorProfitDueLedger::where('investor_id', $this->inv1->id)->first();
    expect($inv1Profit)->not->toBeNull();
    expect((float) $inv1Profit->due)->toBe(5000.0);

    expect(InvestorProfitDueLedger::where('investor_id', $this->inv2->id)->exists())->toBeFalse();
});

it('bulk updates sector opening balances (capital + profit)', function () {
    $response = $this->actingAs($this->superadmin)
        ->put('/opening/sectors', [
            'items' => [
                ['id' => $this->sector1->id, 'capital_due' => 46155000, 'profit_due' => 9000],
                ['id' => $this->sector2->id, 'capital_due' => 44603550, 'profit_due' => 0],
            ],
        ]);

    $response->assertRedirect();

    $sec1Capital = SectorDueLedger::where('sector_id', $this->sector1->id)->first();
    expect((float) $sec1Capital->due)->toBe(46155000.0);

    $sec1Profit = SectorProfitDueLedger::where('sector_id', $this->sector1->id)->first();
    expect($sec1Profit)->not->toBeNull();
    expect((float) $sec1Profit->due)->toBe(9000.0);
});

it('returns totals in the response', function () {
    // Set some balances
    InvestorDueLedger::create(['investor_id' => $this->inv1->id, 'due' => 500000]);
    InvestorDueLedger::create(['investor_id' => $this->inv2->id, 'due' => 300000]);
    SectorDueLedger::create(['sector_id' => $this->sector1->id, 'due' => 1000000]);
    DirectorDueLedger::create(['director_id' => $this->director->id, 'due' => 136162]);

    $response = $this->actingAs($this->superadmin)->get('/opening');

    $response->assertInertia(fn ($page) => $page
        ->where('totals.investor_capital', fn ($v) => $v == 800000)
        ->where('totals.sector_capital', fn ($v) => $v == 1000000)
        ->where('totals.director_due', fn ($v) => $v == 136162)
    );
});

it('validates required fields for director update', function () {
    $response = $this->actingAs($this->superadmin)
        ->put("/opening/director/{$this->director->id}", []);

    $response->assertSessionHasErrors(['due']);
});

it('validates required fields for investor bulk update', function () {
    $response = $this->actingAs($this->superadmin)
        ->put('/opening/investors', []);

    $response->assertSessionHasErrors(['items']);
});

it('excludes closed investors from the list', function () {
    $closedInv = Investor::factory()->create(['name' => 'Closed Inv', 'status' => 'closed']);

    $response = $this->actingAs($this->superadmin)->get('/opening');

    $response->assertInertia(fn ($page) => $page
        ->where('investors', fn ($investors) => ! collect($investors)->contains('name', 'Closed Inv'))
    );
});
