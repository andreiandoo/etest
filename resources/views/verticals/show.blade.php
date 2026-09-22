@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
    @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <div class="mt-7 max-w-4xl">
        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Domeniu</p>
        <h1 class="mt-3 text-5xl font-extrabold tracking-[-0.035em]">{{ $vertical->name }}</h1>
        @if($vertical->description)
            <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">{{ $vertical->description }}</p>
        @endif
    </div>

    @include('partials.monetization.sponsor', ['placement' => $monetization['sponsor']])

    @if($nodes->isNotEmpty())
        <div class="mt-12">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-600">Pregătire structurată</p>
                    <h2 class="mt-2 text-3xl font-extrabold">Alege direcția</h2>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($nodes as $node)
                    <a href="{{ $urlGenerator->taxonomy($node) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-600">{{ $node->type->value }}</p>
                        <h3 class="mt-3 text-xl font-extrabold">{{ $node->name }}</h3>
                        @if($node->description)
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $node->description }}</p>
                        @endif
                        <div class="mt-5 flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-500">{{ $node->published_tests_count }} teste directe</span>
                            <span class="font-bold text-indigo-600">Explorează →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($tests->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-3xl font-extrabold">Teste</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($tests as $test)
                    <a href="{{ $urlGenerator->test($test) }}" class="rounded-3xl border border-slate-200 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-600">{{ $test->mode->value }}</span>
                            @if($test->duration_seconds)
                                <span class="text-xs text-slate-500">{{ (int) ceil($test->duration_seconds / 60) }} min</span>
                            @endif
                        </div>
                        <h3 class="mt-4 text-xl font-extrabold">{{ $test->title }}</h3>
                        @if($test->description)
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $test->description }}</p>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $tests->links() }}</div>
        </div>
    @endif

    @include('partials.monetization.affiliate-resources', ['resources' => $monetization['affiliate_resources']])

    <div class="mt-10 grid gap-5 lg:grid-cols-2">
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

    @if($nodes->isEmpty() && $tests->isEmpty())
        <div class="mt-12 rounded-3xl border border-dashed border-slate-300 p-8 text-slate-500 dark:border-white/15">
            Conținutul pentru această verticală este în curs de publicare.
        </div>
    @endif
</section>
@endsection
