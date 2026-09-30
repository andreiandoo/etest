<?php

use App\Services\Sources\Parsers\IsfCsvParser;

/**
 * Proba are cele cinci feluri de rânduri din transcrierea reală: trei bune, cu
 * litera scrisă și mare și mică, unul fără răspuns marcat — în PDF aveau
 * asterisc două variante sau niciuna — și unul căruia îi lipsește o variantă.
 *
 * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
 */
function isfParsed(): array
{
    return (new IsfCsvParser)->parse(__DIR__.'/../Fixtures/isf-definitivat-excerpt.csv');
}

test('the letter in the last column becomes the correct option', function () {
    $questions = isfParsed()['questions'];

    expect($questions)->toHaveCount(3)
        ->and($questions[0]['correct'])->toBe(1)
        ->and($questions[1]['correct'])->toBe(2)
        ->and($questions[2]['correct'])->toBe(3);
});

test('the letter is read whatever its case', function () {
    // A doua întrebare are litera scrisă „B” în fișier.
    expect(isfParsed()['questions'][1]['correct'])->toBe(2);
});

test('the three options keep their order and their text', function () {
    $question = isfParsed()['questions'][0];

    expect($question['options'])->toBe([
        'un eveniment viitor, posibil, cu caracter întâmplător',
        'o situație concretă de pericol grav și iminent de accidentare',
        'o stare de nesiguranță datorată unui eveniment excepțional',
    ])
        ->and($question['number'])->toBe(1)
        ->and($question['prompt'])->toBe('Care dintre următoarele afirmații se referă la un risc asigurabil?');
});

test('a question with no marked answer is reported, not guessed', function () {
    $parsed = isfParsed();

    expect($parsed['total'])->toBe(5)
        ->and($parsed['rejected'])->toHaveCount(2)
        ->and($parsed['rejected'][0]['code'])->toBe('întrebarea 4')
        ->and($parsed['rejected'][0]['reason'])->toContain('asterisc două variante sau niciuna');
});

test('a question missing one of its three options is refused', function () {
    $rejected = isfParsed()['rejected'][1];

    expect($rejected['code'])->toBe('întrebarea 5')
        ->and($rejected['reason'])->toContain('goală');
});

test('a file with the wrong columns is refused as a whole', function () {
    $path = sys_get_temp_dir().'/isf-gresit-'.uniqid().'.csv';
    file_put_contents($path, "intrebare,raspuns\nCeva,altceva\n");

    $parsed = (new IsfCsvParser)->parse($path);

    expect($parsed['questions'])->toBe([])
        ->and($parsed['rejected'][0]['reason'])->toContain('lipsesc coloanele');

    @unlink($path);
});
