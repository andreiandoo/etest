<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Monetizare</p>
            <h1 class="mt-2 text-4xl font-extrabold">Afiliere</h1>
        </div>
        <p class="text-sm font-bold text-slate-500">{{ $clicks }} click-uri urmărite</p>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
        <form wire:submit="saveMerchant" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">{{ $editingMerchantId ? 'Editează comerciantul' : 'Comerciant nou' }}</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Nume</label><input wire:model="merchantName" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('merchantName')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-semibold">Website</label><input wire:model="merchantWebsiteUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="merchantActive"> Activ</label>
            </div>
            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>@if($editingMerchantId)<button type="button" wire:click="resetMerchantForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">Comercianți</h2>
            <div class="mt-4 space-y-2">
                @forelse($merchants as $merchant)
                    <button wire:click="editMerchant({{ $merchant->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-left dark:border-white/5">
                        <strong>{{ $merchant->name }}</strong>
                        <span class="text-xs font-bold {{ $merchant->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $merchant->is_active ? 'ACTIV' : 'INACTIV' }}</span>
                    </button>
                @empty
                    <p class="text-sm text-slate-500">Niciun comerciant configurat.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-[520px_1fr]">
        <form wire:submit="saveResource" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">{{ $editingResourceId ? 'Editează resursa' : 'Resursă afiliată nouă' }}</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Comerciant</label><select wire:model="merchantId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Alege</option>@foreach($merchants as $merchant)<option value="{{ $merchant->id }}">{{ $merchant->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Titlu</label><input wire:model="title" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Descriere</label><textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="text-sm font-semibold">Tip</label><input wire:model="resourceType" placeholder="book / course / tool" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div><div><label class="text-sm font-semibold">Preț afișat</label><input wire:model="priceLabel" placeholder="ex. de la 59 lei" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div></div>
                <div><label class="text-sm font-semibold">URL afiliat</label><input wire:model="affiliateUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Imagine URL</label><input wire:model="imageUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="text-sm font-semibold">Start</label><input type="datetime-local" wire:model="startsAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div><div><label class="text-sm font-semibold">Final</label><input type="datetime-local" wire:model="endsAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div></div>
                <div><label class="text-sm font-semibold">Prioritate</label><input type="number" wire:model="priority" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                    <p class="text-sm font-extrabold">Targetare</p>
                    <div class="mt-3 space-y-3">
                        <select wire:model="targetVerticalId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Global</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select>
                        <select wire:model="targetTaxonomyNodeId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Fără taxonomie</option>@foreach($taxonomyNodes as $node)<option value="{{ $node->id }}">{{ $node->vertical->name }} — {{ $node->name }}</option>@endforeach</select>
                        <select wire:model="targetTestId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Fără test</option>@foreach($tests as $test)<option value="{{ $test->id }}">{{ $test->vertical->name }} — {{ $test->title }}</option>@endforeach</select>
                    </div>
                </div>
                <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="isActive"> Activă</label>
            </div>
            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează resursa</button>@if($editingResourceId)<button type="button" wire:click="resetResourceForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div>
            <h2 class="text-2xl font-extrabold">Resurse</h2>
            <div class="mt-4 space-y-3">
                @forelse($resources as $resource)
                    <button wire:click="editResource({{ $resource->id }})" class="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold text-indigo-600">{{ $resource->merchant->name }} · {{ $resource->resource_type }}</p><p class="mt-1 font-extrabold">{{ $resource->title }}</p></div><span class="text-xs font-bold {{ $resource->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $resource->is_active ? 'ACTIVĂ' : 'INACTIVĂ' }}</span></div>
                        <p class="mt-2 text-xs text-slate-500">Target: {{ $resource->test?->title ?? $resource->taxonomyNode?->name ?? $resource->vertical?->name ?? 'Global' }} · prioritate {{ $resource->priority }}</p>
                    </button>
                @empty
                    <p class="text-sm text-slate-500">Nicio resursă afiliată.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
