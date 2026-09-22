@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1440px] px-5 py-8 lg:px-10">

    <h1 class="text-[28px] font-bold tracking-tight">
        @if($term === '')
            Caută pe {{ $tenantContext->brand('site_name', 'e-test.ro') }}
        @else
            Rezultate pentru „{{ $term }}”
        @endif
    </h1>

    @if($term !== '')
        <p class="mt-2 text-[15px] text-ink-500">
            {{ $resultCount }} {{ $resultCount === 1 ? 'rezultat' : 'rezultate' }}
        </p>
    @endif

    @if($term === '')
        <p class="mt-3 max-w-2xl text-[15px] leading-7 text-ink-700">
            Scrie numele unui examen, al unei materii sau al unui capitol în câmpul de căutare din partea de sus.
        </p>
    @elseif($resultCount === 0)
        <div class="mt-6 rounded-card border border-line bg-surface-alt p-8">
            <p class="text-[17px] font-bold">Nu am găsit nimic pentru „{{ $term }}”</p>
            <p class="mt-2 max-w-2xl text-[15px] leading-7 text-ink-700">
                Încearcă un termen mai scurt sau pornește de la un domeniu.
            </p>
            <a href="{{ route('home') }}#domenii" class="mt-4 inline-block rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">
                Vezi domeniile
            </a>
        </div>
    @else

        @if($verticals->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-[19px] font-bold">Domenii</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($verticals as $vertical)
                        @php($theme = \App\Services\Content\VerticalTheme::for($vertical))
                        <a href="{{ $urlGenerator->vertical($vertical) }}" class="flex items-center gap-3 rounded-card border border-line p-4 hover:border-line-strong">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-btn" style="background: {{ $theme['soft'] }}">
                                <x-vertical-icon :icon="$theme['icon']" :size="22" :color="$theme['solid']" />
                            </span>
                            <span class="text-[15px] font-bold">{{ $vertical->name }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($nodes->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-[19px] font-bold">Materii și capitole</h2>
                <ul class="mt-3 divide-y divide-line border-y border-line">
                    @foreach($nodes as $node)
                        <li>
                            <a href="{{ $urlGenerator->taxonomy($node) }}" class="flex items-center justify-between gap-4 py-3 hover:text-brand-500">
                                <span class="text-[15px] font-semibold">{{ $node->name }}</span>
                                <span class="shrink-0 text-[13px] text-ink-500">{{ $node->vertical->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($tests->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-[19px] font-bold">Teste</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($tests as $test)
                        @include('partials.test-card', ['test' => $test, 'url' => $urlGenerator->test($test)])
                    @endforeach
                </div>
            </section>
        @endif

    @endif

</div>
@endsection
