<?php

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\EditorialWorkflow;

test('questions follow draft review published workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vertical = Vertical::factory()->create();

    $question = Question::create([
        'vertical_id' => $vertical->id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Draft,
        'prompt' => 'Întrebare editorială',
        'difficulty' => 3,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $workflow = app(EditorialWorkflow::class);
    $workflow->submitForReview($question, $admin);

    expect($question->fresh()->status)->toBe(PublicationStatus::Review);

    $workflow->publish($question->fresh(), $admin);
    $published = $question->fresh();

    expect($published->status)->toBe(PublicationStatus::Published)
        ->and($published->reviewed_by)->toBe($admin->id)
        ->and($published->published_by)->toBe($admin->id)
        ->and($published->reviewed_at)->not->toBeNull();
});

test('tests receive published timestamp only after review', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'status' => PublicationStatus::Draft,
        'published_at' => null,
    ]);

    $workflow = app(EditorialWorkflow::class);
    $workflow->submitForReview($test, $admin);

    expect($test->fresh()->status)->toBe(PublicationStatus::Review)
        ->and($test->fresh()->published_at)->toBeNull();

    $workflow->publish($test->fresh(), $admin);

    expect($test->fresh()->status)->toBe(PublicationStatus::Published)
        ->and($test->fresh()->published_at)->not->toBeNull();
});
