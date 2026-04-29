@php
    $supported = config('app.supported_locales', ['it', 'en']);
    $current = app()->getLocale();
@endphp

<div class="px-4 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/5">
    <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
    <span>{{ __('app.locale.switch') }}:</span>
    @foreach ($supported as $locale)
        @if ($locale === $current)
            <span class="font-semibold text-primary-600 dark:text-primary-400 uppercase">{{ $locale }}</span>
        @else
            <a
                href="{{ route('locale.switch', $locale) }}"
                class="uppercase hover:text-primary-600 dark:hover:text-primary-400"
            >{{ $locale }}</a>
        @endif
    @endforeach
</div>
