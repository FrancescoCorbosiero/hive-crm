<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SettingsPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'settings';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverPages(
                in: __DIR__.'/Pages',
                for: 'App\\Domains\\Settings\\Filament\\Pages',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
