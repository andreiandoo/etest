<?php

namespace App\Livewire\Admin;

use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class VerticalManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public bool $isActive = true;

    public int $sortOrder = 0;

    public string $seoTitle = '';

    public string $seoDescription = '';

    public function edit(int $id): void
    {
        $vertical = Vertical::query()->findOrFail($id);
        $this->editingId = $vertical->id;
        $this->name = $vertical->name;
        $this->slug = $vertical->slug;
        $this->description = $vertical->description ?? '';
        $this->isActive = $vertical->is_active;
        $this->sortOrder = $vertical->sort_order;
        $this->seoTitle = $vertical->seo_title ?? '';
        $this->seoDescription = $vertical->seo_description ?? '';
    }

    public function save(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:120', Rule::unique('verticals', 'slug')->ignore($this->editingId)],
            'description' => ['nullable', 'string'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:32767'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
        ]);

        Vertical::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $this->nullable($validated['description']),
                'is_active' => $validated['isActive'],
                'sort_order' => $validated['sortOrder'],
                'seo_title' => $this->nullable($validated['seoTitle']),
                'seo_description' => $this->nullable($validated['seoDescription']),
            ],
        );

        $this->resetForm();
        session()->flash('admin_message', 'Verticala a fost salvată.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'description', 'seoTitle', 'seoDescription']);
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.vertical-manager', [
            'verticals' => Vertical::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
