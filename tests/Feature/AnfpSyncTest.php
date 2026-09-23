<?php

use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Sources\Connectors\AnfpConnector;
use App\Services\Sources\PdfTextExtractor;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

/**
 * Proba e documentul real, trecut prin extractorul de PDF al aplicației — nu
 * printr-un text pregătit dinainte. Două biblioteci de extragere dau două
 * rezultate, iar diferența dintre ele a rupt deja un parser o dată.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function anfpParsed(): array
{
    $text = app(PdfTextExtractor::class)->extract(base_path('tests/Fixtures/anfp-baterie.pdf'));

    return app(AnfpConnector::class)->parse($text);
}

test('the whole battery is read, with nothing rejected', function () {
    $parsed = anfpParsed();

    expect($parsed['total'])->toBe(23)
        ->and($parsed['questions'])->toHaveCount(23)
        ->and($parsed['rejected'])->toBe([]);
});

test('the four chapters keep the questions the document gave them', function () {
    $counts = [];

    foreach (anfpParsed()['questions'] as $question) {
        $counts[$question['chapter_slug']] = ($counts[$question['chapter_slug']] ?? 0) + 1;
    }

    ksort($counts);

    expect($counts)->toBe([
        'administratie-publica' => 13,
        'constitutia-romaniei' => 6,
        'egalitate-de-sanse' => 2,
        'nediscriminare' => 2,
    ]);
});

test('the cited option is the answer, and the citation leaves the option text', function () {
    $first = anfpParsed()['questions'][0];

    expect($first['prompt'])->toContain('care dintre următoarele principii generale')
        ->and($first['correct'])->toBe(3)
        ->and($first['options'][2])->toBe('România este stat național, suveran și independent')
        ->and($first['reference'])->toBe('art. 1 alin. (1)');
});

test('no option still carries a legal citation, which would give the answer away', function () {
    foreach (anfpParsed()['questions'] as $question) {
        foreach ($question['options'] as $option) {
            expect($option)->not->toContain('(art.')
                ->and($option)->not->toBe('');
        }

        expect($question['reference'])->not->toBe('');
    }
});

test('a nested citation keeps its inner bracket', function () {
    $questions = collect(anfpParsed()['questions'])
        ->filter(fn (array $question): bool => str_contains($question['reference'], 'lit.'))
        ->values();

    expect($questions)->not->toBeEmpty()
        ->and($questions[0]['reference'])->toEndWith(')');
});

test('the answer becomes an explanation with the article behind it', function () {
    $source = Source::query()->create([
        'key' => 'anfp-concurs-national',
        'authority' => 'ANFP',
        'title' => 'test',
        'document_url' => 'https://concurs-national.anfp.gov.ro/wp-content/uploads/Baterie-teste-adm-exemplificative.pdf',
        'rights_status' => 'official_public_unclear',
    ]);

    $rows = app(AnfpConnector::class)->rows($source, [[
        'chapter_slug' => 'constitutia-romaniei',
        'number' => 1,
        'prompt' => 'Enunț',
        'options' => ['una', 'doua', 'trei'],
        'correct' => 3,
        'reference' => 'art. 1 alin. (1)',
    ]]);

    expect($rows[0]['explanation'])->toBe('Temei legal: art. 1 alin. (1).')
        ->and($rows[0]['metadata']['legal_reference'])->toBe('art. 1 alin. (1)')
        ->and($rows[0]['source_key'])->toBe('anfp-teste:constitutia-romaniei:1')
        ->and($rows[0]['options'][2]['is_correct'])->toBeTrue();
});

test('the chapters of the battery become sections under the exam', function () {
    $node = TaxonomyNode::query()->where('slug', 'concurs-national')->firstOrFail();

    $source = Source::query()->create([
        'key' => 'anfp-concurs-national',
        'authority' => 'ANFP',
        'title' => 'test',
        'document_url' => 'https://concurs-national.anfp.gov.ro/',
        'rights_status' => 'official_public_unclear',
        'vertical_id' => $node->vertical_id,
        'taxonomy_node_id' => $node->id,
    ]);

    app(AnfpConnector::class)->prepare($source->refresh());

    $slugs = TaxonomyNode::query()
        ->where('parent_id', $node->id)
        ->pluck('slug')
        ->sort()
        ->values()
        ->all();

    // Cele cinci componente ale probei erau deja în catalog; bateria adaugă
    // Constituția, pe care documentul o tratează ca pe un capitol de sine
    // stătător.
    expect($slugs)->toContain('constitutia-romaniei')
        ->and($slugs)->toContain('administratie-publica')
        ->and($slugs)->toContain('nediscriminare')
        ->and($slugs)->toContain('egalitate-de-sanse');
});
