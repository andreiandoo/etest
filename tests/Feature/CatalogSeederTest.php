<?php

use App\Models\TaxonomyNode;
use App\Models\Vertical;
use App\Services\Content\ReservedSlugs;
use App\Services\Content\VerticalTheme;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\Data\Catalog;

test('the catalogue seeds every documented vertical, inactive', function () {
    $this->seed(CatalogSeeder::class);

    expect(Vertical::query()->count())->toBe(count(Catalog::verticals()))
        ->and(Vertical::query()->where('is_active', true)->count())->toBe(0)
        ->and(TaxonomyNode::query()->count())->toBeGreaterThan(100);
});

test('no vertical claims a slug that belongs to a fixed route', function () {
    $this->seed(CatalogSeeder::class);

    foreach (Vertical::query()->pluck('slug') as $slug) {
        expect(ReservedSlugs::isReserved($slug))->toBeFalse();
    }
});

test('every vertical gets the accent and the icon the catalogue asked for', function () {
    $this->seed(CatalogSeeder::class);

    $declared = collect(Catalog::verticals())->keyBy('slug');

    foreach (Vertical::query()->get() as $vertical) {
        $theme = VerticalTheme::for($vertical);
        $want = $declared[$vertical->slug];

        // Un nume de paletă sau de pictogramă scris greșit nu dă eroare, ci
        // cade tăcut pe valoarea implicită. Aici se vede.
        expect($theme['icon'])->toBe($want['icon'])
            ->and(VerticalTheme::palettes())->toContain($want['palette'])
            ->and($theme['solid'])->toMatch('/^#[0-9A-F]{6}$/');
    }
});

test('the same section name can exist under two different parents', function () {
    $this->seed(CatalogSeeder::class);

    $medicina = Vertical::query()->where('slug', 'medicina')->firstOrFail();

    $rezidentiat = TaxonomyNode::query()
        ->where('vertical_id', $medicina->id)
        ->where('slug', 'rezidentiat')
        ->firstOrFail();

    $gradPrincipal = TaxonomyNode::query()
        ->where('vertical_id', $medicina->id)
        ->where('slug', 'grad-principal')
        ->firstOrFail();

    $underRezidentiat = TaxonomyNode::query()
        ->where('parent_id', $rezidentiat->id)
        ->where('slug', 'farmacie')
        ->firstOrFail();

    $underGradPrincipal = TaxonomyNode::query()
        ->where('parent_id', $gradPrincipal->id)
        ->where('slug', 'farmacie')
        ->firstOrFail();

    expect($underRezidentiat->id)->not->toBe($underGradPrincipal->id);
});

test('a nested path opens the section that actually sits there', function () {
    $this->seed(CatalogSeeder::class);

    Vertical::query()->where('slug', 'medicina')->update(['is_active' => true]);
    TaxonomyNode::query()->update(['is_active' => true]);

    $this->get('/medicina/rezidentiat/farmacie')
        ->assertOk()
        ->assertSee('Rezidențiat Farmacie');

    $this->get('/medicina/grad-principal/farmacie')
        ->assertOk()
        ->assertSee('Farmacie');

    // Aceeași secțiune cerută pe o cale greșită nu dispare, ci trimite la locul ei.
    $this->get('/medicina/farmacie')->assertRedirect();
});

test('running the catalogue twice changes nothing and does not unpublish', function () {
    $this->seed(CatalogSeeder::class);

    Vertical::query()->where('slug', 'radio')->update(['is_active' => true]);

    $verticals = Vertical::query()->count();
    $nodes = TaxonomyNode::query()->count();

    $this->seed(CatalogSeeder::class);

    expect(Vertical::query()->count())->toBe($verticals)
        ->and(TaxonomyNode::query()->count())->toBe($nodes)
        ->and(Vertical::query()->where('slug', 'radio')->firstOrFail()->is_active)->toBeTrue();
});

test('the catalogue refuses to run while demo content is still in the database', function () {
    Vertical::factory()->create([
        'slug' => 'auto',
        'name' => 'Auto demo',
        'metadata' => ['demo' => true],
    ]);

    $this->seed(CatalogSeeder::class);

    expect(Vertical::query()->count())->toBe(1)
        ->and(Vertical::query()->where('slug', 'auto')->firstOrFail()->name)->toBe('Auto demo');
});

test('the only source cleared for reuse is recorded as such', function () {
    $this->seed(CatalogSeeder::class);

    $radio = Vertical::query()->where('slug', 'radio')->firstOrFail();

    $radiotehnica = TaxonomyNode::query()
        ->where('vertical_id', $radio->id)
        ->where('slug', 'radiotehnica-si-electronica')
        ->firstOrFail();

    expect(data_get($radiotehnica->metadata, 'catalog.rights'))->toBe(Catalog::RIGHTS_EXPLICIT)
        ->and(data_get($radiotehnica->metadata, 'catalog.items_raw'))->toBe(632);
});
