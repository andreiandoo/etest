<?php

namespace App\Livewire\Admin;

use App\Enums\TaxonomyNodeType;
use App\Models\TaxonomyNode;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TaxonomyManager extends Component
{
    public ?int $editingId = null;

    public ?int $verticalId = null;

    public ?int $parentId = null;

    public string $type = 'subject';

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public function edit(int $id): void
    {
        $node = TaxonomyNode::query()->findOrFail($id);
        $this->editingId = $node->id;
        $this->verticalId = $node->vertical_id;
        $this->parentId = $node->parent_id;
        $this->type = $node->type->value;
        $this->name = $node->name;
        $this->slug = $node->slug;
        $this->description = $node->description ?? '';
        $this->seoTitle = $node->seo_title ?? '';
        $this->seoDescription = $node->seo_description ?? '';
        $this->sortOrder = $node->sort_order;
        $this->isActive = $node->is_active;
    }

    public function save(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }

        $validated = $this->validate([
            'verticalId' => ['required', 'exists:verticals,id'],
            'parentId' => ['nullable', 'exists:taxonomy_nodes,id'],
            'type' => ['required', Rule::enum(TaxonomyNodeType::class)],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'alpha_dash:ascii',
                'max:150',
                Rule::unique('taxonomy_nodes', 'slug')
                    ->where(fn ($query) => $query->where('vertical_id', $this->verticalId))
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
            'sortOrder' => ['integer', 'min:0', 'max:32767'],
            'isActive' => ['boolean'],
        ]);

        if ($validated['parentId'] !== null) {
            $parent = TaxonomyNode::query()->findOrFail($validated['parentId']);

            abort_unless($parent->vertical_id === $validated['verticalId'] && $parent->id !== $this->editingId, 422);
        }

        TaxonomyNode::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'vertical_id' => $validated['verticalId'],
                'parent_id' => $validated['parentId'],
                'type' => $validated['type'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => trim($validated['description']) !== '' ? $validated['description'] : null,
                'seo_title' => trim($validated['seoTitle']) !== '' ? $validated['seoTitle'] : null,
                'seo_description' => trim($validated['seoDescription']) !== '' ? $validated['seoDescription'] : null,
                'sort_order' => $validated['sortOrder'],
                'is_active' => $validated['isActive'],
            ],
        );

        $this->resetForm();
        session()->flash('admin_message', 'Taxonomia a fost salvată.');
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->type = 'subject';
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.taxonomy-manager', [
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'nodes' => TaxonomyNode::query()->with(['vertical', 'parent'])->orderBy('vertical_id')->orderBy('sort_order')->orderBy('name')->get(),
            'types' => TaxonomyNodeType::cases(),
        ]);
    }
}
