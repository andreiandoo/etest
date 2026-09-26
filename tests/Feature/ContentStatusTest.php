<?php

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

test('a freshly seeded catalogue reports everything as empty', function () {
    $this->artisan('content:status')
        ->expectsOutputToContain('0 domenii cu conținut')
        ->expectsOutputToContain('domenii încă fără conținut')
        ->assertSuccessful();
});

test('a domain with published content is counted, a draft one is not', function () {
    $radio = Vertical::query()->where('slug', 'radio')->firstOrFail();

    Question::create([
        'vertical_id' => $radio->id,
        'type' => 'single_choice',
        'status' => PublicationStatus::Published,
        'prompt' => 'Publicată',
        'difficulty' => 2,
    ]);

    Question::create([
        'vertical_id' => $radio->id,
        'type' => 'single_choice',
        'status' => PublicationStatus::Draft,
        'prompt' => 'Ciornă',
        'difficulty' => 2,
    ]);

    TestDefinition::factory()->for($radio)->create([
        'status' => PublicationStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->artisan('content:status')
        ->expectsOutputToContain('Radiocomunicații')
        ->expectsOutputToContain('1 domenii cu conținut')
        ->assertSuccessful();
});

test('the empty domains can be listed one per line', function () {
    $this->artisan('content:status', ['--tot' => true])
        ->expectsOutputToContain('· Auto')
        ->expectsOutputToContain('· Medicină')
        ->assertSuccessful();
});
