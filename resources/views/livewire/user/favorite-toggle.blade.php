<button type="button" wire:click="toggle"
    class="mt-3 flex w-full items-center justify-center rounded-2xl border px-5 py-3 text-sm font-bold transition
    {{ $favorite ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100' : 'border-white/20 dark:border-slate-300' }}">
    {{ $favorite ? '★ Salvat la favorite' : '☆ Salvează la favorite' }}
</button>
