<x-filament-panels::page>
    @php($data = $this->getViewData())

    {{-- Filter form — kept inside Filament's default surface for the inputs,
         but the submit row picks up the editorial accent rule below it. --}}
    <form wire:submit="$refresh" class="space-y-4">
        {{ $this->form }}

        <div class="hive-accent-rule"></div>

        <div class="flex items-center justify-between gap-4">
            <x-filament::button type="submit" icon="heroicon-o-arrow-path">
                {{ __('finance/projection.recompute') }}
            </x-filament::button>
            <div class="text-xs uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                {{ __('finance/projection.derived_hint') }}
            </div>
        </div>
    </form>

    {{-- Totals — editorial KPI strip replaces the pastel success/danger tiles. --}}
    <section class="space-y-3">
        <x-hive.section
            :heading="__('finance/projection.sections.totals', ['n' => $data['window_months']])"
        />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-hive.kpi-card
                accent="emerald"
                :label="__('finance/projection.totals.income')"
                :value="$data['totals']['income']"
            />
            <x-hive.kpi-card
                accent="rose"
                :label="__('finance/projection.totals.loss')"
                :value="$data['totals']['loss']"
            />
            <x-hive.kpi-card
                :accent="$data['totals']['net_negative'] ? 'rose' : 'emerald'"
                :label="__('finance/projection.totals.net')"
                :value="$data['totals']['net']"
            />
        </div>
    </section>

    {{-- Monthly breakdown — bars get sharper edges, label uses tabular nums
         so the months line up across rows. --}}
    <section class="space-y-3">
        <x-hive.section :heading="__('finance/projection.sections.monthly')" />

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.025]">
            @if ($data['has_data'])
                <div class="space-y-3">
                    @foreach ($data['monthly'] as $row)
                        <div class="grid grid-cols-12 items-center gap-3 text-sm">
                            <div class="hive-display-num col-span-2 font-semibold uppercase tracking-[0.08em] text-gray-700 dark:text-gray-200">
                                {{ $row['label'] }}
                            </div>
                            <div class="col-span-4">
                                <div class="h-1.5 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-500/15">
                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $row['income_pct'] }}%"></div>
                                </div>
                                <div class="hive-display-num mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                                    {{ $row['income'] }}
                                </div>
                            </div>
                            <div class="col-span-4">
                                <div class="h-1.5 overflow-hidden rounded-full bg-rose-100 dark:bg-rose-500/15">
                                    <div class="h-1.5 rounded-full bg-rose-500" style="width: {{ $row['loss_pct'] }}%"></div>
                                </div>
                                <div class="hive-display-num mt-1 text-xs text-rose-700 dark:text-rose-300">
                                    {{ $row['loss'] }}
                                </div>
                            </div>
                            <div @class([
                                'hive-display-num col-span-2 text-right font-semibold',
                                'text-emerald-700 dark:text-emerald-300' => ! $row['net_negative'],
                                'text-rose-700 dark:text-rose-300' => $row['net_negative'],
                            ])>{{ $row['net'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('finance/projection.empty') }}
                </div>
            @endif
        </div>
    </section>

    {{-- Per-entry detail list — collapsed by default; styled to match the
         editorial card chrome instead of Filament's default section card. --}}
    @if (count($data['entries']) > 0)
        <x-filament::section
            :heading="__('finance/projection.sections.entries')"
            collapsible
            collapsed
        >
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="py-2 text-left">{{ __('finance/projection.columns.date') }}</th>
                        <th class="py-2 text-left">{{ __('finance/projection.columns.description') }}</th>
                        <th class="py-2 text-left">{{ __('finance/projection.columns.source') }}</th>
                        <th class="py-2 text-right">{{ __('finance/projection.columns.amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($data['entries'] as $entry)
                        <tr>
                            <td class="hive-display-num py-2 text-gray-700 dark:text-gray-200">
                                {{ \Illuminate\Support\Carbon::parse($entry['date'])->translatedFormat('d MMM YYYY') }}
                            </td>
                            <td class="py-2">{{ $entry['description'] }}</td>
                            <td class="py-2 text-xs uppercase tracking-[0.08em] text-gray-500 dark:text-gray-400">
                                {{ $entry['source'] }}
                            </td>
                            <td @class([
                                'hive-display-num py-2 text-right font-semibold',
                                'text-emerald-700 dark:text-emerald-300' => $entry['type'] === 'income',
                                'text-rose-700 dark:text-rose-300' => $entry['type'] === 'loss',
                            ])>{{ $entry['amount'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
