@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-12 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">Dashboard</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight">Salut, {{ auth()->user()->name }}.</h1>
            <p class="mt-4 text-slate-600 dark:text-slate-300">Aici vor apărea progresul, testele recente, streak-ul, punctele slabe și recomandările tale.</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-white/15">Ieși din cont</button>
        </form>
    </div>
</section>
@endsection
