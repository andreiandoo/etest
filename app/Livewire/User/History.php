<?php

namespace App\Livewire\User;

use App\Enums\AttemptStatus;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class History extends Component
{
    use WithPagination;

    public ?int $verticalId = null;

    public function updatedVerticalId(): void
    {
        $this->resetPage();
    }

    public function render(PublicUrlGenerator $urls, TenantContext $tenantContext): View
    {
        /** @var User $user */
        $user = Auth::user();
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $attempts = TestAttempt::query()
            ->with(['test.vertical', 'test.taxonomyNode'])
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereHas(
                'test',
                fn ($testQuery) => $testQuery->whereIn('vertical_id', $allowedVerticalIds),
            ))
            ->when(
                $this->verticalId,
                fn ($query) => $query->whereHas('test', fn ($testQuery) => $testQuery->where('vertical_id', $this->verticalId)),
            )
            ->latest('completed_at')
            ->paginate(20);

        $verticals = Vertical::query()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->whereHas('tests.attempts', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', AttemptStatus::Completed->value))
            ->orderBy('name')
            ->get();

        return view('livewire.user.history', [
            'attempts' => $attempts,
            'verticals' => $verticals,
            'urlGenerator' => $urls,
        ]);
    }
}
