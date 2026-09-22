@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-2xl px-5 py-24 text-center lg:px-10">
    <p class="text-[15px] font-bold text-vert-auto">Eroare 500</p>
    <h1 class="mt-3 text-[38px] font-bold tracking-tight">Ceva s-a stricat la noi</h1>
    <p class="mx-auto mt-3 max-w-lg text-[16px] leading-7 text-ink-700">
        Nu e din cauza ta. Eroarea a fost înregistrată. Încearcă din nou peste câteva momente.
    </p>
    <a href="{{ route('home') }}" class="mt-8 inline-block rounded-btn bg-brand-500 px-6 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
        Înapoi la prima pagină
    </a>
</section>
@endsection
