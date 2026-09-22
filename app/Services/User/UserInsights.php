<?php

namespace App\Services\User;

use App\Enums\AttemptStatus;
use App\Models\TaxonomyNode;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\UserStat;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class UserInsights
{
    public function __construct(
        private UserProgressService $progress,
        private AchievementCatalog $achievements,
        private TenantContext $tenantContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $allowedVerticalIds = $this->tenantContext->allowedVerticalIds();

        $stats = UserStat::query()->firstOrCreate(['user_id' => $user->id], [
            'xp' => 0,
            'completed_attempts' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'leaderboard_opt_in' => false,
        ]);
        $completed = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereHas(
                'test',
                fn ($testQuery) => $testQuery->whereIn('vertical_id', $allowedVerticalIds),
            ))
            ->whereRaw("COALESCE(configuration->>'completion_reason', 'submitted') <> 'expired'");

        $completedCount = (clone $completed)->count();
        $average = $completedCount > 0 ? (float) (clone $completed)->avg('percentage') : 0.0;
        $best = $completedCount > 0 ? (float) (clone $completed)->max('percentage') : 0.0;
        $totalSeconds = (int) (clone $completed)->sum('duration_seconds');

        $recentAttempts = TestAttempt::query()
            ->with(['test.vertical', 'test.taxonomyNode'])
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereHas(
                'test',
                fn ($testQuery) => $testQuery->whereIn('vertical_id', $allowedVerticalIds),
            ))
            ->whereRaw("COALESCE(configuration->>'completion_reason', 'submitted') <> 'expired'")
            ->latest('completed_at')
            ->limit(6)
            ->get();

        $favoriteTests = $user->favoriteTests()
            ->with(['vertical', 'taxonomyNode'])
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('tests.vertical_id', $allowedVerticalIds))
            ->latest('favorite_tests.created_at')
            ->limit(6)
            ->get();

        $achievementRows = $user->achievements()
            ->latest('unlocked_at')
            ->get()
            ->map(function ($row): array {
                $definition = $this->achievements->get($row->achievement_key);

                return [
                    'key' => $row->achievement_key,
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'unlocked_at' => $row->unlocked_at,
                ];
            });

        return [
            'stats' => $stats,
            'level' => $this->progress->levelProgress((int) $stats->xp),
            'completed_count' => $completedCount,
            'average_percentage' => round($average, 1),
            'best_percentage' => round($best, 1),
            'total_seconds' => $totalSeconds,
            'recent_attempts' => $recentAttempts,
            'vertical_progress' => $this->verticalProgress($user),
            'weak_areas' => $this->weakAreas($user),
            'favorite_tests' => $favoriteTests,
            'achievements' => $achievementRows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function verticalProgress(User $user): array
    {
        $allowedVerticalIds = $this->tenantContext->allowedVerticalIds();

        return DB::table('test_attempts as ta')
            ->join('tests as t', 't.id', '=', 'ta.test_id')
            ->join('verticals as v', 'v.id', '=', 't.vertical_id')
            ->where('ta.user_id', $user->id)
            ->where('ta.status', AttemptStatus::Completed->value)
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('t.vertical_id', $allowedVerticalIds))
            ->whereRaw("COALESCE(ta.configuration->>'completion_reason', 'submitted') <> 'expired'")
            ->groupBy('v.id', 'v.name', 'v.slug')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('v.id, v.name, v.slug, COUNT(*) as attempts_count, AVG(ta.percentage) as average_percentage, MAX(ta.percentage) as best_percentage')
            ->get()
            ->map(static fn ($row): array => [
                'vertical_id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'attempts_count' => (int) $row->attempts_count,
                'average_percentage' => round((float) $row->average_percentage, 1),
                'best_percentage' => round((float) $row->best_percentage, 1),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function weakAreas(User $user): array
    {
        $allowedVerticalIds = $this->tenantContext->allowedVerticalIds();

        $rows = DB::table('attempt_answers as aa')
            ->join('attempt_questions as aq', 'aq.id', '=', 'aa.attempt_question_id')
            ->join('test_attempts as ta', 'ta.id', '=', 'aq.test_attempt_id')
            ->join('tests as t', 't.id', '=', 'ta.test_id')
            ->leftJoin('questions as q', 'q.id', '=', 'aq.question_id')
            ->where('ta.user_id', $user->id)
            ->where('ta.status', AttemptStatus::Completed->value)
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('t.vertical_id', $allowedVerticalIds))
            ->whereNotNull('aa.answered_at')
            ->whereRaw('COALESCE(q.taxonomy_node_id, t.taxonomy_node_id) IS NOT NULL')
            ->groupByRaw('COALESCE(q.taxonomy_node_id, t.taxonomy_node_id)')
            ->selectRaw('COALESCE(q.taxonomy_node_id, t.taxonomy_node_id) as taxonomy_node_id')
            ->selectRaw('COUNT(*) as answered_count')
            ->selectRaw('SUM(CASE WHEN aa.is_correct = true THEN 1 ELSE 0 END) as correct_count')
            ->selectRaw('AVG(CASE WHEN aa.is_correct = true THEN 1.0 ELSE 0.0 END) as accuracy')
            ->havingRaw('COUNT(*) >= 2')
            ->orderBy('accuracy')
            ->orderByDesc('answered_count')
            ->limit(5)
            ->get();

        /** @var Collection<int, TaxonomyNode> $nodes */
        $nodes = TaxonomyNode::query()
            ->with('vertical')
            ->whereIn('id', $rows->pluck('taxonomy_node_id')->map(static fn ($id): int => (int) $id))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($nodes): ?array {
                $node = $nodes->get((int) $row->taxonomy_node_id);

                if ($node === null) {
                    return null;
                }

                return [
                    'taxonomy_node_id' => $node->id,
                    'name' => $node->name,
                    'vertical' => $node->vertical->name,
                    'answered_count' => (int) $row->answered_count,
                    'correct_count' => (int) $row->correct_count,
                    'accuracy' => round((float) $row->accuracy * 100, 1),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
