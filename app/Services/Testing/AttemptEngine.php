<?php

namespace App\Services\Testing;

use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptQuestion;
use App\Models\TestAttempt;
use App\Services\User\UserProgressService;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class AttemptEngine
{
    public function __construct(
        private AnswerNormalizer $normalizer,
        private AnswerScorer $scorer,
        private QuestionAnalytics $analytics,
        private UserProgressService $userProgress,
    ) {}

    public function submit(TestAttempt $attempt, AttemptQuestion $question, mixed $rawAnswer, ?int $durationMs = null): AttemptAnswer
    {
        if ($question->test_attempt_id !== $attempt->id) {
            throw new DomainException('Question does not belong to this attempt.');
        }

        if ($attempt->status !== AttemptStatus::InProgress) {
            throw new DomainException('Attempt is no longer in progress.');
        }

        if ($this->hasExpired($attempt)) {
            $this->finish($attempt, 'expired');

            throw new DomainException('Attempt has expired.');
        }

        $snapshot = $question->question_snapshot;

        try {
            $normalized = $this->normalizer->normalize($snapshot, $rawAnswer);
        } catch (InvalidArgumentException $exception) {
            throw new DomainException($exception->getMessage(), previous: $exception);
        }

        $result = $this->scorer->score($snapshot, $normalized, (float) $question->points);

        $answer = AttemptAnswer::query()->updateOrCreate(
            ['attempt_question_id' => $question->id],
            [
                'answer' => $normalized,
                'is_correct' => $result->isCorrect,
                'awarded_points' => round($result->awardedPoints, 2),
                'answered_at' => now(),
                'duration_ms' => $durationMs,
            ],
        );

        $this->refreshScore($attempt);

        if ($question->question_id !== null) {
            $this->analytics->rebuild((int) $question->question_id);
        }

        return $answer;
    }

    public function finish(TestAttempt $attempt, string $reason = 'submitted'): TestAttempt
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $reason): TestAttempt {
            $locked = TestAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== AttemptStatus::InProgress) {
                return $locked;
            }

            $this->refreshScore($locked);

            $configuration = $locked->configuration ?? [];
            $configuration['completion_reason'] = $reason;

            $locked->forceFill([
                'status' => AttemptStatus::Completed,
                'completed_at' => now(),
                'duration_seconds' => max(0, (int) floor($locked->started_at?->diffInSeconds(now()) ?? 0)),
                'configuration' => $configuration,
            ])->save();

            $completed = $locked->fresh() ?? $locked;
            $this->userProgress->recordCompletion($completed);

            return $completed;
        });
    }

    public function hasExpired(TestAttempt $attempt): bool
    {
        return $attempt->expires_at !== null && $attempt->expires_at->isPast();
    }

    public function refreshScore(TestAttempt $attempt): void
    {
        $score = (float) AttemptAnswer::query()
            ->whereHas('attemptQuestion', fn ($query) => $query->where('test_attempt_id', $attempt->id))
            ->sum('awarded_points');

        $maxScore = (float) $attempt->questions()->sum('points');
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;

        $attempt->forceFill([
            'score' => round($score, 2),
            'max_score' => round($maxScore, 2),
            'percentage' => $percentage,
        ])->save();
    }
}
