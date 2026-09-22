<?php

namespace App\Livewire\User;

use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FavoriteToggle extends Component
{
    public TestDefinition $test;

    public bool $favorite = false;

    public function mount(TestDefinition $test): void
    {
        $this->test = $test;

        /** @var User $user */
        $user = Auth::user();

        $this->favorite = $user->favoriteTests()
            ->whereKey($test->id)
            ->exists();
    }

    public function toggle(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->favorite) {
            $user->favoriteTests()->detach($this->test->id);
            $this->favorite = false;

            return;
        }

        $user->favoriteTests()->syncWithoutDetaching([$this->test->id]);
        $this->favorite = true;
    }

    public function render(): View
    {
        return view('livewire.user.favorite-toggle');
    }
}
