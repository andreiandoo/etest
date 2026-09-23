<div class="space-y-2 text-sm">
    @forelse($errors as $error)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            @if(is_array($error))
                @foreach($error as $key => $value)
                    <p><span class="font-semibold">{{ $key }}:</span>
                        {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</p>
                @endforeach
            @else
                <p>{{ $error }}</p>
            @endif
        </div>
    @empty
        <p>Nu au fost înregistrate detalii pentru rândurile respinse.</p>
    @endforelse
</div>
