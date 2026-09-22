<button type="button" wire:click="toggle"
    aria-pressed="{{ $favorite ? 'true' : 'false' }}"
    class="flex w-full items-center justify-center gap-2 rounded-btn border px-5 py-3 text-sm font-bold transition
        {{ $favorite ? 'border-[#8A6114] bg-[#F6EEDC] text-[#8A6114]' : 'border-line-strong hover:border-brand-500' }}">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="{{ $favorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M19 21l-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
    </svg>
    {{ $favorite ? 'Salvat la favorite' : 'Salvează la favorite' }}
</button>
