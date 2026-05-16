{{-- Tailwind JIT safelist. Every dynamic accent must appear literally in
     the source for the build to ship the matching utilities. The PHP
     string-concatenation in @class([...]) below produces these classes at
     runtime, which the JIT scanner cannot see — so we list them here:
     bg-sky-50      bg-sky-500/10     text-sky-600      ring-sky-200      hover:border-sky-300      group-hover:text-sky-700      dark:bg-sky-500/10      dark:text-sky-300      dark:ring-sky-500/20      dark:hover:border-sky-400/40
     bg-emerald-50  bg-emerald-500/10 text-emerald-600  ring-emerald-200  hover:border-emerald-300  group-hover:text-emerald-700  dark:bg-emerald-500/10  dark:text-emerald-300  dark:ring-emerald-500/20  dark:hover:border-emerald-400/40
     bg-amber-50    bg-amber-500/10   text-amber-600    ring-amber-200    hover:border-amber-300    group-hover:text-amber-700    dark:bg-amber-500/10    dark:text-amber-300    dark:ring-amber-500/20    dark:hover:border-amber-400/40
     bg-violet-50   bg-violet-500/10  text-violet-600   ring-violet-200   hover:border-violet-300   group-hover:text-violet-700   dark:bg-violet-500/10   dark:text-violet-300   dark:ring-violet-500/20   dark:hover:border-violet-400/40
     bg-indigo-50   bg-indigo-500/10  text-indigo-600   ring-indigo-200   hover:border-indigo-300   group-hover:text-indigo-700   dark:bg-indigo-500/10   dark:text-indigo-300   dark:ring-indigo-500/20   dark:hover:border-indigo-400/40
     bg-cyan-50     bg-cyan-500/10    text-cyan-600     ring-cyan-200     hover:border-cyan-300     group-hover:text-cyan-700     dark:bg-cyan-500/10     dark:text-cyan-300     dark:ring-cyan-500/20     dark:hover:border-cyan-400/40
     bg-rose-50     bg-rose-500/10    text-rose-600     ring-rose-200     hover:border-rose-300     group-hover:text-rose-700     dark:bg-rose-500/10     dark:text-rose-300     dark:ring-rose-500/20     dark:hover:border-rose-400/40
     bg-fuchsia-50  bg-fuchsia-500/10 text-fuchsia-600  ring-fuchsia-200  hover:border-fuchsia-300  group-hover:text-fuchsia-700  dark:bg-fuchsia-500/10  dark:text-fuchsia-300  dark:ring-fuchsia-500/20  dark:hover:border-fuchsia-400/40 --}}

<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->getHeading()"
        :description="$this->getDescription()"
        icon="heroicon-o-bolt"
        icon-color="warning"
    >
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($this->tiles as $tile)
                <a
                    href="{{ $tile['url'] }}"
                    wire:navigate
                    @class([
                        'group relative flex flex-col gap-2 overflow-hidden rounded-xl border border-gray-200 bg-white p-4',
                        'transition-all duration-150 ease-out',
                        'hover:-translate-y-0.5 hover:shadow-md',
                        'dark:border-white/10 dark:bg-white/[0.025]',
                        // Per-tile hover accent (border + label color shift on hover)
                        'hover:border-' . $tile['accent'] . '-300',
                        'dark:hover:border-' . $tile['accent'] . '-400/40',
                    ])
                >
                    {{-- Decorative gradient corner --}}
                    <div @class([
                        'pointer-events-none absolute -right-8 -top-8 h-20 w-20 rounded-full opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100',
                        'bg-' . $tile['accent'] . '-500/10',
                    ])></div>

                    <div class="flex items-start justify-between gap-2">
                        <span @class([
                            'flex h-10 w-10 items-center justify-center rounded-lg ring-1',
                            'bg-' . $tile['accent'] . '-50',
                            'text-' . $tile['accent'] . '-600',
                            'ring-' . $tile['accent'] . '-200',
                            'dark:bg-' . $tile['accent'] . '-500/10',
                            'dark:text-' . $tile['accent'] . '-300',
                            'dark:ring-' . $tile['accent'] . '-500/20',
                        ])>
                            <x-filament::icon :icon="$tile['icon']" class="h-5 w-5" />
                        </span>

                        <x-filament::icon
                            icon="heroicon-m-arrow-up-right"
                            class="h-4 w-4 text-gray-300 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-300"
                        />
                    </div>

                    <div class="space-y-0.5">
                        <div @class([
                            'text-sm font-semibold text-gray-950 transition-colors dark:text-white',
                            'group-hover:text-' . $tile['accent'] . '-700',
                        ])>
                            {{ $tile['label'] }}
                        </div>
                        <p class="line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ $tile['description'] }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
