<div class="rounded-card bg-navy-900 p-6 text-white">
    @if($state === 'pending')
        <p class="text-xs font-bold uppercase tracking-wider text-[#7FB0FF]">Verifică emailul</p>
        <h3 class="mt-2 text-[21px] font-bold">Mai e un singur pas</h3>
        <p class="mt-2 text-sm leading-6 text-navy-muted">
            Ți-am trimis un link de confirmare. Abonarea devine activă abia după ce îl deschizi.
        </p>
    @elseif($state === 'active')
        <p class="text-xs font-bold uppercase tracking-wider text-[#3FBCA9]">Deja abonat</p>
        <h3 class="mt-2 text-[21px] font-bold">Ești înscris pentru {{ $interestLabel }}</h3>
        <p class="mt-2 text-sm leading-6 text-navy-muted">Te poți dezabona din orice email primit.</p>
    @else
        <h3 class="text-[21px] font-bold">Noutăți pentru {{ $interestLabel }}</h3>
        <p class="mt-2 text-sm leading-6 text-navy-muted">
            Testele rămân gratuite indiferent de asta. Newsletter-ul e separat și complet opțional.
        </p>

        <form wire:submit="submit" class="mt-5 space-y-3">
            {{-- Capcană pentru roboți: un câmp pe care un om nu-l vede și nu-l completează. --}}
            <div class="hidden" aria-hidden="true">
                <label>Website companie <input wire:model="companyWebsite" tabindex="-1" autocomplete="off"></label>
            </div>

            <div>
                <label for="newsletter-email-{{ $interestKey }}" class="sr-only">Email</label>
                <input id="newsletter-email-{{ $interestKey }}" wire:model="email" type="email" autocomplete="email" placeholder="Adresa ta de email"
                    class="w-full rounded-btn border border-white/20 bg-white/10 px-4 py-3 text-sm text-white outline-none placeholder:text-white/50 focus:border-[#7FB0FF]">
                @error('email')<p class="mt-1 text-xs text-[#F59468]">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-2.5 text-xs leading-5 text-navy-muted">
                <input wire:model="consent" type="checkbox" class="mt-0.5 size-4 shrink-0 rounded accent-brand-500">
                <span>Sunt de acord să primesc prin email informații și resurse relevante pentru {{ $interestLabel }}. Mă pot dezabona oricând.</span>
            </label>
            @error('consent')<p class="text-xs text-[#F59468]">{{ $message }}</p>@enderror

            <button class="w-full rounded-btn bg-brand-500 px-4 py-3 text-sm font-bold text-white hover:bg-brand-600">
                Înscrie-mă
            </button>
        </form>
    @endif
</div>
