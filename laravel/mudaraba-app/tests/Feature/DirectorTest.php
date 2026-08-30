<?php

use App\Models\Director;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create([
        'username' => 'superadmin_dir',
        'role' => 'superadmin',
    ]);

    $this->regularUser = User::factory()->create([
        'username' => 'regular_dir',
        'role' => 'user',
    ]);

    $this->director = Director::factory()->create([
        'name' => 'Test Director',
        'is_my' => false,
    ]);
});

it('allows superadmin to view the directors list', function () {
    $response = $this->actingAs($this->superadmin)->get('/directors');
    $response->assertStatus(200);
});

it('allows superadmin to view a single director', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/directors/{$this->director->id}");
    $response->assertStatus(200);
});

it('allows superadmin to view the create form', function () {
    $response = $this->actingAs($this->superadmin)->get('/directors/new');
    $response->assertStatus(200);
});

it('allows superadmin to create a director', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/directors', [
            'name' => 'New Test Director',
            'is_my' => false,
        ]);

    $response->assertRedirect();
    expect(Director::where('name', 'New Test Director')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/directors', []);

    $response->assertSessionHasErrors(['name']);
});

it('allows superadmin to edit a director', function () {
    $response = $this->actingAs($this->superadmin)
        ->put("/directors/{$this->director->id}", [
            'name' => 'Updated Director Name',
            'is_my' => false,
        ]);

    $response->assertRedirect();
    expect($this->director->fresh()->name)->toBe('Updated Director Name');
});

it('ensures only one primary M/Y at a time on create', function () {
    // Create first primary M/Y
    $first = Director::factory()->create(['name' => 'First M/Y', 'is_my' => true]);

    // Create second and set is_my=true — should unset the first
    $response = $this->actingAs($this->superadmin)
        ->post('/directors', [
            'name' => 'Second M/Y',
            'is_my' => true,
        ]);

    $response->assertRedirect();
    expect($first->fresh()->is_my)->toBeFalse();

    $second = Director::where('name', 'Second M/Y')->first();
    expect($second->is_my)->toBeTrue();
});

it('ensures only one primary M/Y at a time on update', function () {
    $first = Director::factory()->create(['name' => 'Existing M/Y', 'is_my' => true]);

    // Update our test director to be the primary — should unset $first
    $response = $this->actingAs($this->superadmin)
        ->put("/directors/{$this->director->id}", [
            'name' => $this->director->name,
            'is_my' => true,
        ]);

    $response->assertRedirect();
    expect($first->fresh()->is_my)->toBeFalse();
    expect($this->director->fresh()->is_my)->toBeTrue();
});

it('soft-deletes a director on destroy', function () {
    $response = $this->actingAs($this->superadmin)
        ->delete("/directors/{$this->director->id}");

    $response->assertRedirect('/directors');
    expect($this->director->fresh()->deleted_at)->not->toBeNull();
    expect(Director::withTrashed()->where('id', $this->director->id)->exists())->toBeTrue();
});

it('redirects unauthenticated users from directors to login', function () {
    $response = $this->get('/directors');
    $response->assertRedirect('/login');
});

it('blocks regular users without permission from viewing directors', function () {
    $response = $this->actingAs($this->regularUser)->get('/directors');
    $response->assertStatus(403);
});

it('allows regular users with explicit permission to view directors', function () {
    $menu = Menu::where('route', 'directors.index')->first();
    UserPermission::create([
        'user_id' => $this->regularUser->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $response = $this->actingAs($this->regularUser)->get('/directors');
    $response->assertStatus(200);
});

it('supports search filter on directors list', function () {
    Director::factory()->create(['name' => 'Alpha Director']);
    Director::factory()->create(['name' => 'Beta Director']);

    $response = $this->actingAs($this->superadmin)
        ->get('/directors?search=Alpha');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('directors.data')
        ->where('directors.total', 1)
    );
});
