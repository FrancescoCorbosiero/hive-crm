{{-- Tailwind JIT safelist. The accent classes are built at render time
     from $card['accent'] so the scanner can't see them; list them here:
     bg-emerald-500 bg-rose-500 bg-amber-500 bg-sky-500
     text-emerald-600 text-rose-600 text-amber-600 text-sky-600
     dark:text-emerald-300 dark:text-rose-300 dark:text-amber-300 dark:text-sky-300
     stroke-emerald-500 stroke-rose-500 stroke-amber-500 stroke-sky-500
     fill-emerald-500/10 fill-rose-500/10 fill-amber-500/10 fill-sky-500/10
--}}

<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->cards as $card)
            <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/[0.025]">

                {{-- Top accent stripe — single-color band at the very edge --}}
                <div @class([
                    'absolute inset-x-0 top-0 h-[3px]',
                    'bg-' . $card['accent'] . '-500',
                ])></div>

                <div class="flex flex-col gap-3 p-5">

                    {{-- Label row — tiny uppercase, accent dot --}}
                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                        <span @class([
                            'inline-block h-1.5 w-1.5 rounded-full',
                            'bg-' . $card['accent'] . '-500',
                        ])></span>
                        <span class="truncate">{{ $card['label'] }}</span>
                    </div>

                    {{-- The number — oversized, tabular, sharp --}}
                    <div class="hive-display-num text-3xl font-bold leading-none text-gray-950 dark:text-white sm:text-[2rem]">
                        {{ $card['value'] }}
                    </div>

                    {{-- Optional sparkline — only the income card has one --}}
                    @if (! empty($card['sparkline']))
                        @php
                            $points = $card['sparkline'];
                            $count = count($points);
                            $width = 200;
                            $height = 36;
                            $step = $count > 1 ? $width / ($count - 1) : 0;
                            // Build the polyline path (Y is inverted: 0 = top).
                            $coords = [];
                            foreach ($points as $i => $v) {
                                $x = (int) round($i * $step);
                                $y = (int) round($height - ($v / 100) * ($height - 4) - 2);
                                $coords[] = "{$x},{$y}";
                            }
                            $polyline = implode(' ', $coords);
                            $first = $coords[0] ?? '0,0';
                            $last = $coords[$count - 1] ?? '0,0';
                            $area = "0,{$height} {$polyline} {$width},{$height}";
                        @endphp
                        <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="h-9 w-full">
                            <polygon points="{{ $area }}" @class([
                                'fill-' . $card['accent'] . '-500/10',
                            ]) />
                            <polyline points="{{ $polyline }}" fill="none" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" @class([
                                'stroke-' . $card['accent'] . '-500',
                            ]) />
                        </svg>
                    @endif

                    {{-- Hint line — small icon + descriptor --}}
                    <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                        @if (! empty($card['hintIcon']))
                            <x-filament::icon :icon="$card['hintIcon']" @class([
                                'h-3.5 w-3.5 shrink-0',
                                'text-emerald-600 dark:text-emerald-400' => $card['hintTone'] === 'positive',
                                'text-rose-600 dark:text-rose-400' => $card['hintTone'] === 'negative',
                                'text-gray-400 dark:text-gray-500' => $card['hintTone'] === 'neutral',
                            ]) />
                        @endif
                        <span class="truncate">{{ $card['hint'] }}</span>
                    </div>

                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
