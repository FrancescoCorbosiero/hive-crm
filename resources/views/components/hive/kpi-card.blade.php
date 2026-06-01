{{-- Editorial KPI card — sharp top accent stripe, tiny uppercase label with a
     coloured dot, oversized tabular numeral, optional one-line hint. Mirrors
     the dashboard's DashboardKpisWidget card (minus the sparkline) so the
     same visual language carries to projection / analytics / counter pages.

     Tailwind JIT safelist (the accent class is built at render time):
     bg-emerald-500 bg-rose-500 bg-amber-500 bg-sky-500 bg-violet-500
     bg-indigo-500 bg-cyan-500 bg-fuchsia-500 bg-gray-400 --}}
@props([
    'accent' => 'amber',
    'label',
    'value',
    'hint' => null,
    'size' => 'lg',
])

@php
    // Size controls the value's type scale. Defaults to the dashboard KPI
    // size; `sm` makes it usable for counter strips (5+ across a row) where
    // the dashboard size would crowd.
    $valueClass = match ($size) {
        'sm' => 'text-xl sm:text-2xl',
        default => 'text-3xl sm:text-[2rem]',
    };
@endphp

<div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/[0.025]">
    {{-- Top accent stripe — single-color band at the very edge --}}
    <div @class([
        'absolute inset-x-0 top-0 h-[3px]',
        'bg-' . $accent . '-500',
    ])></div>

    <div @class([
        'flex flex-col gap-3',
        'p-4' => $size === 'sm',
        'p-5' => $size !== 'sm',
    ])>
        {{-- Label row — tiny uppercase, accent dot --}}
        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
            <span @class([
                'inline-block h-1.5 w-1.5 rounded-full',
                'bg-' . $accent . '-500',
            ])></span>
            <span class="truncate">{{ $label }}</span>
        </div>

        {{-- The number — oversized, tabular, sharp --}}
        <div @class([
            'hive-display-num font-bold leading-none text-gray-950 dark:text-white',
            $valueClass,
        ])>
            {{ $value }}
        </div>

        @if ($hint)
            <div class="text-xs text-gray-600 dark:text-gray-400">{{ $hint }}</div>
        @endif
    </div>
</div>
