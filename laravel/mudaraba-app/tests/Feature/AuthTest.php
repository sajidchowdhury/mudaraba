<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'username' => 'testuser',
        'password_hash' => bcrypt('password123'),
        'role' => 'admin',
        'status' => 'Active',
    ]);
});

it('shows the login page', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

it('redirects unauthenticated users from dashboard to login', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

it('authenticates a user with valid credentials', function () {
    expect(auth()->check())->toBeFalse();

    $response = $this->post('/login', [
        'username' => 'testuser',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertRedirect('/dashboard');
    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->username)->toBe('testuser');
});

it('rejects invalid credentials', function () {
    $response = $this->post('/login', [
        'username' => 'testuser',
        'password' => 'wrongpassword',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors(['username']);
    expect(auth()->check())->toBeFalse();
});

it('rejects inactive users', function () {
    $this->user->update(['status' => 'Inactive']);

    $response = $this->post('/login', [
        'username' => 'testuser',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors(['username']);
    expect(auth()->check())->toBeFalse();
});

it('logs out the user', function () {
    $this->actingAs($this->user);

    $response = $this->post('/logout');

    $response->assertRedirect('/login');
    expect(auth()->check())->toBeFalse();
});
