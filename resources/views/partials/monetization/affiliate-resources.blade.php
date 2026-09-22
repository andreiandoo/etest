@if($resources->isNotEmpty())
    <section class="mt-12">
        <h2 class="text-[21px] font-bold">Resurse recomandate</h2>
        <p class="mt-1.5 max-w-3xl text-[13px] leading-6 text-ink-500">
            Unele linkuri sunt linkuri de afiliere. Prețul pentru tine nu este modificat de comisionul de afiliere,
            iar recomandarea nu influențează conținutul testelor.
        </p>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($resources as $resource)
                <article class="flex flex-col rounded-card border border-line p-5">
                    <div class="flex items-start gap-4">
                        @if($resource->image_url)
                            <img src="{{ $resource->image_url }}" alt="" class="h-20 w-16 shrink-0 rounded-btn object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs text-ink-500">{{ $resource->resource_type }} · {{ $resource->merchant->name }}</p>
                            <h3 class="mt-1 text-[15px] font-bold leading-snug">{{ $resource->title }}</h3>
                        </div>
                    </div>

                    @if($resource->description)
                        <p class="mt-3 text-sm leading-6 text-ink-700">{{ $resource->description }}</p>
                    @endif

                    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                        <span class="text-sm font-bold">{{ $resource->price_label }}</span>
                        <a href="{{ route('affiliate.click', $resource) }}" rel="sponsored nofollow"
                            class="text-sm font-bold text-brand-500 hover:text-brand-700">Vezi resursa &rarr;</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
