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
    <div class="min-h-screen bg-slate-100 text-slate-950 dark:bg-slate-950 dark:text-slate-50">
        <header class="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-slate-900">
            <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-4 px-5 py-4 lg:px-8">
                <a href="{{ route('admin.dashboard') }}" class="mr-3 text-xl font-extrabold">e-test<span class="text-indigo-600">.ro</span> Admin</a>
                <nav class="flex flex-wrap gap-1 text-sm font-semibold">
                    <a href="{{ route('admin.verticals') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Verticale</a>
                    <a href="{{ route('admin.taxonomy') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Taxonomie</a>
                    <a href="{{ route('admin.tests') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Teste</a>
                    <a href="{{ route('admin.questions') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Întrebări</a>
                    <a href="{{ route('admin.imports') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Import</a>
                    <a href="{{ route('admin.review') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Review</a>
                    <a href="{{ route('admin.quality') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Calitate</a>
                    <span class="mx-1 hidden h-6 w-px bg-slate-200 lg:block dark:bg-white/10"></span>
                    <a href="{{ route('admin.sponsors') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Sponsori</a>
                    <a href="{{ route('admin.leads') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Lead-uri</a>
                    <a href="{{ route('admin.affiliate') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Afiliere</a>
                    <a href="{{ route('admin.newsletter') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">Newsletter</a>
                    <span class="mx-1 hidden h-6 w-px bg-slate-200 lg:block dark:bg-white/10"></span>
                    <a href="{{ route('admin.tenants') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">White-label</a>
                    <a href="{{ route('admin.api') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/10">API</a>
                </nav>
                <a href="{{ route('home') }}" class="ml-auto text-sm font-bold text-indigo-600">Vezi site-ul ↗</a>
            </div>
        </header>

        @if(session('admin_message'))
            <div class="mx-auto mt-5 max-w-[1600px] px-5 lg:px-8">
                <div class="rounded-2xl bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-100">
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
