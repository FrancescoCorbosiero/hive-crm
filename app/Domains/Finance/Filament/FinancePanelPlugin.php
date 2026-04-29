<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FinancePanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'finance';
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
                for: 'App\\Domains\\Finance\\Filament\\Resources',
            )
            ->discoverPages(
                in: __DIR__.'/Pages',
                for: 'App\\Domains\\Finance\\Filament\\Pages',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Finance\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
