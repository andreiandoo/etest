<?php

use App\Enums\AttemptStatus;
use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Livewire\TestRunner;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\QuestionStatistic;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Testing\AttemptBuilder;
use App\Services\Testing\AttemptEngine;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function attachChoiceQuestion(TestDefinition $test, int $position, string $prompt): Question
{
    $question = Question::create([
        'vertical_id' => $test->vertical_id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Published,
        'prompt' => $prompt,
        'explanation' => 'Explicație pentru '.$prompt,
        'difficulty' => 2,
    ]);

    AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Greșit',
        'is_correct' => false,
        'position' => 0,
    ]);

    AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Corect',
        'is_correct' => true,
        'position' => 1,
    ]);

    $test->questions()->attach($question->id, [
        'position' => $position,
        'points' => 1,
        'required' => true,
    ]);

    return $question;
}

test('attempt builder snapshots and resumes the same randomized attempt', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'randomize_questions' => true,
        'randomize_options' => true,
        'question_limit' => 3,
    ]);

    foreach (range(0, 4) as $position) {
        attachChoiceQuestion($test, $position, 'Întrebarea '.$position);
    }

    $builder = app(AttemptBuilder::class);
    $first = $builder->startOrResume($user, $test);
    $ids = $first->questions()->pluck('question_id')->all();

    $resumed = $builder->startOrResume($user, $test);

    expect($first->id)->toBe($resumed->id)
        ->and($ids)->toBe($resumed->questions()->pluck('question_id')->all())
        ->and($first->questions()->count())->toBe(3)
        ->and((float) $first->fresh()->max_score)->toBe(3.0);
});

test('answering a question persists score and finishing completes the attempt', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create();
    attachChoiceQuestion($test, 0, 'Întrebarea');

    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);
    $attemptQuestion = $attempt->questions()->firstOrFail();
    $snapshot = $attemptQuestion->question_snapshot;
    $correctOption = collect($snapshot['options'])->firstWhere('is_correct', true);

    app(AttemptEngine::class)->submit(
        $attempt,
        $attemptQuestion,
        ['selected' => $correctOption['id']]
    );

    $attempt->refresh();

    expect((float) $attempt->score)->toBe(1.0)
        ->and((float) $attempt->percentage)->toBe(100.0);

    $finished = app(AttemptEngine::class)->finish($attempt);

    expect($finished->status)->toBe(AttemptStatus::Completed)
        ->and($finished->completed_at)->not->toBeNull();
});

test('expired attempts are completed with expired reason', function () {
    Carbon::setTestNow('2026-09-19 20:00:00');

    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create(['duration_seconds' => 60]);
    attachChoiceQuestion($test, 0, 'Întrebarea');

    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);

    Carbon::setTestNow('2026-09-19 20:02:00');

    $engine = app(AttemptEngine::class);

    expect($engine->hasExpired($attempt->fresh()))->toBeTrue();

    $finished = $engine->finish($attempt->fresh(), 'expired');

    expect($finished->status)->toBe(AttemptStatus::Completed)
        ->and($finished->configuration['completion_reason'])->toBe('expired');

    Carbon::setTestNow();
});

test('answering rebuilds idempotent question analytics', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create();
    $question = attachChoiceQuestion($test, 0, 'Analytics');

    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);
    $attemptQuestion = $attempt->questions()->firstOrFail();
    $correct = collect($attemptQuestion->question_snapshot['options'])->firstWhere('is_correct', true);

    $engine = app(AttemptEngine::class);
    $engine->submit($attempt, $attemptQuestion, ['selected' => $correct['id']]);
    $engine->submit($attempt->fresh(), $attemptQuestion->fresh(), ['selected' => $correct['id']]);

    $stats = QuestionStatistic::query()->where('question_id', $question->id)->firstOrFail();

    expect($stats->answered_count)->toBe(1)
        ->and($stats->correct_count)->toBe(1)
        ->and((float) $stats->correct_rate)->toBe(1.0);
});

test('a signed in user can report the current question', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create(['slug' => 'auto']);
    $test = TestDefinition::factory()->for($vertical)->create(['slug' => 'raportare']);
    $question = attachChoiceQuestion($test, 0, 'De verificat');

    Livewire::actingAs($user)
        ->test(TestRunner::class, ['vertical' => $vertical, 'test' => $test])
        ->set('reportReason', 'outdated')
        ->set('reportMessage', 'Sursa pare depășită.')
        ->call('submitReport')
        ->assertHasNoErrors();

    expect(QuestionReport::query()
        ->where('user_id', $user->id)
        ->where('question_id', $question->id)
        ->where('reason', 'outdated')
        ->exists())->toBeTrue();
});
