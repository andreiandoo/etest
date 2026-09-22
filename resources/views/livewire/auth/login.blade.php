<section class="mx-auto max-w-md px-5 py-14">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">Bine ai revenit</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Autentificare</h1>

        <form wire:submit="authenticate" class="mt-8 space-y-5">
            <div>
                <label for="email" class="text-sm font-semibold">Email</label>
                <input id="email" type="email" wire:model="email" autocomplete="email"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
                @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="text-sm font-semibold">Parolă</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
                @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="remember" class="rounded">
                Ține-mă autentificat
            </label>

            <button class="w-full rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white">
                Intră în cont
            </button>
        </form>

        @if(config('services.google.client_id'))
            <a href="{{ route('auth.google.redirect') }}"
                class="mt-4 block rounded-2xl border border-slate-300 px-5 py-3 text-center font-bold dark:border-white/15">
                Continuă cu Google
            </a>
        @endif

        <p class="mt-6 text-sm text-slate-600 dark:text-slate-300">
            Nu ai cont? <a href="{{ route('register') }}" class="font-bold text-indigo-600">Creează unul gratuit</a>.
        </p>
    </div>
</section>
