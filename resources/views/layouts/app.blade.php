<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Paginile de eroare se randeaza in afara middleware-ului web, deci
        // ResolveTenant poate sa nu fi rulat si $tenantContext sa lipseasca.
        $tenantContext = $tenantContext ?? app(\App\Services\Tenancy\TenantContext::class);
        $navVerticals = $navVerticals ?? collect();

        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $brandPrimary = (string) $tenantContext->brand('primary_color', '#0056D2');
        $brandLogo = $tenantContext->brand('logo_url');
        $brandFavicon = (string) $tenantContext->brand('favicon_url', '/favicon.svg');
        $brandFooter = (string) $tenantContext->brand(
            'footer_text',
            'Teste gratuite pentru pregătire, evaluare și progres. Contul este necesar doar pentru susținerea testelor și salvarea rezultatelor.',
        );
        $pageTitle = $seoTitle ?? $title ?? $brandName;
        $pageDescription = $seoDescription ?? $description ?? 'Teste online gratuite, organizate pe domenii, examene și certificări.';
        $pageCanonical = $canonical ?? null;
    @endphp

    <meta name="theme-color" content="{{ $brandPrimary }}">
    <link rel="icon" href="{{ $brandFavicon }}">
    <style>:root{--brand-primary:{{ $brandPrimary }};}</style>

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' }}">

    @if($pageCanonical)
        <link rel="canonical" href="{{ $pageCanonical }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brandName }}">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    @if($pageCanonical)
        <meta property="og:url" content="{{ $pageCanonical }}">
    @endif

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">

    @isset($structuredData)
        @foreach($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endforeach
    @endisset

    @include('partials.analytics')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{-- Bara de utilități. Publicurile secundare stau aici, nu în navigația principală. --}}
    <div class="bg-navy-900 text-white">
        <div class="mx-auto flex max-w-[1440px] items-center gap-6 px-5 py-2 lg:px-10">
            <span class="text-[13px] font-bold">Pentru candidați</span>
            <a href="{{ route('home') }}#parteneri" class="hidden text-[13px] text-navy-muted hover:text-white sm:block">Pentru școli și centre de formare</a>
            <a href="{{ route('home') }}#parteneri" class="hidden text-[13px] text-navy-muted hover:text-white lg:block">Pentru universități</a>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-line bg-white">
        <div class="mx-auto flex max-w-[1440px] items-center gap-5 px-5 py-3 lg:px-10">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 text-[22px] font-bold tracking-tight">
                @if(is_string($brandLogo) && $brandLogo !== '')
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 max-w-40 object-contain">
                @else
                    <span style="color: var(--brand-primary)">{{ $brandName }}</span>
                @endif
            </a>

            @if($navVerticals->isNotEmpty())
                <details class="group relative hidden shrink-0 md:block">
                    <summary class="flex cursor-pointer list-none items-center gap-1 text-sm font-semibold marker:content-none">
                        Explorează
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="transition group-open:rotate-180" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                    </summary>
                    <div class="absolute left-0 top-full z-50 mt-3 w-72 rounded-card border border-line bg-white p-2 shadow-lg">
                        @foreach($navVerticals as $navVertical)
                            @php($navTheme = \App\Services\Content\VerticalTheme::for($navVertical))
                            <a href="{{ route('verticals.show', $navVertical) }}" class="flex items-center gap-3 rounded-btn px-3 py-2 hover:bg-surface-alt">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-btn" style="background: {{ $navTheme['soft'] }}">
                                    <x-vertical-icon :icon="$navTheme['icon']" :size="17" :color="$navTheme['solid']" />
                                </span>
                                <span class="text-sm font-semibold">{{ $navVertical->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif

            <form action="{{ route('search') }}" method="GET" role="search"
                class="flex h-10 min-w-0 flex-1 items-center rounded-full border border-line-strong pl-4 pr-1 focus-within:border-brand-500">
                <label for="site-search" class="sr-only">Caută un examen, o materie sau un capitol</label>
                <input id="site-search" type="search" name="q" value="{{ request()->query('q') }}"
                    placeholder="Ce examen pregătești?"
                    class="min-w-0 flex-1 bg-transparent text-sm text-ink-900 placeholder:text-ink-500 focus:outline-none">
                <button type="submit" aria-label="Caută"
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white hover:bg-brand-600">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                </button>
            </form>

            <nav class="flex shrink-0 items-center gap-4 text-sm font-semibold">
                @auth
                    <a href="{{ route('history') }}" class="hidden hover:text-brand-500 lg:block">Istoric</a>
                    <a href="{{ route('dashboard') }}" class="text-brand-500 hover:text-brand-700">Contul meu</a>
                @else
                    <a href="{{ route('login') }}" class="text-brand-500 hover:text-brand-700">Intră în cont</a>
                    <a href="{{ route('register') }}" class="rounded-btn border border-brand-500 px-4 py-2 text-brand-500 hover:bg-brand-50">Începe gratuit</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <footer class="mt-16 bg-surface-alt">
        <div class="mx-auto max-w-[1440px] px-5 py-10 lg:px-10">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-[15px] font-bold">Domenii</p>
                    <ul class="mt-3 space-y-2">
                        @forelse($navVerticals as $navVertical)
                            <li>
                                <a href="{{ route('verticals.show', $navVertical) }}" class="text-[13px] text-ink-700 hover:text-brand-500">{{ $navVertical->name }}</a>
                            </li>
                        @empty
                            <li class="text-[13px] text-ink-500">În curs de configurare</li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <p class="text-[15px] font-bold">Platformă</p>
                    <ul class="mt-3 space-y-2">
                        <li><a href="{{ route('home') }}#cum-functioneaza" class="text-[13px] text-ink-700 hover:text-brand-500">Cum funcționează</a></li>
                        <li><a href="{{ route('home') }}#surse" class="text-[13px] text-ink-700 hover:text-brand-500">Cum verificăm conținutul</a></li>
                        <li><a href="{{ route('home') }}#intrebari" class="text-[13px] text-ink-700 hover:text-brand-500">Întrebări frecvente</a></li>
                        <li><a href="{{ route('sitemap') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Harta site-ului</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-[15px] font-bold">Contul tău</p>
                    <ul class="mt-3 space-y-2">
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Panoul meu</a></li>
                            <li><a href="{{ route('history') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Istoricul testelor</a></li>
                            <li><a href="{{ route('leaderboard') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Clasament</a></li>
                        @else
                            <li><a href="{{ route('register') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Creează cont gratuit</a></li>
                            <li><a href="{{ route('login') }}" class="text-[13px] text-ink-700 hover:text-brand-500">Intră în cont</a></li>
                        @endauth
                    </ul>
                </div>

                <div>
                    <p class="text-[15px] font-bold">{{ $brandName }}</p>
                    <p class="mt-3 max-w-xs text-[13px] leading-6 text-ink-700">{{ $brandFooter }}</p>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-2 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[13px] text-ink-500">&copy; {{ now()->year }} {{ $brandName }}</p>
                <p class="text-[13px] text-ink-500">Testele sunt instrument de exersare și nu reprezintă subiecte oficiale de examen.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
