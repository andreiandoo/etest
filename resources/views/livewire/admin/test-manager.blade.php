<div class="grid gap-8 2xl:grid-cols-[520px_1fr]">
    <form wire:submit="save" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <h1 class="text-2xl font-extrabold">{{ $editingId ? 'Editează testul' : 'Test nou' }}</h1>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><label class="text-sm font-semibold">Verticală</label><select wire:model.live="verticalId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Alege</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Taxonomie</label><select wire:model="taxonomyNodeId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Fără</option>@foreach($taxonomyNodes as $node)<option value="{{ $node->id }}">{{ $node->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Titlu</label><input wire:model="title" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Slug</label><input wire:model="slug" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Mod</label><select wire:model="mode" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900">@foreach($modes as $item)<option value="{{ $item->value }}">{{ $item->value }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Descriere</label><textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">SEO title</label><input wire:model="seoTitle" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">SEO description</label><textarea wire:model="seoDescription" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Instrucțiuni</label><textarea wire:model="instructions" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></textarea></div>
            <div><label class="text-sm font-semibold">Limită întrebări</label><input type="number" wire:model="questionLimit" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Durată minute</label><input type="number" wire:model="durationMinutes" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div><label class="text-sm font-semibold">Prag %</label><input type="number" step="0.01" wire:model="passingPercentage" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-transparent"></div>
            <div class="space-y-2 pt-6 text-sm font-semibold"><label class="flex gap-2"><input type="checkbox" wire:model="randomizeQuestions"> Randomizează întrebările</label><label class="flex gap-2"><input type="checkbox" wire:model="randomizeOptions"> Randomizează variantele</label><label class="flex gap-2"><input type="checkbox" wire:model="allowReview"> Permite review</label><label class="flex gap-2"><input type="checkbox" wire:model="showExplanations"> Arată explicații</label></div>
        </div>

        <div class="mt-6">
            <p class="text-sm font-extrabold">Întrebări incluse</p>
            <div class="mt-2 max-h-72 space-y-2 overflow-auto rounded-2xl border border-slate-200 p-3 dark:border-white/10">
                @forelse($questions as $question)
                    <label class="flex gap-2 text-sm"><input type="checkbox" wire:model="selectedQuestions" value="{{ $question->id }}"><span>#{{ $question->id }} {{ Str::limit($question->prompt, 100) }}</span></label>
                @empty
                    <p class="text-sm text-slate-500">Alege întâi verticala.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6 flex gap-2"><button class="rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white">Salvează</button>@if($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border px-5 py-2.5 font-bold dark:border-white/15">Renunță</button>@endif</div>
    </form>

    <div>
        <h2 class="text-2xl font-extrabold">Teste</h2>
        <div class="mt-4 space-y-3">
            @foreach($tests as $test)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><p class="text-xs font-extrabold uppercase text-indigo-600">{{ $test->status->value }} · {{ $test->mode->value }}</p><h3 class="mt-1 text-lg font-extrabold">{{ $test->title }}</h3><p class="text-sm text-slate-500">{{ $test->vertical->name }} · /{{ $test->slug }}</p></div>
                        <div class="flex gap-2"><button wire:click="edit({{ $test->id }})" class="rounded-xl border px-3 py-2 text-sm font-bold dark:border-white/15">Editează</button>@if($test->status->value === 'draft')<button wire:click="submitForReview({{ $test->id }})" class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Trimite la review</button>@endif</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
