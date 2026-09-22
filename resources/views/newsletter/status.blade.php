@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-20 text-center lg:px-8">
    <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Newsletter e-test.ro</p>
    <h1 class="mt-4 text-4xl font-extrabold">{{ $title }}</h1>
    <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-600 dark:text-slate-300">{{ $message }}</p>
    <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white">Înapoi la e-test.ro</a>
</section>
@endsection
