<?php

use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create([
        'username' => 'superadmin_inv_tx',
        'role' => 'superadmin',
    ]);

    $this->regularUser = User::factory()->create([
        'username' => 'regular_inv_tx',
        'role' => 'user',
    ]);

    $this->investor = Investor::factory()->create([
        'name' => 'Test Investor for TX',
        'status' => 'active',
    ]);

    // Initialize the investor's due ledger at 0
    InvestorDueLedger::firstOrCreate(
        ['investor_id' => $this->investor->id],
        ['due' => 0],
    );
});

it('allows superadmin to view the investments page', function () {
    $response = $this->actingAs($this->superadmin)->get('/investments');
    $response->assertStatus(200);
});

it('allows superadmin to create an add transaction', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investments', [
            'investor_id' => $this->investor->id,
            'amount' => 500000,
            'type' => 'add',
            'transaction_month' => '2026-01-01',
            'transaction_date' => '2026-01-15',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify transaction record was created
    expect(InvestmentTransaction::where('investor_id', $this->investor->id)->count())->toBe(1);

    // Verify DueManager updated the ledger: +500000
    $balance = $this->investor->dueLedger()->first()->due;
    expect((float) $balance)->toBe(500000.0);
});

it('allows superadmin to create a withdraw transaction', function () {
    // First add 500000
    $this->actingAs($this->superadmin)
        ->post('/investments', [
            'investor_id' => $this->investor->id,
            'amount' => 500000,
            'type' => 'add',
            'transaction_month' => '2026-01-01',
            'transaction_date' => '2026-01-15',
        ]);

    // Then withdraw 200000
    $response = $this->actingAs($this->superadmin)
        ->post('/investments', [
            'investor_id' => $this->investor->id,
            'amount' => 200000,
            'type' => 'withdraw',
            'transaction_month' => '2026-02-01',
            'transaction_date' => '2026-02-10',
        ]);

    $response->assertRedirect();

    // Verify balance: 500000 - 200000 = 300000
    $balance = $this->investor->dueLedger()->first()->due;
    expect((float) $balance)->toBe(300000.0);
});

it('rolls back the ledger when a transaction is deleted', function () {
    // Create an add transaction
    $tx = InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 400000,
        'type' => 'add',
        'transaction_month' => '2026-01-01',
        'transaction_date' => '2026-01-15',
        'created_by' => $this->superadmin->id,
    ]);

    // Update the ledger (simulating the store flow)
    $tx->updateDue($this->investor->id, $tx->signedAmount(), '2026-01-01');
    expect((float) $this->investor->dueLedger()->first()->due)->toBe(400000.0);

    // Delete the transaction (should rollback the ledger)
    $response = $this->actingAs($this->superadmin)
        ->delete("/investments/{$tx->id}");

    $response->assertRedirect();

    // Verify ledger is back to 0
    expect((float) $this->investor->dueLedger()->first()->due)->toBe(0.0);

    // Verify transaction is soft-deleted
    expect(InvestmentTransaction::withTrashed()->where('id', $tx->id)->first()->deleted_at)->not->toBeNull();
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investments', []);

    $response->assertSessionHasErrors(['investor_id', 'amount', 'type', 'transaction_month', 'transaction_date']);
});

it('validates amount must be positive', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investments', [
            'investor_id' => $this->investor->id,
            'amount' => -100,
            'type' => 'add',
            'transaction_month' => '2026-01-01',
            'transaction_date' => '2026-01-15',
        ]);

    $response->assertSessionHasErrors(['amount']);
});

it('validates type must be add or withdraw', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investments', [
            'investor_id' => $this->investor->id,
            'amount' => 100,
            'type' => 'invalid',
            'transaction_month' => '2026-01-01',
            'transaction_date' => '2026-01-15',
        ]);

    $response->assertSessionHasErrors(['type']);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/investments');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission', function () {
    $response = $this->actingAs($this->regularUser)->get('/investments');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission', function () {
    $menu = Menu::where('route', 'investments.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/investments');
    $response->assertStatus(200);
});

it('returns investor balance via AJAX endpoint', function () {
    // Set a known balance
    InvestorDueLedger::where('investor_id', $this->investor->id)->update(['due' => 750000]);

    $response = $this->actingAs($this->superadmin)
        ->get("/investments/balance/{$this->investor->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'investor_id' => $this->investor->id,
        'balance' => 750000,
    ]);
});

it('supports filtering transactions by investor', function () {
    $otherInvestor = Investor::factory()->create(['name' => 'Other Investor']);

    InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000,
        'type' => 'add',
        'transaction_month' => '2026-01-01',
        'transaction_date' => '2026-01-15',
        'created_by' => $this->superadmin->id,
    ]);
    InvestmentTransaction::create([
        'investor_id' => $otherInvestor->id,
        'amount' => 200000,
        'type' => 'add',
        'transaction_month' => '2026-01-01',
        'transaction_date' => '2026-01-16',
        'created_by' => $this->superadmin->id,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->get("/investments?investor_id={$this->investor->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->where('transactions.total', 1));
});
