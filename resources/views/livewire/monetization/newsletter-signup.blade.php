<div class="rounded-[2rem] bg-slate-950 p-7 text-white dark:bg-white dark:text-slate-950">
    @if($state === 'pending')
        <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-300 dark:text-indigo-600">Verifică emailul</p>
        <h3 class="mt-2 text-2xl font-extrabold">Mai este un singur pas.</h3>
        <p class="mt-3 text-sm leading-6 opacity-75">Ți-am trimis un link de confirmare. Abonarea devine activă numai după confirmare.</p>
    @elseif($state === 'active')
        <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-emerald-300 dark:text-emerald-600">Deja abonat</p>
        <h3 class="mt-2 text-2xl font-extrabold">Ești deja înscris pentru {{ $interestLabel }}.</h3>
    @else
        <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-indigo-300 dark:text-indigo-600">Newsletter pe interes</p>
        <h3 class="mt-2 text-2xl font-extrabold">Primește noutăți pentru {{ $interestLabel }}</h3>
        <p class="mt-3 text-sm leading-6 opacity-75">Fără acces plătit la teste. Newsletter-ul este separat și opțional.</p>

        <form wire:submit="submit" class="mt-5 space-y-3">
            <div class="hidden" aria-hidden="true">
                <label>Website companie <input wire:model="companyWebsite" tabindex="-1" autocomplete="off"></label>
            </div>

            <input wire:model="email" type="email" autocomplete="email" placeholder="Email"
                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/50 dark:border-slate-300 dark:bg-slate-50 dark:text-slate-950 dark:placeholder:text-slate-400">
            @error('email')<p class="text-xs text-red-300 dark:text-red-600">{{ $message }}</p>@enderror

            <label class="flex items-start gap-3 text-xs leading-5 opacity-75">
                <input wire:model="consent" type="checkbox" class="mt-1">
                <span>Sunt de acord să primesc prin email informații și resurse relevante pentru {{ $interestLabel }}. Pot să mă dezabonez oricând.</span>
            </label>
            @error('consent')<p class="text-xs text-red-300 dark:text-red-600">{{ $message }}</p>@enderror

            <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-extrabold text-white">
                Înscrie-mă
            </button>
        </form>
    @endif
</div>
