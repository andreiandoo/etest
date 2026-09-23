<?php

use App\Services\Sources\AnswerGridReader;

/**
 * Probele sunt două grile reale, alese pentru că sunt făcute cu programe
 * diferite: una așază textul cu `Td` și reutilizează trei imagini pentru toate
 * cele o sută de rânduri, cealaltă folosește `Tm` și o imagine separată pentru
 * fiecare rând, cu axa verticală întoarsă.
 *
 * Valorile așteptate au fost citite din aceleași fișiere pe o cale
 * independentă, iar pentru una dintre ele și cu ochiul liber.
 */
function gridPath(string $name): string
{
    return __DIR__.'/../Fixtures/'.$name;
}

test('it reads a grid whose rows share three images', function () {
    $result = (new AnswerGridReader)->read(gridPath('oamgmamr-moasa-grila.pdf'));
    $answers = $result['answers'];

    expect($answers)->toHaveCount(100)
        ->and(array_keys($answers))->toBe(range(1, 100))
        ->and(array_slice($answers, 0, 12, true))->toBe([
            1 => 'a', 2 => 'b', 3 => 'b', 4 => 'a', 5 => 'a', 6 => 'a',
            7 => 'b', 8 => 'b', 9 => 'c', 10 => 'a', 11 => 'b', 12 => 'c',
        ]);
});

test('it reads a grid with one image per row and a flipped axis', function () {
    $result = (new AnswerGridReader)->read(gridPath('oamgmamr-radiologie-grila.pdf'));
    $answers = $result['answers'];

    expect($answers)->toHaveCount(100)
        ->and(array_keys($answers))->toBe(range(1, 100))
        ->and(array_slice($answers, 0, 12, true))->toBe([
            1 => 'a', 2 => 'b', 3 => 'c', 4 => 'b', 5 => 'a', 6 => 'c',
            7 => 'a', 8 => 'b', 9 => 'b', 10 => 'c', 11 => 'b', 12 => 'c',
        ]);
});

test('every answer is one of the three letters', function () {
    foreach (['oamgmamr-moasa-grila.pdf', 'oamgmamr-radiologie-grila.pdf'] as $file) {
        $answers = (new AnswerGridReader)->read(gridPath($file))['answers'];

        foreach ($answers as $number => $letter) {
            expect(['a', 'b', 'c'])->toContain($letter);
        }
    }
});

test('a file that is not a readable grid is reported, not guessed', function () {
    $result = (new AnswerGridReader)->read(gridPath('oamgmamr-moasa-test.pdf'));

    expect($result['answers'])->toBe([])
        ->and($result['problems'])->not->toBeEmpty();
});
