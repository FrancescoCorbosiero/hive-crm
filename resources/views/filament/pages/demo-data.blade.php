<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current state — editorial KPI strip in the small `sm` variant so
             five counters fit comfortably across a row without crowding. --}}
        <section class="space-y-3">
            <x-hive.section :heading="__('demo_data.current_state.heading')" />

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @php
                    $accents = [
                        'contacts' => 'sky',
                        'websites' => 'violet',
                        'financial_entries' => 'emerald',
                        'leads' => 'amber',
                        'documents' => 'rose',
                    ];
                @endphp

                @foreach ($this->tableCounts as $key => $count)
                    <x-hive.kpi-card
                        :accent="$accents[$key] ?? 'amber'"
                        :label="__('demo_data.tables.' . $key)"
                        :value="$count"
                        size="sm"
                    />
                @endforeach
            </div>
        </section>

        {{-- Help banner — keeps the amber palette but adopts the editorial
             card chrome (top accent rule + rounded square corners). --}}
        <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <div class="hive-accent-rule absolute inset-x-0 top-0"></div>
            <div class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="space-y-1">
                    <p>{{ __('demo_data.help.idempotent') }}</p>
                    <p>{{ __('demo_data.help.no_uninstall') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
