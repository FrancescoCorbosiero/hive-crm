<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                {{ __('demo_data.current_state.heading') }}
            </h3>

            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($this->tableCounts as $key => $count)
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('demo_data.tables.' . $key) }}
                        </dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $count }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
            <p>{{ __('demo_data.help.idempotent') }}</p>
            <p class="mt-2">{{ __('demo_data.help.no_uninstall') }}</p>
        </div>
    </div>
</x-filament-panels::page>
