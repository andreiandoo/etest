<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Models\UserStat;
use App\Services\User\UserProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Leaderboard extends Component
{
    public function render(UserProgressService $progress): View
    {
        /** @var User $user */
        $user = Auth::user();

        $leaders = UserStat::query()
            ->with('user')
            ->where('leaderboard_opt_in', true)
            ->orderByDesc('xp')
            ->orderByDesc('completed_attempts')
            ->orderBy('user_id')
            ->limit(50)
            ->get()
            ->map(function (UserStat $stats, int $index) use ($progress): array {
                return [
                    'rank' => $index + 1,
                    'user_id' => $stats->user_id,
                    'name' => $stats->leaderboard_display_name ?: $stats->user->name,
                    'xp' => (int) $stats->xp,
                    'level' => $progress->levelForXp((int) $stats->xp),
                    'completed_attempts' => (int) $stats->completed_attempts,
                    'current_streak' => (int) $stats->current_streak,
                ];
            });

        return view('livewire.user.leaderboard', [
            'leaders' => $leaders,
            'currentUserId' => $user->id,
        ]);
    }
}
