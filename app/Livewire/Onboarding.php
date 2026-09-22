<?php

namespace App\Livewire;

use App\Models\TaxonomyNode;
use App\Models\Vertical;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Primii pași după creare de cont.
 *
 * Două întrebări, atât: ce pregătești și cât mai ai. Răspunsurile ajung în
 * `users.preferences` și servesc la recomandări. Pasul poate fi sărit —
 * un onboarding obligatoriu care stă între om și primul test costă mai mult
 * decât câștigă.
 */
#[Layout('layouts.app')]
class Onboarding extends Component
{
    /** Pasul curent: 1 = ce examen, 2 = orizont de timp. */
    public int $step = 1;

    /** @var array<int, int> */
    public array $selectedNodes = [];

    /** @var array<int, int> */
    public array $selectedVerticals = [];

    public string $horizon = '';

    public string $search = '';

    public function toggleNode(int $nodeId): void
    {
        $this->selectedNodes = in_array($nodeId, $this->selectedNodes, true)
            ? array_values(array_diff($this->selectedNodes, [$nodeId]))
            : [...$this->selectedNodes, $nodeId];
    }

    public function toggleVertical(int $verticalId): void
    {
        $this->selectedVerticals = in_array($verticalId, $this->selectedVerticals, true)
            ? array_values(array_diff($this->selectedVerticals, [$verticalId]))
            : [...$this->selectedVerticals, $verticalId];
    }

    public function hasSelection(): bool
    {
        return $this->selectedNodes !== [] || $this->selectedVerticals !== [];
    }

    public function goToSecondStep(): void
    {
        if (! $this->hasSelection()) {
            return;
        }

        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = 1;
    }

    public function finish(): void
    {
        $user = Auth::user();

        $preferences = $user->preferences ?? [];
        $preferences['onboarding'] = [
            'vertical_ids' => array_values($this->selectedVerticals),
            'node_ids' => array_values($this->selectedNodes),
            'horizon' => $this->horizon !== '' ? $this->horizon : null,
            'completed_at' => now()->toIso8601String(),
        ];

        $user->forceFill(['preferences' => $preferences])->save();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function skip(): void
    {
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        $allowedVerticalIds = app(TenantContext::class)->allowedVerticalIds();

        $verticals = Vertical::query()
            ->active()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $nodes = TaxonomyNode::query()
            ->active()
            ->whereNull('parent_id')
            ->whereIn('vertical_id', $verticals->pluck('id'))
            ->when($this->search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.onboarding', [
            // Verticalele fără examene definite rămân selectabile ele însele,
            // altfel un domeniu nou ar fi invizibil aici.
            'options' => $this->options($verticals, $nodes),
            'seoTitle' => 'Personalizează-ți contul',
            'robots' => 'noindex,nofollow',
        ]);
    }

    /**
     * @param  Collection<int, Vertical>  $verticals
     * @param  Collection<int, TaxonomyNode>  $nodes
     * @return array<int, array{kind: string, id: int, name: string, vertical: Vertical, selected: bool}>
     */
    private function options(Collection $verticals, Collection $nodes): array
    {
        $options = [];

        foreach ($verticals as $vertical) {
            $verticalNodes = $nodes->where('vertical_id', $vertical->id);

            if ($verticalNodes->isEmpty()) {
                if ($this->search !== '' && ! str_contains(mb_strtolower($vertical->name), mb_strtolower($this->search))) {
                    continue;
                }

                $options[] = [
                    'kind' => 'vertical',
                    'id' => $vertical->id,
                    'name' => $vertical->name,
                    'vertical' => $vertical,
                    'selected' => in_array($vertical->id, $this->selectedVerticals, true),
                ];

                continue;
            }

            foreach ($verticalNodes as $node) {
                $options[] = [
                    'kind' => 'node',
                    'id' => $node->id,
                    'name' => $node->name,
                    'vertical' => $vertical,
                    'selected' => in_array($node->id, $this->selectedNodes, true),
                ];
            }
        }

        return $options;
    }
}
