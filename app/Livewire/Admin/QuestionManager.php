<?php

namespace App\Livewire\Admin;

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\EditorialWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class QuestionManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $verticalId = null;

    public ?int $taxonomyNodeId = null;

    public string $sourceKey = '';

    public string $type = 'single_choice';

    public string $prompt = '';

    public string $explanation = '';

    public int $difficulty = 3;

    public string $sourceLabel = '';

    public string $sourceUrl = '';

    public string $sourceCheckedAt = '';

    public string $answerConfigJson = '{}';

    /** @var array<int, array{content:string,is_correct:bool,feedback:string}> */
    public array $options = [];

    public function mount(): void
    {
        $this->addOption();
        $this->addOption();
    }

    public function addOption(): void
    {
        $this->options[] = ['content' => '', 'is_correct' => false, 'feedback' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function edit(int $id): void
    {
        $question = Question::query()->with('options')->findOrFail($id);
        $this->editingId = $question->id;
        $this->verticalId = $question->vertical_id;
        $this->taxonomyNodeId = $question->taxonomy_node_id;
        $this->sourceKey = $question->source_key ?? '';
        $this->type = $question->type->value;
        $this->prompt = $question->prompt;
        $this->explanation = $question->explanation ?? '';
        $this->difficulty = $question->difficulty;
        $this->sourceLabel = $question->source_label ?? '';
        $this->sourceUrl = $question->source_url ?? '';
        $this->sourceCheckedAt = $question->source_checked_at?->toDateString() ?? '';
        $this->answerConfigJson = json_encode($question->answer_config ?? new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
        $this->options = $question->options->map(fn ($option): array => [
            'content' => $option->content,
            'is_correct' => $option->is_correct,
            'feedback' => $option->feedback ?? '',
        ])->all();

        if ($this->options === []) {
            $this->addOption();
            $this->addOption();
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'verticalId' => ['required', 'exists:verticals,id'],
            'taxonomyNodeId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'sourceKey' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('questions', 'source_key')
                    ->where(fn ($query) => $query->where('vertical_id', $this->verticalId))
                    ->ignore($this->editingId),
            ],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'sourceLabel' => ['nullable', 'string', 'max:255'],
            'sourceUrl' => ['nullable', 'url', 'max:2000'],
            'sourceCheckedAt' => ['nullable', 'date'],
            'answerConfigJson' => ['required', 'json'],
            'options' => ['array'],
            'options.*.content' => ['nullable', 'string'],
            'options.*.is_correct' => ['boolean'],
            'options.*.feedback' => ['nullable', 'string'],
        ]);

        /** @var array<string, mixed> $answerConfig */
        $answerConfig = json_decode($validated['answerConfigJson'], true, 512, JSON_THROW_ON_ERROR);

        /** @var User $user */
        $user = Auth::user();

        DB::transaction(function () use ($validated, $answerConfig, $user): void {
            $existing = $this->editingId ? Question::query()->findOrFail($this->editingId) : null;

            $question = Question::query()->updateOrCreate(
                ['id' => $this->editingId],
                [
                    'vertical_id' => $validated['verticalId'],
                    'taxonomy_node_id' => $validated['taxonomyNodeId'],
                    'source_key' => trim($validated['sourceKey']) !== '' ? trim($validated['sourceKey']) : null,
                    'type' => $validated['type'],
                    'status' => $existing === null ? PublicationStatus::Draft : $existing->status,
                    'prompt' => $validated['prompt'],
                    'explanation' => trim($validated['explanation']) !== '' ? $validated['explanation'] : null,
                    'difficulty' => $validated['difficulty'],
                    'source_label' => trim($validated['sourceLabel']) !== '' ? $validated['sourceLabel'] : null,
                    'source_url' => trim($validated['sourceUrl']) !== '' ? $validated['sourceUrl'] : null,
                    'source_checked_at' => trim($validated['sourceCheckedAt']) !== '' ? $validated['sourceCheckedAt'] : null,
                    'answer_config' => $answerConfig,
                    'created_by' => $existing === null ? $user->id : $existing->created_by,
                    'updated_by' => $user->id,
                ],
            );

            $question->options()->delete();

            foreach (array_values($this->options) as $position => $option) {
                $content = trim($option['content']);

                if ($content === '') {
                    continue;
                }

                $question->options()->create([
                    'content' => $content,
                    'is_correct' => (bool) $option['is_correct'],
                    'position' => $position,
                    'feedback' => trim($option['feedback']) !== '' ? $option['feedback'] : null,
                ]);
            }
        });

        $this->resetForm();
        session()->flash('admin_message', 'Întrebarea a fost salvată.');
    }

    public function submitForReview(int $id, EditorialWorkflow $workflow): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workflow->submitForReview(Question::query()->findOrFail($id), $user);
        session()->flash('admin_message', 'Întrebarea a fost trimisă la review.');
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->type = 'single_choice';
        $this->difficulty = 3;
        $this->answerConfigJson = '{}';
        $this->options = [];
        $this->addOption();
        $this->addOption();
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.question-manager', [
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'taxonomyNodes' => TaxonomyNode::query()
                ->when($this->verticalId, fn ($query) => $query->where('vertical_id', $this->verticalId))
                ->orderBy('name')->get(),
            'questions' => Question::query()->with(['vertical', 'taxonomyNode'])->orderByDesc('id')->paginate(50),
            'types' => QuestionType::cases(),
        ]);
    }
}
