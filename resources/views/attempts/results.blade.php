@extends('layouts.app')

@section('content')
@php
    $test = $attempt->test;
    $vertical = $test->vertical;
    $percentage = (float) $attempt->percentage;
    $passing = $test->passing_percentage !== null ? (float) $test->passing_percentage : null;
    $passed = $passing === null || $percentage >= $passing;
    $expired = ($attempt->configuration['completion_reason'] ?? 'submitted') === 'expired';
@endphp

<section class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
    <a href="{{ $presentationUrl }}" class="text-sm font-bold text-indigo-600">← {{ $test->title }}</a>

    <div class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-bold uppercase tracking-[0.18em] {{ $passed ? 'text-emerald-600' : 'text-amber-600' }}">
            {{ $expired ? 'Timp expirat' : ($passed ? 'Test finalizat' : 'Sub pragul de promovare') }}
        </p>

        <div class="mt-5 grid gap-6 sm:grid-cols-3">
            <div>
                <p class="text-sm text-slate-500">Scor</p>
                <p class="mt-1 text-3xl font-extrabold">{{ $attempt->score }} / {{ $attempt->max_score }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Procent</p>
                <p class="mt-1 text-3xl font-extrabold">{{ number_format($percentage, 1) }}%</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Durată</p>
                <p class="mt-1 text-3xl font-extrabold">{{ intdiv((int) $attempt->duration_seconds, 60) }}m {{ (int) $attempt->duration_seconds % 60 }}s</p>
            </div>
        </div>

        @if($passing !== null)
            <p class="mt-6 text-sm text-slate-600 dark:text-slate-300">Prag de promovare: {{ number_format($passing, 1) }}%.</p>
        @endif
    </div>

    @if($test->allow_review)
        <div class="mt-8 space-y-4">
            @foreach($attempt->questions as $attemptQuestion)
                @php
                    $snapshot = $attemptQuestion->question_snapshot;
                    $answer = $attemptQuestion->answer;
                    $options = $snapshot['options'] ?? [];
                    $selected = $answer?->answer['selected'] ?? [];
                @endphp

                <article class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-extrabold">Întrebarea {{ $attemptQuestion->position + 1 }}</p>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $answer?->is_correct ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-100' }}">
                            {{ $answer?->awarded_points ?? 0 }} / {{ $attemptQuestion->points }} pct.
                        </span>
                    </div>

                    <h2 class="mt-4 text-lg font-extrabold">{{ $snapshot['prompt'] ?? '' }}</h2>

                    @if(in_array($snapshot['type'] ?? null, ['single_choice', 'multiple_choice'], true))
                        <div class="mt-4 space-y-2">
                            @foreach($options as $option)
                                @php
                                    $wasSelected = in_array((int) $option['id'], array_map('intval', (array) $selected), true);
                                    $isCorrect = (bool) ($option['is_correct'] ?? false);
                                @endphp
                                <div class="rounded-xl border px-4 py-3 text-sm {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : ($wasSelected ? 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10' : 'border-slate-200 dark:border-white/10') }}">
                                    {{ $option['content'] }}
                                    @if($wasSelected) <span class="ml-2 font-bold">— răspunsul tău</span> @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif($answer)
                        <div class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-sm dark:bg-white/10">
                            Răspunsul tău: <code>{{ json_encode($answer->answer, JSON_UNESCAPED_UNICODE) }}</code>
                        </div>
                    @endif

                    @if($test->show_explanations && !empty($snapshot['explanation']))
                        <div class="mt-5 border-t border-slate-200 pt-5 dark:border-white/10">
                            <p class="text-sm font-extrabold">Explicație</p>
                            <p class="mt-2 leading-7 text-slate-600 dark:text-slate-300">{{ $snapshot['explanation'] }}</p>

                            @if(!empty($snapshot['source_url']))
                                <a href="{{ $snapshot['source_url'] }}" rel="nofollow noopener" target="_blank" class="mt-3 inline-block text-sm font-bold text-indigo-600">
                                    Sursă: {{ $snapshot['source_label'] ?? 'documentație' }} ↗
                                </a>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
