<?php

use App\Models\TaxonomyNode;
use App\Models\Vertical;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

test('publishing a vertical takes its whole taxonomy with it', function () {
    $this->artisan('content:activate', ['vertical' => 'vanatoare'])->assertSuccessful();

    $vertical = Vertical::query()->where('slug', 'vanatoare')->firstOrFail();

    expect($vertical->is_active)->toBeTrue()
        ->and(TaxonomyNode::query()->where('vertical_id', $vertical->id)->where('is_active', false)->count())->toBe(0);
});

test('a section can be published without the rest of the domain', function () {
    $this->artisan('content:activate', ['vertical' => 'medicina', '--sectiune' => 'grad-principal'])
        ->assertSuccessful();

    $medicina = Vertical::query()->where('slug', 'medicina')->firstOrFail();
    $gradPrincipal = TaxonomyNode::query()
        ->where('vertical_id', $medicina->id)
        ->where('slug', 'grad-principal')
        ->firstOrFail();

    expect($medicina->is_active)->toBeTrue()
        ->and($gradPrincipal->is_active)->toBeTrue()
        // Rezidențiatul nu are conținut încă și rămâne ascuns.
        ->and(TaxonomyNode::query()->where('slug', 'rezidentiat')->value('is_active'))->toBeFalse();
});

test('publishing a section takes the ones under it too', function () {
    $this->artisan('content:activate', ['vertical' => 'medicina', '--sectiune' => 'rezidentiat'])
        ->assertSuccessful();

    $rezidentiat = TaxonomyNode::query()->where('slug', 'rezidentiat')->firstOrFail();

    expect(TaxonomyNode::query()->where('parent_id', $rezidentiat->id)->where('is_active', false)->count())->toBe(0);
});

test('a section that does not exist is refused', function () {
    $this->artisan('content:activate', ['vertical' => 'medicina', '--sectiune' => 'nu-exista'])
        ->assertFailed();
});
