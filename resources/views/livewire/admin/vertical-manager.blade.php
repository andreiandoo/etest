<div class="grid gap-8 xl:grid-cols-[420px_1fr]">
    <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <h1 class="text-2xl font-extrabold">{{ $editingId ? 'Editează verticala' : 'Verticală nouă' }}</h1>
        <div class="mt-5 space-y-4">
            <div><label class="text-sm font-semibold">Nume</label><input wire:model="name" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Slug</label><input wire:model="slug" placeholder="se generează automat" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent">@error('slug')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Descriere</label><textarea wire:model="description" rows="4" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">SEO title</label><input wire:model="seoTitle" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">SEO description</label><textarea wire:model="seoDescription" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">Ordine</label><input type="number" wire:model="sortOrder" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="isActive"> Activă</label>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>
            @if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif
        </div>
    </form>

    <div>
        <h2 class="text-2xl font-extrabold">Verticale</h2>
        <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
            @foreach($verticals as $vertical)
                <button wire:click="edit({{ $vertical->id }})" class="flex w-full items-center justify-between gap-5 border-b border-slate-100 px-5 py-4 text-left last:border-0 hover:bg-slate-50 dark:border-white/5 dark:hover:bg-white/5">
                    <span><strong>{{ $vertical->name }}</strong><span class="ml-2 text-sm text-slate-500">/{{ $vertical->slug }}</span></span>
                    <span class="text-xs font-bold {{ $vertical->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $vertical->is_active ? 'ACTIVĂ' : 'INACTIVĂ' }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
