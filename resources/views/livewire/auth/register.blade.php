<section class="mx-auto max-w-md px-5 py-14">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">Cont gratuit</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Creează contul</h1>
        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Ai nevoie de cont pentru a începe testele și pentru a-ți salva progresul.</p>

        <form wire:submit="register" class="mt-8 space-y-5">
            <div>
                <label for="name" class="text-sm font-semibold">Nume</label>
                <input id="name" type="text" wire:model="name" autocomplete="name"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="text-sm font-semibold">Email</label>
                <input id="email" type="email" wire:model="email" autocomplete="email"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
                @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="text-sm font-semibold">Parolă</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
                @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="text-sm font-semibold">Confirmă parola</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15">
            </div>

            <button class="w-full rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white">
                Creează cont
            </button>
        </form>

        @if(config('services.google.client_id'))
            <a href="{{ route('auth.google.redirect') }}"
                class="mt-4 block rounded-2xl border border-slate-300 px-5 py-3 text-center font-bold dark:border-white/15">
                Continuă cu Google
            </a>
        @endif

        <p class="mt-6 text-sm text-slate-600 dark:text-slate-300">
            Ai deja cont? <a href="{{ route('login') }}" class="font-bold text-indigo-600">Autentifică-te</a>.
        </p>
    </div>
</section>
