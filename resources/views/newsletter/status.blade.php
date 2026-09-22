@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-2xl px-5 py-20 text-center lg:px-10">
    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#0056D2" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
            <path d="M3 7l9 6 9-6"></path>
        </svg>
    </span>

    <h1 class="mt-6 text-[32px] font-bold tracking-tight">{{ $title }}</h1>
    <p class="mx-auto mt-3 max-w-xl text-[16px] leading-7 text-ink-700">{{ $message }}</p>

    <a href="{{ route('home') }}" class="mt-8 inline-block rounded-btn bg-brand-500 px-6 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
        Înapoi la {{ $tenantContext->brand('site_name', 'e-test.ro') }}
    </a>
</section>
@endsection
