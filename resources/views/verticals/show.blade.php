@extends('layouts.app')

@section('content')
@php($theme = \App\Services\Content\VerticalTheme::for($vertical))

<div class="mx-auto max-w-[1440px] px-5 py-6 lg:px-10">

    @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <header class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex gap-4">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-card" style="background: {{ $theme['soft'] }}">
                <x-vertical-icon :icon="$theme['icon']" :size="30" :color="$theme['solid']" :width="1.5" />
            </span>
            <div class="max-w-3xl">
                <h1 class="text-[34px] font-bold leading-tight tracking-tight">{{ $vertical->name }}</h1>
                @if($vertical->description)
                    <p class="mt-2 text-[15px] leading-7 text-ink-700">{{ $vertical->description }}</p>
                @endif
            </div>
        </div>

        <dl class="flex shrink-0 gap-8">
            <div>
                <dt class="sr-only">Teste publicate</dt>
                <dd class="text-[22px] font-bold">{{ $tests->total() }}</dd>
                <p class="text-[13px] text-ink-500">{{ $tests->total() === 1 ? 'test' : 'teste' }}</p>
            </div>
            <div>
                <dt class="sr-only">Secțiuni</dt>
                <dd class="text-[22px] font-bold">{{ $nodes->count() }}</dd>
                <p class="text-[13px] text-ink-500">{{ $nodes->count() === 1 ? 'secțiune' : 'secțiuni' }}</p>
            </div>
            <div>
                <dt class="sr-only">Întrebări publicate</dt>
                <dd class="text-[22px] font-bold">{{ number_format($questionsCount, 0, ',', ' ') }}</dd>
                <p class="text-[13px] text-ink-500">întrebări</p>
            </div>
        </dl>
    </header>

    @include('partials.monetization.sponsor', ['placement' => $monetization['sponsor']])

    @if($popularTests->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-[19px] font-bold">Cele mai date teste</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($popularTests as $test)
                    @include('partials.test-card', ['test' => $test, 'url' => $urlGenerator->test($test)])
                @endforeach
            </div>
        </section>
    @endif

    @if($nodes->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-[19px] font-bold">Structura domeniului</h2>
            <p class="mt-1 text-[15px] text-ink-700">Intră într-o secțiune ca să vezi capitolele și testele ei.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($nodes as $node)
                    <a href="{{ $urlGenerator->taxonomy($node) }}"
                        class="group flex flex-col gap-2 rounded-card border border-line p-5 transition hover:border-line-strong hover:shadow-sm">
                        <span class="flex items-center justify-between gap-3">
                            <span class="text-[17px] font-bold group-hover:text-brand-500">{{ $node->name }}</span>
                            <span class="shrink-0 rounded-[4px] px-2 py-0.5 text-xs font-bold"
                                style="background: {{ $theme['soft'] }}; color: {{ $theme['solid'] }}">
                                {{ $node->published_tests_count }} {{ $node->published_tests_count === 1 ? 'test' : 'teste' }}
                            </span>
                        </span>
                        @if($node->description)
                            <span class="line-clamp-2 text-sm leading-relaxed text-ink-700">{{ $node->description }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10">
        <div class="flex items-baseline justify-between gap-6">
            <h2 class="text-[19px] font-bold">Toate testele</h2>
            <p class="shrink-0 text-[13px] text-ink-500">
                {{ $tests->total() }} {{ $tests->total() === 1 ? 'rezultat' : 'rezultate' }}
            </p>
        </div>

        @if($tests->isEmpty())
            <div class="mt-4 rounded-card border border-dashed border-line-strong p-8 text-center">
                <p class="text-[17px] font-bold">Încă nu există teste publicate aici</p>
                <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                    Domeniul e configurat, dar conținutul e în lucru. Creează-ți contul ca să pornești imediat ce apare primul test.
                </p>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($tests as $test)
                    @include('partials.test-card', ['test' => $test, 'url' => $urlGenerator->test($test)])
                @endforeach
            </div>

            @if($tests->hasPages())
                <div class="mt-8">
                    {{ $tests->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </section>

    @include('partials.monetization.affiliate-resources', ['resources' => $monetization['affiliate_resources']])

    <div class="mt-10 grid gap-4 lg:grid-cols-2">
        @if($monetization['lead'])
            <livewire:monetization.lead-capture :campaign="$monetization['lead']" :key="'lead-'.$monetization['lead']->id" />
        @endif
        <livewire:monetization.newsletter-signup
            :interest-key="$monetization['newsletter']['interest_key']"
            :vertical-id="$monetization['newsletter']['vertical_id']"
            :taxonomy-node-id="$monetization['newsletter']['taxonomy_node_id']"
            :interest-label="$monetization['newsletter']['label']"
            :key="'newsletter-'.$monetization['newsletter']['interest_key']"
        />
    </div>

</div>
@endsection
