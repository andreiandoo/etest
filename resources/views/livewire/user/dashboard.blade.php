<section class="mx-auto max-w-[1440px] px-5 py-8 lg:px-10">

    @if(session('user_message'))
        <div class="mb-6 rounded-card bg-[#EAF4F2] px-5 py-3 text-sm font-semibold text-[#0A574E]">
            {{ session('user_message') }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
            <h1 class="text-[34px] font-bold tracking-tight">Salut, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-[15px] text-ink-700">Progresul tău se actualizează la fiecare test finalizat.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('history') }}" class="rounded-btn border border-line-strong px-4 py-2 text-sm font-bold hover:border-brand-500">Istoric</a>
            <a href="{{ route('leaderboard') }}" class="rounded-btn border border-line-strong px-4 py-2 text-sm font-bold hover:border-brand-500">Clasament</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-btn border border-line-strong px-4 py-2 text-sm font-bold hover:border-brand-500">Ieși din cont</button>
            </form>
        </div>
    </div>

    <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-card bg-navy-900 p-5 text-white">
            <p class="text-[13px] text-navy-muted">Nivel</p>
            <p class="mt-1 text-[34px] font-bold leading-none">{{ $level['level'] }}</p>
            <p class="mt-2 text-sm font-semibold">{{ number_format($stats->xp, 0, ',', ' ') }} XP</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/20">
                <div class="h-full rounded-full bg-[#4DA3FF]" style="width: {{ min(100, (int) round(($level['progress'] / $level['required']) * 100)) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-navy-muted">{{ max(0, $level['next_threshold'] - $stats->xp) }} XP până la nivelul următor</p>
        </div>

        <div class="rounded-card border border-line p-5">
            <p class="text-[13px] text-ink-500">Teste finalizate</p>
            <p class="mt-1 text-[34px] font-bold leading-none tabular-nums">{{ $completed_count }}</p>
        </div>
        <div class="rounded-card border border-line p-5">
            <p class="text-[13px] text-ink-500">Medie</p>
            <p class="mt-1 text-[34px] font-bold leading-none tabular-nums">{{ number_format($average_percentage, 1, ',', ' ') }}%</p>
        </div>
        <div class="rounded-card border border-line p-5">
            <p class="text-[13px] text-ink-500">Cel mai bun scor</p>
            <p class="mt-1 text-[34px] font-bold leading-none tabular-nums">{{ number_format($best_percentage, 1, ',', ' ') }}%</p>
        </div>
        <div class="rounded-card border border-line p-5">
            <p class="text-[13px] text-ink-500">Serie zilnică</p>
            <p class="mt-1 text-[34px] font-bold leading-none tabular-nums">{{ $stats->current_streak }}</p>
            <p class="mt-1 text-xs text-ink-500">record {{ $stats->longest_streak }} zile · total {{ intdiv($total_seconds, 3600) }}h {{ intdiv($total_seconds % 3600, 60) }}m</p>
        </div>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-[1.4fr_.6fr]">
        <section>
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="text-[19px] font-bold">Teste recente</h2>
                <a href="{{ route('history') }}" class="text-sm font-bold text-brand-500 hover:text-brand-700">Vezi tot &rarr;</a>
            </div>

            <div class="mt-4 space-y-2.5">
                @forelse($recent_attempts as $attempt)
                    <a href="{{ route('attempts.results', $attempt) }}"
                        class="flex flex-wrap items-center justify-between gap-4 rounded-card border border-line p-4 transition hover:border-line-strong">
                        <div class="min-w-0">
                            <p class="text-xs text-ink-500">{{ $attempt->test->vertical->name }}</p>
                            <p class="mt-0.5 text-[15px] font-bold">{{ $attempt->test->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                {{ $attempt->completed_at?->timezone(auth()->user()->timezone ?: 'Europe/Bucharest')->format('d.m.Y, H:i') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-[22px] font-bold tabular-nums">{{ number_format((float) $attempt->percentage, 1, ',', ' ') }}%</p>
                            <p class="text-xs text-ink-500">{{ intdiv((int) $attempt->duration_seconds, 60) }} min</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-card border border-dashed border-line-strong p-6 text-center">
                        <p class="text-[15px] font-bold">Niciun test finalizat încă</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-ink-700">Alege un domeniu și începe. Aici apare istoricul tău.</p>
                        <a href="{{ route('home') }}#domenii" class="mt-3 inline-block rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">
                            Vezi domeniile
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-[19px] font-bold">Clasament</h2>
            <div class="mt-4 rounded-card border border-line p-5">
                <p class="text-sm leading-6 text-ink-700">
                    Participarea e opțională și dezactivată implicit. Poți folosi un nume public diferit de numele contului.
                </p>
                <form wire:submit="saveLeaderboardSettings" class="mt-4 space-y-3">
                    <label class="flex items-center gap-2.5 text-sm font-semibold">
                        <input type="checkbox" wire:model.live="leaderboardOptIn" class="size-4 rounded accent-brand-500">
                        Particip în clasament
                    </label>
                    @if($leaderboardOptIn)
                        <div>
                            <label for="leaderboard-name" class="sr-only">Nume public</label>
                            <input id="leaderboard-name" wire:model="leaderboardDisplayName" maxlength="80"
                                class="w-full rounded-btn border border-line-strong bg-white px-3 py-2 text-sm outline-none focus:border-brand-500"
                                placeholder="Nume public">
                            @error('leaderboardDisplayName')<p class="mt-1 text-sm text-vert-auto">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <button class="rounded-btn bg-ink-900 px-4 py-2 text-sm font-bold text-white">Salvează</button>
                </form>
            </div>
        </section>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-2">
        <section>
            <h2 class="text-[19px] font-bold">Progresul pe domenii</h2>
            <div class="mt-4 space-y-2.5">
                @forelse($vertical_progress as $item)
                    <a href="{{ route('verticals.show', $item['slug']) }}"
                        class="flex items-center justify-between gap-4 rounded-card border border-line p-4 transition hover:border-line-strong">
                        <div>
                            <p class="text-[15px] font-bold">{{ $item['name'] }}</p>
                            <p class="text-xs text-ink-500">{{ $item['attempts_count'] }} teste finalizate</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[19px] font-bold tabular-nums">{{ $item['average_percentage'] }}%</p>
                            <p class="text-xs text-ink-500">cel mai bun {{ $item['best_percentage'] }}%</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Încă nu există suficiente date.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-[19px] font-bold">Ce trebuie consolidat</h2>
            <div class="mt-4 space-y-2.5">
                @forelse($weak_areas as $area)
                    <div class="flex items-center justify-between gap-4 rounded-card border border-line p-4">
                        <div>
                            <p class="text-[15px] font-bold">{{ $area['name'] }}</p>
                            <p class="text-xs text-ink-500">{{ $area['vertical'] }} · {{ $area['answered_count'] }} răspunsuri</p>
                        </div>
                        <p class="text-[19px] font-bold tabular-nums {{ $area['accuracy'] < 50 ? 'text-vert-auto' : '' }}">{{ $area['accuracy'] }}%</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Punctele slabe apar după minimum două răspunsuri într-un capitol.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-2">
        <section>
            <h2 class="text-[19px] font-bold">Teste salvate</h2>
            <div class="mt-4 space-y-2.5">
                @forelse($favorite_tests as $favorite)
                    <a href="{{ $urlGenerator->test($favorite) }}"
                        class="block rounded-card border border-line p-4 transition hover:border-line-strong">
                        <p class="text-xs text-ink-500">{{ $favorite->vertical->name }}</p>
                        <p class="mt-0.5 text-[15px] font-bold">{{ $favorite->title }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Poți salva un test direct din pagina lui.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-[19px] font-bold">Insigne deblocate</h2>
            <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                @forelse($achievements as $achievement)
                    <div class="rounded-card border border-line p-4">
                        <p class="text-[15px] font-bold">{{ $achievement['title'] }}</p>
                        <p class="mt-1 text-sm leading-6 text-ink-700">{{ $achievement['description'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Prima insignă se deblochează când finalizezi un test.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
