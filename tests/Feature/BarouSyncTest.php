<?php

use App\Enums\QuestionType;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Sources\Connectors\BarouConnector;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

/**
 * Proba sunt chiar fișierele sesiunii, din depozit. Nu se poate altfel: ce
 * verificăm aici e tocmai că cele două publicări ale răspunsului — cea scrisă
 * sub întrebare și cea desenată în grila de corectură — spun același lucru.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function barouParsed(): array
{
    $connector = app(BarouConnector::class);
    $directory = base_path($connector->directory());
    $paths = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $paths[str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1))] = $file->getPathname();
        }
    }

    return $connector->parseDocuments($paths);
}

test('all eight grids are read, with nothing rejected', function () {
    $parsed = barouParsed();

    expect($parsed['total'])->toBe(800)
        ->and($parsed['questions'])->toHaveCount(800)
        ->and($parsed['rejected'])->toBe([]);
})->skip(fn (): bool => ! is_dir(base_path('docs/barou-2026')), 'Fișierele sesiunii nu sunt în depozit.');

test('each grid splits into five subjects of twenty questions', function () {
    $counts = [];

    foreach (barouParsed()['questions'] as $question) {
        $key = $question['category'].'/'.$question['grila'].'/'.$question['subject_slug'];
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    expect($counts)->toHaveCount(40)
        ->and(array_unique(array_values($counts)))->toBe([20]);
})->skip(fn (): bool => ! is_dir(base_path('docs/barou-2026')), 'Fișierele sesiunii nu sunt în depozit.');

test('a question has one or two correct options out of three', function () {
    $spread = [];

    foreach (barouParsed()['questions'] as $question) {
        expect($question['options'])->toHaveCount(3);
        $spread[count($question['correct'])] = true;
    }

    expect(array_keys($spread))->toBe([1, 2]);
})->skip(fn (): bool => ! is_dir(base_path('docs/barou-2026')), 'Fișierele sesiunii nu sunt în depozit.');

test('the answer read under the question matches the one drawn in the grid', function () {
    // Parserul respinge orice întrebare unde cele două nu coincid, deci un
    // rezultat fără respinse e chiar confirmarea.
    expect(barouParsed()['rejected'])->toBe([]);
})->skip(fn (): bool => ! is_dir(base_path('docs/barou-2026')), 'Fișierele sesiunii nu sunt în depozit.');

test('a question keeps the grid and the number it had in the session', function () {
    $source = Source::query()->create([
        'key' => 'barou-primire-profesie',
        'authority' => 'INPPA',
        'title' => 'test',
        'source_page_url' => 'https://inppa.ro/examene/',
        'document_url' => 'https://inppa.ro/examene/',
        'rights_status' => 'official_public_unclear',
    ]);

    $rows = app(BarouConnector::class)->rows($source, [[
        'number' => 7,
        'prompt' => 'Enunț',
        'options' => ['una', 'doua', 'trei'],
        'correct' => [1, 3],
        'subject_slug' => 'drept-civil',
        'category' => 'stagiari',
        'grila' => 2,
    ]]);

    expect($rows[0]['source_key'])->toBe('barou-2026-04:stagiari:g2:7')
        ->and($rows[0]['type'])->toBe(QuestionType::MultipleChoice->value)
        ->and($rows[0]['taxonomy_slug'])->toBe('drept-civil')
        ->and($rows[0]['options'][0]['is_correct'])->toBeTrue()
        ->and($rows[0]['options'][1]['is_correct'])->toBeFalse()
        ->and($rows[0]['options'][2]['is_correct'])->toBeTrue()
        ->and($rows[0]['source_label'])->toContain('grila 2')
        ->and($rows[0]['metadata']['grila'])->toBe(2);
});

test('the two categories each get the five subjects of the exam', function () {
    $barou = TaxonomyNode::query()->where('slug', 'barou')->firstOrFail();

    $source = Source::query()->create([
        'key' => 'barou-primire-profesie',
        'authority' => 'INPPA',
        'title' => 'test',
        'document_url' => 'https://inppa.ro/examene/',
        'rights_status' => 'official_public_unclear',
        'vertical_id' => $barou->vertical_id,
        'taxonomy_node_id' => $barou->id,
    ]);

    app(BarouConnector::class)->prepare($source->refresh());

    foreach (['stagiari', 'definitivi'] as $slug) {
        $node = TaxonomyNode::query()->where('parent_id', $barou->id)->where('slug', $slug)->firstOrFail();

        expect(TaxonomyNode::query()->where('parent_id', $node->id)->count())->toBe(5);
    }
});
