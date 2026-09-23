<?php

use App\Services\Sources\Parsers\AncomRadioParser;

/**
 * Proba e un extras din documentul real, ales pe defectele lui.
 *
 * Extragerea din PDF citește uneori coloanele în ordinea 1, 3, 2, 4; unele
 * variante și-au pierdut paranteza; unele coduri nu au bara de după; iar unele
 * enunțuri conțin „(în /4)”, care arată exact ca începutul variantei a patra.
 * Toate sunt aici, ca o schimbare de parser să nu le poată rupe tăcut.
 */
function ancomExcerpt(): string
{
    return (string) file_get_contents(__DIR__.'/../Fixtures/ancom-radiotehnica-excerpt.txt');
}

/**
 * @return array<string, array<string, mixed>>
 */
function ancomParsed(): array
{
    $result = (new AncomRadioParser)->parse(ancomExcerpt());

    return collect($result['questions'])->keyBy('code')->all();
}

test('it finds every subject in the excerpt, including the ones without a trailing slash', function () {
    $result = (new AncomRadioParser)->parse(ancomExcerpt());

    expect($result['total'])->toBe(7)
        ->and($result['questions'])->toHaveCount(6)
        ->and(array_column($result['questions'], 'code'))->toContain('01D34');
});

test('it reads the marked answer and keeps the options in their numbered order', function () {
    $question = ancomParsed()['01A11'];

    expect($question['prompt'])->toStartWith('Rigiditatea dielectricilor')
        ->and($question['options'])->toHaveCount(4)
        ->and($question['correct'])->toBe(3)
        ->and($question['options'][2])->toBe('Un câmp electric mare');
});

test('options read out of column order are put back in their numbered order', function () {
    $question = ancomParsed()['08B16K'];

    expect($question['options'])->toBe(['T=0,1ms', 'T=1ms', 'T=10ms', 'T=100ms'])
        ->and($question['correct'])->toBe(2);
});

test('an option whose bracket is missing is still recognised', function () {
    $question = ancomParsed()['07D14'];

    expect($question['options'])->toHaveCount(4)
        ->and($question['options'][3])->toBe('Se resping reciproc')
        ->and($question['correct'])->toBe(3);
});

test('a slash inside the prompt is not mistaken for the fourth option', function () {
    $question = ancomParsed()['08C61K'];

    expect($question['prompt'])->toContain('(în /4)')
        ->and($question['options'][0])->toBe('Zin creşte; Fcreşte')
        ->and($question['correct'])->toBe(4);
});

test('options glued to the previous value are split correctly', function () {
    $question = ancomParsed()['04B11L'];

    expect($question['options'])->toBe(['150mA', '15mA', '1,5mA', '0,15mA'])
        ->and($question['correct'])->toBe(3);
});

test('the code carries the chapter and the difficulty', function () {
    $parsed = ancomParsed();

    expect($parsed['01A11']['difficulty'])->toBe(1)
        ->and($parsed['01A11']['chapter_slug'])->toBe('notiuni-teoretice')
        ->and($parsed['01D34']['difficulty'])->toBe(4)
        ->and($parsed['01D34']['chapter_slug'])->toBe('circuite')
        ->and($parsed['08C61K']['chapter_slug'])->toBe('antene-si-linii')
        ->and($parsed['04B11L']['group'])->toBe('L');
});

test('a subject the document itself got wrong is rejected instead of guessed', function () {
    $result = (new AncomRadioParser)->parse(ancomExcerpt());

    expect($result['rejected'])->toHaveCount(1)
        ->and($result['rejected'][0]['code'])->toBe('01B44')
        ->and($result['rejected'][0]['text'])->toContain('Sensibilitatea receptoarelor');
});

test('headings, page footers and the preamble never reach a question', function () {
    foreach (ancomParsed() as $question) {
        expect($question['prompt'])->not->toContain('Pagina ')
            ->and($question['prompt'])->not->toContain('LISTĂ DE SUBIECTE');

        foreach ($question['options'] as $option) {
            expect($option)->not->toContain('Pagina ');
        }
    }
});
