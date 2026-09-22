<div class="grid gap-8 xl:grid-cols-[500px_1fr]">
    <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Monetizare</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ $editingId ? 'Editează campania' : 'Lead campaign nou' }}</h1>

        <div class="mt-5 space-y-4">
            <div><label class="text-sm font-semibold">Nume intern</label><input wire:model="name" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Titlu public</label><input wire:model="title" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Descriere</label><textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">CTA</label><input wire:model="ctaLabel" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div class="flex flex-wrap gap-4 text-sm font-semibold"><label class="flex gap-2"><input type="checkbox" wire:model="requestName"> Cere nume</label><label class="flex gap-2"><input type="checkbox" wire:model="requestPhone"> Cere telefon</label></div>
            <div><label class="text-sm font-semibold">Text consimțământ</label><textarea wire:model="consentText" rows="4" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea>@error('consentText')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
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

        <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>@if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
    </form>

    <div class="space-y-8">
        <section>
            <h2 class="text-2xl font-extrabold">Campanii</h2>
            <div class="mt-4 space-y-3">
                @foreach($campaigns as $campaign)
                    <button wire:click="edit({{ $campaign->id }})" class="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left dark:border-white/10 dark:bg-white/5">
                        <div class="flex justify-between gap-4"><div><strong>{{ $campaign->name }}</strong><p class="mt-1 text-sm text-slate-500">{{ $campaign->title }}</p></div><span class="text-xs font-bold {{ $campaign->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $campaign->submissions_count }} lead-uri</span></div>
                        <p class="mt-2 text-xs text-slate-500">Target: {{ $campaign->test?->title ?? $campaign->taxonomyNode?->name ?? $campaign->vertical?->name ?? 'Global' }}</p>
                    </button>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-extrabold">Ultimele lead-uri</h2>
            <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
                @forelse($submissions as $lead)
                    <div class="border-b border-slate-100 p-5 last:border-0 dark:border-white/5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div><p class="font-extrabold">{{ $lead->name ?: $lead->email }}</p><p class="text-sm text-slate-500">{{ $lead->email }} @if($lead->phone) · {{ $lead->phone }} @endif</p><p class="mt-1 text-xs text-slate-400">{{ $lead->campaign->name }} · {{ $lead->created_at->format('d.m.Y H:i') }}</p></div>
                            <select wire:change="setSubmissionStatus({{ $lead->id }}, $event.target.value)" class="rounded-lg border px-2 py-1 text-xs dark:border-white/15 dark:bg-transparent">
                                @foreach(['new' => 'Nou', 'contacted' => 'Contactat', 'qualified' => 'Calificat', 'closed' => 'Închis'] as $value => $label)<option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-sm text-slate-500">Nu există lead-uri.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
