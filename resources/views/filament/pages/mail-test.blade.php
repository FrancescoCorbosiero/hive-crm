<x-filament-panels::page>
    <form wire:submit="send" class="space-y-4">
        <div class="flex items-start gap-3">
            <span class="hive-accent-bar h-10 mt-1"></span>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('mail/test.subtitle') }}
            </p>
        </div>

        {{ $this->form }}

        <div class="hive-accent-rule"></div>

        <div class="flex">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ __('mail/test.send') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
