<?php

namespace App\Livewire\Admin;

use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\EditorialWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TestManager extends Component
{
    public ?int $editingId = null;

    public ?int $verticalId = null;

    public ?int $taxonomyNodeId = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public string $instructions = '';

    public string $mode = 'practice';

    public ?int $questionLimit = null;

    public ?int $durationMinutes = null;

    public ?float $passingPercentage = null;

    public bool $randomizeQuestions = false;

    public bool $randomizeOptions = false;

    public bool $allowReview = true;

    public bool $showExplanations = true;

    /** @var array<int, int|string> */
    public array $selectedQuestions = [];

    public function edit(int $id): void
    {
        $test = TestDefinition::query()->with('questions')->findOrFail($id);
        $this->editingId = $test->id;
        $this->verticalId = $test->vertical_id;
        $this->taxonomyNodeId = $test->taxonomy_node_id;
        $this->title = $test->title;
        $this->slug = $test->slug;
        $this->description = $test->description ?? '';
        $this->seoTitle = $test->seo_title ?? '';
        $this->seoDescription = $test->seo_description ?? '';
        $this->instructions = $test->instructions ?? '';
        $this->mode = $test->mode->value;
        $this->questionLimit = $test->question_limit;
        $this->durationMinutes = $test->duration_seconds ? (int) ceil($test->duration_seconds / 60) : null;
        $this->passingPercentage = $test->passing_percentage !== null ? (float) $test->passing_percentage : null;
        $this->randomizeQuestions = $test->randomize_questions;
        $this->randomizeOptions = $test->randomize_options;
        $this->allowReview = $test->allow_review;
        $this->showExplanations = $test->show_explanations;
        $this->selectedQuestions = $test->questions->modelKeys();
    }

    public function save(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->title);
        }

        $validated = $this->validate([
            'verticalId' => ['required', 'exists:verticals,id'],
            'taxonomyNodeId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'alpha_dash:ascii',
                'max:200',
                Rule::unique('tests', 'slug')
                    ->where(fn ($query) => $query->where('vertical_id', $this->verticalId))
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string'],
            'mode' => ['required', Rule::enum(TestMode::class)],
            'questionLimit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'passingPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'selectedQuestions' => ['array'],
            'selectedQuestions.*' => ['integer', 'exists:questions,id'],
        ]);

        $test = TestDefinition::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'vertical_id' => $validated['verticalId'],
                'taxonomy_node_id' => $validated['taxonomyNodeId'],
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => trim($validated['description']) !== '' ? $validated['description'] : null,
                'seo_title' => trim($validated['seoTitle']) !== '' ? $validated['seoTitle'] : null,
                'seo_description' => trim($validated['seoDescription']) !== '' ? $validated['seoDescription'] : null,
                'instructions' => trim($validated['instructions']) !== '' ? $validated['instructions'] : null,
                'mode' => $validated['mode'],
                'status' => $this->editingId
                    ? TestDefinition::query()->findOrFail($this->editingId)->status
                    : PublicationStatus::Draft,
                'question_limit' => $validated['questionLimit'],
                'duration_seconds' => $validated['durationMinutes'] ? $validated['durationMinutes'] * 60 : null,
                'passing_percentage' => $validated['passingPercentage'],
                'randomize_questions' => $this->randomizeQuestions,
                'randomize_options' => $this->randomizeOptions,
                'allow_review' => $this->allowReview,
                'show_explanations' => $this->showExplanations,
            ],
        );

        $sync = [];

        foreach (array_values(array_unique(array_map('intval', $this->selectedQuestions))) as $position => $questionId) {
            $question = Question::query()->findOrFail($questionId);

            if ($question->vertical_id !== $test->vertical_id) {
                continue;
            }

            $sync[$questionId] = [
                'position' => $position,
                'points' => 1,
                'required' => true,
            ];
        }

        $test->questions()->sync($sync);

        $this->resetForm();
        session()->flash('admin_message', 'Testul a fost salvat.');
    }

    public function submitForReview(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->submitForReview(TestDefinition::query()->findOrFail($id), $user);
        session()->flash('admin_message', 'Testul a fost trimis la review.');
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->mode = 'practice';
        $this->allowReview = true;
        $this->showExplanations = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.test-manager', [
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'taxonomyNodes' => TaxonomyNode::query()
                ->when($this->verticalId, fn ($query) => $query->where('vertical_id', $this->verticalId))
                ->orderBy('name')->get(),
            'questions' => Question::query()
                ->when($this->verticalId, fn ($query) => $query->where('vertical_id', $this->verticalId))
                ->orderByDesc('id')->limit(300)->get(),
            'tests' => TestDefinition::query()->with('vertical')->orderByDesc('id')->get(),
            'modes' => TestMode::cases(),
        ]);
    }
}
