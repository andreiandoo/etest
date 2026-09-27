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
