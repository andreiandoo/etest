@props(['media' => null, 'compact' => false])

@php
    /**
     * Imaginea unei întrebări: un indicator, un marcaj, o intersecție.
     *
     * Se desenează la fel în timpul testului și în recapitulare, ca să nu se
     * întâmple ca la rezolvare să vezi altceva decât la revizuire. Textul
     * alternativ e obligatoriu la încărcare, iar dacă totuși lipsește punem
     * ceva neutru: o imagine fără alternativă e o întrebare pe care un cititor
     * de ecran nu o poate răspunde deloc.
     */
    $media = is_array($media) ? $media : [];
    $path = trim((string) ($media['path'] ?? ''));
    $alt = trim((string) ($media['alt'] ?? ''));
    $credit = trim((string) ($media['credit'] ?? ''));
    $license = trim((string) ($media['license'] ?? ''));
    $width = (int) ($media['width'] ?? 0);
    $height = (int) ($media['height'] ?? 0);
    $maxHeight = $compact ? 140 : 280;
@endphp

@if($path !== '')
    <figure {{ $attributes->merge(['class' => 'mt-5 max-w-3xl']) }}>
        <div class="flex items-center justify-center rounded-card border-[1.5px] border-line bg-white p-4"
            style="min-height: {{ $compact ? 120 : 180 }}px">
            <img
                src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"
                alt="{{ $alt !== '' ? $alt : 'Imaginea întrebării' }}"
                loading="lazy"
                decoding="async"
                @if($width > 0 && $height > 0) width="{{ $width }}" height="{{ $height }}" @endif
                class="h-auto w-auto object-contain"
                style="max-height: {{ $maxHeight }}px">
        </div>

        @if($credit !== '')
            <figcaption class="mt-2 text-[12px] text-ink-500">
                {{ $credit }}@if($license !== '') · {{ $license }}@endif
            </figcaption>
        @endif
    </figure>
@endif
