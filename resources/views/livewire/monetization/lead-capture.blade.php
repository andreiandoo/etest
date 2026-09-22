<div class="rounded-[2rem] border border-slate-200 bg-white p-6 sm:p-7 dark:border-white/10 dark:bg-white/5">
    @if($submitted)
        <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-emerald-600">Solicitare trimisă</p>
        <h3 class="mt-2 text-2xl font-extrabold">Mulțumim.</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Datele tale au fost înregistrate pentru această solicitare.</p>
    @else
        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-600">Opțional</p>
        <h3 class="mt-2 text-2xl font-extrabold">{{ $campaign->title }}</h3>
        @if($campaign->description)
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $campaign->description }}</p>
        @endif

        <form wire:submit="submit" class="mt-5 space-y-3">
            <div class="hidden" aria-hidden="true">
                <label>Website companie <input wire:model="companyWebsite" tabindex="-1" autocomplete="off"></label>
            </div>

            @if(in_array('name', $requestedFields, true))
                <input wire:model="name" type="text" autocomplete="name" placeholder="Nume"
                    class="w-full rounded-xl border border-slate-300 bg-transparent px-4 py-3 text-sm dark:border-white/15">
                @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @endif

            <input wire:model="email" type="email" autocomplete="email" placeholder="Email"
                class="w-full rounded-xl border border-slate-300 bg-transparent px-4 py-3 text-sm dark:border-white/15">
            @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            @if(in_array('phone', $requestedFields, true))
                <input wire:model="phone" type="tel" autocomplete="tel" placeholder="Telefon"
                    class="w-full rounded-xl border border-slate-300 bg-transparent px-4 py-3 text-sm dark:border-white/15">
                @error('phone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @endif

            <label class="flex items-start gap-3 text-xs leading-5 text-slate-500">
                <input wire:model="consent" type="checkbox" class="mt-1">
                <span>{{ $campaign->consent_text }}</span>
            </label>
            @error('consent')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-extrabold text-white">
                {{ $campaign->cta_label }}
            </button>
        </form>
    @endif
</div>
