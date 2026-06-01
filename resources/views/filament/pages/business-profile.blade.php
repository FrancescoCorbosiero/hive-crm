<x-filament-panels::page>
    <form wire:submit="save" class="space-y-5">
        {{ $this->form }}

        <div class="hive-accent-rule"></div>

        <div class="flex items-center justify-between gap-3">
            <p class="text-xs uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                {{ __('settings/profile.save_hint') }}
            </p>
            <x-filament::button type="submit" color="primary">
                {{ __('settings/profile.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
