@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1440px] px-5 lg:px-10">

    {{-- Două bannere, nu un hero editorial: primul lucru de pe pagină e o ofertă concretă. --}}
    <section class="grid gap-4 pt-6 lg:grid-cols-2">
        <div class="flex items-center gap-5 overflow-hidden rounded-card bg-brand-500 p-7">
            <div class="flex flex-col items-start gap-2.5">
                <span class="rounded-[3px] bg-white px-2 py-0.5 text-xs font-bold text-brand-500">GRATUIT</span>
                <h2 class="text-[27px] font-bold leading-tight text-white">Toate testele sunt gratuite. Fără excepții.</h2>
                <p class="text-sm leading-relaxed text-brand-100">
                    Nicio întrebare nu stă în spatele unui abonament. Contul există ca să-ți salvăm progresul, nu ca să-ți vindem accesul.
                </p>
                @guest
                    <a href="{{ route('register') }}" class="mt-1 rounded-btn bg-white px-5 py-2.5 text-sm font-bold text-brand-500 hover:bg-brand-50">Creează cont gratuit</a>
                @else
                    <a href="{{ route('dashboard') }}" class="mt-1 rounded-btn bg-white px-5 py-2.5 text-sm font-bold text-brand-500 hover:bg-brand-50">Continuă de unde ai rămas</a>
                @endguest
            </div>
            <svg width="150" height="150" viewBox="0 0 170 170" fill="none" class="hidden shrink-0 sm:block" aria-hidden="true">
                <circle cx="85" cy="85" r="76" stroke="#3E7AE0" stroke-width="1.5"></circle>
                <circle cx="85" cy="85" r="56" stroke="#3E7AE0" stroke-width="1.5"></circle>
                <path d="M85 9a76 76 0 0 1 66 38" stroke="#FFD24D" stroke-width="5" stroke-linecap="round"></path>
                <path d="M55 88l20 20 42-46" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>

        <div class="flex items-center gap-5 overflow-hidden rounded-card bg-navy-900 p-7">
            <div class="flex flex-col items-start gap-2.5">
                <span class="rounded-[3px] bg-[#FFD24D] px-2 py-0.5 text-xs font-bold text-[#3A2A00]">CU SURSĂ</span>
                <h2 class="text-[27px] font-bold leading-tight text-white">Fiecare răspuns arată de unde vine</h2>
                <p class="text-sm leading-relaxed text-navy-muted">
                    Explicație, referință și dată de verificare la fiecare întrebare. Dacă găsești o greșeală, o raportezi direct din test.
                </p>
                <a href="#surse" class="mt-1 rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">Cum verificăm conținutul</a>
            </div>
            <svg width="150" height="150" viewBox="0 0 170 170" fill="none" class="hidden shrink-0 sm:block" aria-hidden="true">
                <circle cx="85" cy="85" r="74" stroke="#2A4E85" stroke-width="1.5"></circle>
                <circle cx="85" cy="85" r="52" stroke="#2A4E85" stroke-width="1.5"></circle>
                <path d="M85 11a74 74 0 0 1 64 37" stroke="#FF7043" stroke-width="5" stroke-linecap="round"></path>
                <path d="M60 46h40l16 16v62H60z" stroke="#FFFFFF" stroke-width="4" stroke-linejoin="round"></path>
                <path d="M72 74h44M72 92h44M72 108h26" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"></path>
            </svg>
        </div>
    </section>

    {{-- Domenii --}}
    <section id="domenii" class="scroll-mt-24 pt-10">
        <div class="flex items-end justify-between gap-6">
            <div>
                <h2 class="text-[21px] font-bold">Alege domeniul tău</h2>
                <p class="mt-1 text-[15px] text-ink-700">Fiecare domeniu are structura lui: categorii, materii și capitole.</p>
            </div>
        </div>

        @if($verticals->isEmpty())
            <div class="mt-4 rounded-card border border-dashed border-line-strong p-8 text-center">
                <p class="text-[17px] font-bold">Domeniile sunt în curs de configurare</p>
                <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                    Revino în curând. Între timp îți poți crea contul, ca să pornești imediat ce primul domeniu e publicat.
                </p>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @foreach($verticals as $vertical)
                    @php($theme = \App\Services\Content\VerticalTheme::for($vertical))
                    <a href="{{ $urlGenerator->vertical($vertical) }}"
                        class="group flex flex-col overflow-hidden rounded-card border border-line transition hover:border-line-strong hover:shadow-sm">
                        <span class="flex h-24 items-center justify-center" style="background: {{ $theme['soft'] }}">
                            <x-vertical-icon :icon="$theme['icon']" :size="40" :color="$theme['solid']" :width="1.5" />
                        </span>
                        <span class="flex flex-1 flex-col gap-1.5 p-4">
                            <span class="text-[17px] font-bold group-hover:text-brand-500">{{ $vertical->name }}</span>
                            @if($vertical->description)
                                <span class="line-clamp-2 text-sm leading-relaxed text-ink-700">{{ $vertical->description }}</span>
                            @endif
                            <span class="mt-auto pt-1.5 text-[13px] font-semibold text-ink-500">
                                {{ $vertical->published_tests_count }} {{ $vertical->published_tests_count === 1 ? 'test' : 'teste' }}
                                · {{ $vertical->published_questions_count }} întrebări
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Trasee --}}
    <section class="grid gap-4 pt-10 lg:grid-cols-3">
        <a href="#domenii" class="flex items-center justify-between gap-4 rounded-btn border border-line-strong p-5 hover:border-brand-500">
            <span class="text-[17px] font-bold">Îmi iau permisul</span>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0056D2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="9" cy="11" r="2"></circle><path d="M14 10h4M14 14h4"></path></svg>
        </a>
        <a href="#domenii" class="flex items-center justify-between gap-4 rounded-btn border border-line-strong p-5 hover:border-brand-500">
            <span class="text-[17px] font-bold">Dau un concurs de admitere</span>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0056D2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"></path></svg>
        </a>
        <a href="#domenii" class="flex items-center justify-between gap-4 rounded-btn border border-line-strong p-5 hover:border-brand-500">
            <span class="text-[17px] font-bold">Obțin o certificare</span>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0056D2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="5"></circle><path d="M8.5 13.5L7 22l5-2.5L17 22l-1.5-8.5"></path></svg>
        </a>
    </section>

    @if($popularTests->isNotEmpty())
        <section class="pt-10">
            <h2 class="text-[21px] font-bold">Cele mai date teste</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($popularTests as $test)
                    @include('partials.test-card', ['test' => $test, 'url' => $urlGenerator->test($test)])
                @endforeach
            </div>
        </section>
    @endif

    @if($recentTests->isNotEmpty())
        <section class="pt-10">
            <h2 class="text-[21px] font-bold">Adăugate recent</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($recentTests as $test)
                    @include('partials.test-card', ['test' => $test, 'url' => $urlGenerator->test($test)])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Bandă de statistici --}}
    <section class="mt-10 rounded-card bg-navy-900 p-8 lg:p-10">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-2xl font-bold leading-snug text-white">Exersatul repetat bate cititul repetat</h2>
                <p class="mt-2 text-sm leading-relaxed text-navy-muted">
                    De asta platforma e construită în jurul grilelor și al diagnosticului pe capitole, nu în jurul cursurilor video. Vezi unde pierzi puncte și te întorci exact acolo.
                </p>
                <a href="#cum-functioneaza" class="mt-3 inline-block text-sm font-bold text-[#7FB0FF] hover:text-white">Cum funcționează &rarr;</a>
            </div>
            <div class="flex shrink-0 gap-10">
                <div>
                    <p class="text-[30px] font-bold leading-none text-white">{{ number_format($publishedQuestionsCount, 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-navy-muted">întrebări publicate</p>
                </div>
                <div>
                    <p class="text-[30px] font-bold leading-none text-white">{{ number_format($publishedTestsCount, 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-navy-muted">teste publicate</p>
                </div>
                <div>
                    <p class="text-[30px] font-bold leading-none text-white">0 lei</p>
                    <p class="mt-1 text-xs text-navy-muted">acum și după lansare</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Cum funcționează --}}
    <section id="cum-functioneaza" class="scroll-mt-24 pt-10">
        <h2 class="text-[21px] font-bold">Trei lucruri pe care le facem altfel</h2>
        <div class="mt-4 grid gap-6 lg:grid-cols-3">
            <div class="border-t-2 border-brand-500 pt-4">
                <h3 class="text-[17px] font-bold">Testele rămân gratuite</h3>
                <p class="mt-2 text-[15px] leading-7 text-ink-700">
                    Nicio întrebare nu stă în spatele unui abonament și nu există versiune „pro”. Contul e pentru progres și istoric, nu pentru acces.
                </p>
            </div>
            <div class="border-t-2 border-vert-medical pt-4">
                <h3 class="text-[17px] font-bold">Fiecare răspuns are o sursă</h3>
                <p class="mt-2 text-[15px] leading-7 text-ink-700">
                    Articol de lege, manual sau ghid oficial, cu data ultimei verificări. Dacă găsești o greșeală, o raportezi din test și intră la revizuire.
                </p>
            </div>
            <div class="border-t-2 border-vert-auto pt-4">
                <h3 class="text-[17px] font-bold">Îți arătăm unde pierzi puncte</h3>
                <p class="mt-2 text-[15px] leading-7 text-ink-700">
                    Capitolele slabe ies singure la suprafață după fiecare test, iar seria de zile consecutive te ține în ritm fără să te pedepsească.
                </p>
            </div>
        </div>
    </section>

    {{-- Surse --}}
    <section id="surse" class="scroll-mt-24 mt-10 rounded-card bg-surface-alt p-8 lg:p-10">
        <div class="grid gap-8 lg:grid-cols-[360px_1fr]">
            <div>
                <h2 class="text-[21px] font-bold">Cum verificăm conținutul</h2>
                <p class="mt-2 text-[15px] leading-7 text-ink-700">
                    Nu publicăm întrebări fără referință. Fluxul e același pentru toate domeniile.
                </p>
            </div>
            <ol class="grid gap-5 sm:grid-cols-2">
                <li class="flex gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">1</span>
                    <span class="text-[15px] leading-7 text-ink-700"><strong class="text-ink-900">Redactare.</strong> Întrebarea se scrie cu explicație și cu referința exactă la textul-sursă.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">2</span>
                    <span class="text-[15px] leading-7 text-ink-700"><strong class="text-ink-900">Revizuire.</strong> Un al doilea ochi verifică formularea, variantele și sursa înainte de publicare.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">3</span>
                    <span class="text-[15px] leading-7 text-ink-700"><strong class="text-ink-900">Datare.</strong> Fiecare întrebare poartă data ultimei verificări, vizibilă lângă răspuns.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">4</span>
                    <span class="text-[15px] leading-7 text-ink-700"><strong class="text-ink-900">Raportare.</strong> Orice utilizator poate semnala o greșeală din test. Semnalarea intră la revizuire.</span>
                </li>
            </ol>
        </div>
    </section>

    {{-- Parteneri --}}
    <section id="parteneri" class="scroll-mt-24 grid gap-4 pt-10 lg:grid-cols-2">
        <div class="flex items-center justify-between gap-5 rounded-card bg-vert-legal p-6">
            <div>
                <p class="text-[17px] font-bold text-white">Ești școală de șoferi sau centru de formare?</p>
                <p class="mt-1 text-sm text-white/80">Cont de instructor, grupe de cursanți și rapoarte de progres.</p>
            </div>
            <a href="{{ route('register') }}" class="shrink-0 rounded-btn bg-white px-4 py-2 text-[13px] font-bold text-vert-legal">Află mai multe</a>
        </div>
        <div class="flex items-center justify-between gap-5 rounded-card bg-navy-900 p-6">
            <div>
                <p class="text-[17px] font-bold text-white">Ești universitate sau organizație profesională?</p>
                <p class="mt-1 text-sm text-navy-muted">Platformă cu marcă proprie, pe domeniul tău, cu API pentru parteneri.</p>
            </div>
            <a href="{{ route('register') }}" class="shrink-0 rounded-btn bg-brand-500 px-4 py-2 text-[13px] font-bold text-white hover:bg-brand-600">Află mai multe</a>
        </div>
    </section>

    {{-- Întrebări frecvente --}}
    <section id="intrebari" class="scroll-mt-24 pt-10">
        <h2 class="text-[21px] font-bold">Întrebări frecvente</h2>
        <div class="mt-3">
            @foreach($faq as $entry)
                <details class="group border-b border-line">
                    <summary class="flex cursor-pointer list-none items-center gap-3 py-3.5 marker:content-none">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 transition group-open:rotate-180" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                        <span class="text-[15px] font-semibold">{{ $entry['question'] }}</span>
                    </summary>
                    <p class="max-w-4xl pb-4 pl-7 text-[15px] leading-7 text-ink-700">{{ $entry['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Îndemn final --}}
    @guest
        <section class="mb-4 mt-10 flex flex-col items-center gap-4 rounded-card bg-navy-900 px-6 py-10 text-center">
            <h2 class="max-w-2xl text-2xl font-bold text-white">Creează cont gratuit și primești recomandări pentru examenul tău</h2>
            <a href="{{ route('register') }}" class="rounded-btn bg-brand-500 px-7 py-3 text-[15px] font-bold text-white hover:bg-brand-600">Începe gratuit</a>
        </section>
    @endguest

</div>
@endsection
