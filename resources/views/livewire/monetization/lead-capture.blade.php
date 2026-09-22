<div class="rounded-card border border-line p-6">
    @if($submitted)
        <span class="flex size-10 items-center justify-center rounded-full bg-[#EAF4F2]">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0D6E63" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
        </span>
        <h3 class="mt-3 text-[21px] font-bold">Solicitare trimisă</h3>
        <p class="mt-2 text-sm leading-6 text-ink-700">Datele tale au fost înregistrate pentru această solicitare.</p>
    @else
        <p class="text-xs font-bold uppercase tracking-wider text-ink-500">Opțional</p>
        <h3 class="mt-2 text-[21px] font-bold">{{ $campaign->title }}</h3>
        @if($campaign->description)
            <p class="mt-2 text-sm leading-6 text-ink-700">{{ $campaign->description }}</p>
        @endif

        <form wire:submit="submit" class="mt-5 space-y-3">
            {{-- Capcană pentru roboți: un câmp pe care un om nu-l vede și nu-l completează. --}}
            <div class="hidden" aria-hidden="true">
                <label>Website companie <input wire:model="companyWebsite" tabindex="-1" autocomplete="off"></label>
            </div>

            @if(in_array('name', $requestedFields, true))
                <div>
                    <label for="lead-name-{{ $campaign->id }}" class="sr-only">Nume</label>
                    <input id="lead-name-{{ $campaign->id }}" wire:model="name" type="text" autocomplete="name" placeholder="Nume"
                        class="w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-sm outline-none focus:border-brand-500">
                    @error('name')<p class="mt-1 text-xs text-vert-auto">{{ $message }}</p>@enderror
                </div>
            @endif

            <div>
                <label for="lead-email-{{ $campaign->id }}" class="sr-only">Email</label>
                <input id="lead-email-{{ $campaign->id }}" wire:model="email" type="email" autocomplete="email" placeholder="Email"
                    class="w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-sm outline-none focus:border-brand-500">
                @error('email')<p class="mt-1 text-xs text-vert-auto">{{ $message }}</p>@enderror
            </div>

            @if(in_array('phone', $requestedFields, true))
                <div>
                    <label for="lead-phone-{{ $campaign->id }}" class="sr-only">Telefon</label>
                    <input id="lead-phone-{{ $campaign->id }}" wire:model="phone" type="tel" autocomplete="tel" placeholder="Telefon"
                        class="w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-sm outline-none focus:border-brand-500">
                    @error('phone')<p class="mt-1 text-xs text-vert-auto">{{ $message }}</p>@enderror
                </div>
            @endif

            <label class="flex items-start gap-2.5 text-xs leading-5 text-ink-500">
                <input wire:model="consent" type="checkbox" class="mt-0.5 size-4 shrink-0 rounded accent-brand-500">
                <span>{{ $campaign->consent_text }}</span>
            </label>
            @error('consent')<p class="text-xs text-vert-auto">{{ $message }}</p>@enderror

            <button class="w-full rounded-btn bg-brand-500 px-4 py-3 text-sm font-bold text-white hover:bg-brand-600">
                {{ $campaign->cta_label }}
            </button>
        </form>
    @endif
</div>
