<?php

use App\Models\Source;
use App\Services\Sources\Connectors\IsfConducatorConnector;
use App\Services\Sources\Connectors\IsfDistributieConnector;

/**
 * Numele fișierelor vin de la Institut, cu spații, diacritice și data
 * actualizării în ele, deci conectorul își recunoaște fișierul după o bucată
 * stabilă din nume, nu după numele întreg.
 */
function isfDocuments(): array
{
    return [
        'Întrebari MDA Definitivat 2022 Platforma_actualizare 16.01.2026_IA_PP_0.csv' => base_path('tests/Fixtures/isf-definitivat-excerpt.csv'),
        'Întrebări Conducător_16.01.2026_0.csv' => base_path('tests/Fixtures/isf-definitivat-excerpt.csv'),
        'Întrebari MDA Definitivat 2022 Platforma_actualizare 16.01.2026_IA_PP_0.pdf' => base_path('tests/Fixtures/isf-definitivat-excerpt.csv'),
    ];
}

test('each exam picks its own file out of the folder', function () {
    $distributie = app(IsfDistributieConnector::class)->parseDocuments(isfDocuments());
    $conducator = app(IsfConducatorConnector::class)->parseDocuments(isfDocuments());

    expect($distributie['questions'])->toHaveCount(3)
        ->and($conducator['questions'])->toHaveCount(3);
});

test('a folder without the right file says so instead of importing nothing quietly', function () {
    $parsed = app(IsfConducatorConnector::class)->parseDocuments([
        'altceva.csv' => base_path('tests/Fixtures/isf-definitivat-excerpt.csv'),
    ]);

    expect($parsed['questions'])->toBe([])
        ->and($parsed['rejected'][0]['reason'])->toContain('conduc');
});

test('the two exams keep their own copy of a shared question', function () {
    $documents = isfDocuments();
    $source = new Source(['source_page_url' => 'https://platforma.isfin.ro/ro/examinari-online']);

    $first = app(IsfDistributieConnector::class);
    $second = app(IsfConducatorConnector::class);

    $a = $first->rows($source, $first->parseDocuments($documents)['questions']);
    $b = $second->rows($source, $second->parseDocuments($documents)['questions']);

    expect($a[0]['prompt'])->toBe($b[0]['prompt'])
        ->and($a[0]['source_key'])->not->toBe($b[0]['source_key'])
        ->and($a[0]['taxonomy_slug'])->toBe('distributie-asigurari')
        ->and($b[0]['taxonomy_slug'])->toBe('conducator-asigurari')
        ->and($a[0]['type'])->toBe('single_choice');
});

test('the marked letter becomes the correct option in the row', function () {
    $connector = app(IsfDistributieConnector::class);
    $rows = $connector->rows(
        new Source(['source_page_url' => 'https://platforma.isfin.ro/']),
        $connector->parseDocuments(isfDocuments())['questions'],
    );

    expect(array_column($rows[0]['options'], 'is_correct'))->toBe([true, false, false])
        ->and(array_column($rows[1]['options'], 'is_correct'))->toBe([false, true, false]);
});
