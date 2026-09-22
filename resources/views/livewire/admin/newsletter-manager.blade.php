<div>
    <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Monetizare</p>
    <h1 class="mt-2 text-4xl font-extrabold">Newsletter</h1>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        @foreach(['pending' => 'În așteptare', 'active' => 'Active', 'unsubscribed' => 'Dezabonate'] as $key => $label)
            <button wire:click="$set('status', '{{ $key }}')" class="rounded-3xl border border-slate-200 bg-white p-6 text-left dark:border-white/10 dark:bg-white/5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-4xl font-extrabold">{{ $counts[$key] }}</p>
            </button>
        @endforeach
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-[1fr_320px]">
        <section>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-extrabold">Abonări</h2>
                <select wire:model.live="status" class="rounded-xl border px-3 py-2 text-sm dark:border-white/15 dark:bg-transparent">
                    <option value="">Toate</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="unsubscribed">Unsubscribed</option>
                </select>
            </div>

            <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
                @forelse($subscriptions as $subscription)
                    <div class="border-b border-slate-100 p-5 last:border-0 dark:border-white/5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-extrabold">{{ $subscription->email }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $subscription->interest_key }} · {{ $subscription->status }}</p>
                                <p class="mt-1 text-xs text-slate-400">Consimțământ: {{ $subscription->consented_at->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="flex gap-2">
                                @if($subscription->status === 'pending')
                                    <button wire:click="resendConfirmation({{ $subscription->id }})" class="rounded-lg border px-3 py-2 text-xs font-bold dark:border-white/15">Retrimite confirmarea</button>
                                @endif
                                @if($subscription->status !== 'unsubscribed')
                                    <button wire:click="unsubscribe({{ $subscription->id }})" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Dezabonează</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-sm text-slate-500">Nu există abonări pentru filtrul selectat.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $subscriptions->links() }}</div>
        </section>

        <aside>
            <h2 class="text-2xl font-extrabold">Interese active</h2>
            <div class="mt-4 space-y-2">
                @forelse($interests as $interest)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                        <p class="text-sm font-bold">{{ $interest->interest_key }}</p>
                        <p class="text-xs text-slate-500">{{ $interest->total }} abonați activi</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Încă nu există interese confirmate.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
