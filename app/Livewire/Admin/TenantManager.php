<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TenantManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public bool $isActive = true;

    public string $domainsText = '';

    public string $siteName = '';

    public string $logoUrl = '';

    public string $faviconUrl = '';

    public string $primaryColor = '#4f46e5';

    public string $tagline = '';

    public string $footerText = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    /** @var array<int, int|string> */
    public array $selectedVerticals = [];

    public function edit(int $id): void
    {
        $tenant = Tenant::query()->with(['domains', 'verticals'])->findOrFail($id);
        $branding = $tenant->branding ?? [];

        $this->editingId = $tenant->id;
        $this->name = $tenant->name;
        $this->slug = $tenant->slug;
        $this->isActive = $tenant->is_active;
        $this->domainsText = $tenant->domains
            ->sortByDesc('is_primary')
            ->pluck('host')
            ->implode("\n");
        $this->siteName = (string) ($branding['site_name'] ?? '');
        $this->logoUrl = (string) ($branding['logo_url'] ?? '');
        $this->faviconUrl = (string) ($branding['favicon_url'] ?? '');
        $this->primaryColor = (string) ($branding['primary_color'] ?? '#4f46e5');
        $this->tagline = (string) ($branding['tagline'] ?? '');
        $this->footerText = (string) ($branding['footer_text'] ?? '');
        $this->seoTitle = (string) ($branding['seo_title'] ?? '');
        $this->seoDescription = (string) ($branding['seo_description'] ?? '');
        $this->selectedVerticals = $tenant->verticals->modelKeys();
    }

    public function save(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:120', Rule::unique('tenants', 'slug')->ignore($this->editingId)],
            'isActive' => ['boolean'],
            'domainsText' => ['required', 'string', 'max:4000'],
            'siteName' => ['required', 'string', 'max:160'],
            'logoUrl' => ['nullable', 'url', 'max:2048'],
            'faviconUrl' => ['nullable', 'url', 'max:2048'],
            'primaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'footerText' => ['nullable', 'string', 'max:1000'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
            'selectedVerticals' => ['array'],
            'selectedVerticals.*' => ['integer', 'exists:verticals,id'],
        ]);

        $domains = collect(preg_split('/\R+/', $validated['domainsText']) ?: [])
            ->map(static fn (string $host): string => mb_strtolower(rtrim(trim($host), '.')))
            ->filter()
            ->unique()
            ->values();

        if ($domains->isEmpty()) {
            $this->addError('domainsText', 'Adaugă cel puțin un domeniu.');

            return;
        }

        foreach ($domains as $host) {
            if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/', $host)) {
                $this->addError('domainsText', 'Domeniu invalid: '.$host);

                return;
            }

            $conflict = TenantDomain::query()
                ->where('host', $host)
                ->when($this->editingId, fn ($query) => $query->where('tenant_id', '!=', $this->editingId))
                ->exists();

            if ($conflict) {
                $this->addError('domainsText', 'Domeniul este deja folosit: '.$host);

                return;
            }
        }

        DB::transaction(function () use ($validated, $domains): void {
            $tenant = Tenant::query()->updateOrCreate(
                ['id' => $this->editingId],
                [
                    'name' => $validated['name'],
                    'slug' => $validated['slug'],
                    'is_active' => $validated['isActive'],
                    'branding' => [
                        'site_name' => $validated['siteName'],
                        'logo_url' => $this->nullable($validated['logoUrl']),
                        'favicon_url' => $this->nullable($validated['faviconUrl']),
                        'primary_color' => $validated['primaryColor'],
                        'tagline' => $this->nullable($validated['tagline']),
                        'footer_text' => $this->nullable($validated['footerText']),
                        'seo_title' => $this->nullable($validated['seoTitle']),
                        'seo_description' => $this->nullable($validated['seoDescription']),
                    ],
                ],
            );

            $tenant->verticals()->sync(array_values(array_unique(array_map('intval', $validated['selectedVerticals']))));

            $tenant->domains()->whereNotIn('host', $domains->all())->delete();

            foreach ($domains as $index => $host) {
                $tenant->domains()->updateOrCreate(
                    ['host' => $host],
                    [
                        'is_primary' => $index === 0,
                        'is_active' => true,
                    ],
                );
            }
        });

        $this->resetForm();
        session()->flash('admin_message', 'Tenantul white-label a fost salvat.');
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->isActive = true;
        $this->primaryColor = '#4f46e5';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.tenant-manager', [
            'tenants' => Tenant::query()
                ->with(['domains', 'verticals'])
                ->orderBy('name')
                ->get(),
            'verticals' => Vertical::query()->orderBy('name')->get(),
        ]);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
