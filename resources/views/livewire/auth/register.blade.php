<section class="mx-auto max-w-[440px] px-5 py-14">
    <h1 class="text-[30px] font-bold tracking-tight">Creează cont gratuit</h1>
    <p class="mt-2 text-[15px] leading-7 text-ink-700">
        Fără card și fără abonament. Contul îți trebuie doar ca să-ți salvăm răspunsurile și progresul.
    </p>

    <form wire:submit="register" class="mt-8 space-y-5">
        <div>
            <label for="name" class="text-sm font-semibold">Nume</label>
            <input id="name" type="text" wire:model="name" autocomplete="name"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
            @error('name') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="text-sm font-semibold">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="email"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
            @error('email') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="text-sm font-semibold">Parolă</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
            <p class="mt-2 text-[13px] text-ink-500">Minimum 8 caractere, cu litere și cifre.</p>
            @error('password') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="text-sm font-semibold">Confirmă parola</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
        </div>

        <button class="w-full rounded-btn bg-brand-500 px-5 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
            Creează cont
        </button>
    </form>

    @if(config('services.google.client_id'))
        <div class="my-6 flex items-center gap-4">
            <span class="h-px flex-1 bg-line"></span>
            <span class="text-[13px] text-ink-500">sau</span>
            <span class="h-px flex-1 bg-line"></span>
        </div>

        <a href="{{ route('auth.google.redirect') }}"
            class="block rounded-btn border border-line-strong px-5 py-3 text-center text-[15px] font-bold hover:border-brand-500">
            Continuă cu Google
        </a>
    @endif

    <p class="mt-8 border-t border-line pt-6 text-[15px] text-ink-700">
        Ai deja cont? <a href="{{ route('login') }}" class="font-bold text-brand-500 hover:text-brand-700">Intră în cont</a>.
    </p>
</section>
