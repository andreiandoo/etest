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

        @case('truck')
            <path d="M3 7h10v9H3zM13 10h4l3 3v3h-7z"></path>
            <circle cx="7" cy="18" r="1.8"></circle>
            <circle cx="17" cy="18" r="1.8"></circle>
            @break

        @case('pulse')
            <path d="M2 12h4l2.5-6 4 13 2.5-7h5"></path>
            @break

        @case('scales')
            <path d="M12 4v16M5 8h14M7 8l-3 6h6zM17 8l-3 6h6z"></path>
            @break

        @case('gavel')
            <path d="M3 21h8M14 3l7 7M17.5 6.5l-4 4M10.5 6.5l4 4M7 10l4 4-4 4-4-4z"></path>
            @break

        @case('terminal')
            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
            <path d="M7 9l3 3-3 3M13 15h5"></path>
            @break

        @case('globe')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"></path>
            @break

        @case('briefcase')
            <rect x="3" y="7" width="18" height="13" rx="2"></rect>
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 12h18"></path>
            @break

        @case('chart')
            <path d="M4 20V4M4 20h16M8 17v-5M12.5 17V8M17 17v-7"></path>
            @break

        @case('receipt')
            <path d="M6 3h12v18l-3-1.6-3 1.6-3-1.6L6 21z"></path>
            <path d="M9.5 8h5M9.5 12h5"></path>
            @break

        @case('calculator')
            <rect x="4" y="3" width="16" height="18" rx="2"></rect>
            <path d="M8 7h8M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01"></path>
            @break

        @case('gauge')
            <path d="M3.5 17a9 9 0 1 1 17 0"></path>
            <path d="M12 17l4-5"></path>
            <circle cx="12" cy="17" r="1.4"></circle>
            @break

        @case('umbrella')
            <path d="M12 3a9 9 0 0 1 9 9H3a9 9 0 0 1 9-9z"></path>
            <path d="M12 12v6a2.5 2.5 0 0 0 5 0"></path>
            @break

        @case('antenna')
            <path d="M12 9v12M6.5 4.5a8 8 0 0 0 0 9M17.5 4.5a8 8 0 0 1 0 9M9.2 7.2a4 4 0 0 0 0 3.6M14.8 7.2a4 4 0 0 1 0 3.6"></path>
            @break

        @case('target')
            <circle cx="12" cy="12" r="8"></circle>
            <circle cx="12" cy="12" r="3.5"></circle>
            <path d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
            @break

        @case('drone')
            <path d="M9.5 9.5h5v5h-5z"></path>
            <path d="M9.5 9.5L6 6M14.5 9.5L18 6M9.5 14.5L6 18M14.5 14.5L18 18"></path>
            <circle cx="5" cy="5" r="2"></circle>
            <circle cx="19" cy="5" r="2"></circle>
            <circle cx="5" cy="19" r="2"></circle>
            <circle cx="19" cy="19" r="2"></circle>
            @break

        @case('bolt')
            <path d="M13.5 2L4 14h7l-.5 8L20 10h-7z"></path>
            @break

        @case('radiation')
            <circle cx="12" cy="12" r="9"></circle>
            <circle cx="12" cy="12" r="2"></circle>
            <path d="M12 3.2a8.8 8.8 0 0 1 4.4 1.2l-3.1 5.3M4.4 16.8a8.8 8.8 0 0 1-1.2-4.4h6.1M19.6 16.8a8.8 8.8 0 0 1-7.6 4h.1l3-5.3"></path>
            @break

        @case('anchor')
            <circle cx="12" cy="5" r="2.2"></circle>
            <path d="M12 7.2V21M8 11h8M4 15a8 8 0 0 0 16 0"></path>
            @break

        @case('train')
            <rect x="5" y="3" width="14" height="13" rx="3"></rect>
            <path d="M5 10h14M9 6h6M7.5 20l1.5-4M16.5 20L15 16"></path>
            <path d="M9 13h.01M15 13h.01"></path>
            @break

        @case('wrench')
            <path d="M15 3a5 5 0 0 0-4.6 7L3 17.4 6.6 21l7.4-7.4A5 5 0 1 0 15 3z"></path>
            @break

        @case('building')
            <path d="M4 21V6l8-3 8 3v15M4 21h16"></path>
            <path d="M9 21v-4h6v4M8.5 9h.01M12 9h.01M15.5 9h.01M8.5 13h.01M12 13h.01M15.5 13h.01"></path>
            @break

        @case('map')
            <path d="M3 6.5l6-2.5 6 2.5 6-2.5v13l-6 2.5-6-2.5-6 2.5z"></path>
            <path d="M9 4v13M15 7v13"></path>
            @break

        @case('leaf')
            <path d="M4 20c0-9 6-14 16-15 0 10-5 15-13 15H4z"></path>
            <path d="M4 20c4-5 7-7.5 11-9"></path>
            @break

        @case('lightbulb')
            <path d="M9 18h6M10 21h4"></path>
            <path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5.9 1.2.9 2h5.2c0-.8.3-1.5.9-2A6 6 0 0 0 12 3z"></path>
            @break

        @case('shield')
            <path d="M12 3l8 3v6c0 5-3.4 8.2-8 9.9C7.4 20.2 4 17 4 12V6z"></path>
            <path d="M9 12l2 2 4-4"></path>
            @break

        @case('cap')
            <path d="M2 9l10-4.5L22 9l-10 4.5z"></path>
            <path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5M20 10v5"></path>
            @break

        @default
            <path d="M4 19V5a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path>
            <path d="M8 8h8M8 12h8M8 16h5"></path>
    @endswitch
</svg>
