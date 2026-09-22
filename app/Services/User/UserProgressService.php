<?php

namespace App\Services\User;

use App\Enums\AttemptStatus;
use App\Models\TestAttempt;
use App\Models\UserAchievement;
use App\Models\UserStat;
use App\Models\UserXpEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class UserProgressService
{
    public function recordCompletion(TestAttempt $attempt): void
    {
        if ($attempt->status !== AttemptStatus::Completed || $attempt->completed_at === null) {
            return;
        }

        if (($attempt->configuration['completion_reason'] ?? 'submitted') === 'expired') {
            return;
        }

        DB::transaction(function () use ($attempt): void {
            $attempt->loadMissing(['user', 'test']);

            $existingEvent = UserXpEvent::query()
                ->where('test_attempt_id', $attempt->id)
                ->where('reason', 'attempt_completed')
                ->first();

            if ($existingEvent !== null) {
                return;
            }

            $percentage = (float) ($attempt->percentage ?? 0);
            $passing = $attempt->test->passing_percentage !== null
                ? (float) $attempt->test->passing_percentage
                : null;

            $xp = 20 + (int) round($percentage / 2);

            if ($passing !== null && $percentage >= $passing) {
                $xp += 15;
            }

            if ($percentage >= 100) {
                $xp += 25;
            }

            UserXpEvent::create([
                'user_id' => $attempt->user_id,
                'test_attempt_id' => $attempt->id,
                'points' => $xp,
                'reason' => 'attempt_completed',
                'metadata' => [
                    'percentage' => $percentage,
                    'test_id' => $attempt->test_id,
                ],
            ]);

            $stats = UserStat::query()->firstOrCreate(
                ['user_id' => $attempt->user_id],
                [
                    'xp' => 0,
                    'completed_attempts' => 0,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                ],
            );

            $activityDate = Carbon::parse($attempt->completed_at)
                ->timezone($attempt->user->timezone ?: 'Europe/Bucharest')
                ->startOfDay();

            $currentStreak = $this->nextStreak(
                $stats->last_activity_date,
                $activityDate,
                (int) $stats->current_streak,
            );

            $stats->forceFill([
                'xp' => (int) $stats->xp + $xp,
                'completed_attempts' => (int) $stats->completed_attempts + 1,
                'current_streak' => $currentStreak,
                'longest_streak' => max((int) $stats->longest_streak, $currentStreak),
                'last_activity_date' => $activityDate->toDateString(),
            ])->save();

            $this->unlockAchievements($attempt, $stats);
        });
    }

    public function levelForXp(int $xp): int
    {
        return (int) floor(sqrt(max(0, $xp) / 100)) + 1;
    }

    /**
     * @return array{level:int,current_threshold:int,next_threshold:int,progress:int,required:int}
     */
    public function levelProgress(int $xp): array
    {
        $level = $this->levelForXp($xp);
        $currentThreshold = ($level - 1) ** 2 * 100;
        $nextThreshold = $level ** 2 * 100;

        return [
            'level' => $level,
            'current_threshold' => $currentThreshold,
            'next_threshold' => $nextThreshold,
            'progress' => max(0, $xp - $currentThreshold),
            'required' => max(1, $nextThreshold - $currentThreshold),
        ];
    }

    private function nextStreak(?Carbon $lastActivity, Carbon $activityDate, int $current): int
    {
        if ($lastActivity === null) {
            return 1;
        }

        $last = $lastActivity->copy()->startOfDay();

        if ($last->isSameDay($activityDate)) {
            return max(1, $current);
        }

        if ($last->copy()->addDay()->isSameDay($activityDate)) {
            return max(1, $current + 1);
        }

        return 1;
    }

    private function unlockAchievements(TestAttempt $attempt, UserStat $stats): void
    {
        $keys = [];

        if ((int) $stats->completed_attempts >= 1) {
            $keys[] = 'first_test';
        }

        if ((int) $stats->completed_attempts >= 5) {
            $keys[] = 'five_tests';
        }

        if ((int) $stats->completed_attempts >= 20) {
            $keys[] = 'twenty_tests';
        }

        if ((float) $attempt->percentage >= 100) {
            $keys[] = 'perfect_score';
        }

        if ((int) $stats->current_streak >= 3) {
            $keys[] = 'streak_3';
        }

        if ((int) $stats->current_streak >= 7) {
            $keys[] = 'streak_7';
        }

        if ((int) $stats->xp >= 100) {
            $keys[] = 'xp_100';
        }

        $verticalCount = TestAttempt::query()
            ->where('test_attempts.user_id', $attempt->user_id)
            ->where('test_attempts.status', AttemptStatus::Completed->value)
            ->join('tests', 'tests.id', '=', 'test_attempts.test_id')
            ->distinct('tests.vertical_id')
            ->count('tests.vertical_id');

        if ($verticalCount >= 3) {
            $keys[] = 'explorer_3';
        }

        foreach (array_unique($keys) as $key) {
            UserAchievement::query()->firstOrCreate(
                [
                    'user_id' => $attempt->user_id,
                    'achievement_key' => $key,
                ],
                [
                    'unlocked_at' => now(),
                    'metadata' => ['attempt_id' => $attempt->id],
                ],
            );
        }
    }
}
