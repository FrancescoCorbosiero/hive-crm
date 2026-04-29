<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WebsitesPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'websites';
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
                for: 'App\\Domains\\Websites\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Websites\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
