<div class="grid gap-8 xl:grid-cols-[420px_1fr]">
    <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <h1 class="text-2xl font-extrabold">{{ $editingId ? 'Editează nodul' : 'Nod taxonomic nou' }}</h1>
        <div class="mt-5 space-y-4">
            <div><label class="text-sm font-semibold">Verticală</label><select wire:model.live="verticalId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Alege</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Părinte</label><select wire:model="parentId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Fără părinte</option>@foreach($nodes->where('vertical_id', $verticalId) as $node)<option value="{{ $node->id }}">{{ $node->name }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Tip</label><select wire:model="type" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900">@foreach($types as $item)<option value="{{ $item->value }}">{{ $item->value }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Nume</label><input wire:model="name" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Slug</label><input wire:model="slug" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Descriere</label><textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">SEO title</label><input wire:model="seoTitle" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">SEO description</label><textarea wire:model="seoDescription" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">Ordine</label><input type="number" wire:model="sortOrder" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <label class="flex gap-2 text-sm font-semibold"><input type="checkbox" wire:model="isActive"> Activ</label>
        </div>
        <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>@if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
    </form>

    <div>
        <h2 class="text-2xl font-extrabold">Taxonomie</h2>
        <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
            @foreach($nodes as $node)
                <button wire:click="edit({{ $node->id }})" class="grid w-full grid-cols-[1fr_auto] gap-4 border-b border-slate-100 px-5 py-4 text-left last:border-0 dark:border-white/5">
                    <span><strong>{{ $node->name }}</strong><span class="ml-2 text-xs uppercase text-indigo-600">{{ $node->type->value }}</span><span class="block text-sm text-slate-500">{{ $node->vertical->name }}{{ $node->parent ? ' · '.$node->parent->name : '' }}</span></span>
                    <span class="text-sm text-slate-500">/{{ $node->slug }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
