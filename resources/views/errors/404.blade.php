@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-2xl px-5 py-24 text-center lg:px-10">
    <p class="text-[15px] font-bold text-brand-500">Eroare 404</p>
    <h1 class="mt-3 text-[38px] font-bold tracking-tight">Pagina asta nu există</h1>
    <p class="mx-auto mt-3 max-w-lg text-[16px] leading-7 text-ink-700">
        Poate linkul e vechi, poate adresa are o greșeală de scriere. Caută ce te interesează sau pornește de la un domeniu.
    </p>

    <form action="{{ route('search') }}" method="GET" role="search"
        class="mx-auto mt-8 flex h-12 max-w-md items-center rounded-full border border-line-strong pl-5 pr-1 focus-within:border-brand-500">
        <label for="notfound-search" class="sr-only">Caută</label>
        <input id="notfound-search" type="search" name="q" placeholder="Ce examen pregătești?"
            class="min-w-0 flex-1 bg-transparent text-[15px] outline-none placeholder:text-ink-500">
        <button type="submit" aria-label="Caută"
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white hover:bg-brand-600">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
        </button>
    </form>

    <a href="{{ route('home') }}" class="mt-6 inline-block text-[15px] font-bold text-brand-500 hover:text-brand-700">
        Înapoi la prima pagină
    </a>
</section>
@endsection
