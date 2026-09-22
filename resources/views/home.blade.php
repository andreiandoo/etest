@extends('layouts.app')

@section('content')
<section class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[radial-gradient(circle_at_20%_15%,rgba(99,102,241,0.18),transparent_33%),radial-gradient(circle_at_80%_0%,rgba(14,165,233,0.14),transparent_30%)]"></div>

    <div class="mx-auto max-w-7xl px-5 pb-16 pt-20 lg:px-8 lg:pb-24 lg:pt-28">
        <div class="max-w-5xl">
            <p class="mb-5 text-sm font-extrabold uppercase tracking-[0.22em] text-indigo-600">Teste gratuite. Progres măsurabil.</p>
            <h1 class="max-w-5xl text-5xl font-extrabold tracking-[-0.04em] sm:text-6xl lg:text-7xl">
                Exersează pentru examenul care contează.
            </h1>
            <p class="mt-7 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                {{ $tenantContext->brand('tagline', 'e-test.ro organizează testele pe domenii, examene și materii, cu explicații, surse și progres salvat. Accesul la teste rămâne gratuit.') }}
            </p>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="#domenii" class="rounded-2xl bg-indigo-600 px-6 py-3 font-bold text-white shadow-lg shadow-indigo-600/20">Alege domeniul</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-300 bg-white/70 px-6 py-3 font-bold backdrop-blur dark:border-white/15 dark:bg-white/5">Continuă din cont</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-2xl border border-slate-300 bg-white/70 px-6 py-3 font-bold backdrop-blur dark:border-white/15 dark:bg-white/5">Creează cont gratuit</a>
                @endauth
            </div>
        </div>

        <div class="mt-14 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 backdrop-blur dark:border-white/10 dark:bg-white/5">
                <p class="text-3xl font-extrabold">{{ $verticals->count() }}</p>
                <p class="mt-1 text-sm text-slate-500">domenii active</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 backdrop-blur dark:border-white/10 dark:bg-white/5">
                <p class="text-3xl font-extrabold">{{ $publishedTestsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">teste publicate</p>
            </div>
            <div class="col-span-2 rounded-2xl border border-slate-200/80 bg-white/70 p-5 backdrop-blur sm:col-span-1 dark:border-white/10 dark:bg-white/5">
                <p class="text-3xl font-extrabold">{{ $publishedQuestionsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">întrebări verificate</p>
            </div>
        </div>
    </div>
</section>

<section id="domenii" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-12 lg:px-8 lg:py-16">
    <div class="max-w-3xl">
        <p class="text-sm font-extrabold uppercase tracking-[0.2em] text-indigo-600">Domenii</p>
        <h2 class="mt-3 text-4xl font-extrabold tracking-tight">Pornește de la obiectivul tău</h2>
        <p class="mt-4 text-slate-600 dark:text-slate-300">Intră într-un domeniu și mergi până la examen, materie sau capitol. Structura poate fi diferită pentru fiecare verticală.</p>
    </div>

    <div class="mt-9 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($verticals as $vertical)
            <a href="{{ route('verticals.show', $vertical) }}"
                class="group rounded-[1.75rem] border border-slate-200 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-slate-900/5 dark:border-white/10 dark:bg-white/5 dark:hover:border-indigo-400/30">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-lg font-extrabold text-indigo-600 dark:bg-indigo-500/10">
                        {{ mb_strtoupper(mb_substr($vertical->name, 0, 1)) }}
                    </div>
                    <span class="text-sm font-bold text-slate-400 transition group-hover:text-indigo-600">→</span>
                </div>
                <h3 class="mt-5 text-xl font-extrabold">{{ $vertical->name }}</h3>
                <p class="mt-3 min-h-12 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $vertical->description }}</p>
                <p class="mt-5 text-sm font-bold text-indigo-600">{{ $vertical->published_tests_count }} teste publicate</p>
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-slate-500 dark:border-white/15">
                Verticalele sunt în curs de configurare.
            </div>
        @endforelse
    </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-12 lg:px-8 lg:py-16">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-3xl bg-slate-950 p-7 text-white dark:bg-white dark:text-slate-950">
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] opacity-60">01</p>
            <h2 class="mt-4 text-2xl font-extrabold">Teste gratuite</h2>
            <p class="mt-3 text-sm leading-6 opacity-75">Nu blocăm întrebările în spatele unui abonament. Contul există pentru progres, istoric și personalizare.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-7 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">02</p>
            <h2 class="mt-4 text-2xl font-extrabold">Conținut cu sursă</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Întrebările pot avea explicații, surse și dată de verificare, iar problemele pot fi raportate direct din test.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-7 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">03</p>
            <h2 class="mt-4 text-2xl font-extrabold">Structură pe obiective</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">De la auto la medicină sau drept, fiecare domeniu poate avea propria ierarhie de examene, materii și capitole.</p>
        </div>
    </div>
</section>
@endsection
