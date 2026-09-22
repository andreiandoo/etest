@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-5xl px-5 py-10 lg:px-8 lg:py-14">
    @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    @include('partials.monetization.sponsor', ['placement' => $monetization['sponsor']])

    <div class="mt-7 grid gap-6 lg:grid-cols-[1fr_280px]">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-sm sm:p-9 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">{{ $test->mode->value }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.035em] sm:text-5xl">{{ $test->title }}</h1>

            @if($test->description)
                <p class="mt-5 text-lg leading-8 text-slate-600 dark:text-slate-300">{{ $test->description }}</p>
            @endif

            <div class="mt-7 flex flex-wrap gap-2 text-sm font-semibold">
                <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-white/10">{{ $test->question_limit ?? $questionCount }} întrebări</span>
                @if($test->duration_seconds)
                    <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-white/10">{{ (int) ceil($test->duration_seconds / 60) }} minute</span>
                @endif
                @if($test->passing_percentage !== null)
                    <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-white/10">Prag {{ rtrim(rtrim(number_format((float) $test->passing_percentage, 2, '.', ''), '0'), '.') }}%</span>
                @endif
                <span class="rounded-full bg-emerald-50 px-4 py-2 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">Gratuit</span>
            </div>

            @if($test->instructions)
                <div class="mt-8 border-t border-slate-200 pt-7 dark:border-white/10">
                    <h2 class="text-xl font-extrabold">Cum funcționează</h2>
                    <p class="mt-3 whitespace-pre-line leading-7 text-slate-600 dark:text-slate-300">{{ $test->instructions }}</p>
                </div>
            @endif
        </article>

        <aside class="h-fit rounded-[2rem] bg-slate-950 p-6 text-white dark:bg-white dark:text-slate-950">
            <p class="text-sm font-bold opacity-60">Pregătit?</p>
            <h2 class="mt-2 text-2xl font-extrabold">Începe testul</h2>
            <p class="mt-3 text-sm leading-6 opacity-75">Rezultatul și progresul sunt salvate în contul tău.</p>

            @auth
                <a href="{{ route('tests.start', [$vertical, $test]) }}" class="mt-6 flex justify-center rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white">
                    Începe acum
                </a>
            @else
                <a href="{{ route('login', ['redirect' => $canonical]) }}" class="mt-6 flex justify-center rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white">
                    Autentifică-te
                </a>
                <a href="{{ route('register') }}" class="mt-3 flex justify-center rounded-2xl border border-white/20 px-5 py-3 text-sm font-bold dark:border-slate-300">
                    Creează cont gratuit
                </a>
            @endauth

            @auth
                <livewire:user.favorite-toggle :test="$test" :key="'favorite-test-'.$test->id" />
            @endauth
        </aside>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Tip</p>
            <p class="mt-1 font-extrabold">{{ ucfirst($test->mode->value) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Review</p>
            <p class="mt-1 font-extrabold">{{ $test->allow_review ? 'Disponibil' : 'Dezactivat' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-slate-500">Explicații</p>
            <p class="mt-1 font-extrabold">{{ $test->show_explanations ? 'Incluse' : 'La final indisponibile' }}</p>
        </div>
    </div>

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
</section>
@endsection
