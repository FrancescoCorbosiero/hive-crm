<x-filament-panels::page>
    @php($data = $this->getViewData())

    {{-- Filter form — same accent rule + caption pattern as the projection page. --}}
    <form wire:submit="$refresh" class="space-y-4">
        {{ $this->form }}

        <div class="hive-accent-rule"></div>

        <div class="flex items-center justify-between gap-4">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                {{ __('finance/analytics.apply') }}
            </x-filament::button>
            <div class="hive-display-num text-xs uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                {{ \Illuminate\Support\Carbon::parse($data['from'])->translatedFormat('d MMM YYYY') }}
                <span class="mx-1 text-gray-400">→</span>
                {{ \Illuminate\Support\Carbon::parse($data['until'])->translatedFormat('d MMM YYYY') }}
            </div>
        </div>
    </form>

    @if ($data['include_non_taxable'])
        <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <div class="hive-accent-rule absolute inset-x-0 top-0"></div>
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5" />
                <span>{{ __('finance/analytics.banners.non_taxable_included') }}</span>
            </div>
        </div>
    @endif

    {{-- Period totals — editorial KPI strip --}}
    <section class="space-y-3">
        <x-hive.section :heading="__('finance/analytics.sections.totals')" />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-hive.kpi-card
                accent="emerald"
                :label="__('finance/analytics.totals.income')"
                :value="$data['totalIncome']"
            />
            <x-hive.kpi-card
                accent="rose"
                :label="__('finance/analytics.totals.loss')"
                :value="$data['totalLoss']"
            />
            <x-hive.kpi-card
                :accent="$data['netNegative'] ? 'rose' : 'emerald'"
                :label="__('finance/analytics.totals.net')"
                :value="$data['net']"
            />
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Income by category --}}
        <section class="space-y-3">
            <x-hive.section :heading="__('finance/analytics.sections.income_by_category')" />

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.025]">
                @if ($data['income']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance/analytics.empty') }}</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2">{{ __('finance/analytics.columns.category') }}</th>
                                <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($data['income'] as $row)
                                <tr>
                                    <td class="py-2">{{ $row['category'] }}</td>
                                    <td class="hive-display-num py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                        {{ $row['amount'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

        {{-- Loss by category --}}
        <section class="space-y-3">
            <x-hive.section :heading="__('finance/analytics.sections.loss_by_category')" />

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.025]">
                @if ($data['loss']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance/analytics.empty') }}</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2">{{ __('finance/analytics.columns.category') }}</th>
                                <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($data['loss'] as $row)
                                <tr>
                                    <td class="py-2">{{ $row['category'] }}</td>
                                    <td class="hive-display-num py-2 text-right font-semibold text-rose-700 dark:text-rose-300">
                                        {{ $row['amount'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>

    {{-- Income by website --}}
    <section class="space-y-3">
        <x-hive.section :heading="__('finance/analytics.sections.income_by_website')" />

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.025]">
            @if ($data['per_website']->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance/analytics.empty') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2">{{ __('finance/analytics.columns.website') }}</th>
                            <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($data['per_website'] as $row)
                            <tr>
                                <td class="py-2">{{ $row['name'] }}</td>
                                <td class="hive-display-num py-2 text-right font-semibold text-gray-950 dark:text-white">
                                    {{ $row['amount'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-filament-panels::page>
