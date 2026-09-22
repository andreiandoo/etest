@if($resources->isNotEmpty())
    <section class="mt-12">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Resurse recomandate</p>
            <h2 class="mt-2 text-3xl font-extrabold">Cărți și materiale utile</h2>
            <p class="mt-2 text-xs leading-5 text-slate-500">Unele linkuri sunt linkuri de afiliere. Prețul pentru tine nu este modificat de comisionul de afiliere.</p>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($resources as $resource)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start gap-4">
                        @if($resource->image_url)
                            <img src="{{ $resource->image_url }}" alt="" class="h-20 w-16 rounded-lg object-cover">
                        @endif
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-indigo-600">{{ $resource->resource_type }}</p>
                            <h3 class="mt-2 font-extrabold">{{ $resource->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $resource->merchant->name }}</p>
                        </div>
                    </div>
                    @if($resource->description)
                        <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $resource->description }}</p>
                    @endif
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold">{{ $resource->price_label }}</span>
                        <a href="{{ route('affiliate.click', $resource) }}" rel="sponsored nofollow"
                            class="text-sm font-extrabold text-indigo-600">Vezi resursa →</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
