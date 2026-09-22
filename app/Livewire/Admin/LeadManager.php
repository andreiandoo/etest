<?php

namespace App\Livewire\Admin;

use App\Models\LeadCampaign;
use App\Models\LeadSubmission;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class LeadManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $title = '';

    public string $description = '';

    public string $ctaLabel = 'Solicită informații';

    public bool $requestName = true;

    public bool $requestPhone = false;

    public string $consentText = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public int $priority = 0;

    public bool $isActive = true;

    public ?int $targetVerticalId = null;

    public ?int $targetTaxonomyNodeId = null;

    public ?int $targetTestId = null;

    public function edit(int $id): void
    {
        $campaign = LeadCampaign::query()->findOrFail($id);
        $fields = $campaign->requested_fields ?? [];

        $this->editingId = $campaign->id;
        $this->name = $campaign->name;
        $this->title = $campaign->title;
        $this->description = $campaign->description ?? '';
        $this->ctaLabel = $campaign->cta_label;
        $this->requestName = in_array('name', $fields, true);
        $this->requestPhone = in_array('phone', $fields, true);
        $this->consentText = $campaign->consent_text;
        $this->startsAt = $campaign->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $campaign->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->priority = $campaign->priority;
        $this->isActive = $campaign->is_active;
        $this->targetVerticalId = $campaign->vertical_id;
        $this->targetTaxonomyNodeId = $campaign->taxonomy_node_id;
        $this->targetTestId = $campaign->test_id;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ctaLabel' => ['required', 'string', 'max:80'],
            'consentText' => ['required', 'string', 'min:20', 'max:2000'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'priority' => ['integer', 'min:0', 'max:100000'],
            'isActive' => ['boolean'],
            'targetVerticalId' => ['nullable', 'exists:verticals,id'],
            'targetTaxonomyNodeId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'targetTestId' => ['nullable', 'exists:tests,id'],
        ]);

        [$verticalId, $taxonomyNodeId, $testId] = $this->exclusiveTarget();

        LeadCampaign::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'vertical_id' => $verticalId,
                'taxonomy_node_id' => $taxonomyNodeId,
                'test_id' => $testId,
                'name' => $validated['name'],
                'title' => $validated['title'],
                'description' => $this->nullable($validated['description']),
                'cta_label' => $validated['ctaLabel'],
                'requested_fields' => array_values(array_filter([
                    $this->requestName ? 'name' : null,
                    $this->requestPhone ? 'phone' : null,
                ])),
                'consent_text' => $validated['consentText'],
                'starts_at' => $this->nullable($validated['startsAt']),
                'ends_at' => $this->nullable($validated['endsAt']),
                'priority' => $validated['priority'],
                'is_active' => $validated['isActive'],
            ],
        );

        $this->resetForm();
        session()->flash('admin_message', 'Campania de lead generation a fost salvată.');
    }

    public function setSubmissionStatus(int $id, string $status): void
    {
        if (! in_array($status, ['new', 'contacted', 'qualified', 'closed'], true)) {
            return;
        }

        LeadSubmission::query()->findOrFail($id)->update(['status' => $status]);
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->ctaLabel = 'Solicită informații';
        $this->requestName = true;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.lead-manager', [
            'campaigns' => LeadCampaign::query()
                ->with(['vertical', 'taxonomyNode', 'test'])
                ->withCount('submissions')
                ->orderByDesc('id')
                ->get(),
            'submissions' => LeadSubmission::query()
                ->with('campaign')
                ->latest()
                ->limit(100)
                ->get(),
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'taxonomyNodes' => TaxonomyNode::query()->with('vertical')->orderBy('name')->get(),
            'tests' => TestDefinition::query()->with('vertical')->orderBy('title')->get(),
        ]);
    }

    /**
     * @return array{0:?int,1:?int,2:?int}
     */
    private function exclusiveTarget(): array
    {
        if ($this->targetTestId !== null) {
            return [null, null, $this->targetTestId];
        }

        if ($this->targetTaxonomyNodeId !== null) {
            return [null, $this->targetTaxonomyNodeId, null];
        }

        if ($this->targetVerticalId !== null) {
            return [$this->targetVerticalId, null, null];
        }

        return [null, null, null];
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
