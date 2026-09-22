<section class="mx-auto max-w-[900px] px-5 py-8 lg:px-10">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 hover:text-brand-500">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
        Panoul meu
    </a>

    <h1 class="mt-4 text-[34px] font-bold tracking-tight">Clasament</h1>
    <p class="mt-1 max-w-2xl text-[15px] leading-7 text-ink-700">
        Apar doar cei care au ales explicit să participe. Participarea e dezactivată implicit și o poți opri oricând din panou.
    </p>

    <div class="mt-7 overflow-hidden rounded-card border border-line">
        @forelse($leaders as $leader)
            @php($isMe = $leader['user_id'] === $currentUserId)
            <div class="grid grid-cols-[48px_1fr_auto] items-center gap-4 border-b border-line px-5 py-4 last:border-0 {{ $isMe ? 'bg-brand-50' : '' }}">
                <span class="text-[17px] font-bold tabular-nums text-ink-500">{{ $leader['rank'] }}</span>
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-bold">
                        {{ $leader['name'] }}
                        @if($isMe)
                            <span class="ml-1 rounded-[3px] bg-brand-500 px-1.5 py-0.5 text-[11px] font-bold text-white">tu</span>
                        @endif
                    </p>
                    <p class="text-xs text-ink-500">
                        Nivel {{ $leader['level'] }} · {{ $leader['completed_attempts'] }} teste · serie {{ $leader['current_streak'] }} zile
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[19px] font-bold tabular-nums">{{ number_format($leader['xp'], 0, ',', ' ') }}</p>
                    <p class="text-xs text-ink-500">XP</p>
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <p class="text-[17px] font-bold">Clasamentul e gol</p>
                <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                    Nimeni nu participă încă. Poți intra din panoul tău, dacă vrei.
                </p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-block rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">
                    Mergi la panou
                </a>
            </div>
        @endforelse
    </div>
</section>
