<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Models\TestAttempt;
use App\Models\UserStat;
use App\Models\UserXpEvent;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AttemptResultController extends Controller
{
    public function __invoke(
        TestAttempt $attempt,
        PublicUrlGenerator $urls,
        TenantContext $tenantContext,
    ): View {
        $attempt->loadMissing('test.vertical');

        abort_unless(
            $attempt->user_id === Auth::id()
            && $attempt->status === AttemptStatus::Completed
            && $tenantContext->allowsVertical($attempt->test->vertical),
            404
        );

        $attempt->load([
            'test.vertical',
            'questions.answer',
            'questions.sourceQuestion.taxonomyNode',
        ]);

        return view('attempts.results', [
            'attempt' => $attempt,
            'presentationUrl' => $urls->test($attempt->test),
            'verticalUrl' => $urls->vertical($attempt->test->vertical),
            'breakdown' => $this->breakdown($attempt),
            'xpEarned' => (int) UserXpEvent::query()
                ->where('test_attempt_id', $attempt->id)
                ->where('reason', 'attempt_completed')
                ->sum('points'),
            'currentStreak' => (int) (UserStat::query()
                ->where('user_id', $attempt->user_id)
                ->value('current_streak') ?? 0),
            // Rezultatele sunt private; nu au ce căuta în index.
            'robots' => 'noindex,nofollow',
            'seoTitle' => 'Rezultat — '.$attempt->test->title,
        ]);
    }

    /**
     * Rezultatul pe capitole.
     *
     * Asta e partea utilă a paginii: un scor global spune doar dacă ai trecut,
     * dar defalcarea pe capitole spune ce anume trebuie reluat. Întrebările
     * fără nod de taxonomie sunt grupate separat, ca să nu dispară din total.
     *
     * @return array<int, array{name: string, correct: int, total: int, percentage: int, node_id: int|null}>
     */
    private function breakdown(TestAttempt $attempt): array
    {
        $groups = [];

        foreach ($attempt->questions as $attemptQuestion) {
            $node = $attemptQuestion->sourceQuestion?->taxonomyNode;
            $key = $node?->id ?? 0;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $node?->name ?? 'Întrebări fără capitol',
                    'node_id' => $node?->id,
                    'correct' => 0,
                    'total' => 0,
                ];
            }

            $groups[$key]['total']++;

            if ($attemptQuestion->answer?->is_correct === true) {
                $groups[$key]['correct']++;
            }
        }

        $breakdown = array_map(
            static function (array $group): array {
                $group['percentage'] = $group['total'] > 0
                    ? (int) round($group['correct'] / $group['total'] * 100)
                    : 0;

                return $group;
            },
            array_values($groups),
        );

        // Capitolele slabe sus: acolo e treaba de făcut.
        usort($breakdown, static fn (array $a, array $b): int => $a['percentage'] <=> $b['percentage']);

        return $breakdown;
    }
}
