<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Administrare — e-test.ro' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{--
        Administrarea rulează în panoul Filament, la /admin. Layout-ul acesta
        rămâne doar ca plasă de siguranță pentru componentele care încă declară
        #[Layout('layouts.admin')]; în panou sunt randate inline, deci atributul
        nu se aplică.
    --}}
    <main class="mx-auto max-w-[1600px] px-5 py-8 lg:px-8">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
