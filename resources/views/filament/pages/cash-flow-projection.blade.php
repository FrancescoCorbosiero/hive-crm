<x-filament-panels::page>
    @php($data = $this->getViewData())

    <form wire:submit="$refresh">
        {{ $this->form }}
        <div class="mt-4 flex items-center justify-between">
            <x-filament::button type="submit" icon="heroicon-o-arrow-path">
                {{ __('finance/projection.recompute') }}
            </x-filament::button>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('finance/projection.derived_hint') }}
            </div>
        </div>
    </form>

    {{-- Totals --}}
    <x-filament::section :heading="__('finance/projection.sections.totals', ['n' => $data['window_months']])">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-success-50 dark:bg-success-950/30">
                <div class="text-xs uppercase tracking-wide text-success-700 dark:text-success-300">
                    {{ __('finance/projection.totals.income') }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-success-700 dark:text-success-300">
                    {{ $data['totals']['income'] }}
                </div>
            </div>
            <div class="p-4 rounded-xl bg-danger-50 dark:bg-danger-950/30">
                <div class="text-xs uppercase tracking-wide text-danger-700 dark:text-danger-300">
                    {{ __('finance/projection.totals.loss') }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-danger-700 dark:text-danger-300">
                    {{ $data['totals']['loss'] }}
                </div>
            </div>
            <div @class([
                'p-4 rounded-xl',
                'bg-success-50 dark:bg-success-950/30' => ! $data['totals']['net_negative'],
                'bg-danger-50 dark:bg-danger-950/30' => $data['totals']['net_negative'],
            ])>
                <div @class([
                    'text-xs uppercase tracking-wide',
                    'text-success-700 dark:text-success-300' => ! $data['totals']['net_negative'],
                    'text-danger-700 dark:text-danger-300' => $data['totals']['net_negative'],
                ])>{{ __('finance/projection.totals.net') }}</div>
                <div @class([
                    'mt-1 text-2xl font-semibold',
                    'text-success-700 dark:text-success-300' => ! $data['totals']['net_negative'],
                    'text-danger-700 dark:text-danger-300' => $data['totals']['net_negative'],
                ])>{{ $data['totals']['net'] }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- Monthly breakdown with horizontal bars --}}
    <x-filament::section :heading="__('finance/projection.sections.monthly')">
        @if ($data['has_data'])
            <div class="space-y-3">
                @foreach ($data['monthly'] as $row)
                    <div class="grid grid-cols-12 gap-3 items-center text-sm">
                        <div class="col-span-2 font-medium">{{ $row['label'] }}</div>
                        <div class="col-span-4">
                            <div class="h-2 rounded-full bg-success-100 dark:bg-success-900/30 overflow-hidden">
                                <div class="h-2 rounded-full bg-success-500" style="width: {{ $row['income_pct'] }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-success-700 dark:text-success-300">{{ $row['income'] }}</div>
                        </div>
                        <div class="col-span-4">
                            <div class="h-2 rounded-full bg-danger-100 dark:bg-danger-900/30 overflow-hidden">
                                <div class="h-2 rounded-full bg-danger-500" style="width: {{ $row['loss_pct'] }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-danger-700 dark:text-danger-300">{{ $row['loss'] }}</div>
                        </div>
                        <div @class([
                            'col-span-2 text-right font-medium',
                            'text-success-700 dark:text-success-300' => ! $row['net_negative'],
                            'text-danger-700 dark:text-danger-300' => $row['net_negative'],
                        ])>{{ $row['net'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('finance/projection.empty') }}
            </div>
        @endif
    </x-filament::section>

    {{-- Per-entry detail list --}}
    @if (count($data['entries']) > 0)
        <x-filament::section :heading="__('finance/projection.sections.entries')" collapsible collapsed>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="text-left py-2">{{ __('finance/projection.columns.date') }}</th>
                        <th class="text-left py-2">{{ __('finance/projection.columns.description') }}</th>
                        <th class="text-left py-2">{{ __('finance/projection.columns.source') }}</th>
                        <th class="text-right py-2">{{ __('finance/projection.columns.amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($data['entries'] as $entry)
                        <tr>
                            <td class="py-2">{{ \Illuminate\Support\Carbon::parse($entry['date'])->translatedFormat('d MMM YYYY') }}</td>
                            <td class="py-2">{{ $entry['description'] }}</td>
                            <td class="py-2 text-gray-500 dark:text-gray-400">{{ $entry['source'] }}</td>
                            <td @class([
                                'py-2 text-right font-medium',
                                'text-success-700 dark:text-success-300' => $entry['type'] === 'income',
                                'text-danger-700 dark:text-danger-300' => $entry['type'] === 'loss',
                            ])>{{ $entry['amount'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
