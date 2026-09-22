@extends('layouts.app')

@section('content')
@php($theme = \App\Services\Content\VerticalTheme::for($node->vertical))

<div class="mx-auto max-w-[1440px] px-5 py-6 lg:px-10">

    @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <header class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-[13px] font-bold" style="color: {{ $theme['solid'] }}">{{ $node->vertical->name }}</p>
            <h1 class="mt-1 text-[34px] font-bold leading-tight tracking-tight">{{ $node->name }}</h1>
            @if($node->description)
                <p class="mt-2 text-[15px] leading-7 text-ink-700">{{ $node->description }}</p>
            @endif
        </div>

        <dl class="flex shrink-0 gap-8">
            <div>
                <dt class="sr-only">Teste publicate</dt>
                <dd class="text-[22px] font-bold">{{ $tests->total() }}</dd>
                <p class="text-[13px] text-ink-500">{{ $tests->total() === 1 ? 'test' : 'teste' }}</p>
            </div>
            @if($children->isNotEmpty())
                <div>
                    <dt class="sr-only">Capitole</dt>
                    <dd class="text-[22px] font-bold">{{ $children->count() }}</dd>
                    <p class="text-[13px] text-ink-500">{{ $children->count() === 1 ? 'capitol' : 'capitole' }}</p>
                </div>
            @endif
        </dl>
    </header>

    @include('partials.monetization.sponsor', ['placement' => $monetization['sponsor']])

    @if($children->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-[19px] font-bold">Capitole</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($children as $child)
                    <a href="{{ $urlGenerator->taxonomy($child) }}"
                        class="group flex flex-col gap-2 rounded-card border border-line p-5 transition hover:border-line-strong hover:shadow-sm">
                        <span class="text-[17px] font-bold group-hover:text-brand-500">{{ $child->name }}</span>
                        @if($child->description)
                            <span class="line-clamp-2 text-sm leading-relaxed text-ink-700">{{ $child->description }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10">
        <div class="flex items-baseline justify-between gap-6">
            <h2 class="text-[19px] font-bold">Teste</h2>
            <p class="shrink-0 text-[13px] text-ink-500">
                {{ $tests->total() }} {{ $tests->total() === 1 ? 'rezultat' : 'rezultate' }}
            </p>
        </div>

        @if($tests->isEmpty())
            <div class="mt-4 rounded-card border border-dashed border-line-strong p-8 text-center">
                <p class="text-[17px] font-bold">Încă nu există teste publicate aici</p>
                <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                    Conținutul pentru această secțiune e în lucru.
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
