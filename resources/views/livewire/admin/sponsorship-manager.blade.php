<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Monetizare</p>
            <h1 class="mt-2 text-4xl font-extrabold">Sponsorizări</h1>
        </div>
        <p class="text-sm font-bold text-slate-500">{{ $clicks }} click-uri urmărite</p>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
        <form wire:submit="saveSponsor" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">{{ $editingSponsorId ? 'Editează sponsorul' : 'Sponsor nou' }}</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Nume</label><input wire:model="sponsorName" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('sponsorName')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-semibold">Website</label><input wire:model="sponsorWebsiteUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Logo URL</label><input wire:model="sponsorLogoUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="sponsorActive"> Activ</label>
            </div>
            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>@if($editingSponsorId)<button type="button" wire:click="resetSponsorForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">Sponsori</h2>
            <div class="mt-4 space-y-2">
                @forelse($sponsors as $sponsor)
                    <button wire:click="editSponsor({{ $sponsor->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-left dark:border-white/5">
                        <strong>{{ $sponsor->name }}</strong>
                        <span class="text-xs font-bold {{ $sponsor->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $sponsor->is_active ? 'ACTIV' : 'INACTIV' }}</span>
                    </button>
                @empty
                    <p class="text-sm text-slate-500">Niciun sponsor configurat.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-[520px_1fr]">
        <form wire:submit="saveCampaign" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">{{ $editingCampaignId ? 'Editează campania' : 'Campanie nouă' }}</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Sponsor</label><select wire:model="sponsorId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Alege</option>@foreach($sponsors as $sponsor)<option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Nume intern</label><input wire:model="campaignName" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Headline</label><input wire:model="headline" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Text</label><textarea wire:model="body" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="text-sm font-semibold">CTA</label><input wire:model="ctaLabel" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div><div><label class="text-sm font-semibold">Disclosure</label><input wire:model="disclosureLabel" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div></div>
                <div><label class="text-sm font-semibold">URL destinație</label><input wire:model="ctaUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="text-sm font-semibold">Start</label><input type="datetime-local" wire:model="startsAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div><div><label class="text-sm font-semibold">Final</label><input type="datetime-local" wire:model="endsAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div></div>
                <div><label class="text-sm font-semibold">Prioritate</label><input type="number" wire:model="priority" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                    <p class="text-sm font-extrabold">Targetare</p><p class="mt-1 text-xs text-slate-500">Ordinea este: test → taxonomie → verticală → global.</p>
                    <div class="mt-3 space-y-3">
                        <select wire:model="targetVerticalId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Global / orice verticală</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select>
                        <select wire:model="targetTaxonomyNodeId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Fără target taxonomic</option>@foreach($taxonomyNodes as $node)<option value="{{ $node->id }}">{{ $node->vertical->name }} — {{ $node->name }}</option>@endforeach</select>
                        <select wire:model="targetTestId" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Fără target test</option>@foreach($tests as $test)<option value="{{ $test->id }}">{{ $test->vertical->name }} — {{ $test->title }}</option>@endforeach</select>
                    </div>
                </div>
                <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="campaignActive"> Campanie activă</label>
            </div>
            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează campania</button>@if($editingCampaignId)<button type="button" wire:click="resetCampaignForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div>
            <h2 class="text-2xl font-extrabold">Campanii</h2>
            <div class="mt-4 space-y-3">
                @forelse($campaigns as $campaign)
                    @php($placement = $campaign->placements->first())
                    <button wire:click="editCampaign({{ $campaign->id }})" class="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold text-indigo-600">{{ $campaign->sponsor->name }}</p><p class="mt-1 font-extrabold">{{ $campaign->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $campaign->headline }}</p></div><span class="text-xs font-bold {{ $campaign->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $campaign->is_active ? 'ACTIVĂ' : 'INACTIVĂ' }}</span></div>
                        <p class="mt-3 text-xs text-slate-500">Target: {{ $placement?->test?->title ?? $placement?->taxonomyNode?->name ?? $placement?->vertical?->name ?? 'Global' }} · prioritate {{ $campaign->priority }}</p>
                    </button>
                @empty
                    <p class="text-sm text-slate-500">Nicio campanie.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
