<?php

use App\Services\Sources\Parsers\InmGrilaParser;

/**
 * Proba are cinci întrebări din două materii, una fără răspuns tipărit și un
 * rând rătăcit care începe cu o cifră — exact ce se găsește în caietele
 * institutului.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function inmParsed(): array
{
    return (new InmGrilaParser)->parse(
        (string) file_get_contents(__DIR__.'/../Fixtures/inm-grila-excerpt.txt')
    );
}

test('the discipline comes from the page header, not from a list inside the text', function () {
    $questions = inmParsed()['questions'];

    expect(array_column($questions, 'number'))->toBe([1, 2, 4, 5])
        ->and(array_column($questions, 'discipline_slug'))->toBe([
            'drept-civil', 'drept-civil', 'drept-procesual-penal', 'drept-procesual-penal',
        ])
        ->and($questions[2]['discipline_name'])->toBe('Drept procesual penal');
});

test('the printed answer becomes the correct option', function () {
    $questions = collect(inmParsed()['questions'])->keyBy('number');

    expect($questions[1]['correct'])->toBe(2)
        ->and($questions[1]['options'][1])->toBe('părinții pentru bunurile persoanei pe care o reprezintă')
        ->and($questions[2]['correct'])->toBe(3)
        ->and($questions[4]['correct'])->toBe(2)
        ->and($questions[5]['correct'])->toBe(3);
});

test('an option broken over two lines is put back together, without the answer line', function () {
    $questions = collect(inmParsed()['questions'])->keyBy('number');

    expect($questions[4]['options'][1])->toBe(
        'are ca obiect verificarea competenței instanței și a legalității probelor administrate în cursul urmăririi penale'
    )
        ->and($questions[4]['options'][2])->toBe('se încheie printr-o hotărâre judecătorească')
        ->and($questions[4]['options'][2])->not->toContain('Răspuns');
});

test('a question without a printed answer is reported, not published', function () {
    $parsed = inmParsed();

    expect($parsed['total'])->toBe(5)
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['code'])->toBe('întrebarea 3')
        ->and($parsed['rejected'][0]['reason'])->toContain('nu are răspunsul tipărit');
});

test('a stray line starting with a digit does not become a question', function () {
    // „2 alin. 1 din Codul de procedură penală…” vine după întrebarea 4, unde
    // se așteaptă 5. Fără regula asta, ar intra ca o întrebare fără variante.
    $numbers = array_column(inmParsed()['questions'], 'number');

    expect($numbers)->not->toContain(3)
        ->and(count($numbers))->toBe(4);
});

test('a header with a discipline outside the four is refused', function () {
    $text = str_replace('Drept civil - -', 'Dreptul muncii - -', (string) file_get_contents(
        __DIR__.'/../Fixtures/inm-grila-excerpt.txt'
    ));

    $parsed = (new InmGrilaParser)->parse($text);

    expect(array_column($parsed['questions'], 'number'))->toBe([4, 5])
        ->and($parsed['rejected'])->toHaveCount(3)
        ->and($parsed['rejected'][0]['reason'])->toContain('Dreptul muncii');
});
