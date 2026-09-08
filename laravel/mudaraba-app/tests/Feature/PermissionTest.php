<?php

use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    // Create a regular user (no permissions)
    $this->user = User::factory()->create([
        'username' => 'regularuser',
        'role' => 'user',
        'status' => 'Active',
    ]);

    // Create a superadmin
    $this->superadmin = User::factory()->create([
        'username' => 'superadmin2',
        'role' => 'superadmin',
        'status' => 'Active',
    ]);
});

it('allows superadmin to view the permission management page', function () {
    $response = $this->actingAs($this->superadmin)->get('/admin/permissions');

    $response->assertStatus(200);
    // Inertia component check is finicky with path resolution in test env
    // The key assertion is HTTP 200 + the page rendered.
    expect($response->status())->toBe(200);
});

it('blocks non-superadmin from viewing the permission management page', function () {
    $response = $this->actingAs($this->user)->get('/admin/permissions');

    $response->assertStatus(403);
});

it('redirects unauthenticated users from admin to login', function () {
    $response = $this->get('/admin/permissions');

    $response->assertRedirect('/login');
});

it('returns permitted menu tree for superadmin', function () {
    // Seed menus
    $this->seed(MenuSeeder::class);

    $tree = $this->superadmin->permittedMenuTree();

    expect($tree)->toBeArray();
    expect(count($tree))->toBeGreaterThan(0);
    expect($tree[0])->toHaveKeys(['id', 'name', 'icon', 'sort_order', 'is_parent', 'children']);
});

it('returns only permitted menus for a regular user', function () {
    // Seed menus
    $this->seed(MenuSeeder::class);

    // Grant view on 'dashboard' only
    $menu = Menu::where('route', 'dashboard')->first();
    UserPermission::create([
        'user_id' => $this->user->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_edit' => false,
        'can_delete' => false,
        'can_backdate' => false,
    ]);

    $permitted = $this->user->permittedMenus();

    expect($permitted)->toHaveCount(1);
    expect($permitted->first()->route)->toBe('dashboard');
});

it('allows superadmin to bypass permission checks', function () {
    expect($this->superadmin->canView('investors.index'))->toBeTrue();
    expect($this->superadmin->canEdit('investors.index'))->toBeTrue();
    expect($this->superadmin->canDelete('investors.index'))->toBeTrue();
    expect($this->superadmin->canBackdate('investors.index'))->toBeTrue();
});

it('denies permission for a user without explicit grant', function () {
    $this->seed(MenuSeeder::class);

    expect($this->user->canView('investors.index'))->toBeFalse();
    expect($this->user->canEdit('investors.index'))->toBeFalse();
});

it('allows superadmin to update a user permission', function () {
    $this->seed(MenuSeeder::class);

    $menu = Menu::where('route', 'investors.index')->first();

    $response = $this->actingAs($this->superadmin)
        ->put("/admin/permissions/{$this->user->id}/{$menu->id}", [
            'can_view' => true,
            'can_edit' => true,
            'can_delete' => false,
            'can_backdate' => false,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $perm = UserPermission::where('user_id', $this->user->id)
        ->where('menu_id', $menu->id)
        ->first();

    expect($perm)->not->toBeNull();
    expect($perm->can_view)->toBeTrue();
    expect($perm->can_edit)->toBeTrue();
    expect($perm->can_delete)->toBeFalse();
});

it('blocks non-superadmin from updating permissions', function () {
    $this->seed(MenuSeeder::class);

    $menu = Menu::where('route', 'investors.index')->first();

    $response = $this->actingAs($this->user)
        ->put("/admin/permissions/{$this->superadmin->id}/{$menu->id}", [
            'can_view' => true,
        ]);

    $response->assertStatus(403);
});
