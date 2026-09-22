<?php

use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;

test('public vertical and test pages do not use a redundant tests prefix', function () {
    $vertical = Vertical::factory()->create(['slug' => 'auto']);
    $test = TestDefinition::factory()->for($vertical)->create(['slug' => 'categoria-b']);

    $this->get('/auto')->assertOk();
    $this->get('/auto/categoria-b')->assertOk();
    $this->get('/teste')->assertNotFound();
});

test('a guest must authenticate before starting a test', function () {
    $vertical = Vertical::factory()->create(['slug' => 'medicina']);
    $test = TestDefinition::factory()->for($vertical)->create(['slug' => 'rezidentiat']);

    $this->get('/medicina/rezidentiat/start')
        ->assertRedirect('/login');
});

test('an authenticated user can start a published test and gets an attempt', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create(['slug' => 'drept']);
    $test = TestDefinition::factory()->for($vertical)->create(['slug' => 'barou']);

    $this->actingAs($user)
        ->get('/drept/barou/start')
        ->assertOk()
        ->assertSee($test->title);

    expect(TestAttempt::query()
        ->where('user_id', $user->id)
        ->where('test_id', $test->id)
        ->exists())->toBeTrue();
});
