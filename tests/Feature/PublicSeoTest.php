<?php

use App\Enums\PublicationStatus;
use App\Enums\TaxonomyNodeType;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;

function seoFixture(): array
{
    $vertical = Vertical::factory()->create([
        'name' => 'Auto',
        'slug' => 'auto',
        'description' => 'Teste auto.',
        'is_active' => true,
    ]);

    $exam = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'Chestionare DRPCIV',
        'slug' => 'chestionare-drpciv',
        'description' => 'Pregătire DRPCIV.',
        'is_active' => true,
    ]);

    $category = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'parent_id' => $exam->id,
        'type' => TaxonomyNodeType::Subject,
        'name' => 'Categoria B',
        'slug' => 'categoria-b',
        'description' => 'Teste categoria B.',
        'is_active' => true,
    ]);

    $test = TestDefinition::factory()->for($vertical)->create([
        'taxonomy_node_id' => $category->id,
        'title' => 'Simulare Categoria B 001',
        'slug' => 'simulare-001',
        'description' => 'Simulare gratuită.',
        'status' => PublicationStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    return compact('vertical', 'exam', 'category', 'test');
}

test('nested taxonomy pages use hierarchical canonical urls and breadcrumb structured data', function () {
    $fixture = seoFixture();

    $this->get('/auto/chestionare-drpciv/categoria-b')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/auto/chestionare-drpciv/categoria-b').'">', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('Categoria B');
});

test('published test pages use taxonomy hierarchy in their canonical url', function () {
    $fixture = seoFixture();

    $this->get('/auto/chestionare-drpciv/categoria-b/simulare-001')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/auto/chestionare-drpciv/categoria-b/simulare-001').'">', false)
        ->assertSee('"@type":"Quiz"', false)
        ->assertSee('Simulare Categoria B 001');
});

test('shallow test urls redirect permanently to the canonical hierarchical url', function () {
    seoFixture();

    $this->get('/auto/simulare-001')
        ->assertRedirect(url('/auto/chestionare-drpciv/categoria-b/simulare-001'))
        ->assertStatus(301);
});

test('sitemaps expose only public active urls', function () {
    $fixture = seoFixture();

    $inactive = Vertical::factory()->create([
        'name' => 'Ascuns',
        'slug' => 'ascuns',
        'is_active' => false,
    ]);

    TestDefinition::factory()->for($inactive)->create([
        'slug' => 'draft',
        'status' => PublicationStatus::Draft,
        'published_at' => null,
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('sitemaps.verticals'), false)
        ->assertSee(route('sitemaps.taxonomy'), false)
        ->assertSee(route('sitemaps.tests'), false);

    $this->get('/sitemaps/verticals.xml')
        ->assertOk()
        ->assertSee(url('/auto'), false)
        ->assertDontSee(url('/ascuns'), false);

    $this->get('/sitemaps/tests.xml')
        ->assertOk()
        ->assertSee(app(PublicUrlGenerator::class)->test($fixture['test']), false)
        ->assertDontSee('/ascuns/draft', false);
});

test('private account and runner routes emit noindex headers', function () {
    $fixture = seoFixture();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->actingAs($user)
        ->get('/auto/simulare-001/start')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('pagination uses a self canonical instead of canonicalizing page two to page one', function () {
    $vertical = Vertical::factory()->create([
        'name' => 'Medicină',
        'slug' => 'medicina',
        'is_active' => true,
    ]);

    foreach (range(1, 25) as $index) {
        TestDefinition::factory()->for($vertical)->create([
            'taxonomy_node_id' => null,
            'slug' => 'test-'.$index,
            'status' => PublicationStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }

    $this->get('/medicina?page=2')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/medicina').'?page=2">', false);
});
