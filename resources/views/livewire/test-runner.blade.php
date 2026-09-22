@php
    use App\Enums\TestMode;
    use App\Services\Content\VerticalTheme;

    $theme = VerticalTheme::for($vertical);
    $isExam = $test->mode === TestMode::Exam;
    $answeredCount = count($answeredPositions);
    $progress = $questionCount > 0 ? $answeredCount / $questionCount : 0;

    // Inelul de progres. Circumferinta unui cerc cu raza 14.
    $ringLength = 87.96;
    $ringFilled = round($ringLength * $progress, 2);
@endphp

<div @if($remainingSeconds !== null) wire:poll.1s="tick" @endif>

    {{-- Bara de control. Intunecata, ca sa separe testul de restul site-ului. --}}
    <div class="sticky top-0 z-30 bg-navy-900 text-white">
        <div class="mx-auto flex max-w-[1440px] flex-wrap items-center gap-4 px-5 py-3 lg:px-10">
            <a href="{{ $presentationUrl }}" class="flex shrink-0 items-center gap-2 text-sm font-semibold text-navy-muted hover:text-white">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
                Ieși din test
            </a>

            <span class="hidden h-6 w-px bg-white/15 sm:block"></span>

            <div class="min-w-0 flex-1">
                <p class="truncate text-[15px] font-bold">{{ $test->title }}</p>
                <p class="truncate text-xs text-navy-muted">
                    {{ $vertical->name }} · {{ $isExam ? 'mod examen' : 'mod exersare' }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-4">
                <div class="flex items-center gap-2.5">
                    <svg width="34" height="34" viewBox="0 0 34 34" fill="none" aria-hidden="true">
                        <circle cx="17" cy="17" r="14" stroke="#1B3A63" stroke-width="4"></circle>
                        <circle cx="17" cy="17" r="14" stroke="#4DA3FF" stroke-width="4" stroke-linecap="round"
                            stroke-dasharray="{{ $ringFilled }} {{ $ringLength }}" transform="rotate(-90 17 17)"></circle>
                    </svg>
                    <div class="leading-tight">
                        <p class="text-sm font-bold tabular-nums">{{ $answeredCount }} / {{ $questionCount }}</p>
                        <p class="text-[11px] text-navy-muted">rezolvate</p>
                    </div>
                </div>

                @if($remainingSeconds !== null)
                    <div x-data="{ remaining: {{ $remainingSeconds }} }"
                        x-init="setInterval(() => { if (remaining > 0) remaining-- }, 1000)"
                        class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4DA3FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                        <span class="text-[15px] font-bold tabular-nums"
                            x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')"></span>
                    </div>
                @endif

                <button wire:click="finish" wire:confirm="Vrei să finalizezi testul acum?"
                    class="rounded-btn border border-white/25 px-4 py-2 text-sm font-bold hover:bg-white/10">
                    Termină testul
                </button>
            </div>
        </div>
    </div>

    <div class="surface-reading min-h-[calc(100vh-64px)]">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-8 px-5 py-8 lg:flex-row lg:px-10">

            <main class="min-w-0 flex-1">
                @if($currentQuestion)
                    @php
                        $snapshot = $currentQuestion->question_snapshot;
                        $type = $snapshot['type'] ?? null;
                        $options = $snapshot['options'] ?? [];
                        $config = $snapshot['answer_config'] ?? [];
                        $storedAnswer = $currentQuestion->answer;
                    @endphp

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-[13px] font-bold text-ink-500">
                            Întrebarea {{ min($position + 1, $questionCount) }} din {{ $questionCount }}
                        </p>
                        <p class="text-[13px] text-ink-500">{{ $currentQuestion->points }} {{ (float) $currentQuestion->points === 1.0 ? 'punct' : 'puncte' }}</p>
                    </div>

                    <h1 class="mt-3 max-w-3xl text-[26px] font-bold leading-snug sm:text-[30px]">{{ $snapshot['prompt'] ?? '' }}</h1>

                    <form wire:submit="submitCurrent" class="mt-7 max-w-3xl">
                        @if($type === 'single_choice')
                            <div class="space-y-2.5">
                                @foreach($options as $optionIndex => $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-card border-[1.5px] border-line bg-white p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-50">
                                        <input type="radio" wire:model="answer.selected" value="{{ $option['id'] }}" class="mt-1 size-4 accent-brand-500">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-surface-alt text-xs font-bold">{{ chr(65 + $optionIndex) }}</span>
                                        <span class="pt-0.5 text-[16px] leading-6">{{ $option['content'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($type === 'multiple_choice')
                            <p class="mb-3 text-[13px] text-ink-500">Poți alege mai multe variante.</p>
                            <div class="space-y-2.5">
                                @foreach($options as $optionIndex => $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-card border-[1.5px] border-line bg-white p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-50">
                                        <input type="checkbox" wire:model="answer.selected" value="{{ $option['id'] }}" class="mt-1 size-4 rounded accent-brand-500">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-surface-alt text-xs font-bold">{{ chr(65 + $optionIndex) }}</span>
                                        <span class="pt-0.5 text-[16px] leading-6">{{ $option['content'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($type === 'true_false')
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-3 rounded-card border-[1.5px] border-line bg-white p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-50">
                                    <input type="radio" wire:model="answer.value" value="1" class="size-4 accent-brand-500">
                                    <span class="text-[16px] font-semibold">Adevărat</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-card border-[1.5px] border-line bg-white p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-50">
                                    <input type="radio" wire:model="answer.value" value="0" class="size-4 accent-brand-500">
                                    <span class="text-[16px] font-semibold">Fals</span>
                                </label>
                            </div>
                        @elseif($type === 'numeric')
                            <input type="text" inputmode="decimal" wire:model="answer.value"
                                class="w-full max-w-sm rounded-card border-[1.5px] border-line-strong bg-white px-4 py-3 text-[16px] outline-none focus:border-brand-500"
                                placeholder="Răspuns numeric">
                        @elseif($type === 'short_text')
                            <input type="text" wire:model="answer.value"
                                class="w-full rounded-card border-[1.5px] border-line-strong bg-white px-4 py-3 text-[16px] outline-none focus:border-brand-500"
                                placeholder="Scrie răspunsul">
                        @elseif($type === 'matching')
                            <div class="space-y-3">
                                @foreach(($config['left_items'] ?? []) as $left)
                                    <div class="grid gap-3 rounded-card border border-line bg-white p-4 sm:grid-cols-2 sm:items-center">
                                        <span class="font-semibold">{{ $left['label'] ?? $left['id'] }}</span>
                                        <select wire:model="answer.pairs.{{ $left['id'] }}"
                                            class="rounded-btn border border-line-strong bg-white px-3 py-2 outline-none focus:border-brand-500">
                                            <option value="">Alege…</option>
                                            @foreach(($config['right_items'] ?? []) as $right)
                                                <option value="{{ $right['id'] }}">{{ $right['label'] ?? $right['id'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($type === 'ordering')
                            <div class="space-y-2.5">
                                @foreach(($config['correct_order'] ?? []) as $index => $unused)
                                    <select wire:model="answer.items.{{ $index }}"
                                        class="w-full rounded-card border border-line-strong bg-white px-4 py-3 outline-none focus:border-brand-500">
                                        <option value="">Poziția {{ $index + 1 }}</option>
                                        @foreach(($config['items'] ?? []) as $item)
                                            <option value="{{ $item['id'] }}">{{ $item['label'] ?? $item['id'] }}</option>
                                        @endforeach
                                    </select>
                                @endforeach
                            </div>
                        @endif

                        @error('answer')
                            <p class="mt-4 rounded-card bg-[#FBEEE9] px-4 py-3 text-sm font-semibold text-vert-auto">{{ $message }}</p>
                        @enderror

                        <div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
                            <button type="button" wire:click="previous" @disabled(! $test->allow_review || $position === 0)
                                class="flex items-center gap-2 rounded-btn border-[1.5px] border-line-strong px-5 py-2.5 text-[15px] font-bold disabled:cursor-not-allowed disabled:opacity-40">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
                                Înapoi
                            </button>

                            <div class="flex gap-2.5">
                                @if($position < $questionCount - 1)
                                    <button type="button" wire:click="next"
                                        class="rounded-btn border-[1.5px] border-line-strong px-5 py-2.5 text-[15px] font-bold hover:border-brand-500">
                                        Sari peste
                                    </button>
                                @endif

                                <button type="submit" class="rounded-btn bg-brand-500 px-6 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
                                    {{ $isExam
                                        ? ($position === $questionCount - 1 ? 'Răspunde și finalizează' : 'Răspunde și continuă')
                                        : 'Verifică răspunsul' }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($showFeedback && $storedAnswer)
                        <div class="mt-6 max-w-3xl rounded-card p-5 {{ $storedAnswer->is_correct ? 'bg-[#EAF4F2]' : 'bg-[#FBEEE9]' }}">
                            <p class="flex items-center gap-2 text-[17px] font-bold {{ $storedAnswer->is_correct ? 'text-[#0A574E]' : 'text-[#8C3617]' }}">
                                @if($storedAnswer->is_correct)
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
                                    Corect
                                @else
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                                    Răspunsul nu este complet corect
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-ink-700">
                                Ai obținut {{ $storedAnswer->awarded_points }} din {{ $currentQuestion->points }} puncte.
                            </p>

                            @if($test->show_explanations && ! empty($snapshot['explanation']))
                                <p class="mt-3 leading-7 text-ink-900">{{ $snapshot['explanation'] }}</p>
                            @endif

                            @if(! empty($snapshot['source_label']))
                                <p class="mt-3 flex flex-wrap items-center gap-2 border-t border-black/10 pt-3 text-[13px] text-ink-700">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M8 8h8M8 12h8"></path></svg>
                                    <span class="font-semibold">{{ $snapshot['source_label'] }}</span>
                                    @if(! empty($snapshot['source_checked_at']))
                                        <span class="text-ink-500">· verificat {{ $snapshot['source_checked_at'] }}</span>
                                    @endif
                                </p>
                            @endif

                            @if($position < $questionCount - 1)
                                <button type="button" wire:click="next" class="mt-5 rounded-btn bg-ink-900 px-5 py-2.5 text-[15px] font-bold text-white">
                                    Următoarea întrebare
                                </button>
                            @else
                                <button type="button" wire:click="finish" class="mt-5 rounded-btn bg-ink-900 px-5 py-2.5 text-[15px] font-bold text-white">
                                    Vezi rezultatul
                                </button>
                            @endif
                        </div>
                    @endif

                    <div class="mt-8 max-w-3xl border-t border-line pt-5">
                        @if(session('question_reported'))
                            <p class="mb-4 rounded-card bg-[#EAF4F2] px-4 py-3 text-sm font-semibold text-[#0A574E]">
                                {{ session('question_reported') }}
                            </p>
                        @endif

                        <button type="button" wire:click="$toggle('reportOpen')"
                            class="flex items-center gap-2 text-[13px] font-semibold text-ink-500 hover:text-brand-500">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9L2.4 17a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path></svg>
                            Raportează o problemă la această întrebare
                        </button>

                        @if($reportOpen)
                            <div class="mt-4 rounded-card border border-line bg-white p-5">
                                <label for="report-reason" class="text-sm font-semibold">Tipul problemei</label>
                                <select id="report-reason" wire:model="reportReason"
                                    class="mt-2 w-full rounded-btn border border-line-strong bg-white px-3 py-2 outline-none focus:border-brand-500">
                                    <option value="incorrect">Răspuns sau explicație incorectă</option>
                                    <option value="outdated">Informație depășită</option>
                                    <option value="ambiguous">Formulare ambiguă</option>
                                    <option value="typo">Greșeală de scriere</option>
                                    <option value="other">Altă problemă</option>
                                </select>

                                <label for="report-message" class="mt-4 block text-sm font-semibold">Detalii (opțional)</label>
                                <textarea id="report-message" wire:model="reportMessage" rows="3"
                                    class="mt-2 w-full rounded-btn border border-line-strong bg-white px-3 py-2 outline-none focus:border-brand-500"
                                    placeholder="Spune-ne ce ar trebui verificat."></textarea>
                                @error('reportMessage') <p class="mt-2 text-sm text-vert-auto">{{ $message }}</p> @enderror

                                <div class="mt-4 flex justify-end">
                                    <button type="button" wire:click="submitReport" class="rounded-btn bg-ink-900 px-4 py-2 text-sm font-bold text-white">
                                        Trimite raportarea
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-card border border-dashed border-line-strong bg-white p-8 text-center">
                        <p class="text-[17px] font-bold">Acest test nu are încă întrebări publicate</p>
                        <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                            Conținutul e în lucru. Încearcă alt test din același domeniu.
                        </p>
                        <a href="{{ $presentationUrl }}" class="mt-4 inline-block rounded-btn bg-brand-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-600">
                            Înapoi la test
                        </a>
                    </div>
                @endif
            </main>

            @if($questionCount > 0)
                <aside class="w-full shrink-0 lg:w-[276px]">
                    <div class="rounded-card border border-line bg-white p-5">
                        <p class="text-[13px] font-bold text-ink-500">Navigare</p>

                        <div class="mt-3 grid grid-cols-8 gap-1.5 lg:grid-cols-6">
                            @for($index = 0; $index < $questionCount; $index++)
                                @php($isAnswered = in_array($index, $answeredPositions, true))
                                <button type="button" wire:click="goTo({{ $index }})"
                                    @disabled(! $test->allow_review && $index !== $position)
                                    aria-label="Întrebarea {{ $index + 1 }}"
                                    aria-current="{{ $index === $position ? 'true' : 'false' }}"
                                    class="flex aspect-square items-center justify-center rounded-btn text-xs font-bold tabular-nums disabled:cursor-not-allowed disabled:opacity-40
                                        {{ $index === $position
                                            ? 'bg-brand-500 text-white'
                                            : ($isAnswered ? 'bg-ink-900 text-white' : 'border border-line text-ink-500 hover:border-line-strong') }}">
                                    {{ $index + 1 }}
                                </button>
                            @endfor
                        </div>

                        <dl class="mt-4 space-y-2 border-t border-line pt-4 text-[13px]">
                            <div class="flex items-center gap-2.5">
                                <span class="size-3 shrink-0 rounded-[3px] bg-brand-500"></span>
                                <dt class="text-ink-700">Întrebarea curentă</dt>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="size-3 shrink-0 rounded-[3px] bg-ink-900"></span>
                                <dt class="text-ink-700">Răspuns dat</dt>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="size-3 shrink-0 rounded-[3px] border border-line"></span>
                                <dt class="text-ink-700">Neatinsă</dt>
                            </div>
                        </dl>

                        <button wire:click="finish" wire:confirm="Vrei să finalizezi testul acum?"
                            class="mt-5 w-full rounded-btn border-[1.5px] border-ink-900 py-2.5 text-[15px] font-bold hover:bg-ink-900 hover:text-white">
                            Termină testul
                        </button>
                    </div>
                </aside>
            @endif

        </div>
    </div>
</div>
