<?php

use App\Services\Testing\AnswerNormalizer;
use App\Services\Testing\AnswerScorer;

test('single choice is normalized and scored exactly', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'single_choice',
        'options' => [
            ['id' => 10, 'is_correct' => false],
            ['id' => 20, 'is_correct' => true],
        ],
        'answer_config' => [],
    ];

    $answer = $normalizer->normalize($snapshot, ['selected' => '20']);
    $result = $scorer->score($snapshot, $answer, 2.0);

    expect($answer)->toBe(['selected' => [20]])
        ->and($result->isCorrect)->toBeTrue()
        ->and($result->awardedPoints)->toBe(2.0);
});

test('multiple choice supports partial credit and penalties', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'multiple_choice',
        'options' => [
            ['id' => 1, 'is_correct' => true],
            ['id' => 2, 'is_correct' => true],
            ['id' => 3, 'is_correct' => false],
        ],
        'answer_config' => [
            'partial_credit' => true,
            'penalty_per_wrong' => 0.5,
        ],
    ];

    $answer = $normalizer->normalize($snapshot, ['selected' => [1, 3]]);
    $result = $scorer->score($snapshot, $answer, 4.0);

    expect($result->isCorrect)->toBeFalse()
        ->and($result->awardedPoints)->toBe(1.5);
});

test('numeric questions support tolerance', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'numeric',
        'answer_config' => [
            'correct_value' => 9.81,
            'tolerance' => 0.02,
        ],
    ];

    $answer = $normalizer->normalize($snapshot, ['value' => '9,80']);
    $result = $scorer->score($snapshot, $answer, 1.0);

    expect($result->isCorrect)->toBeTrue()
        ->and($result->awardedPoints)->toBe(1.0);
});

test('short text can be case insensitive', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'short_text',
        'answer_config' => [
            'accepted_answers' => ['București'],
            'case_sensitive' => false,
        ],
    ];

    $answer = $normalizer->normalize($snapshot, ['value' => '  BUCUREȘTI ']);
    $result = $scorer->score($snapshot, $answer, 1.0);

    expect($result->isCorrect)->toBeTrue();
});

test('true false questions are scored from boolean configuration', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'true_false',
        'answer_config' => ['correct_boolean' => true],
    ];

    $answer = $normalizer->normalize($snapshot, ['value' => '1']);
    $result = $scorer->score($snapshot, $answer, 1.0);

    expect($answer)->toBe(['value' => true])
        ->and($result->isCorrect)->toBeTrue();
});

test('matching supports proportional partial credit', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'matching',
        'answer_config' => [
            'correct_pairs' => ['a' => '1', 'b' => '2'],
            'partial_credit' => true,
        ],
    ];

    $answer = $normalizer->normalize($snapshot, ['pairs' => ['a' => '1', 'b' => '3']]);
    $result = $scorer->score($snapshot, $answer, 2.0);

    expect($result->isCorrect)->toBeFalse()
        ->and($result->awardedPoints)->toBe(1.0);
});

test('ordering supports positional partial credit', function () {
    $normalizer = new AnswerNormalizer;
    $scorer = new AnswerScorer;

    $snapshot = [
        'type' => 'ordering',
        'answer_config' => [
            'correct_order' => ['a', 'b', 'c'],
            'partial_credit' => true,
        ],
    ];

    $answer = $normalizer->normalize($snapshot, ['items' => ['a', 'c', 'b']]);
    $result = $scorer->score($snapshot, $answer, 3.0);

    expect($result->isCorrect)->toBeFalse()
        ->and($result->awardedPoints)->toBe(1.0);
});
