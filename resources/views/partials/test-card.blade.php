@php
    use App\Enums\TestMode;
    use App\Services\Content\VerticalTheme;

    $theme = VerticalTheme::for($test->vertical);
    $isExam = $test->mode === TestMode::Exam;

    $minutes = $test->duration_seconds !== null && $test->duration_seconds > 0
        ? (int) round($test->duration_seconds / 60)
        : null;
@endphp

{{--
    Cardul de test. Miniatură colorată cu accentul verticalei, sursa deasupra
    titlului, metadate pe un rând și eticheta „Gratuit”, care e argumentul.
--}}
<a href="{{ $url }}" class="group flex flex-col overflow-hidden rounded-card border border-line transition hover:border-line-strong hover:shadow-sm">
    <span class="flex h-[92px] items-center justify-center" style="background: {{ $theme['solid'] }}">
        <x-vertical-icon :icon="$theme['icon']" :size="32" color="#FFFFFF" :width="1.5" />
    </span>

    <span class="flex flex-1 flex-col gap-1.5 p-4">
        <span class="text-xs text-ink-500">
            {{ $test->taxonomyNode?->name ?? $test->vertical->name }}
        </span>

        <span class="text-[15px] font-bold leading-snug group-hover:text-brand-500">{{ $test->title }}</span>

        <span class="mt-auto pt-1.5 text-xs text-ink-500">
            {{ $isExam ? 'Examen' : 'Exersare' }}
            @if($test->question_limit)
                · {{ $test->question_limit }} întrebări
            @endif
            @if($minutes !== null)
                · {{ $minutes }} min
            @else
                · fără timp
            @endif
        </span>

        <span class="text-xs font-bold text-free">Gratuit</span>
    </span>
</a>
