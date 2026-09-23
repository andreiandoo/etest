<?php

use App\Services\Sources\Parsers\HuntingLicenceParser;

/**
 * Proba e un extras din documentul real, ales pe cazurile care l-ar putea rupe.
 *
 * Marcajul răspunsului e doar îngroșarea, iar îngroșarea vine uneori pe un
 * punct și virgulă rămas dintr-o corectură. Trei întrebări din o mie nu au
 * punctuație la final. Câteva zeci se sprijină pe imagini, iar sub ele stă un
 * rând de etichete „A B C” care, aruncat, înghițea întrebarea următoare.
 */
function huntingExcerpt(): string
{
    return (string) file_get_contents(__DIR__.'/../Fixtures/vanatoare-docbook-excerpt.xml');
}

/**
 * @return array<int, array<string, mixed>>
 */
function huntingParsed(): array
{
    return (new HuntingLicenceParser)->parse(huntingExcerpt())['questions'];
}

test('it reads the bold option as the correct answer', function () {
    $question = huntingParsed()[0];

    expect($question['prompt'])->toBe('Durata creşterii coarnelor cerbului comun este de:')
        ->and($question['options'])->toBe(['60 - 70 zile', '90 – 100 zile', '120 – 130 zile'])
        ->and($question['correct'])->toBe(3)
        ->and($question['chapter_slug'])->toBe('mamifere-mari');
});

test('bold left on a stray semicolon is not mistaken for an answer', function () {
    $question = huntingParsed()[1];

    expect($question['prompt'])->toBe('La muflon, mărimea coarnelor reprezintă:')
        ->and($question['correct'])->toBe(3);
});

test('a question with no punctuation at the end is still recognised', function () {
    $question = huntingParsed()[2];

    expect($question['prompt'])->toBe('Cornul de cerb lopătar dezvoltat normal, poate avea cel mult')
        ->and($question['correct'])->toBe(1);
});

test('a question that needs a picture is rejected instead of published answerless', function () {
    $result = (new HuntingLicenceParser)->parse(huntingExcerpt());

    expect($result['rejected'])->toHaveCount(1)
        ->and($result['rejected'][0]['reason'])->toContain('imagini')
        ->and($result['rejected'][0]['code'])->toContain('urma tipar');
});

test('the label row under the pictures does not swallow the next question', function () {
    $prompts = array_column(huntingParsed(), 'prompt');

    expect($prompts)->toContain('Care urme tipar sunt mai mari la mistreţ?');
});

test('the chapter follows the headings, and the umbrella chapter claims nothing', function () {
    $result = (new HuntingLicenceParser)->parse(huntingExcerpt());
    $byChapter = [];

    foreach ($result['questions'] as $question) {
        $byChapter[$question['chapter_slug']] = ($byChapter[$question['chapter_slug']] ?? 0) + 1;
    }

    expect($result['total'])->toBe(6)
        ->and($result['questions'])->toHaveCount(5)
        ->and($byChapter)->toBe(['mamifere-mari' => 4, 'arme-si-munitii' => 1]);
});

test('the footnote and the species annex never become questions', function () {
    foreach (huntingParsed() as $question) {
        expect($question['prompt'])->not->toContain('NOTĂ')
            ->and($question['prompt'])->not->toContain('Bizamul');
    }
});
