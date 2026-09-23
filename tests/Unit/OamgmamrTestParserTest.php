<?php

use App\Services\Sources\Parsers\OamgmamrTestParser;

/**
 * @return array{total: int, questions: array<int, array{number: int, prompt: string, options: array<int, string>}>, rejected: array<int, array<string, string>>}
 */
function oamgmamrParsed(): array
{
    $text = (string) file_get_contents(__DIR__.'/../Fixtures/oamgmamr-test-excerpt.txt');

    return (new OamgmamrTestParser)->parse($text);
}

test('the header before the first question never becomes a question', function () {
    $parsed = oamgmamrParsed();

    expect($parsed['questions'][0]['number'])->toBe(1)
        ->and($parsed['questions'][0]['prompt'])->toStartWith('Amnioscopia permite')
        ->and($parsed['questions'][0]['prompt'])->not->toContain('Specialitatea');
});

test('it splits the three options and keeps their order', function () {
    $question = oamgmamrParsed()['questions'][0];

    expect($question['options'])->toBe([
        '36 săptămâni de sarcină',
        '42 săptămâni de sarcină',
        '12 săptămâni de sarcină',
    ]);
});

test('the generator footer does not stick to the last option', function () {
    $parsed = oamgmamrParsed();
    $last = end($parsed['questions']);

    expect($last['number'])->toBe(100)
        ->and($last['options'][2])->not->toContain('TCPDF')
        ->and($last['options'][2])->toBe('Avort spontan în săptămâna 6-8 de sarcină');
});

test('every question carries its number and three options', function () {
    $parsed = oamgmamrParsed();

    expect($parsed['rejected'])->toBe([]);

    foreach ($parsed['questions'] as $question) {
        expect($question['options'])->toHaveCount(3)
            ->and($question['prompt'])->not->toBe('')
            ->and($question['number'])->toBeGreaterThan(0);
    }
});
