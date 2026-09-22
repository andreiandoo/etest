<div class="grid gap-8 xl:grid-cols-[520px_1fr]">
    <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">M7 · White-label</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ $editingId ? 'Editează tenantul' : 'Tenant nou' }}</h1>

        <div class="mt-5 space-y-4">
            <div><label class="text-sm font-semibold">Nume intern</label><input wire:model="name" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Slug</label><input wire:model="slug" placeholder="se generează automat" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('slug')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div>
                <label class="text-sm font-semibold">Domenii</label>
                <textarea wire:model="domainsText" rows="4" placeholder="teste.partener.ro&#10;www.teste.partener.ro" class="mt-1 w-full rounded-xl border px-3 py-2 font-mono text-sm dark:border-white/15 dark:bg-transparent"></textarea>
                <p class="mt-1 text-xs text-slate-500">Primul domeniu este considerat principal. Fără https://.</p>
                @error('domainsText')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <p class="font-extrabold">Branding</p>
                <div class="mt-4 space-y-3">
                    <div><label class="text-sm font-semibold">Nume public</label><input wire:model="siteName" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                    <div><label class="text-sm font-semibold">Logo URL</label><input wire:model="logoUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                    <div><label class="text-sm font-semibold">Favicon URL</label><input wire:model="faviconUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                    <div><label class="text-sm font-semibold">Culoare principală</label><input wire:model="primaryColor" placeholder="#4f46e5" class="mt-1 w-full rounded-xl border px-3 py-2 font-mono dark:border-white/15 dark:bg-transparent">@error('primaryColor')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold">Tagline homepage</label><textarea wire:model="tagline" rows="2" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                    <div><label class="text-sm font-semibold">Footer</label><textarea wire:model="footerText" rows="2" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                    <div><label class="text-sm font-semibold">SEO title homepage</label><input wire:model="seoTitle" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                    <div><label class="text-sm font-semibold">SEO description</label><textarea wire:model="seoDescription" rows="2" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                </div>
            </div>

            <div>
                <p class="text-sm font-extrabold">Verticale disponibile</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    @foreach($verticals as $vertical)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-white/10">
                            <input type="checkbox" wire:model="selectedVerticals" value="{{ $vertical->id }}">
                            {{ $vertical->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="isActive"> Tenant activ</label>
        </div>

        <div class="mt-6 flex gap-2">
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează tenantul</button>
            @if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif
        </div>
    </form>

    <div>
        <h2 class="text-2xl font-extrabold">Tenants</h2>
        <div class="mt-4 space-y-3">
            @forelse($tenants as $tenant)
                <button wire:click="edit({{ $tenant->id }})" class="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-extrabold">{{ $tenant->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $tenant->domains->sortByDesc('is_primary')->pluck('host')->implode(' · ') }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $tenant->verticals->pluck('name')->implode(', ') ?: 'Nicio verticală asignată' }}</p>
                        </div>
                        <span class="text-xs font-bold {{ $tenant->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $tenant->is_active ? 'ACTIV' : 'INACTIV' }}</span>
                    </div>
                </button>
            @empty
                <p class="text-sm text-slate-500">Nu există încă tenants white-label.</p>
            @endforelse
        </div>
    </div>
</div>
