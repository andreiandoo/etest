@extends('layouts.app')

@section('content')
@php
    use App\Enums\TestMode;
    use App\Services\Content\VerticalTheme;

    $theme = VerticalTheme::for($vertical);
    $isExam = $test->mode === TestMode::Exam;
    $minutes = $test->duration_seconds !== null && $test->duration_seconds > 0
        ? (int) ceil($test->duration_seconds / 60)
        : null;

    $benefits = [
        'Acces complet la toate întrebările, gratuit',
        'Explicație și sursă la fiecare răspuns',
        'Rezultat pe capitole, ca să știi ce să reiei',
        'Progresul rămâne salvat în cont',
    ];
@endphp

<div class="mx-auto max-w-[1440px] px-5 py-6 lg:px-10">

    @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    @include('partials.monetization.sponsor', ['placement' => $monetization['sponsor']])

    <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_380px]">

        <div>
            <div class="rounded-card p-8 lg:p-10" style="background: {{ $theme['solid'] }}">
                <p class="text-[13px] font-bold text-white/80">
                    {{ $test->taxonomyNode?->name ?? $vertical->name }}
                </p>
                <h1 class="mt-2 text-[38px] font-bold leading-tight tracking-tight text-white">{{ $test->title }}</h1>

                @if($test->description)
                    <p class="mt-3 max-w-2xl text-[17px] leading-7 text-white/85">{{ $test->description }}</p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/85">
                    <span class="rounded-[4px] bg-white/15 px-2.5 py-1 font-bold text-white">
                        {{ $isExam ? 'Mod examen' : 'Mod exersare' }}
                    </span>
                    <span>{{ $test->question_limit ?? $questionCount }} întrebări</span>
                    <span>{{ $minutes !== null ? $minutes.' minute' : 'fără limită de timp' }}</span>
                    @if($test->passing_percentage !== null)
                        <span>prag {{ rtrim(rtrim(number_format((float) $test->passing_percentage, 2, ',', ' '), '0'), ',') }}%</span>
                    @endif
                </div>
            </div>

            @if($test->instructions)
                <section class="mt-8">
                    <h2 class="text-[19px] font-bold">Înainte să începi</h2>
                    <p class="mt-2 whitespace-pre-line text-[15px] leading-7 text-ink-700">{{ $test->instructions }}</p>
                </section>
            @endif

            <section class="mt-8">
                <h2 class="text-[19px] font-bold">Cum funcționează acest test</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-card border border-line p-4">
                        <dt class="text-[13px] text-ink-500">Explicații</dt>
                        <dd class="mt-1 text-[15px] font-bold">
                            {{ $test->show_explanations ? 'Afișate după fiecare răspuns' : 'Afișate la final' }}
                        </dd>
                    </div>
                    <div class="rounded-card border border-line p-4">
                        <dt class="text-[13px] text-ink-500">Revizuire</dt>
                        <dd class="mt-1 text-[15px] font-bold">
                            {{ $test->allow_review ? 'Poți relua răspunsurile' : 'Indisponibilă' }}
                        </dd>
                    </div>
                    <div class="rounded-card border border-line p-4">
                        <dt class="text-[13px] text-ink-500">Ordinea întrebărilor</dt>
                        <dd class="mt-1 text-[15px] font-bold">
                            {{ $test->randomize_questions ? 'Amestecată la fiecare încercare' : 'Fixă' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="mt-8 rounded-card bg-surface-alt p-6">
                <h2 class="text-[19px] font-bold">Fiecare răspuns are o sursă</h2>
                <p class="mt-2 max-w-3xl text-[15px] leading-7 text-ink-700">
                    Întrebările din acest test poartă explicație, referința pe care se bazează și data ultimei verificări.
                    Dacă găsești o greșeală, o poți raporta direct din test, iar semnalarea intră la revizuire.
                </p>
            </section>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-card border border-line p-6">
                <p class="text-[17px] font-bold">Ce primești</p>

                <ul class="mt-4 space-y-3">
                    @foreach($benefits as $benefit)
                        <li class="flex gap-2.5">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D6E63" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
                            <span class="text-[15px] leading-6">{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>

                @auth
                    <a href="{{ route('tests.start', [$vertical, $test]) }}"
                        class="mt-6 block rounded-btn bg-brand-500 px-5 py-3 text-center text-[15px] font-bold text-white hover:bg-brand-600">
                        Începe testul
                    </a>
                @else
                    <a href="{{ route('login', ['redirect' => $canonical]) }}"
                        class="mt-6 block rounded-btn bg-brand-500 px-5 py-3 text-center text-[15px] font-bold text-white hover:bg-brand-600">
                        Intră în cont ca să începi
                    </a>
                    <a href="{{ route('register') }}"
                        class="mt-3 block rounded-btn border border-line-strong px-5 py-3 text-center text-[15px] font-bold hover:border-brand-500">
                        Creează cont gratuit
                    </a>
                    <p class="mt-3 text-[13px] leading-5 text-ink-500">
                        Testul e gratuit. Contul îți trebuie doar ca să-ți salvăm rezultatul și progresul.
                    </p>
                @endauth

                @auth
                    <div class="mt-4">
                        <livewire:user.favorite-toggle :test="$test" :key="'favorite-test-'.$test->id" />
                    </div>
                @endauth
            </div>
        </aside>

    </div>

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
