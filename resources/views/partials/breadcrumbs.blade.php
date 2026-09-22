<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
    @foreach($breadcrumbs as $index => $item)
        @if(!$loop->last)
            <a href="{{ $item['url'] }}" class="font-semibold hover:text-indigo-600">{{ $item['name'] }}</a>
            <span aria-hidden="true">/</span>
        @else
            <span aria-current="page" class="text-slate-700 dark:text-slate-300">{{ $item['name'] }}</span>
        @endif
    @endforeach
</nav>
