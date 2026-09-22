<?php

namespace App\Livewire\Admin;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\User;
use App\Services\Content\EditorialWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ReviewQueue extends Component
{
    public function publishQuestion(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->publish(Question::query()->findOrFail($id), $user);
        session()->flash('admin_message', 'Întrebarea a fost publicată.');
    }

    public function publishTest(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->publish(TestDefinition::query()->findOrFail($id), $user);
        session()->flash('admin_message', 'Testul a fost publicat.');
    }

    public function draftQuestion(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->returnToDraft(Question::query()->findOrFail($id), $user);
    }

    public function draftTest(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->returnToDraft(TestDefinition::query()->findOrFail($id), $user);
    }

    public function render(): View
    {
        return view('livewire.admin.review-queue', [
            'questions' => Question::query()
                ->with(['vertical', 'taxonomyNode'])
                ->where('status', PublicationStatus::Review->value)
                ->latest()->get(),
            'tests' => TestDefinition::query()
                ->with('vertical')
                ->where('status', PublicationStatus::Review->value)
                ->latest()->get(),
        ]);
    }
}
