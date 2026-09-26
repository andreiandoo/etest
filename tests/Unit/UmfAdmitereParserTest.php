<?php

use App\Services\Sources\Parsers\UmfAdmitereParser;

/**
 * Proba are trei variante ale acelorași trei întrebări, amestecate, exact cum
 * le dă UMFT. Răspunsul corect e marcat cu altă literă în fiecare variantă,
 * deci un parser care se ia după literă, nu după textul răspunsului, cade aici.
 */
function umfSubjects(): array
{
    return [(string) file_get_contents(__DIR__.'/../Fixtures/umf-caiet-excerpt.txt')];
}

function umfAnswers(): string
{
    return (string) file_get_contents(__DIR__.'/../Fixtures/umf-barem-excerpt.txt');
}

function umfParsed(): array
{
    return (new UmfAdmitereParser)->parse(umfSubjects(), umfAnswers());
}

test('the same question read from three variants comes out once', function () {
    $parsed = umfParsed();

    expect($parsed['questions'])->toHaveCount(3)
        ->and($parsed['total'])->toBe(3)
        ->and($parsed['rejected'])->toBe([]);

    foreach ($parsed['questions'] as $question) {
        expect($question['variants'])->toBe(3)
            ->and($question['options'])->toHaveCount(5);
    }
});

test('the correct answers are the marked texts, whatever letter they sit on', function () {
    $questions = collect(umfParsed()['questions'])->keyBy('prompt');

    $mictiune = $questions['Micțiunea:'];
    $correct = array_map(static fn (int $index): string => $mictiune['options'][$index - 1], $mictiune['correct']);

    expect($correct)->toHaveCount(2)
        ->and($correct[0])->toBe('Este procesul de golire a vezicii urinare, atunci când este plină')
        ->and($correct[1])->toBe('Se desfășoară în mod reflex');
});

test('an answer that runs over two lines is put back together', function () {
    $questions = collect(umfParsed()['questions'])->keyBy('prompt');
    $rinichi = $questions['Rinichii sunt așezați:'];

    expect($rinichi['options'][3])->toBe(
        'În regiunea lombară a cavității abdominale, retroperitoneal, de o parte și de alta a coloanei vertebrale'
    );

    // Tot răspunsul lung e printre cele corecte — semn că textul din varianta A
    // s-a potrivit cu cel din B și C, unde stă pe altă literă.
    expect($rinichi['correct'])->toBe([2, 3, 4]);
});

test('a question the variants disagree on is refused, not guessed', function () {
    // O singură literă schimbată în coloana variantei C: acolo „Micțiunea” ar
    // avea drept corect „Este procesul de umplere”, pe care A și B nu-l dau.
    $broken = (string) preg_replace('/(?<=\s)bc$/m', 'ab', umfAnswers());

    $parsed = (new UmfAdmitereParser)->parse(umfSubjects(), $broken);

    expect($parsed['questions'])->toHaveCount(2)
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['code'])->toBe('Micțiunea:')
        ->and($parsed['rejected'][0]['reason'])->toContain('nu dau același răspuns');
});

test('a single variant is refused as a whole: there is nothing to check it against', function () {
    $single = [mb_substr(umfSubjects()[0], 0, (int) mb_strpos(umfSubjects()[0], "\f"))];

    $parsed = (new UmfAdmitereParser)->parse($single, umfAnswers());

    expect($parsed['questions'])->toBe([])
        ->and($parsed['rejected'])->toHaveCount(1)
        ->and($parsed['rejected'][0]['reason'])->toContain('cel puțin două');
});

test('the signature under the table is not mistaken for an answer', function () {
    // „Comisiei Centrale de Admitere” ascunde un „de”. Dacă ar intra în tabel,
    // una dintre coloane ar avea patru grupe în loc de trei și toată sesiunea
    // ar fi respinsă.
    expect(umfAnswers())->toContain('Comisiei Centrale de Admitere')
        ->and(umfParsed()['questions'])->toHaveCount(3);
});
