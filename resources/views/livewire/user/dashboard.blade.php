<section class="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
    @if(session('user_message'))
        <div class="mb-6 rounded-2xl bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-100">
            {{ session('user_message') }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Dashboard</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight">Salut, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">Progresul tău se actualizează la fiecare test finalizat.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('history') }}" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-white/15">Istoric</a>
            <a href="{{ route('leaderboard') }}" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-white/15">Clasament</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-white/15">Ieși din cont</button>
            </form>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl bg-slate-950 p-6 text-white dark:bg-white dark:text-slate-950">
            <p class="text-sm opacity-60">Nivel</p>
            <p class="mt-2 text-4xl font-extrabold">{{ $level['level'] }}</p>
            <p class="mt-2 text-sm font-semibold">{{ $stats->xp }} XP</p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/20 dark:bg-slate-200">
                <div class="h-full rounded-full bg-indigo-500" style="width: {{ min(100, round(($level['progress'] / $level['required']) * 100)) }}%"></div>
            </div>
            <p class="mt-2 text-xs opacity-60">{{ $level['next_threshold'] - $stats->xp }} XP până la nivelul următor</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Teste finalizate</p>
            <p class="mt-2 text-4xl font-extrabold">{{ $completed_count }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Medie</p>
            <p class="mt-2 text-4xl font-extrabold">{{ number_format($average_percentage, 1) }}%</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Cel mai bun scor</p>
            <p class="mt-2 text-4xl font-extrabold">{{ number_format($best_percentage, 1) }}%</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Streak</p>
            <p class="mt-2 text-4xl font-extrabold">{{ $stats->current_streak }} zile</p>
            <p class="mt-2 text-xs text-slate-500">Record: {{ $stats->longest_streak }} zile</p>
        </div>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-[1.35fr_.65fr]">
        <div>
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Activitate</p>
                    <h2 class="mt-2 text-2xl font-extrabold">Teste recente</h2>
                </div>
                <a href="{{ route('history') }}" class="text-sm font-bold text-indigo-600">Vezi tot →</a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($recent_attempts as $attempt)
                    <a href="{{ route('attempts.results', $attempt) }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-200 dark:border-white/10 dark:bg-white/5">
                        <div>
                            <p class="text-xs font-extrabold uppercase text-indigo-600">{{ $attempt->test->vertical->name }}</p>
                            <p class="mt-1 font-extrabold">{{ $attempt->test->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $attempt->completed_at?->timezone(auth()->user()->timezone)->format('d.m.Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-extrabold">{{ number_format((float) $attempt->percentage, 1) }}%</p>
                            <p class="text-xs text-slate-500">{{ intdiv((int) $attempt->duration_seconds, 60) }} min</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-white/15">
                        După primul test finalizat, aici apare istoricul tău recent.
                    </div>
                @endforelse
            </div>
        </div>

        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Timp de învățare</p>
            <h2 class="mt-2 text-2xl font-extrabold">{{ intdiv($total_seconds, 3600) }}h {{ intdiv($total_seconds % 3600, 60) }}m</h2>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                <h3 class="font-extrabold">Clasament</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Participarea este opțională. Poți folosi un nume public diferit de numele contului.</p>
                <form wire:submit="saveLeaderboardSettings" class="mt-4 space-y-3">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" wire:model.live="leaderboardOptIn">
                        Particip în clasament
                    </label>
                    @if($leaderboardOptIn)
                        <input wire:model="leaderboardDisplayName" maxlength="80" class="w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-white/15" placeholder="Nume public">
                        @error('leaderboardDisplayName')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    @endif
                    <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Salvează</button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-2">
        <section>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Progres</p>
            <h2 class="mt-2 text-2xl font-extrabold">Pe domenii</h2>
            <div class="mt-4 space-y-3">
                @forelse($vertical_progress as $item)
                    <a href="{{ route('verticals.show', $item['slug']) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="font-extrabold">{{ $item['name'] }}</p><p class="text-xs text-slate-500">{{ $item['attempts_count'] }} tentative finalizate</p></div>
                            <div class="text-right"><p class="text-xl font-extrabold">{{ $item['average_percentage'] }}%</p><p class="text-xs text-slate-500">best {{ $item['best_percentage'] }}%</p></div>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Încă nu există suficiente date.</p>
                @endforelse
            </div>
        </section>

        <section>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-amber-600">De consolidat</p>
            <h2 class="mt-2 text-2xl font-extrabold">Puncte slabe</h2>
            <div class="mt-4 space-y-3">
                @forelse($weak_areas as $area)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="font-extrabold">{{ $area['name'] }}</p><p class="text-xs text-slate-500">{{ $area['vertical'] }} · {{ $area['answered_count'] }} răspunsuri</p></div>
                            <p class="text-xl font-extrabold {{ $area['accuracy'] < 50 ? 'text-amber-600' : 'text-slate-900 dark:text-white' }}">{{ $area['accuracy'] }}%</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Punctele slabe apar după minimum două răspunsuri într-un subiect.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-10 grid gap-8 xl:grid-cols-2">
        <section>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Favorite</p>
            <h2 class="mt-2 text-2xl font-extrabold">Teste salvate</h2>
            <div class="mt-4 space-y-3">
                @forelse($favorite_tests as $favorite)
                    <a href="{{ $urlGenerator->test($favorite) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-bold text-indigo-600">{{ $favorite->vertical->name }}</p>
                        <p class="mt-1 font-extrabold">{{ $favorite->title }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Poți salva un test direct din pagina lui publică.</p>
                @endforelse
            </div>
        </section>

        <section>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Achievements</p>
            <h2 class="mt-2 text-2xl font-extrabold">Insigne deblocate</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse($achievements as $achievement)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-lg font-extrabold">{{ $achievement['title'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $achievement['description'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Prima insignă se deblochează când finalizezi un test.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
