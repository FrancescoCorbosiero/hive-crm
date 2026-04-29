<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CalendarPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'calendar';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'App\\Domains\\Calendar\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Calendar\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
