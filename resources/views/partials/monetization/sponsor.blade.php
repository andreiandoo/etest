@if($placement)
    @php($campaign = $placement->campaign)
    <aside class="mt-8 rounded-3xl border border-indigo-200 bg-indigo-50/70 p-6 dark:border-indigo-400/20 dark:bg-indigo-500/10">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-3xl">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-600">{{ $campaign->disclosure_label }}</p>
                <div class="mt-3 flex items-center gap-3">
                    @if($campaign->sponsor->logo_url)
                        <img src="{{ $campaign->sponsor->logo_url }}" alt="{{ $campaign->sponsor->name }}" class="h-8 max-w-32 object-contain">
                    @endif
                    <p class="text-sm font-bold text-slate-500">Partener: {{ $campaign->sponsor->name }}</p>
                </div>
                <h2 class="mt-3 text-2xl font-extrabold">{{ $campaign->headline }}</h2>
                @if($campaign->body)
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $campaign->body }}</p>
                @endif
            </div>
            <a href="{{ route('sponsor.click', $placement) }}" rel="sponsored nofollow"
                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white">
                {{ $campaign->cta_label }} →
            </a>
        </div>
    </aside>
@endif
