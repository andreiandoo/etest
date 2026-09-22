<?php

use App\Models\User;

test('admin area requires authentication', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('regular users cannot access admin area', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('administrators can access content platform', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Administrare e-test.ro');
});
