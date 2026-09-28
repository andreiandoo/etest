<?php

use App\Services\Sources\Parsers\DrpcivCsvParser;

/**
 * Proba are cele patru forme din fișierele reale: o întrebare cu un singur
 * răspuns corect, una cu două și cu imagine, una cu toate trei corecte, și un
 * rând stricat, căruia îi lipsește litera de la o variantă.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function drpcivParsed(): array
{
    return (new DrpcivCsvParser)->parse(__DIR__.'/../Fixtures/drpciv-excerpt.csv');
}

test('the letter in front of an answer sets the order, then leaves the text', function () {
    $question = drpcivParsed()['questions'][0];

    // În fișier, varianta corectă e prima coloană, dar litera ei e B: la examen
    // stătea la mijloc, iar acolo trebuie să se întoarcă.
    expect(array_column($question['options'], 'content'))->toBe([
        'Conducătorul care circulă în interiorul sensului giratoriu',
        'Conducătorul care pătrunde în intersecție',
        'Conducătorul vehiculului mai greu',
    ])
        ->and(array_column($question['options'], 'is_correct'))->toBe([false, true, false]);
});

test('a question can have two or three correct answers', function () {
    $questions = drpcivParsed()['questions'];

    expect(array_column($questions[1]['options'], 'is_correct'))->toBe([true, false, true])
        ->and(array_column($questions[2]['options'], 'is_correct'))->toBe([true, true, true]);
});

test('the explanation is the law text behind the markup, and an empty div is no explanation', function () {
    $questions = drpcivParsed()['questions'];

    expect($questions[1]['explanation'])
        ->toBe('Articolul 135 Conducatorul de vehicul este obligat sa acorde prioritate de trecere.')
        ->and($questions[0]['explanation'])->toBeNull()
        ->and($questions[2]['explanation'])->toBeNull();
});

test('the chapter comes from the last piece of the category address', function () {
    $questions = drpcivParsed()['questions'];

    expect(array_column($questions, 'chapter'))->toBe([
        'prioritate-de-trecere',
        'indicatoare-si-marcaje',
        'obligatiile-conducatorilor-de-autovehicule',
    ]);
});

test('the image address is kept as it is, for the fetching step', function () {
    $questions = drpcivParsed()['questions'];

    expect($questions[1]['image'])->toBe('https://exemplu.ro/storage/questions/77.jpeg')
        ->and($questions[0]['image'])->toBeNull();
});

test('a row whose answer lost its letter is reported, not guessed', function () {
    $parsed = drpcivParsed();

    expect($parsed['total'])->toBe(4)
        ->and($parsed['questions'])->toHaveCount(3)
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['code'])->toBe('rândul 5')
        ->and($parsed['rejected'][0]['reason'])->toContain('nu începe cu litera ei');
});

/**
 * Al doilea fel de fișier: alte nume de coloane, litera lipită de text fără
 * liniuță, fără explicații și fără capitole. Tăierea pe simplu spațiu ar fi
 * primejdioasă singură — există răspunsuri care sunt chiar „C” sau „U” — dar
 * rândul trebuie să iasă cu exact literele A, B și C, iar invariantul acela o
 * ține în frâu.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function drpcivTrParsed(): array
{
    return (new DrpcivCsvParser)->parse(__DIR__.'/../Fixtures/drpciv-tr-tv-excerpt.csv');
}

test('the other file shape is read too, columns and separator included', function () {
    $parsed = drpcivTrParsed();
    $first = $parsed['questions'][0];

    expect($first['prompt'])->toBe('Ce semnifică panoul adițional?')
        ->and(array_column($first['options'], 'content'))->toBe([
            'începutul zonei de acțiune a indicatorului „Staționarea interzisă“',
            'începutul zonei de acțiune a indicatorului „Oprirea interzisă“',
            'staționarea este interzisă până la indicator',
        ])
        ->and(array_column($first['options'], 'is_correct'))->toBe([false, true, false]);
});

test('an answer that is itself a single letter survives the cut', function () {
    $question = drpcivTrParsed()['questions'][1];

    expect(array_column($question['options'], 'content'))->toBe(['D', 'C', 'U'])
        ->and(array_column($question['options'], 'is_correct'))->toBe([true, false, true]);
});

test('a file without chapters leaves the question without one, and that is no error', function () {
    $parsed = drpcivTrParsed();

    expect($parsed['questions'][0]['chapter'])->toBeNull()
        ->and($parsed['questions'][0]['explanation'])->toBeNull()
        ->and($parsed['questions'][0]['image'])->toBe('https://exemplu.ro/poze/3979.jpg')
        ->and($parsed['questions'][2]['image'])->toBeNull();
});

test('two answers on the same letter are refused, not silently merged', function () {
    $parsed = drpcivTrParsed();

    expect($parsed['questions'])->toHaveCount(3)
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['reason'])->toContain('apare de două ori');
});
