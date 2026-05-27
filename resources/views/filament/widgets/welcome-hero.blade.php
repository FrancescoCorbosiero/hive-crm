<x-filament-widgets::widget>
    <div class="hive-dot-grid relative overflow-hidden rounded-xl bg-slate-900 text-white shadow-xl ring-1 ring-white/5 dark:bg-slate-950">
        {{-- Diagonal accent shape — subtle, geometric, no blurry blob.      --}}
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rotate-12 bg-gradient-to-br from-amber-500/20 via-amber-500/5 to-transparent"></div>

        {{-- Top accent rule — single 2px amber line bleeding to transparent. --}}
        <div class="hive-accent-rule absolute inset-x-0 top-0"></div>

        <div class="relative grid gap-8 p-6 sm:p-10 lg:grid-cols-[1.4fr_1fr] lg:items-end lg:gap-12">

            {{-- ── Left column: brand mark + date + headline + tagline ───── --}}
            <div class="space-y-5">
                <div class="flex items-center gap-3 text-xs font-medium uppercase tracking-[0.18em] text-white/60">
                    <span class="hive-monogram inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-500 text-base text-slate-950">H</span>
                    <span class="h-px flex-1 bg-white/15 sm:max-w-[3rem]"></span>
                    <span>{{ $this->todayLabel }}</span>
                </div>

                <h2 class="hive-display-num text-3xl font-bold leading-[1.05] tracking-tight sm:text-4xl lg:text-5xl">
                    {{ $this->greeting }}@if ($this->userName)<span class="text-amber-400">,</span>
                        <span class="block sm:inline">{{ $this->userName }}</span>
                    @endif
                </h2>

                <p class="max-w-xl text-sm leading-relaxed text-white/65 sm:text-base">
                    {{ __('dashboard.hero.tagline') }}
                </p>
            </div>

            {{-- ── Right column: 2×2 grid of counters with mono numerals ── --}}
            <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg bg-white/10 ring-1 ring-white/10">
                @foreach ($this->counters as $counter)
                    <div class="group relative flex flex-col gap-1 bg-slate-900 p-4 transition-colors hover:bg-slate-800/80 dark:bg-slate-950 dark:hover:bg-slate-900/80">
                        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/55">
                            <x-filament::icon :icon="$counter['icon']" class="h-3.5 w-3.5 text-amber-400" />
                            <span>{{ $counter['label'] }}</span>
                        </div>
                        <dd class="hive-display-num text-3xl font-bold text-white sm:text-4xl">
                            {{ $counter['value'] }}
                        </dd>
                    </div>
                @endforeach
            </dl>

        </div>
    </div>
</x-filament-widgets::widget>
