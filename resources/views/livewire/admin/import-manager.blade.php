<div>
    <div class="grid gap-8 xl:grid-cols-[460px_1fr]">
        <form wire:submit="upload" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h1 class="text-2xl font-extrabold">Import întrebări</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">CSV, JSON, XLSX sau XLS. Conținutul importat intră întotdeauna ca draft.</p>
            <div class="mt-5 space-y-4">
                <div><label class="text-sm font-semibold">Verticală</label><select wire:model="verticalId" class="mt-1 w-full rounded-xl border px-3 py-2 dark:border-white/15 dark:bg-slate-900"><option value="">Alege</option>@foreach($verticals as $vertical)<option value="{{ $vertical->id }}">{{ $vertical->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold">Fișier</label><input type="file" wire:model="file" accept=".csv,.json,.xlsx,.xls" class="mt-2 block w-full text-sm">@error('file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <button class="mt-6 rounded-xl bg-indigo-600 px-5 py-2.5 font-bold text-white" wire:loading.attr="disabled">Pune importul în coadă</button>
        </form>

        <div>
            <h2 class="text-2xl font-extrabold">Istoric importuri</h2>
            <div class="mt-4 overflow-x-auto rounded-3xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/5">
                <table class="w-full min-w-[800px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-white/5"><tr><th class="p-4">Fișier</th><th>Verticală</th><th>Status</th><th>Procesate</th><th>Create</th><th>Update</th><th>Erori</th></tr></thead>
                    <tbody>@foreach($imports as $import)<tr class="border-t border-slate-100 dark:border-white/5"><td class="p-4 font-semibold">{{ $import->original_name }}</td><td>{{ $import->vertical?->name }}</td><td>{{ $import->status }}</td><td>{{ $import->processed_rows }}/{{ $import->total_rows }}</td><td>{{ $import->created_rows }}</td><td>{{ $import->updated_rows }}</td><td>{{ $import->failed_rows }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 text-sm dark:border-white/10 dark:bg-white/5">
        <h2 class="text-lg font-extrabold">Coloane acceptate</h2>
        <p class="mt-2 leading-7"><code>source_key, taxonomy_slug, type, prompt, explanation, difficulty, source_label, source_url, source_checked_at, answer_config, options</code></p>
        <p class="mt-2 text-slate-500">În CSV/XLSX, <code>answer_config</code> și <code>options</code> sunt JSON. În JSON pot fi obiecte/array native.</p>
    </div>
</div>
