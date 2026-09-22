<nav aria-label="Firimituri" class="flex flex-wrap items-center gap-2 text-[13px] text-ink-500">
    @foreach($breadcrumbs as $item)
        @if(! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-brand-500">{{ $item['name'] }}</a>
            <span aria-hidden="true">&rsaquo;</span>
        @else
            <span aria-current="page" class="text-ink-900">{{ $item['name'] }}</span>
        @endif
    @endforeach
</nav>
