<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class QualityManager extends Component
{
    public function verifySource(int $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        Question::query()->findOrFail($id)->update([
            'source_checked_at' => today(),
            'updated_by' => $user->id,
        ]);

        session()->flash('admin_message', 'Sursa a fost marcată ca verificată astăzi.');
    }

    public function resolveReport(int $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        QuestionReport::query()->findOrFail($id)->update([
            'status' => 'resolved',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.quality-manager', [
            'staleQuestions' => Question::query()
                ->with('vertical')
                ->whereNotNull('source_url')
                ->where(function ($query) {
                    $query->whereNull('source_checked_at')
                        ->orWhere('source_checked_at', '<', today()->subMonths(6));
                })
                ->orderBy('source_checked_at')
                ->limit(100)
                ->get(),
            'missingSources' => Question::query()
                ->with('vertical')
                ->whereNull('source_url')
                ->latest()
                ->limit(100)
                ->get(),
            'reports' => QuestionReport::query()
                ->with(['question.vertical', 'user'])
                ->where('status', 'open')
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }
}
