<?php

use App\Models\Investor;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create([
        'username' => 'superadmin_inv',
        'role' => 'superadmin',
    ]);

    $this->regularUser = User::factory()->create([
        'username' => 'regular_inv',
        'role' => 'user',
    ]);

    $this->investor = Investor::factory()->create([
        'name' => 'Test Investor',
        'deed_ratio' => '100',
        'status' => 'active',
    ]);
});

it('allows superadmin to view the investors list', function () {
    $response = $this->actingAs($this->superadmin)->get('/investors');
    $response->assertStatus(200);
});

it('allows superadmin to view a single investor', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/investors/{$this->investor->id}");
    $response->assertStatus(200);
});

it('allows superadmin to view the create form', function () {
    $response = $this->actingAs($this->superadmin)->get('/investors/new');
    $response->assertStatus(200);
});

it('allows superadmin to create an investor', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investors', [
            'name' => 'New Test Investor',
            'deed_ratio' => '80',
            'status' => 'active',
        ]);

    $response->assertRedirect();
    expect(Investor::where('name', 'New Test Investor')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investors', []);

    $response->assertSessionHasErrors(['name', 'deed_ratio', 'status']);
});

it('validates deed_ratio must be 60, 80, or 100', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/investors', [
            'name' => 'Bad Investor',
            'deed_ratio' => '50',
            'status' => 'active',
        ]);

    $response->assertSessionHasErrors(['deed_ratio']);
});

it('allows superadmin to edit an investor', function () {
    $response = $this->actingAs($this->superadmin)
        ->put("/investors/{$this->investor->id}", [
            'name' => 'Updated Name',
            'deed_ratio' => '60',
            'status' => 'inactive',
        ]);

    $response->assertRedirect();
    expect($this->investor->fresh()->name)->toBe('Updated Name');
    expect($this->investor->fresh()->deed_ratio)->toBe('60');
});

it('soft-deletes an investor on destroy', function () {
    $response = $this->actingAs($this->superadmin)
        ->delete("/investors/{$this->investor->id}");

    $response->assertRedirect('/investors');
    expect($this->investor->fresh()->deleted_at)->not->toBeNull();
    // Record still exists in DB (soft delete)
    expect(Investor::withTrashed()->where('id', $this->investor->id)->exists())->toBeTrue();
});

it('redirects unauthenticated users from investors to login', function () {
    $response = $this->get('/investors');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission from viewing investors', function () {
    $response = $this->actingAs($this->regularUser)->get('/investors');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission to view investors', function () {
    $this->seed(MenuSeeder::class);

    $menu = Menu::where('route', 'investors.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/investors');
    $response->assertStatus(200);
});

it('supports search filter on investors list', function () {
    Investor::factory()->create(['name' => 'Alpha Investor']);
    Investor::factory()->create(['name' => 'Beta Investor']);

    $response = $this->actingAs($this->superadmin)
        ->get('/investors?search=Alpha');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('investors.data')
        ->where('investors.total', 1)
    );
});
