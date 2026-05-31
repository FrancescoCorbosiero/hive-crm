{{-- Editorial section heading — vertical amber accent bar + bold uppercase
     label, no card chrome. Matches the dashboard's quick-actions heading so
     the visual language carries through to the rest of the app. --}}
@props([
    'heading',
    'description' => null,
])

<div class="flex items-end justify-between gap-4 px-1">
    <div class="flex items-center gap-3">
        <span class="hive-accent-bar h-6"></span>
        <div>
            <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-gray-950 dark:text-white">
                {{ $heading }}
            </h3>
            @if ($description)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    </div>

    {{-- Optional right-hand slot (e.g. a small action / link). --}}
    @isset($trailing)
        <div class="flex items-center gap-2">
            {{ $trailing }}
        </div>
    @endisset
</div>
