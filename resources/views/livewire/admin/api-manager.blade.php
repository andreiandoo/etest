<div>
    <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">M7 · API</p>
    <h1 class="mt-2 text-4xl font-extrabold">API e-test.ro</h1>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5"><p class="text-sm text-slate-500">Request-uri · 30 zile</p><p class="mt-2 text-4xl font-extrabold">{{ number_format($usage30Days['requests']) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5"><p class="text-sm text-slate-500">Erori · 30 zile</p><p class="mt-2 text-4xl font-extrabold">{{ number_format($usage30Days['errors']) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5"><p class="text-sm text-slate-500">Trafic răspuns · 30 zile</p><p class="mt-2 text-4xl font-extrabold">{{ number_format($usage30Days['bytes'] / 1024 / 1024, 1) }} MB</p></div>
    </div>

    @if($newPlainTextKey)
        <div class="mt-8 rounded-3xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-400/30 dark:bg-amber-500/10">
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">Cheie nouă · afișată o singură dată</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <code class="min-w-0 flex-1 break-all rounded-xl bg-white px-4 py-3 text-sm dark:bg-slate-950">{{ $newPlainTextKey }}</code>
                <button type="button" wire:click="clearPlainTextKey" class="rounded-xl border border-amber-300 px-4 py-2 text-sm font-bold">Am copiat-o</button>
            </div>
        </div>
    @endif

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
        <form wire:submit="saveClient" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">{{ $editingClientId ? 'Editează clientul' : 'Client API nou' }}</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Nume</label><input wire:model="clientName" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Email contact</label><input wire:model="contactEmail" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Tenant</label><select wire:model="tenantId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Acces platformă globală</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">Un client legat de tenant vede doar verticalele tenantului.</p></div>
                <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="clientActive"> Activ</label>
            </div>
            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează client</button>@if($editingClientId)<button type="button" wire:click="resetClientForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">Clienți API</h2>
            <div class="mt-4 space-y-2">
                @forelse($clients as $client)
                    <button wire:click="editClient({{ $client->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-left dark:border-white/5">
                        <span><strong>{{ $client->name }}</strong><span class="ml-2 text-xs text-slate-500">{{ $client->tenant?->name ?? 'Global' }}</span></span>
                        <span class="text-xs font-bold {{ $client->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $client->is_active ? 'ACTIV' : 'INACTIV' }}</span>
                    </button>
                @empty
                    <p class="text-sm text-slate-500">Niciun client API.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-[520px_1fr]">
        <form wire:submit="issueKey" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-2xl font-extrabold">Emite cheie</h2>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Client</label><select wire:model="keyClientId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><option value="">Alege</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Nume cheie</label><input wire:model="keyName" placeholder="Production / Backend / Partner sync" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>

                <div>
                    <p class="text-sm font-extrabold">Scopes</p>
                    <div class="mt-2 space-y-2">
                        @foreach($scopeOptions as $scope)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 font-mono text-sm dark:border-white/10"><input type="checkbox" wire:model="selectedScopes" value="{{ $scope }}">{{ $scope }}</label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500"><code>answers:read</code> expune answer_config și corectitudinea opțiunilor; fără acest scope răspunsurile corecte nu ies prin API.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="text-sm font-semibold">Quota / zi</label><input type="number" wire:model="dailyQuota" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                    <div><label class="text-sm font-semibold">Quota / lună</label><input type="number" wire:model="monthlyQuota" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                </div>
                <div><label class="text-sm font-semibold">Expiră la</label><input type="datetime-local" wire:model="expiresAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            </div>

            <button class="mt-6 rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Emite cheia</button>
        </form>

        <div>
            <h2 class="text-2xl font-extrabold">Chei API</h2>
            <div class="mt-4 space-y-3">
                @forelse($keys as $key)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-extrabold">{{ $key->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $key->client->name }} · <code>{{ $key->key_prefix }}…</code></p>
                                <p class="mt-2 text-xs text-slate-400">{{ implode(', ', $key->scopes) }}</p>
                                <p class="mt-1 text-xs text-slate-400">Quota: {{ $key->daily_quota ?? '∞' }}/zi · {{ $key->monthly_quota ?? '∞' }}/lună @if($key->last_used_at) · last used {{ $key->last_used_at->format('d.m.Y H:i') }} @endif</p>
                            </div>
                            @if($key->revoked_at)
                                <span class="text-xs font-extrabold text-red-500">REVOCATĂ</span>
                            @else
                                <button wire:click="revokeKey({{ $key->id }})" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Revocă</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nicio cheie emisă.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
