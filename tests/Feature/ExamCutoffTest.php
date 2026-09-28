<?php

use App\Enums\AttemptStatus;
use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TestMode;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Testing\AttemptBuilder;
use App\Services\Testing\AttemptEngine;

/**
 * La proba teoretică auto, examinarea nu merge până la ultima întrebare: la a
 * patra greșeală pentru categoria A sau a cincea pentru B, chestionarul se
 * închide pe loc. O simulare care se lasă rezolvată până la capăt ar spune o
 * minciună liniștitoare despre cum decurge examenul.
 */
function cutoffTest(int $maxWrong, int $questions = 6): TestDefinition
{
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'mode' => TestMode::Exam,
        'question_limit' => $questions,
        'max_wrong_answers' => $maxWrong,
        'passing_questions' => $questions - $maxWrong + 1,
        'randomize_questions' => false,
        'randomize_options' => false,
    ]);

    foreach (range(0, $questions - 1) as $position) {
        $question = Question::create([
            'vertical_id' => $vertical->id,
            'type' => QuestionType::SingleChoice,
            'status' => PublicationStatus::Published,
            'prompt' => 'Întrebarea '.($position + 1),
            'difficulty' => 2,
        ]);

        foreach ([['Greșit', false], ['Corect', true]] as $index => [$content, $correct]) {
            AnswerOption::create([
                'question_id' => $question->id,
                'content' => $content,
                'is_correct' => $correct,
                'position' => $index,
            ]);
        }

        $test->questions()->attach($question->id, ['position' => $position, 'points' => 1, 'required' => true]);
    }

    return $test;
}

/**
 * @return array{0: TestAttempt, 1: AttemptEngine}
 */
function cutoffAttempt(TestDefinition $test, User $user): array
{
    return [app(AttemptBuilder::class)->startOrResume($user, $test), app(AttemptEngine::class)];
}

function answerNth(TestAttempt $attempt, AttemptEngine $engine, int $position, bool $correct): void
{
    $question = $attempt->questions()->where('position', $position)->firstOrFail();
    $option = collect($question->question_snapshot['options'])->firstWhere('is_correct', $correct);

    $engine->submit($attempt, $question, ['selected' => [$option['id']]]);
}

test('the paper closes the moment the wrong answers run out', function () {
    $user = User::factory()->create();
    $test = cutoffTest(maxWrong: 2);
    [$attempt, $engine] = cutoffAttempt($test, $user);

    answerNth($attempt, $engine, 0, false);

    expect($attempt->refresh()->status)->toBe(AttemptStatus::InProgress);

    answerNth($attempt, $engine, 1, false);

    expect($attempt->refresh()->status)->toBe(AttemptStatus::Completed)
        ->and(data_get($attempt->configuration, 'completion_reason'))->toBe('too_many_wrong');
});

test('a paper without the rule runs to the last question', function () {
    $user = User::factory()->create();
    $test = cutoffTest(maxWrong: 2);
    $test->forceFill(['max_wrong_answers' => null])->save();

    [$attempt, $engine] = cutoffAttempt($test, $user);

    answerNth($attempt, $engine, 0, false);
    answerNth($attempt, $engine, 1, false);
    answerNth($attempt, $engine, 2, false);

    expect($attempt->refresh()->status)->toBe(AttemptStatus::InProgress);
});

test('nothing more can be answered once the paper has closed', function () {
    $user = User::factory()->create();
    $test = cutoffTest(maxWrong: 1);
    [$attempt, $engine] = cutoffAttempt($test, $user);

    answerNth($attempt, $engine, 0, false);

    expect(fn () => answerNth($attempt->refresh(), $engine, 1, true))
        ->toThrow(DomainException::class);
});

test('the result page says why the paper ended early', function () {
    $user = User::factory()->create();
    $test = cutoffTest(maxWrong: 2);
    [$attempt, $engine] = cutoffAttempt($test, $user);

    answerNth($attempt, $engine, 0, false);
    answerNth($attempt, $engine, 1, false);

    $this->actingAs($user)
        ->get(route('attempts.results', $attempt))
        ->assertSuccessful()
        ->assertSee('Chestionarul s-a închis la 2 răspunsuri greșite');
});
