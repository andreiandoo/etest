<section class="mx-auto max-w-[1100px] px-5 py-8 lg:px-10">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 hover:text-brand-500">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
        Panoul meu
    </a>

    <div class="mt-4 flex flex-wrap items-end justify-between gap-5">
        <div>
            <h1 class="text-[34px] font-bold tracking-tight">Istoricul testelor</h1>
            <p class="mt-1 text-[15px] text-ink-700">Fiecare încercare finalizată, cu scorul și timpul folosit.</p>
        </div>

        <div>
            <label for="history-filter" class="sr-only">Filtrează după domeniu</label>
            <select id="history-filter" wire:model.live="verticalId"
                class="rounded-btn border border-line-strong bg-white px-4 py-2.5 text-sm outline-none focus:border-brand-500">
                <option value="">Toate domeniile</option>
                @foreach($verticals as $vertical)
                    <option value="{{ $vertical->id }}">{{ $vertical->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-7 space-y-2.5">
        @forelse($attempts as $attempt)
            @php($expired = ($attempt->configuration['completion_reason'] ?? 'submitted') === 'expired')
            <article class="grid gap-4 rounded-card border border-line p-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="min-w-0">
                    <p class="text-xs text-ink-500">{{ $attempt->test->vertical->name }}</p>
                    <h2 class="mt-0.5 text-[17px] font-bold">{{ $attempt->test->title }}</h2>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-ink-500">
                        <span>{{ $attempt->completed_at?->timezone(auth()->user()->timezone ?: 'Europe/Bucharest')->format('d.m.Y, H:i') }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ intdiv((int) $attempt->duration_seconds, 60) }}m {{ (int) $attempt->duration_seconds % 60 }}s</span>
                        @if($expired)
                            <span aria-hidden="true">·</span>
                            <span class="font-bold text-vert-auto">timp expirat</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-[22px] font-bold tabular-nums">{{ number_format((float) $attempt->percentage, 1, ',', ' ') }}%</p>
                        <p class="text-xs text-ink-500">{{ $attempt->score }} din {{ $attempt->max_score }} puncte</p>
                    </div>
                    <a href="{{ route('attempts.results', $attempt) }}"
                        class="shrink-0 rounded-btn border border-line-strong px-4 py-2 text-sm font-bold hover:border-brand-500">
                        Vezi rezultatul
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-card border border-dashed border-line-strong p-8 text-center">
                <p class="text-[17px] font-bold">Niciun test finalizat pentru filtrul ales</p>
                <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                    Schimbă domeniul sau începe un test nou.
                </p>
                <a href="{{ route('home') }}#domenii" class="mt-4 inline-block rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">
                    Vezi domeniile
                </a>
            </div>
        @endforelse
    </div>

    @if($attempts->hasPages())
        <div class="mt-8">{{ $attempts->links() }}</div>
    @endif
</section>
