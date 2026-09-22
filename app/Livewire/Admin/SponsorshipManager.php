<?php

namespace App\Livewire\Admin;

use App\Models\MonetizationClick;
use App\Models\Sponsor;
use App\Models\SponsorCampaign;
use App\Models\SponsorPlacement;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class SponsorshipManager extends Component
{
    public ?int $editingSponsorId = null;

    public string $sponsorName = '';

    public string $sponsorWebsiteUrl = '';

    public string $sponsorLogoUrl = '';

    public bool $sponsorActive = true;

    public ?int $editingCampaignId = null;

    public ?int $sponsorId = null;

    public string $campaignName = '';

    public string $headline = '';

    public string $body = '';

    public string $ctaLabel = 'Află mai multe';

    public string $ctaUrl = '';

    public string $disclosureLabel = 'Conținut sponsorizat';

    public string $startsAt = '';

    public string $endsAt = '';

    public int $priority = 0;

    public bool $campaignActive = true;

    public ?int $targetVerticalId = null;

    public ?int $targetTaxonomyNodeId = null;

    public ?int $targetTestId = null;

    public function editSponsor(int $id): void
    {
        $sponsor = Sponsor::query()->findOrFail($id);
        $this->editingSponsorId = $sponsor->id;
        $this->sponsorName = $sponsor->name;
        $this->sponsorWebsiteUrl = $sponsor->website_url ?? '';
        $this->sponsorLogoUrl = $sponsor->logo_url ?? '';
        $this->sponsorActive = $sponsor->is_active;
    }

    public function saveSponsor(): void
    {
        $validated = $this->validate([
            'sponsorName' => ['required', 'string', 'max:160'],
            'sponsorWebsiteUrl' => ['nullable', 'url', 'max:2048'],
            'sponsorLogoUrl' => ['nullable', 'url', 'max:2048'],
            'sponsorActive' => ['boolean'],
        ]);

        Sponsor::query()->updateOrCreate(
            ['id' => $this->editingSponsorId],
            [
                'name' => $validated['sponsorName'],
                'website_url' => $this->nullable($validated['sponsorWebsiteUrl']),
                'logo_url' => $this->nullable($validated['sponsorLogoUrl']),
                'is_active' => $validated['sponsorActive'],
            ],
        );

        $this->resetSponsorForm();
        session()->flash('admin_message', 'Sponsorul a fost salvat.');
    }

    public function editCampaign(int $id): void
    {
        $campaign = SponsorCampaign::query()->with('placements')->findOrFail($id);
        $placement = $campaign->placements->first();

        $this->editingCampaignId = $campaign->id;
        $this->sponsorId = $campaign->sponsor_id;
        $this->campaignName = $campaign->name;
        $this->headline = $campaign->headline;
        $this->body = $campaign->body ?? '';
        $this->ctaLabel = $campaign->cta_label;
        $this->ctaUrl = $campaign->cta_url;
        $this->disclosureLabel = $campaign->disclosure_label;
        $this->startsAt = $campaign->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $campaign->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->priority = $campaign->priority;
        $this->campaignActive = $campaign->is_active;
        $this->targetVerticalId = $placement?->vertical_id;
        $this->targetTaxonomyNodeId = $placement?->taxonomy_node_id;
        $this->targetTestId = $placement?->test_id;
    }

    public function saveCampaign(): void
    {
        $validated = $this->validate([
            'sponsorId' => ['required', 'exists:sponsors,id'],
            'campaignName' => ['required', 'string', 'max:160'],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:2000'],
            'ctaLabel' => ['required', 'string', 'max:80'],
            'ctaUrl' => ['required', 'url', 'max:2048'],
            'disclosureLabel' => ['required', 'string', 'max:80'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'priority' => ['integer', 'min:0', 'max:100000'],
            'campaignActive' => ['boolean'],
            'targetVerticalId' => ['nullable', 'exists:verticals,id'],
            'targetTaxonomyNodeId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'targetTestId' => ['nullable', 'exists:tests,id'],
        ]);

        $campaign = SponsorCampaign::query()->updateOrCreate(
            ['id' => $this->editingCampaignId],
            [
                'sponsor_id' => $validated['sponsorId'],
                'name' => $validated['campaignName'],
                'headline' => $validated['headline'],
                'body' => $this->nullable($validated['body']),
                'cta_label' => $validated['ctaLabel'],
                'cta_url' => $validated['ctaUrl'],
                'disclosure_label' => $validated['disclosureLabel'],
                'starts_at' => $this->nullable($validated['startsAt']),
                'ends_at' => $this->nullable($validated['endsAt']),
                'priority' => $validated['priority'],
                'is_active' => $validated['campaignActive'],
            ],
        );

        [$verticalId, $taxonomyNodeId, $testId] = $this->exclusiveTarget();

        $campaign->placements()->delete();
        SponsorPlacement::create([
            'sponsor_campaign_id' => $campaign->id,
            'vertical_id' => $verticalId,
            'taxonomy_node_id' => $taxonomyNodeId,
            'test_id' => $testId,
            'placement' => 'content',
            'is_active' => true,
        ]);

        $this->resetCampaignForm();
        session()->flash('admin_message', 'Campania de sponsorizare a fost salvată.');
    }

    public function resetSponsorForm(): void
    {
        $this->reset(['editingSponsorId', 'sponsorName', 'sponsorWebsiteUrl', 'sponsorLogoUrl']);
        $this->sponsorActive = true;
        $this->resetValidation();
    }

    public function resetCampaignForm(): void
    {
        $this->reset([
            'editingCampaignId',
            'sponsorId',
            'campaignName',
            'headline',
            'body',
            'ctaUrl',
            'startsAt',
            'endsAt',
            'targetVerticalId',
            'targetTaxonomyNodeId',
            'targetTestId',
        ]);
        $this->ctaLabel = 'Află mai multe';
        $this->disclosureLabel = 'Conținut sponsorizat';
        $this->priority = 0;
        $this->campaignActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.sponsorship-manager', [
            'sponsors' => Sponsor::query()->orderBy('name')->get(),
            'campaigns' => SponsorCampaign::query()
                ->with(['sponsor', 'placements.vertical', 'placements.taxonomyNode', 'placements.test'])
                ->withCount('placements')
                ->orderByDesc('id')
                ->get(),
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'taxonomyNodes' => TaxonomyNode::query()->with('vertical')->orderBy('name')->get(),
            'tests' => TestDefinition::query()->with('vertical')->orderBy('title')->get(),
            'clicks' => MonetizationClick::query()->where('channel', 'sponsor')->count(),
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
