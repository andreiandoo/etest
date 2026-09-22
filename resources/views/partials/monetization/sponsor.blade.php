@if($placement)
    @php($campaign = $placement->campaign)
    {{-- Conținutul sponsorizat se declară explicit și stă vizual separat de conținutul editorial. --}}
    <aside class="mt-8 rounded-card border border-line bg-surface-alt p-6">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-wider text-ink-500">{{ $campaign->disclosure_label }}</p>
                <div class="mt-2.5 flex items-center gap-3">
                    @if($campaign->sponsor->logo_url)
                        <img src="{{ $campaign->sponsor->logo_url }}" alt="{{ $campaign->sponsor->name }}" class="h-8 max-w-32 object-contain">
                    @endif
                    <p class="text-sm font-semibold text-ink-500">Partener: {{ $campaign->sponsor->name }}</p>
                </div>
                <h2 class="mt-3 text-[21px] font-bold">{{ $campaign->headline }}</h2>
                @if($campaign->body)
                    <p class="mt-2 text-[15px] leading-7 text-ink-700">{{ $campaign->body }}</p>
                @endif
            </div>
            <a href="{{ route('sponsor.click', $placement) }}" rel="sponsored nofollow"
                class="shrink-0 rounded-btn bg-brand-500 px-5 py-3 text-sm font-bold text-white hover:bg-brand-600">
                {{ $campaign->cta_label }}
            </a>
        </div>
    </aside>
@endif
