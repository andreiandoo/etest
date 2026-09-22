<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Models\UserStat;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\User\UserInsights;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public bool $leaderboardOptIn = false;

    public string $leaderboardDisplayName = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $stats = UserStat::query()->firstOrCreate(['user_id' => $user->id], [
            'xp' => 0,
            'completed_attempts' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'leaderboard_opt_in' => false,
        ]);

        $this->leaderboardOptIn = (bool) $stats->leaderboard_opt_in;
        $this->leaderboardDisplayName = $stats->leaderboard_display_name ?? $user->name;
    }

    public function saveLeaderboardSettings(): void
    {
        $validated = $this->validate([
            'leaderboardOptIn' => ['boolean'],
            'leaderboardDisplayName' => ['nullable', 'string', 'min:2', 'max:80'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        UserStat::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'leaderboard_opt_in' => $validated['leaderboardOptIn'],
                'leaderboard_display_name' => $validated['leaderboardOptIn']
                    ? (trim($validated['leaderboardDisplayName']) !== '' ? trim($validated['leaderboardDisplayName']) : $user->name)
                    : null,
            ],
        );

        session()->flash('user_message', 'Preferințele pentru clasament au fost salvate.');
    }

    public function render(UserInsights $insights, PublicUrlGenerator $urls): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.user.dashboard', array_merge(
            $insights->dashboard($user),
            ['urlGenerator' => $urls],
        ));
    }
}
