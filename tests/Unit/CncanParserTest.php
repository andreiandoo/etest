<?php

use App\Services\Sources\Parsers\CncanParser;

/**
 * Proba are, dinadins, mai multe răspunsuri decât întrebări: baremul CNCAN
 * chiar are răspunsuri pentru numere care lipsesc din lista de întrebări, iar
 * un gol nu trebuie să mute răspunsurile celorlalte.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function cncanParsed(): array
{
    return (new CncanParser)->parse(
        (string) file_get_contents(__DIR__.'/../Fixtures/cncan-intrebari-excerpt.txt'),
        (string) file_get_contents(__DIR__.'/../Fixtures/cncan-raspunsuri-excerpt.txt'),
    );
}

test('the three chapters are paired in order, not by name', function () {
    $parsed = cncanParsed();
    $chapters = array_values(array_unique(array_column($parsed['questions'], 'section_slug')));

    expect($parsed['questions'])->toHaveCount(9)
        ->and($chapters)->toBe(['radioprotectie', 'legislatie-de-baza', 'radioprotectie-operationala']);
});

test('a question carries its five options and the marked answer', function () {
    $question = cncanParsed()['questions'][1];

    expect($question['number'])->toBe(2)
        ->and($question['prompt'])->toBe('Energia de prag pentru formarea de perechi este:')
        ->and($question['options'])->toBe(['1,022 keV', '5,11 keV', '511 keV', '1,022 MeV', 'nu există energie de prag'])
        ->and($question['correct'])->toBe(4);
});

test('the commission explanation comes along with the answer', function () {
    $question = cncanParsed()['questions'][1];

    expect($question['explanation'])->toContain('Producerea de perechi')
        ->and($question['explanation'])->toContain('1,022 MeV')
        ->and(mb_strlen($question['explanation']))->toBeGreaterThan(80);
});

test('every question has an explanation and exactly one answer', function () {
    foreach (cncanParsed()['questions'] as $question) {
        expect($question['options'])->toHaveCount(5)
            ->and($question['correct'])->toBeGreaterThanOrEqual(1)
            ->and($question['correct'])->toBeLessThanOrEqual(5)
            ->and($question['explanation'])->not->toBe('');
    }
});

test('an answer without its question is reported, and shifts nothing', function () {
    $parsed = cncanParsed();

    expect($parsed['total'])->toBe(12)
        ->and($parsed['rejected'])->toHaveCount(3)
        ->and($parsed['rejected'][0]['reason'])->toContain('întrebarea lipsește');

    // Numerele rămase sunt tot 1, 2, 3 în fiecare capitol — golul de la 4 nu a
    // împins nimic.
    expect(array_column($parsed['questions'], 'number'))->toBe([1, 2, 3, 1, 2, 3, 1, 2, 3]);
});

test('documents whose chapters do not line up are refused as a whole', function () {
    $parsed = (new CncanParser)->parse('fără capitole', (string) file_get_contents(__DIR__.'/../Fixtures/cncan-raspunsuri-excerpt.txt'));

    expect($parsed['questions'])->toBe([])
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['reason'])->toContain('capitole');
});
