<?php

use App\Models\Source;
use App\Services\Sources\Connectors\DrpcivCategoriaBConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function drpcivDocuments(): array
{
    return ['DRPCIV_qa_B.csv' => base_path('tests/Fixtures/drpciv-excerpt.csv')];
}

test('the image is brought to our own disk and served from there', function () {
    Http::fake(['exemplu.ro/*' => Http::response(str_repeat('x', 4096), 200, ['Content-Type' => 'image/jpeg'])]);

    $parsed = app(DrpcivCategoriaBConnector::class)->parseDocuments(drpcivDocuments());
    $withImage = collect($parsed['questions'])->firstWhere(fn (array $q): bool => $q['media'] !== null);

    expect($withImage['media']['path'])->toStartWith('questions/auto/b/')
        ->and($withImage['media']['path'])->toEndWith('.jpg')
        ->and($withImage['media']['alt'])->not->toBe('')
        ->and(Storage::disk('public')->exists($withImage['media']['path']))->toBeTrue();
});

test('a second run recognises what the first one brought', function () {
    Http::fake(['exemplu.ro/*' => Http::response(str_repeat('x', 4096), 200, ['Content-Type' => 'image/jpeg'])]);

    $connector = app(DrpcivCategoriaBConnector::class);
    $connector->parseDocuments(drpcivDocuments());
    $connector->parseDocuments(drpcivDocuments());

    Http::assertSentCount(1);
});

test('a question whose image cannot be brought is reported, not published without it', function () {
    Http::fake(['exemplu.ro/*' => Http::response('', 404)]);

    $parsed = app(DrpcivCategoriaBConnector::class)->parseDocuments(drpcivDocuments());

    expect($parsed['questions'])->toHaveCount(2)
        ->and(collect($parsed['rejected'])->pluck('reason')->implode(' '))->toContain('Imaginea nu a putut fi adusă');
});

test('a page returned instead of an image is refused', function () {
    Http::fake(['exemplu.ro/*' => Http::response('<html>nu aici</html>', 200, ['Content-Type' => 'text/html'])]);

    $parsed = app(DrpcivCategoriaBConnector::class)->parseDocuments(drpcivDocuments());

    expect(collect($parsed['rejected'])->pluck('reason')->implode(' '))->toContain('octeți');
});

test('the rows carry the chapter and the licence category it sits under', function () {
    Http::fake(['exemplu.ro/*' => Http::response(str_repeat('x', 4096), 200, ['Content-Type' => 'image/jpeg'])]);

    $connector = app(DrpcivCategoriaBConnector::class);
    $parsed = $connector->parseDocuments(drpcivDocuments());
    $rows = $connector->rows(new Source(['source_page_url' => 'https://dgpci.mai.gov.ro/']), $parsed['questions']);

    expect($rows[0]['taxonomy_slug'])->toBe('prioritate-de-trecere')
        ->and($rows[0]['taxonomy_parent_slug'])->toBe('permis-categoria-b')
        ->and($rows[0]['type'])->toBe('multiple_choice')
        ->and($rows[1]['metadata']['needs_alt'])->toBeTrue();
});
