<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CatalogPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'catalog';
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
                for: 'App\\Domains\\Catalog\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Catalog\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
