<?php

use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Sources\Connectors\OamgmamrConnector;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

/**
 * Proba merge pe o singură specialitate, cu fișierele ei reale. Celelalte opt
 * lipsesc dinadins: o sursă căreia îi lipsește un fișier trebuie să raporteze,
 * nu să importe pe jumătate.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function oamgmamrParse(): array
{
    return app(OamgmamrConnector::class)->parseDocuments([
        'moasa-test.pdf' => base_path('tests/Fixtures/oamgmamr-moasa-test.pdf'),
        'moasa-grila.pdf' => base_path('tests/Fixtures/oamgmamr-moasa-grila.pdf'),
    ]);
}

test('the test and its grid are joined into a hundred answered questions', function () {
    $parsed = oamgmamrParse();
    $moasa = array_values(array_filter(
        $parsed['questions'],
        fn (array $question): bool => $question['specialty'] === 'moasa',
    ));

    expect($moasa)->toHaveCount(100)
        ->and(array_column($moasa, 'number'))->toBe(range(1, 100));

    foreach ($moasa as $question) {
        expect($question['correct'])->toBeGreaterThanOrEqual(1)
            ->and($question['correct'])->toBeLessThanOrEqual(3)
            ->and($question['options'])->toHaveCount(3);
    }
});

test('the answer comes from the grid, not from the order of the options', function () {
    $parsed = oamgmamrParse();
    $byNumber = collect($parsed['questions'])->keyBy('number');

    // Primele douăsprezece răspunsuri, citite din grila oficială.
    $expected = [1 => 1, 2 => 2, 3 => 2, 4 => 1, 5 => 1, 6 => 1, 7 => 2, 8 => 2, 9 => 3, 10 => 1, 11 => 2, 12 => 3];

    foreach ($expected as $number => $correct) {
        expect($byNumber[$number]['correct'])->toBe($correct, 'Întrebarea '.$number);
    }
});

test('a specialty whose files are missing is reported, not half imported', function () {
    $parsed = oamgmamrParse();

    expect($parsed['rejected'])->toHaveCount(8)
        ->and(collect($parsed['rejected'])->pluck('reason')->unique()->all())
        ->toBe(['Lipsește testul sau grila.']);
});

test('the nine specialties become sections under the exam', function () {
    $source = Source::query()->create([
        'key' => 'oamgmamr-grad-principal',
        'authority' => 'OAMGMAMR',
        'title' => 'test',
        'document_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
        'rights_status' => 'official_public_unclear',
        'vertical_id' => TaxonomyNode::query()->where('slug', 'grad-principal')->value('vertical_id'),
        'taxonomy_node_id' => TaxonomyNode::query()->where('slug', 'grad-principal')->value('id'),
    ]);

    app(OamgmamrConnector::class)->prepare($source->refresh());

    expect(TaxonomyNode::query()->where('parent_id', $source->taxonomy_node_id)->count())->toBe(9);
});

test('the question keeps the exam number it had in the session', function () {
    $source = Source::query()->create([
        'key' => 'oamgmamr-grad-principal',
        'authority' => 'OAMGMAMR',
        'title' => 'test',
        'source_page_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
        'document_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
        'rights_status' => 'official_public_unclear',
    ]);

    $rows = app(OamgmamrConnector::class)->rows($source, [[
        'specialty' => 'moasa',
        'number' => 7,
        'prompt' => 'Enunț',
        'options' => ['una', 'doua', 'trei'],
        'correct' => 2,
    ]]);

    expect($rows[0]['source_key'])->toBe('oamgmamr-2026:moasa:7')
        ->and($rows[0]['taxonomy_slug'])->toBe('moasa')
        ->and($rows[0]['options'][1]['is_correct'])->toBeTrue()
        ->and($rows[0]['options'][0]['is_correct'])->toBeFalse()
        ->and($rows[0]['metadata']['exam_number'])->toBe(7);
});

test('nothing is imported into the catalogue by parsing alone', function () {
    oamgmamrParse();

    expect(Question::query()->count())->toBe(0);
});
