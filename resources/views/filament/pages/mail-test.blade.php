<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('mail/test.subtitle') }}
    </p>

    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-4 flex">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ __('mail/test.send') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
