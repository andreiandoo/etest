<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $brandPrimary = (string) $tenantContext->brand('primary_color', '#4f46e5');
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-extrabold tracking-tight">
                @if(is_string($brandLogo) && $brandLogo !== '')
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 max-w-40 object-contain">
                @else
                    <span style="color: var(--brand-primary)">{{ $brandName }}</span>
                @endif
            </a>

            <nav class="flex items-center gap-2 text-sm font-semibold">
                <a href="{{ route('home') }}#domenii" class="hidden rounded-xl px-3 py-2 hover:bg-slate-100 sm:block dark:hover:bg-white/10">Domenii</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Contul meu</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Autentificare</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-white dark:bg-white dark:text-slate-950">Cont gratuit</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <footer class="mt-20 border-t border-slate-200 bg-white dark:border-white/10 dark:bg-slate-950">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 sm:grid-cols-2 lg:px-8">
            <div>
                <p class="text-xl font-extrabold" style="color: var(--brand-primary)">{{ $brandName }}</p>
                <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">{{ $brandFooter }}</p>
            </div>
            <div class="flex items-start gap-5 text-sm font-semibold sm:justify-end">
                <a href="{{ route('home') }}#domenii" class="hover:text-indigo-600">Domenii</a>
                @guest
                    <a href="{{ route('register') }}" class="hover:text-indigo-600">Creează cont</a>
                @endguest
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
