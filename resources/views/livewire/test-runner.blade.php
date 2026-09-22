<section class="mx-auto max-w-6xl px-5 py-8 lg:px-8" @if($remainingSeconds !== null) wire:poll.1s="tick" @endif>
    <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
            <a href="{{ $presentationUrl }}" class="text-sm font-bold text-indigo-600">← Înapoi la prezentare</a>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">{{ $test->title }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                Întrebarea {{ min($position + 1, $questionCount) }} din {{ $questionCount }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($remainingSeconds !== null)
                <div
                    x-data="{ remaining: {{ $remainingSeconds }} }"
                    x-init="setInterval(() => { if (remaining > 0) remaining-- }, 1000)"
                    class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold tabular-nums dark:bg-white/10"
                >
                    <span x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')"></span>
                </div>
            @endif

            <button wire:click="finish" wire:confirm="Vrei să finalizezi testul acum?"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold dark:border-white/15">
                Finalizează
            </button>
        </div>
    </div>

    @if($questionCount > 0)
        <div class="mt-7 flex flex-wrap gap-2">
            @for($index = 0; $index < $questionCount; $index++)
                <button
                    type="button"
                    wire:click="goTo({{ $index }})"
                    @disabled(!$test->allow_review && $index !== $position)
                    class="flex h-9 w-9 items-center justify-center rounded-xl text-xs font-extrabold
                        {{ $index === $position ? 'bg-indigo-600 text-white' : (in_array($index, $answeredPositions, true) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300') }}">
                    {{ $index + 1 }}
                </button>
            @endfor
        </div>
    @endif

    @if($currentQuestion)
        @php
            $snapshot = $currentQuestion->question_snapshot;
            $type = $snapshot['type'] ?? null;
            $options = $snapshot['options'] ?? [];
            $config = $snapshot['answer_config'] ?? [];
            $storedAnswer = $currentQuestion->answer;
        @endphp

        <div class="mt-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-600">{{ str_replace('_', ' ', (string) $type) }}</span>
                <span class="text-sm font-semibold text-slate-500">{{ $currentQuestion->points }} pct.</span>
            </div>

            <h2 class="mt-5 text-xl font-extrabold leading-8 sm:text-2xl">{{ $snapshot['prompt'] ?? '' }}</h2>

            <form wire:submit="submitCurrent" class="mt-7">
                @if(in_array($type, ['single_choice'], true))
                    <div class="space-y-3">
                        @foreach($options as $option)
                            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-white/10">
                                <input type="radio" wire:model="answer.selected" value="{{ $option['id'] }}" class="mt-1">
                                <span>{{ $option['content'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif($type === 'multiple_choice')
                    <div class="space-y-3">
                        @foreach($options as $option)
                            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-white/10">
                                <input type="checkbox" wire:model="answer.selected" value="{{ $option['id'] }}" class="mt-1 rounded">
                                <span>{{ $option['content'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif($type === 'true_false')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 dark:border-white/10">
                            <input type="radio" wire:model="answer.value" value="1">
                            <span>Adevărat</span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 dark:border-white/10">
                            <input type="radio" wire:model="answer.value" value="0">
                            <span>Fals</span>
                        </label>
                    </div>
                @elseif($type === 'numeric')
                    <input type="text" inputmode="decimal" wire:model="answer.value"
                        class="w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15"
                        placeholder="Răspuns numeric">
                @elseif($type === 'short_text')
                    <input type="text" wire:model="answer.value"
                        class="w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 outline-none focus:border-indigo-500 dark:border-white/15"
                        placeholder="Scrie răspunsul">
                @elseif($type === 'matching')
                    <div class="space-y-4">
                        @foreach(($config['left_items'] ?? []) as $left)
                            <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 sm:items-center dark:border-white/10">
                                <span class="font-semibold">{{ $left['label'] ?? $left['id'] }}</span>
                                <select wire:model="answer.pairs.{{ $left['id'] }}"
                                    class="rounded-xl border border-slate-300 bg-transparent px-3 py-2 dark:border-white/15">
                                    <option value="">Alege...</option>
                                    @foreach(($config['right_items'] ?? []) as $right)
                                        <option value="{{ $right['id'] }}">{{ $right['label'] ?? $right['id'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                @elseif($type === 'ordering')
                    <div class="space-y-3">
                        @foreach(($config['correct_order'] ?? []) as $index => $unused)
                            <select wire:model="answer.items.{{ $index }}"
                                class="w-full rounded-xl border border-slate-300 bg-transparent px-3 py-3 dark:border-white/15">
                                <option value="">Poziția {{ $index + 1 }}</option>
                                @foreach(($config['items'] ?? []) as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['label'] ?? $item['id'] }}</option>
                                @endforeach
                            </select>
                        @endforeach
                    </div>
                @endif

                @error('answer')
                    <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-200">{{ $message }}</p>
                @enderror

                <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" wire:click="previous" @disabled(!$test->allow_review || $position === 0)
                        class="rounded-2xl border border-slate-300 px-5 py-3 font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/15">
                        Înapoi
                    </button>

                    <div class="flex gap-3">
                        @if($position < $questionCount - 1)
                            <button type="button" wire:click="next"
                                class="rounded-2xl border border-slate-300 px-5 py-3 font-bold dark:border-white/15">
                                Sari peste
                            </button>
                        @endif

                        <button type="submit" class="rounded-2xl bg-indigo-600 px-6 py-3 font-bold text-white">
                            {{ $test->mode->value === 'practice' ? 'Verifică răspunsul' : ($position === $questionCount - 1 ? 'Răspunde și finalizează' : 'Răspunde și continuă') }}
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-5 dark:border-white/10">
                @if(session('question_reported'))
                    <p class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-100">
                        {{ session('question_reported') }}
                    </p>
                @endif

                <button type="button" wire:click="$toggle('reportOpen')" class="text-sm font-bold text-slate-500 hover:text-indigo-600">
                    Raportează o problemă la această întrebare
                </button>

                @if($reportOpen)
                    <div class="mt-4 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <label class="text-sm font-semibold">Tipul problemei</label>
                        <select wire:model="reportReason" class="mt-2 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 dark:border-white/15">
                            <option value="incorrect">Răspuns / explicație incorectă</option>
                            <option value="outdated">Informație depășită</option>
                            <option value="ambiguous">Formulare ambiguă</option>
                            <option value="typo">Greșeală de scriere</option>
                            <option value="other">Altă problemă</option>
                        </select>

                        <label class="mt-4 block text-sm font-semibold">Detalii (opțional)</label>
                        <textarea wire:model="reportMessage" rows="3"
                            class="mt-2 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 dark:border-white/15"
                            placeholder="Spune-ne ce ar trebui verificat."></textarea>
                        @error('reportMessage') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-4 flex justify-end">
                            <button type="button" wire:click="submitReport" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">
                                Trimite raportarea
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if($showFeedback && $storedAnswer)
                <div class="mt-7 rounded-2xl {{ $storedAnswer->is_correct ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-amber-50 dark:bg-amber-500/10' }} p-5">
                    <p class="font-extrabold">
                        {{ $storedAnswer->is_correct ? 'Corect.' : 'Răspunsul nu este complet corect.' }}
                        Ai obținut {{ $storedAnswer->awarded_points }} / {{ $currentQuestion->points }} puncte.
                    </p>

                    @if($test->show_explanations && !empty($snapshot['explanation']))
                        <p class="mt-3 leading-7 text-slate-700 dark:text-slate-200">{{ $snapshot['explanation'] }}</p>
                    @endif

                    @if($position < $questionCount - 1)
                        <button type="button" wire:click="next" class="mt-5 rounded-2xl bg-slate-950 px-5 py-3 font-bold text-white dark:bg-white dark:text-slate-950">
                            Următoarea întrebare
                        </button>
                    @else
                        <button type="button" wire:click="finish" class="mt-5 rounded-2xl bg-slate-950 px-5 py-3 font-bold text-white dark:bg-white dark:text-slate-950">
                            Vezi rezultatul
                        </button>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="mt-8 rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
            Acest test nu are încă întrebări publicate.
        </div>
    @endif
</section>
