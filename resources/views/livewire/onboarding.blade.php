@php
    use App\Services\Content\VerticalTheme;

    $horizons = [
        'sub-o-luna' => ['Sub o lună', 'Mergem direct pe simulări și pe capitolele slabe.'],
        'una-trei-luni' => ['Una până la trei luni', 'Alternăm exersarea pe capitole cu simulări.'],
        'peste-trei-luni' => ['Peste trei luni', 'Construim de la zero, capitol cu capitol.'],
        'nu-stiu' => ['Încă nu știu', 'Începem cu un test de diagnostic.'],
    ];
@endphp

<div class="flex min-h-[calc(100vh-112px)] flex-col">

    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-center px-5 py-12">

        <p class="text-[13px] font-bold text-ink-500">Pasul {{ $step }} din 2</p>

        @if($step === 1)
            <h1 class="mt-2 text-center text-[32px] font-bold leading-tight tracking-tight">Pentru ce examen te pregătești?</h1>
            <p class="mt-2 text-center text-[16px] text-ink-700">Poți alege mai multe. Îți construim recomandările din ele.</p>

            <label class="mt-8 flex h-12 w-full max-w-lg items-center gap-3 rounded-btn border border-line-strong px-4 focus-within:border-brand-500">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0056D2" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                <span class="sr-only">Caută un examen</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Caută un examen"
                    class="min-w-0 flex-1 bg-transparent text-[15px] outline-none placeholder:text-ink-500">
            </label>

            @if($options === [])
                <div class="mt-8 w-full rounded-card border border-dashed border-line-strong p-8 text-center">
                    <p class="text-[17px] font-bold">
                        {{ $search !== '' ? 'Niciun examen pentru „'.$search.'”' : 'Încă nu există domenii publicate' }}
                    </p>
                    <p class="mx-auto mt-2 max-w-md text-[15px] leading-7 text-ink-700">
                        {{ $search !== '' ? 'Încearcă un termen mai scurt.' : 'Poți continua fără să alegi nimic acum.' }}
                    </p>
                </div>
            @else
                <div class="mt-8 grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($options as $option)
                        @php($theme = VerticalTheme::for($option['vertical']))
                        <button type="button"
                            wire:click="{{ $option['kind'] === 'node' ? 'toggleNode('.$option['id'].')' : 'toggleVertical('.$option['id'].')' }}"
                            aria-pressed="{{ $option['selected'] ? 'true' : 'false' }}"
                            class="flex min-h-[72px] items-center gap-3.5 rounded-card p-3.5 text-left transition
                                {{ $option['selected']
                                    ? 'border-2 border-brand-500 bg-brand-50'
                                    : 'border border-line-strong hover:border-brand-500' }}">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-btn" style="background: {{ $theme['solid'] }}">
                                <x-vertical-icon :icon="$theme['icon']" :size="22" color="#FFFFFF" :width="1.8" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[15px] font-bold leading-snug">{{ $option['name'] }}</span>
                                @if($option['kind'] === 'node')
                                    <span class="block truncate text-[13px] text-ink-500">{{ $option['vertical']->name }}</span>
                                @endif
                            </span>
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full text-[15px] font-bold
                                {{ $option['selected'] ? 'bg-brand-500 text-white' : 'text-ink-500' }}">
                                {{ $option['selected'] ? '✓' : '+' }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @else
            <h1 class="mt-2 text-center text-[32px] font-bold leading-tight tracking-tight">Cât mai ai până la examen?</h1>
            <p class="mt-2 text-center text-[16px] text-ink-700">Ne ajută să alegem între exersare pe capitole și simulări.</p>

            <div class="mt-8 grid w-full max-w-xl gap-3">
                @foreach($horizons as $value => [$label, $hint])
                    <button type="button" wire:click="$set('horizon', '{{ $value }}')"
                        aria-pressed="{{ $horizon === $value ? 'true' : 'false' }}"
                        class="flex items-start gap-3.5 rounded-card p-4 text-left transition
                            {{ $horizon === $value
                                ? 'border-2 border-brand-500 bg-brand-50'
                                : 'border border-line-strong hover:border-brand-500' }}">
                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2
                            {{ $horizon === $value ? 'border-brand-500' : 'border-line-strong' }}">
                            @if($horizon === $value)
                                <span class="size-2.5 rounded-full bg-brand-500"></span>
                            @endif
                        </span>
                        <span>
                            <span class="block text-[16px] font-bold">{{ $label }}</span>
                            <span class="mt-0.5 block text-[14px] leading-6 text-ink-700">{{ $hint }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        @endif

    </div>

    <div class="sticky bottom-0 border-t border-line bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-5 py-4">
            @if($step === 1)
                <button type="button" wire:click="skip" class="text-[15px] font-semibold text-ink-500 hover:text-brand-500">
                    Sari peste
                </button>

                <button type="button" wire:click="goToSecondStep" @disabled(! $this->hasSelection())
                    class="flex items-center gap-2 rounded-btn bg-brand-500 px-7 py-3 text-[15px] font-bold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-line-strong disabled:text-ink-500">
                    Continuă
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </button>
            @else
                <button type="button" wire:click="back"
                    class="flex items-center gap-2 rounded-btn border-[1.5px] border-brand-500 px-5 py-2.5 text-[15px] font-bold text-brand-500 hover:bg-brand-50">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
                    Înapoi
                </button>

                <button type="button" wire:click="finish"
                    class="rounded-btn bg-brand-500 px-7 py-3 text-[15px] font-bold text-white hover:bg-brand-600">
                    Gata, intră în cont
                </button>
            @endif
        </div>
    </div>
</div>
