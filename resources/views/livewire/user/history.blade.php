<section class="mx-auto max-w-6xl px-5 py-10 lg:px-8 lg:py-14">
    <a href="{{ route('dashboard') }}" class="text-sm font-bold text-indigo-600">← Dashboard</a>
    <div class="mt-4 flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Istoric</p>
            <h1 class="mt-2 text-4xl font-extrabold">Testele tale</h1>
        </div>

        <select wire:model.live="verticalId" class="rounded-xl border border-slate-300 bg-transparent px-4 py-2 text-sm dark:border-white/15">
            <option value="">Toate domeniile</option>
            @foreach($verticals as $vertical)
                <option value="{{ $vertical->id }}">{{ $vertical->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-8 space-y-3">
        @forelse($attempts as $attempt)
            <article class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-[1fr_auto] sm:items-center dark:border-white/10 dark:bg-white/5">
                <div>
                    <p class="text-xs font-extrabold uppercase text-indigo-600">{{ $attempt->test->vertical->name }}</p>
                    <h2 class="mt-1 font-extrabold">{{ $attempt->test->title }}</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $attempt->completed_at?->timezone(auth()->user()->timezone ?: 'Europe/Bucharest')->format('d.m.Y H:i') }}
                        · {{ intdiv((int) $attempt->duration_seconds, 60) }}m {{ (int) $attempt->duration_seconds % 60 }}s
                        @if(($attempt->configuration['completion_reason'] ?? 'submitted') === 'expired')
                            · <span class="font-bold text-amber-600">expirat</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right"><p class="text-2xl font-extrabold">{{ number_format((float) $attempt->percentage, 1) }}%</p><p class="text-xs text-slate-500">{{ $attempt->score }}/{{ $attempt->max_score }} pct.</p></div>
                    <a href="{{ route('attempts.results', $attempt) }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Detalii</a>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-slate-500 dark:border-white/15">Nu ai încă teste finalizate pentru filtrul selectat.</div>
        @endforelse
    </div>

    <div class="mt-8">{{ $attempts->links() }}</div>
</section>
