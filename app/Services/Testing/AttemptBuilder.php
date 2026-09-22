<?php

namespace App\Services\Testing;

use App\Enums\AttemptStatus;
use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class AttemptBuilder
{
    public function __construct(
        private DeterministicOrder $order,
    ) {}

    public function startOrResume(User $user, TestDefinition $test): TestAttempt
    {
        return DB::transaction(function () use ($user, $test): TestAttempt {
            $existing = TestAttempt::query()
                ->where('test_id', $test->id)
                ->where('user_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (! $existing->questions()->exists()) {
                    $this->buildSnapshot($existing, $test);
                }

                return $existing->fresh() ?? $existing;
            }

            $attempt = TestAttempt::create([
                'test_id' => $test->id,
                'user_id' => $user->id,
                'status' => AttemptStatus::InProgress,
                'mode' => $test->mode,
                'seed' => random_int(1, PHP_INT_MAX),
                'started_at' => now(),
                'expires_at' => $test->duration_seconds
                    ? now()->addSeconds($test->duration_seconds)
                    : null,
                'configuration' => [
                    'randomize_questions' => $test->randomize_questions,
                    'randomize_options' => $test->randomize_options,
                    'question_limit' => $test->question_limit,
                ],
            ]);

            $this->buildSnapshot($attempt, $test);

            return $attempt->fresh() ?? $attempt;
        });
    }

    private function buildSnapshot(TestAttempt $attempt, TestDefinition $test): void
    {
        /** @var Collection<int, Question> $questions */
        $questions = $test->questions()
            ->with('options')
            ->where('questions.status', PublicationStatus::Published->value)
            ->get();

        $ids = $questions->modelKeys();

        if ($test->randomize_questions) {
            $ids = array_map('intval', $this->order->sort($ids, (int) $attempt->seed, 'questions'));
        }

        $limit = $test->question_limit;

        if ($limit !== null && $limit > 0) {
            $ids = array_slice($ids, 0, $limit);
        }

        $byId = $questions->keyBy('id');
        $maxScore = 0.0;

        foreach ($ids as $position => $questionId) {
            $question = $byId->get($questionId);

            if (! $question) {
                continue;
            }

            $points = (float) ($question->pivot?->getAttribute('points') ?? 1);
            $optionIds = $question->options->modelKeys();

            if ($test->randomize_options) {
                $optionIds = array_map(
                    'intval',
                    $this->order->sort($optionIds, (int) $attempt->seed, 'options:'.$question->id)
                );
            }

            $options = $question->options
                ->keyBy('id')
                ->only($optionIds)
                ->map(fn ($option): array => [
                    'id' => (int) $option->id,
                    'content' => (string) $option->content,
                    'is_correct' => (bool) $option->is_correct,
                    'feedback' => $option->feedback,
                ])
                ->values()
                ->all();

            if ($optionIds !== []) {
                $optionMap = collect($options)->keyBy('id');
                $options = collect($optionIds)
                    ->map(fn (int $id): ?array => $optionMap->get($id))
                    ->filter()
                    ->values()
                    ->all();
            }

            $attempt->questions()->create([
                'question_id' => $question->id,
                'position' => $position,
                'points' => $points,
                'option_order' => $optionIds,
                'question_snapshot' => [
                    'type' => $question->type->value,
                    'prompt' => $question->prompt,
                    'explanation' => $question->explanation,
                    'difficulty' => $question->difficulty,
                    'source_label' => $question->source_label,
                    'source_url' => $question->source_url,
                    'source_checked_at' => $question->source_checked_at?->toDateString(),
                    'answer_config' => $question->answer_config,
                    'metadata' => $question->metadata,
                    'options' => $options,
                ],
            ]);

            $maxScore += $points;
        }

        $attempt->forceFill([
            'max_score' => $maxScore,
            'current_position' => 0,
        ])->save();
    }
}
