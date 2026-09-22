<div>
    <div class="grid gap-8 2xl:grid-cols-[620px_1fr]">
        <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h1 class="text-2xl font-extrabold">{{ $editingId ? 'Editează întrebarea #'.$editingId : 'Întrebare nouă' }}</h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><label class="text-sm font-semibold">Verticală</label><select wire:model.live="verticalId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Alege</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Taxonomie</label><select wire:model="taxonomyNodeId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Fără</option>@foreach($taxonomyNodes as $node)<option value="{{ $node->id }}">{{ $node->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Source key</label><input wire:model="sourceKey" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Tip</label><select wire:model.live="type" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900">@foreach($types as $item)<option value="{{ $item->value }}">{{ $item->value }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="text-sm font-semibold">Întrebare</label><textarea wire:model="prompt" rows="4" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                <div class="sm:col-span-2"><label class="text-sm font-semibold">Explicație</label><textarea wire:model="explanation" rows="4" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
                <div><label class="text-sm font-semibold">Dificultate 1–5</label><input type="number" min="1" max="5" wire:model="difficulty" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Data verificării sursei</label><input type="date" wire:model="sourceCheckedAt" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">Etichetă sursă</label><input wire:model="sourceLabel" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div><label class="text-sm font-semibold">URL sursă</label><input wire:model="sourceUrl" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
                <div class="sm:col-span-2"><label class="text-sm font-semibold">answer_config JSON</label><textarea wire:model="answerConfigJson" rows="8" class="mt-1 w-full rounded-xl border font-mono text-sm px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea>@error('answerConfigJson')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>

            @if(in_array($type, ['single_choice','multiple_choice'], true))
                <div class="mt-6">
                    <div class="flex items-center justify-between"><h2 class="font-extrabold">Variante</h2><button type="button" wire:click="addOption" class="text-sm font-bold text-indigo-600">+ Adaugă variantă</button></div>
                    <div class="mt-3 space-y-3">
                        @foreach($options as $index => $option)
                            <div class="grid gap-2 rounded-2xl bg-slate-50 p-3 sm:grid-cols-[1fr_auto] dark:bg-white/5">
                                <div><input wire:model="options.{{ $index }}.content" placeholder="Text variantă" class="w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"><input wire:model="options.{{ $index }}.feedback" placeholder="Feedback opțional" class="mt-2 w-full rounded-xl border px-3 py-2 text-sm dark:border-white/15 dark:bg-transparent"></div>
                                <div class="flex items-center gap-3"><label class="text-xs font-bold"><input type="checkbox" wire:model="options.{{ $index }}.is_correct"> corect</label><button type="button" wire:click="removeOption({{ $index }})" class="text-sm font-bold text-red-600">Șterge</button></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează draft</button>@if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
        </form>

        <div>
            <h2 class="text-2xl font-extrabold">Banca de întrebări</h2>
            <div class="mt-4 space-y-3">
                @foreach($questions as $question)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-extrabold uppercase text-indigo-600">{{ $question->status->value }} · {{ $question->type->value }} · D{{ $question->difficulty }}</p><p class="mt-2 font-bold">{{ Str::limit($question->prompt, 170) }}</p><p class="mt-1 text-xs text-slate-500">{{ $question->vertical->name }}{{ $question->taxonomyNode ? ' · '.$question->taxonomyNode->name : '' }}{{ $question->source_key ? ' · '.$question->source_key : '' }}</p></div><div class="flex shrink-0 gap-2"><button wire:click="edit({{ $question->id }})" class="rounded-xl border px-3 py-2 text-sm font-bold dark:border-white/15">Editează</button>@if($question->status->value === 'draft')<button wire:click="submitForReview({{ $question->id }})" class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Review</button>@endif</div></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $questions->links() }}</div>
        </div>
    </div>
</div>
