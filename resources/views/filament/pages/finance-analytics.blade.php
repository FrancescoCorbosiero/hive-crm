<x-filament-panels::page>
    @php($data = $this->getViewData())

    <form wire:submit="$refresh">
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                {{ __('finance/analytics.apply') }}
            </x-filament::button>
        </div>
    </form>

    @if ($data['include_non_taxable'])
        <div class="rounded-lg border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950/30 dark:text-warning-200">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5" />
                <span>{{ __('finance/analytics.banners.non_taxable_included') }}</span>
            </div>
        </div>
    @endif

    {{-- Period totals --}}
    <x-filament::section :heading="__('finance/analytics.sections.totals')">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-success-50 dark:bg-success-950/30">
                <div class="text-xs uppercase tracking-wide text-success-700 dark:text-success-300">
                    {{ __('finance/analytics.totals.income') }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-success-700 dark:text-success-300">
                    {{ $data['totalIncome'] }}
                </div>
            </div>
            <div class="p-4 rounded-xl bg-danger-50 dark:bg-danger-950/30">
                <div class="text-xs uppercase tracking-wide text-danger-700 dark:text-danger-300">
                    {{ __('finance/analytics.totals.loss') }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-danger-700 dark:text-danger-300">
                    {{ $data['totalLoss'] }}
                </div>
            </div>
            <div @class([
                'p-4 rounded-xl',
                'bg-success-50 dark:bg-success-950/30' => ! $data['netNegative'],
                'bg-danger-50 dark:bg-danger-950/30' => $data['netNegative'],
            ])>
                <div @class([
                    'text-xs uppercase tracking-wide',
                    'text-success-700 dark:text-success-300' => ! $data['netNegative'],
                    'text-danger-700 dark:text-danger-300' => $data['netNegative'],
                ])>{{ __('finance/analytics.totals.net') }}</div>
                <div @class([
                    'mt-1 text-2xl font-semibold',
                    'text-success-700 dark:text-success-300' => ! $data['netNegative'],
                    'text-danger-700 dark:text-danger-300' => $data['netNegative'],
                ])>{{ $data['net'] }}</div>
            </div>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Income by category --}}
        <x-filament::section :heading="__('finance/analytics.sections.income_by_category')">
            @if ($data['income']->isEmpty())
                <p class="text-sm text-gray-500">{{ __('finance/analytics.empty') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="py-2">{{ __('finance/analytics.columns.category') }}</th>
                            <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($data['income'] as $row)
                        <tr>
                            <td class="py-2">{{ $row['category'] }}</td>
                            <td class="py-2 text-right font-medium">{{ $row['amount'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        {{-- Loss by category --}}
        <x-filament::section :heading="__('finance/analytics.sections.loss_by_category')">
            @if ($data['loss']->isEmpty())
                <p class="text-sm text-gray-500">{{ __('finance/analytics.empty') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="py-2">{{ __('finance/analytics.columns.category') }}</th>
                            <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($data['loss'] as $row)
                        <tr>
                            <td class="py-2">{{ $row['category'] }}</td>
                            <td class="py-2 text-right font-medium">{{ $row['amount'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>

    {{-- Income by website --}}
    <x-filament::section :heading="__('finance/analytics.sections.income_by_website')">
        @if ($data['per_website']->isEmpty())
            <p class="text-sm text-gray-500">{{ __('finance/analytics.empty') }}</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">{{ __('finance/analytics.columns.website') }}</th>
                        <th class="py-2 text-right">{{ __('finance/analytics.columns.amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($data['per_website'] as $row)
                    <tr>
                        <td class="py-2">{{ $row['name'] }}</td>
                        <td class="py-2 text-right font-medium">{{ $row['amount'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
