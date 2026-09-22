<?php

namespace App\Livewire;

use App\Enums\AttemptStatus;
use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use App\Models\AttemptQuestion;
use App\Models\QuestionReport;
use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use App\Services\Testing\AttemptBuilder;
use App\Services\Testing\AttemptEngine;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TestRunner extends Component
{
    public Vertical $vertical;

    public TestDefinition $test;

    public TestAttempt $attempt;

    public string $presentationUrl = '';

    public int $position = 0;

    /** @var array<string, mixed> */
    public array $answer = [];

    public bool $showFeedback = false;

    public bool $reportOpen = false;

    public string $reportReason = 'incorrect';

    public string $reportMessage = '';

    public function mount(
        Vertical $vertical,
        TestDefinition $test,
        AttemptBuilder $builder,
        PublicUrlGenerator $urls,
        TenantContext $tenantContext,
    ): void {
        abort_unless(
            $vertical->is_active
            && $tenantContext->allowsVertical($vertical)
            && $test->vertical_id === $vertical->id
            && $test->status === PublicationStatus::Published
            && $test->published_at?->isPast(),
            404
        );

        /** @var User $user */
        $user = Auth::user();

        $this->vertical = $vertical;
        $this->test = $test;
        $this->test->loadMissing(['vertical', 'taxonomyNode.parent']);
        $this->presentationUrl = $urls->test($this->test);
        $this->attempt = $builder->startOrResume($user, $test);
        $this->position = min(
            (int) $this->attempt->current_position,
            max(0, $this->questionCount() - 1)
        );

        $this->loadAnswer();
    }

    public function submitCurrent(AttemptEngine $engine): void
    {
        $question = $this->currentQuestion();

        if (! $question) {
            return;
        }

        try {
            $engine->submit($this->attempt, $question, $this->answer);
        } catch (DomainException $exception) {
            $this->addError('answer', $exception->getMessage());

            if ($this->attempt->fresh()?->status === AttemptStatus::Completed) {
                $this->redirectRoute('attempts.results', $this->attempt, navigate: true);
            }

            return;
        }

        $this->attempt->refresh();

        if ($this->test->mode === TestMode::Practice) {
            $this->showFeedback = true;

            return;
        }

        if ($this->position >= $this->questionCount() - 1) {
            $this->finish($engine);

            return;
        }

        $this->next();
    }

    public function next(): void
    {
        if ($this->position >= $this->questionCount() - 1) {
            return;
        }

        $this->position++;
        $this->persistPosition();
        $this->loadAnswer();
    }

    public function previous(): void
    {
        if (! $this->test->allow_review || $this->position <= 0) {
            return;
        }

        $this->position--;
        $this->persistPosition();
        $this->loadAnswer();
    }

    public function goTo(int $position): void
    {
        if (! $this->test->allow_review) {
            return;
        }

        if ($position < 0 || $position >= $this->questionCount()) {
            return;
        }

        $this->position = $position;
        $this->persistPosition();
        $this->loadAnswer();
    }

    public function submitReport(): void
    {
        $question = $this->currentQuestion();

        if (! $question) {
            return;
        }

        $validated = $this->validate([
            'reportReason' => ['required', 'in:incorrect,outdated,ambiguous,typo,other'],
            'reportMessage' => ['nullable', 'string', 'max:1000'],
        ]);

        QuestionReport::create([
            'user_id' => (int) Auth::id(),
            'question_id' => $question->question_id,
            'attempt_question_id' => $question->id,
            'reason' => $validated['reportReason'],
            'message' => $validated['reportMessage'] !== '' ? $validated['reportMessage'] : null,
            'context' => [
                'test_id' => $this->test->id,
                'attempt_id' => $this->attempt->id,
                'position' => $this->position,
            ],
        ]);

        $this->reportOpen = false;
        $this->reportReason = 'incorrect';
        $this->reportMessage = '';

        session()->flash('question_reported', 'Mulțumim. Întrebarea a fost trimisă pentru verificare.');
    }

    public function finish(AttemptEngine $engine): void
    {
        $this->attempt = $engine->finish($this->attempt);

        $this->redirectRoute('attempts.results', $this->attempt, navigate: true);
    }

    public function tick(AttemptEngine $engine): void
    {
        $this->attempt->refresh();

        if ($this->attempt->status !== AttemptStatus::InProgress) {
            $this->redirectRoute('attempts.results', $this->attempt, navigate: true);

            return;
        }

        if ($engine->hasExpired($this->attempt)) {
            $this->attempt = $engine->finish($this->attempt, 'expired');
            $this->redirectRoute('attempts.results', $this->attempt, navigate: true);
        }
    }

    public function render(): View
    {
        $currentQuestion = $this->currentQuestion();
        $answeredPositions = $this->attempt->questions()
            ->whereHas('answer')
            ->pluck('position')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        return view('livewire.test-runner', [
            'currentQuestion' => $currentQuestion,
            'answeredPositions' => $answeredPositions,
            'questionCount' => $this->questionCount(),
            'remainingSeconds' => $this->remainingSeconds(),
        ]);
    }

    private function currentQuestion(): ?AttemptQuestion
    {
        return $this->attempt->questions()
            ->with('answer')
            ->where('position', $this->position)
            ->first();
    }

    private function questionCount(): int
    {
        return $this->attempt->questions()->count();
    }

    private function persistPosition(): void
    {
        $this->attempt->forceFill(['current_position' => $this->position])->save();
        $this->showFeedback = false;
    }

    private function loadAnswer(): void
    {
        $stored = $this->currentQuestion()?->answer?->answer;
        $this->answer = $stored ?? [];
        $this->showFeedback = false;
        $this->resetErrorBag('answer');
    }

    private function remainingSeconds(): ?int
    {
        if ($this->attempt->expires_at === null) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->attempt->expires_at, false));
    }
}
