<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
        @php($rows = $this->rows)

        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('dashboard.active_subscriptions.empty') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.name') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.kind') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.counterparty') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('dashboard.active_subscriptions.cols.amount') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.frequency') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.started_at') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('dashboard.active_subscriptions.cols.next_due_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rows as $row)
                            <tr @class([
                                'bg-red-50/40 dark:bg-red-950/30' => $row['delayed'],
                            ])>
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">
                                    <a href="{{ $row['edit_url'] }}" class="hover:underline">
                                        {{ $row['name'] }}
                                    </a>
                                </td>
                                <td class="px-3 py-2">
                                    <span @class([
                                        'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' => $row['direction'] === 'income',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $row['direction'] === 'loss',
                                    ])>
                                        {{ $row['kind'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                    {{ $row['counterparty'] ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums @if ($row['direction'] === 'income') text-green-700 dark:text-green-300 @else text-rose-700 dark:text-rose-300 @endif">
                                    {{ $row['amount'] ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                    {{ $row['frequency'] }}
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 tabular-nums">
                                    {{ $row['started_at'] ?? '—' }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    @if ($row['delayed'])
                                        <span class="inline-flex items-center rounded-md bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                            {{ $row['next_due_at'] }} · {{ __('dashboard.active_subscriptions.delayed') }}
                                        </span>
                                    @else
                                        {{ $row['next_due_at'] ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
