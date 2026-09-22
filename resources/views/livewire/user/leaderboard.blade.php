<section class="mx-auto max-w-5xl px-5 py-10 lg:px-8 lg:py-14">
    <a href="{{ route('dashboard') }}" class="text-sm font-bold text-indigo-600">← Dashboard</a>
    <div class="mt-4">
        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Opt-in leaderboard</p>
        <h1 class="mt-2 text-4xl font-extrabold">Clasament</h1>
        <p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-300">Apar doar utilizatorii care au ales explicit să participe.</p>
    </div>

    <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
        @forelse($leaders as $leader)
            <div class="grid grid-cols-[52px_1fr_auto] items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0 dark:border-white/5 {{ $leader['user_id'] === $currentUserId ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }}">
                <div class="text-xl font-extrabold">#{{ $leader['rank'] }}</div>
                <div>
                    <p class="font-extrabold">{{ $leader['name'] }}</p>
                    <p class="text-xs text-slate-500">Nivel {{ $leader['level'] }} · {{ $leader['completed_attempts'] }} teste · streak {{ $leader['current_streak'] }}</p>
                </div>
                <div class="text-right"><p class="text-xl font-extrabold">{{ $leader['xp'] }}</p><p class="text-xs text-slate-500">XP</p></div>
            </div>
        @empty
            <div class="p-8 text-slate-500">Clasamentul este gol. Participarea este opțională.</div>
        @endforelse
    </div>
</section>
