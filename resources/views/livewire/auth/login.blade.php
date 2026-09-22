<section class="mx-auto max-w-[440px] px-5 py-14">
    <h1 class="text-[30px] font-bold tracking-tight">Intră în cont</h1>
    <p class="mt-2 text-[15px] text-ink-700">Testele sunt gratuite. Contul îți păstrează progresul și rezultatele.</p>

    <form wire:submit="authenticate" class="mt-8 space-y-5">
        <div>
            <label for="email" class="text-sm font-semibold">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="email"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
            @error('email') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="text-sm font-semibold">Parolă</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                class="mt-2 w-full rounded-btn border border-line-strong bg-white px-4 py-3 text-[15px] outline-none focus:border-brand-500">
            @error('password') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm">
            <input type="checkbox" wire:model="remember" class="size-4 rounded accent-brand-500">
            Ține-mă autentificat
        </label>

        <button class="w-full rounded-btn bg-brand-500 px-5 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
            Intră în cont
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
        Nu ai cont? <a href="{{ route('register') }}" class="font-bold text-brand-500 hover:text-brand-700">Creează unul gratuit</a>.
    </p>
</section>
