@extends('layouts.app')

@section('content')
@php
    use App\Services\Content\VerticalTheme;

    $test = $attempt->test;
    $vertical = $test->vertical;
    $theme = VerticalTheme::for($vertical);

    $percentage = (int) round((float) ($attempt->percentage ?? 0));
    $passing = $test->passing_percentage !== null ? (float) $test->passing_percentage : null;
    $passed = $passing !== null && $percentage >= $passing;

    $correctCount = $attempt->questions->filter(fn ($q) => $q->answer?->is_correct === true)->count();
    $totalCount = $attempt->questions->count();

    // Inelul de scor. Circumferinta unui cerc cu raza 102.
    $ringLength = 640.9;
    $ringFilled = round($ringLength * ($percentage / 100), 1);
    $ringColor = $passing === null
        ? '#0056D2'
        : ($passed ? '#0D6E63' : '#B4451F');

    $weakest = $breakdown[0] ?? null;
    $hasWeakSpot = $weakest !== null && $weakest['percentage'] < 100 && count($breakdown) > 1;

    $minutes = intdiv($attempt->duration_seconds, 60);
    $seconds = $attempt->duration_seconds % 60;
@endphp

<div class="mx-auto max-w-[1440px] px-5 py-8 lg:px-10">

    <nav aria-label="Firimituri" class="flex flex-wrap items-center gap-2 text-[13px] text-ink-500">
        <a href="{{ $verticalUrl }}" class="hover:text-brand-500">{{ $vertical->name }}</a>
        <span aria-hidden="true">&rsaquo;</span>
        <a href="{{ $presentationUrl }}" class="hover:text-brand-500">{{ $test->title }}</a>
        <span aria-hidden="true">&rsaquo;</span>
        <span aria-current="page" class="text-ink-900">Rezultat</span>
    </nav>

    <div class="mt-6 grid gap-8 lg:grid-cols-[400px_1fr]">

        <div class="flex flex-col gap-5">
            <div class="flex flex-col items-center gap-5 rounded-card border border-line p-7">
                <div class="relative flex size-[240px] items-center justify-center">
                    <svg width="240" height="240" viewBox="0 0 240 240" fill="none" aria-hidden="true">
                        <circle cx="120" cy="120" r="102" stroke="#EDEFF3" stroke-width="16"></circle>
                        <circle cx="120" cy="120" r="102" stroke="{{ $ringColor }}" stroke-width="16" stroke-linecap="round"
                            stroke-dasharray="{{ $ringFilled }} {{ $ringLength }}" transform="rotate(-90 120 120)"></circle>
                    </svg>
                    <div class="absolute flex flex-col items-center">
                        <p class="text-[62px] font-bold leading-none tabular-nums">{{ $percentage }}<span class="text-[30px] text-ink-500">%</span></p>
                        <p class="mt-1 text-[15px] text-ink-500">{{ $correctCount }} din {{ $totalCount }} corecte</p>
                    </div>
                </div>

                @if($passing !== null)
                    <div class="flex w-full items-start gap-3 rounded-card p-4 {{ $passed ? 'bg-[#EAF4F2]' : 'bg-[#FBEEE9]' }}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ $passed ? '#0D6E63' : '#B4451F' }}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true">
                            @if($passed)
                                <path d="M20 6L9 17l-5-5"></path>
                            @else
                                <path d="M12 9v4M12 17h.01M10.3 3.9L2.4 17a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path>
                            @endif
                        </svg>
                        <div>
                            <p class="text-[17px] font-bold">{{ $passed ? 'Ai trece examenul' : 'Încă nu ai atinge pragul' }}</p>
                            <p class="mt-0.5 text-sm leading-6 {{ $passed ? 'text-[#235B54]' : 'text-[#8C3617]' }}">
                                Pragul pentru acest test este {{ rtrim(rtrim(number_format($passing, 2, ',', ' '), '0'), ',') }}%.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-card border border-line p-4">
                    <p class="text-[24px] font-bold text-brand-500 tabular-nums">+{{ $xpEarned }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-500">XP câștigat</p>
                </div>
                <div class="rounded-card border border-line p-4">
                    <p class="text-[24px] font-bold tabular-nums">{{ $currentStreak }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-500">{{ $currentStreak === 1 ? 'zi la rând' : 'zile la rând' }}</p>
                </div>
                <div class="rounded-card border border-line p-4">
                    <p class="text-[24px] font-bold tabular-nums">{{ $minutes }}:{{ str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-500">timp folosit</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col">
            <h1 class="text-[34px] font-bold leading-tight tracking-tight">
                @if($hasWeakSpot)
                    Cel mai mult pierzi la „{{ $weakest['name'] }}”
                @elseif($percentage === 100)
                    Punctaj maxim. Nimic de reparat aici.
                @else
                    Rezultatul tău la {{ $test->title }}
                @endif
            </h1>

            @if($hasWeakSpot)
                <p class="mt-2 max-w-2xl text-[15px] leading-7 text-ink-700">
                    Ai {{ $weakest['correct'] }} din {{ $weakest['total'] }} corecte la acest capitol. Reia-l înainte de următoarea simulare.
                </p>
            @endif

            @if(count($breakdown) > 0)
                <section class="mt-7">
                    <h2 class="text-[15px] font-bold text-ink-500">Rezultat pe capitole</h2>
                    <ul class="mt-4 space-y-3.5">
                        @foreach($breakdown as $group)
                            @php($isWeak = $group['percentage'] < 60)
                            <li class="flex items-center gap-5">
                                <span class="w-52 shrink-0 text-[15px] font-semibold">{{ $group['name'] }}</span>
                                <span class="relative h-2 flex-1 overflow-hidden rounded-full bg-[#EDEFF3]">
                                    <span class="absolute inset-y-0 left-0 rounded-full"
                                        style="width: {{ max($group['percentage'], 2) }}%; background: {{ $isWeak ? '#B4451F' : '#0D6E63' }}"></span>
                                </span>
                                <span class="w-24 shrink-0 text-right text-sm font-bold tabular-nums {{ $isWeak ? 'text-vert-auto' : 'text-ink-500' }}">
                                    {{ $group['correct'] }} din {{ $group['total'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="mt-auto pt-8">
                @if($hasWeakSpot && $weakest['node_id'] !== null)
                    <div class="flex flex-col items-start justify-between gap-4 rounded-card bg-surface-warm p-6 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-btn" style="background: {{ $theme['soft'] }}">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $theme['solid'] }}" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l5-5 4 4 8-8"></path><path d="M15 8h5v5"></path></svg>
                            </span>
                            <div>
                                <p class="text-[17px] font-bold">Reia capitolul „{{ $weakest['name'] }}”</p>
                                <p class="mt-0.5 text-sm leading-6 text-ink-700">Exersează doar pe acest capitol, până nu mai greșești.</p>
                            </div>
                        </div>
                        <a href="{{ $verticalUrl }}" class="shrink-0 rounded-btn bg-brand-500 px-6 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
                            Vezi testele capitolului
                        </a>
                    </div>
                @endif

                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <a href="#raspunsuri" class="rounded-btn border-[1.5px] border-line-strong px-5 py-3 text-center text-[15px] font-bold hover:border-brand-500">
                        Vezi toate răspunsurile
                    </a>
                    <a href="{{ $presentationUrl }}" class="rounded-btn border-[1.5px] border-line-strong px-5 py-3 text-center text-[15px] font-bold hover:border-brand-500">
                        Repetă testul
                    </a>
                    <a href="{{ route('dashboard') }}" class="rounded-btn border-[1.5px] border-line-strong px-5 py-3 text-center text-[15px] font-bold hover:border-brand-500">
                        Panoul meu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section id="raspunsuri" class="mt-14 scroll-mt-24">
        <h2 class="text-[21px] font-bold">Toate răspunsurile</h2>
        <p class="mt-1 text-[15px] text-ink-700">Verde e ce ai nimerit, gri e varianta corectă acolo unde ai greșit.</p>

        <ol class="mt-6 space-y-4">
            @foreach($attempt->questions as $attemptQuestion)
                @php
                    $snapshot = $attemptQuestion->question_snapshot;
                    $answer = $attemptQuestion->answer;
                    $isCorrect = $answer?->is_correct === true;
                    $selected = (array) ($answer->answer['selected'] ?? []);
                @endphp

                <li class="rounded-card border border-line p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="flex items-center gap-2.5 text-[13px] font-bold {{ $isCorrect ? 'text-[#0A574E]' : 'text-vert-auto' }}">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                @if($isCorrect)
                                    <path d="M20 6L9 17l-5-5"></path>
                                @else
                                    <path d="M18 6L6 18M6 6l12 12"></path>
                                @endif
                            </svg>
                            Întrebarea {{ $attemptQuestion->position + 1 }} · {{ $isCorrect ? 'corect' : 'greșit' }}
                        </span>
                        <span class="text-[13px] text-ink-500">
                            {{ $answer?->awarded_points ?? 0 }} din {{ $attemptQuestion->points }} puncte
                        </span>
                    </div>

                    <p class="mt-3 text-[17px] font-bold leading-snug">{{ $snapshot['prompt'] ?? '' }}</p>

                    @if(! empty($snapshot['options']))
                        <ul class="mt-3 space-y-1.5">
                            @foreach($snapshot['options'] as $option)
                                @php
                                    $wasSelected = in_array((string) $option['id'], array_map('strval', $selected), true);
                                    $isRight = ($option['is_correct'] ?? false) === true;
                                @endphp
                                <li class="flex items-start gap-2.5 rounded-btn px-3 py-2 text-[15px] leading-6
                                    {{ $isRight ? 'bg-[#EAF4F2]' : ($wasSelected ? 'bg-[#FBEEE9]' : '') }}">
                                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $isRight ? 'bg-[#0D6E63]' : ($wasSelected ? 'bg-vert-auto' : 'bg-line-strong') }}"></span>
                                    <span>{{ $option['content'] }}</span>
                                    @if($wasSelected)
                                        <span class="ml-auto shrink-0 text-[12px] font-bold text-ink-500">răspunsul tău</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if(! empty($snapshot['explanation']))
                        <p class="mt-4 border-t border-line pt-3 text-[15px] leading-7 text-ink-700">{{ $snapshot['explanation'] }}</p>
                    @endif

                    @if(! empty($snapshot['source_label']))
                        <p class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-ink-500">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M8 8h8M8 12h8"></path></svg>
                            <span class="font-semibold text-ink-700">{{ $snapshot['source_label'] }}</span>
                            @if(! empty($snapshot['source_checked_at']))
                                <span>· verificat {{ $snapshot['source_checked_at'] }}</span>
                            @endif
                        </p>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

</div>
@endsection
