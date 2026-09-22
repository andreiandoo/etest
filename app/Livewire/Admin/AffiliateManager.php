<?php

namespace App\Livewire\Admin;

use App\Models\AffiliateMerchant;
use App\Models\AffiliateResource;
use App\Models\MonetizationClick;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class AffiliateManager extends Component
{
    public ?int $editingMerchantId = null;

    public string $merchantName = '';

    public string $merchantWebsiteUrl = '';

    public bool $merchantActive = true;

    public ?int $editingResourceId = null;

    public ?int $merchantId = null;

    public string $title = '';

    public string $description = '';

    public string $resourceType = 'book';

    public string $affiliateUrl = '';

    public string $imageUrl = '';

    public string $priceLabel = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public int $priority = 0;

    public bool $isActive = true;

    public ?int $targetVerticalId = null;

    public ?int $targetTaxonomyNodeId = null;

    public ?int $targetTestId = null;

    public function editMerchant(int $id): void
    {
        $merchant = AffiliateMerchant::query()->findOrFail($id);

        $this->editingMerchantId = $merchant->id;
        $this->merchantName = $merchant->name;
        $this->merchantWebsiteUrl = $merchant->website_url ?? '';
        $this->merchantActive = $merchant->is_active;
    }

    public function saveMerchant(): void
    {
        $validated = $this->validate([
            'merchantName' => ['required', 'string', 'max:160'],
            'merchantWebsiteUrl' => ['nullable', 'url', 'max:2048'],
            'merchantActive' => ['boolean'],
        ]);

        AffiliateMerchant::query()->updateOrCreate(
            ['id' => $this->editingMerchantId],
            [
                'name' => $validated['merchantName'],
                'website_url' => $this->nullable($validated['merchantWebsiteUrl']),
                'is_active' => $validated['merchantActive'],
            ],
        );

        $this->resetMerchantForm();
        session()->flash('admin_message', 'Comerciantul afiliat a fost salvat.');
    }

    public function editResource(int $id): void
    {
        $resource = AffiliateResource::query()->findOrFail($id);

        $this->editingResourceId = $resource->id;
        $this->merchantId = $resource->affiliate_merchant_id;
        $this->title = $resource->title;
        $this->description = $resource->description ?? '';
        $this->resourceType = $resource->resource_type;
        $this->affiliateUrl = $resource->affiliate_url;
        $this->imageUrl = $resource->image_url ?? '';
        $this->priceLabel = $resource->price_label ?? '';
        $this->startsAt = $resource->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $resource->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->priority = $resource->priority;
        $this->isActive = $resource->is_active;
        $this->targetVerticalId = $resource->vertical_id;
        $this->targetTaxonomyNodeId = $resource->taxonomy_node_id;
        $this->targetTestId = $resource->test_id;
    }

    public function saveResource(): void
    {
        $validated = $this->validate([
            'merchantId' => ['required', 'exists:affiliate_merchants,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'resourceType' => ['required', 'string', 'max:40'],
            'affiliateUrl' => ['required', 'url', 'max:2048'],
            'imageUrl' => ['nullable', 'url', 'max:2048'],
            'priceLabel' => ['nullable', 'string', 'max:100'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'priority' => ['integer', 'min:0', 'max:100000'],
            'isActive' => ['boolean'],
            'targetVerticalId' => ['nullable', 'exists:verticals,id'],
            'targetTaxonomyNodeId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'targetTestId' => ['nullable', 'exists:tests,id'],
        ]);

        [$verticalId, $taxonomyNodeId, $testId] = $this->exclusiveTarget();

        AffiliateResource::query()->updateOrCreate(
            ['id' => $this->editingResourceId],
            [
                'affiliate_merchant_id' => $validated['merchantId'],
                'vertical_id' => $verticalId,
                'taxonomy_node_id' => $taxonomyNodeId,
                'test_id' => $testId,
                'title' => $validated['title'],
                'description' => $this->nullable($validated['description']),
                'resource_type' => $validated['resourceType'],
                'affiliate_url' => $validated['affiliateUrl'],
                'image_url' => $this->nullable($validated['imageUrl']),
                'price_label' => $this->nullable($validated['priceLabel']),
                'starts_at' => $this->nullable($validated['startsAt']),
                'ends_at' => $this->nullable($validated['endsAt']),
                'priority' => $validated['priority'],
                'is_active' => $validated['isActive'],
            ],
        );

        $this->resetResourceForm();
        session()->flash('admin_message', 'Resursa afiliată a fost salvată.');
    }

    public function resetMerchantForm(): void
    {
        $this->reset(['editingMerchantId', 'merchantName', 'merchantWebsiteUrl']);
        $this->merchantActive = true;
        $this->resetValidation();
    }

    public function resetResourceForm(): void
    {
        $this->reset([
            'editingResourceId',
            'merchantId',
            'title',
            'description',
            'affiliateUrl',
            'imageUrl',
            'priceLabel',
            'startsAt',
            'endsAt',
            'targetVerticalId',
            'targetTaxonomyNodeId',
            'targetTestId',
        ]);
        $this->resourceType = 'book';
        $this->priority = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.affiliate-manager', [
            'merchants' => AffiliateMerchant::query()->orderBy('name')->get(),
            'resources' => AffiliateResource::query()
                ->with(['merchant', 'vertical', 'taxonomyNode', 'test'])
                ->orderByDesc('id')
                ->get(),
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'taxonomyNodes' => TaxonomyNode::query()->with('vertical')->orderBy('name')->get(),
            'tests' => TestDefinition::query()->with('vertical')->orderBy('title')->get(),
            'clicks' => MonetizationClick::query()->where('channel', 'affiliate')->count(),
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
