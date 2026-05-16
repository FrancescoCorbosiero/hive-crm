<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 p-6 text-white shadow-lg dark:from-amber-600 dark:via-orange-700 dark:to-rose-700 sm:p-8">
        {{-- Decorative blobs --}}
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-12 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="space-y-2">
                <p class="text-sm font-medium uppercase tracking-wider text-white/80">
                    {{ $this->todayLabel }}
                </p>
                <h2 class="text-2xl font-bold leading-tight sm:text-3xl">
                    {{ $this->greeting }}@if ($this->userName), <span class="text-white">{{ $this->userName }}</span>@endif
                </h2>
                <p class="max-w-xl text-sm text-white/90 sm:text-base">
                    {{ __('dashboard.hero.tagline') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3 lg:justify-end">
                @foreach ($this->counters as $counter)
                    <div class="flex min-w-[7.5rem] items-center gap-3 rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm ring-1 ring-white/20 transition hover:bg-white/25">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20">
                            <x-filament::icon :icon="$counter['icon']" class="h-5 w-5 text-white" />
                        </span>
                        <div class="leading-tight">
                            <div class="text-xl font-bold tabular-nums">{{ $counter['value'] }}</div>
                            <div class="text-[11px] uppercase tracking-wide text-white/80">{{ $counter['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
