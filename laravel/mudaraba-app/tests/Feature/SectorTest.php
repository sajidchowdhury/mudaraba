<?php

use App\Models\Menu;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create([
        'username' => 'superadmin_sec',
        'role' => 'superadmin',
    ]);

    $this->regularUser = User::factory()->create([
        'username' => 'regular_sec',
        'role' => 'user',
    ]);

    $this->sector = Sector::factory()->create([
        'name' => 'Test Sector',
        'status' => 'active',
    ]);
});

it('allows superadmin to view the sectors list', function () {
    $response = $this->actingAs($this->superadmin)->get('/sectors');
    $response->assertStatus(200);
});

it('allows superadmin to view a single sector', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/sectors/{$this->sector->id}");
    $response->assertStatus(200);
});

it('allows superadmin to view the create form', function () {
    $response = $this->actingAs($this->superadmin)->get('/sectors/new');
    $response->assertStatus(200);
});

it('allows superadmin to create a sector', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/sectors', [
            'name' => 'New Test Sector',
            'status' => 'active',
        ]);

    $response->assertRedirect();
    expect(Sector::where('name', 'New Test Sector')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/sectors', []);

    $response->assertSessionHasErrors(['name', 'status']);
});

it('allows superadmin to edit a sector', function () {
    $response = $this->actingAs($this->superadmin)
        ->put("/sectors/{$this->sector->id}", [
            'name' => 'Updated Sector Name',
            'status' => 'inactive',
        ]);

    $response->assertRedirect();
    expect($this->sector->fresh()->name)->toBe('Updated Sector Name');
    expect($this->sector->fresh()->status)->toBe('inactive');
});

it('soft-deletes a sector on destroy', function () {
    $response = $this->actingAs($this->superadmin)
        ->delete("/sectors/{$this->sector->id}");

    $response->assertRedirect('/sectors');
    expect($this->sector->fresh()->deleted_at)->not->toBeNull();
    expect(Sector::withTrashed()->where('id', $this->sector->id)->exists())->toBeTrue();
});

it('redirects unauthenticated users from sectors to login', function () {
    $response = $this->get('/sectors');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission from viewing sectors', function () {
    $response = $this->actingAs($this->regularUser)->get('/sectors');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission to view sectors', function () {
    $menu = Menu::where('route', 'sectors.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/sectors');
    $response->assertStatus(200);
});

it('supports search filter on sectors list', function () {
    Sector::factory()->create(['name' => 'Alpha Sector']);
    Sector::factory()->create(['name' => 'Beta Sector']);

    $response = $this->actingAs($this->superadmin)
        ->get('/sectors?search=Alpha');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('sectors.data')
        ->where('sectors.total', 1)
    );
});
