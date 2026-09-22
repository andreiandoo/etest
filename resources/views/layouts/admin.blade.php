<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin — e-test.ro' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="min-h-screen bg-surface-alt text-ink-900">
        <header class="border-b border-line bg-white">
            <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-4 px-5 py-4 lg:px-8">
                <a href="{{ route('admin.dashboard') }}" class="mr-3 text-xl font-bold">e-test<span class="text-brand-500">.ro</span> Admin</a>
                <nav class="flex flex-wrap gap-1 text-sm font-semibold">
                    <a href="{{ route('admin.verticals') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Verticale</a>
                    <a href="{{ route('admin.taxonomy') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Taxonomie</a>
                    <a href="{{ route('admin.tests') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Teste</a>
                    <a href="{{ route('admin.questions') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Întrebări</a>
                    <a href="{{ route('admin.imports') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Import</a>
                    <a href="{{ route('admin.review') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Review</a>
                    <a href="{{ route('admin.quality') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Calitate</a>
                    <span class="mx-1 hidden h-6 w-px bg-line lg:block"></span>
                    <a href="{{ route('admin.sponsors') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Sponsori</a>
                    <a href="{{ route('admin.leads') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Lead-uri</a>
                    <a href="{{ route('admin.affiliate') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Afiliere</a>
                    <a href="{{ route('admin.newsletter') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">Newsletter</a>
                    <span class="mx-1 hidden h-6 w-px bg-line lg:block"></span>
                    <a href="{{ route('admin.tenants') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">White-label</a>
                    <a href="{{ route('admin.api') }}" class="rounded-btn px-3 py-2 hover:bg-surface-alt">API</a>
                </nav>
                <a href="{{ route('home') }}" class="ml-auto text-sm font-bold text-brand-500">Vezi site-ul ↗</a>
            </div>
        </header>

        @if(session('admin_message'))
            <div class="mx-auto mt-5 max-w-[1600px] px-5 lg:px-8">
                <div class="rounded-card bg-[#EAF4F2] px-5 py-3 text-sm font-semibold text-[#0A574E]">
                    {{ session('admin_message') }}
                </div>
            </div>
        @endif

        <main class="mx-auto max-w-[1600px] px-5 py-8 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
