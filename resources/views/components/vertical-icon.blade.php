@props(['icon' => 'book', 'size' => 24, 'color' => 'currentColor', 'width' => 1.6])

{{-- Pictogramele verticalelor. SVG inline, niciodată emoji. --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
    stroke="{{ $color }}" stroke-width="{{ $width }}" stroke-linecap="round" stroke-linejoin="round"
    aria-hidden="true">
    @switch($icon)
        @case('wheel')
            <circle cx="12" cy="12" r="9"></circle>
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 3v6M4.2 16.5l5.2-3M19.8 16.5l-5.2-3"></path>
            @break

        @case('pulse')
            <path d="M2 12h4l2.5-6 4 13 2.5-7h5"></path>
            @break

        @case('scales')
            <path d="M12 4v16M5 8h14M7 8l-3 6h6zM17 8l-3 6h6z"></path>
            @break

        @case('terminal')
            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
            <path d="M7 9l3 3-3 3M13 15h5"></path>
            @break

        @case('globe')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"></path>
            @break

        @default
            <path d="M4 19V5a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path>
            <path d="M8 8h8M8 12h8M8 16h5"></path>
    @endswitch
</svg>
