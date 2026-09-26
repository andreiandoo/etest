<?php

use App\Services\Sources\Parsers\CcfGrilaParser;

/**
 * Proba adună la un loc toate formele pe care Camera le-a folosit: întrebări
 * numerotate și nenumerotate, marcajul răspunsului în română și în engleză, un
 * antet cu majuscule și un titlu de document în mijlocul paginii.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function ccfParsed(): array
{
    return (new CcfGrilaParser)->parse(
        (string) file_get_contents(__DIR__.'/../Fixtures/ccf-chestionar-excerpt.txt')
    );
}

test('a question is cut at the answer marker, numbered or not', function () {
    $parsed = ccfParsed();

    expect($parsed['total'])->toBe(4)
        ->and($parsed['questions'])->toHaveCount(3)
        ->and($parsed['questions'][2]['prompt'])->toBe(
            'Cine are obligația de a elibera certificatul de atestare a impozitului plătit de nerezidenți în România?'
        );
});

test('the marker is read in Romanian and in English alike', function () {
    $questions = ccfParsed()['questions'];

    expect($questions[0]['correct'])->toBe(1)
        ->and($questions[1]['correct'])->toBe(3)
        ->and($questions[2]['correct'])->toBe(1);
});

test('the number in front of the question does not stay in the text', function () {
    $questions = ccfParsed()['questions'];

    expect($questions[1]['prompt'])->toBe('Codul fiscal definește nerezidentul ca fiind')
        ->and($questions[0]['prompt'])->toStartWith('Pentru a fi scutit de impozit în România');
});

test('an answer running over two lines is put back together', function () {
    $questions = ccfParsed()['questions'];

    expect($questions[0]['options'][0])->toBe(
        'să dețină minimum 10% din capitalul social al întreprinderii persoană juridică română plătitoare, pe o perioadă neîntreruptă de cel puțin un an'
    )
        ->and($questions[0]['options'])->toHaveCount(4);
});

test('the heading and the document title never reach a question', function () {
    foreach (ccfParsed()['questions'] as $question) {
        expect($question['prompt'])->not->toContain('CAMERA')
            ->and($question['prompt'])->not->toContain('SESIUNEA')
            ->and($question['prompt'])->not->toContain('grila de corectare');
    }
});

test('a question with a lost option is reported, not published with three', function () {
    $parsed = ccfParsed();

    expect($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['reason'])->toContain('3 variante')
        ->and($parsed['rejected'][0]['code'])->toStartWith('O întrebare din care s-a pierdut');
});
