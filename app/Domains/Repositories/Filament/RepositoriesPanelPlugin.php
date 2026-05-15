<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class RepositoriesPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'repositories';
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
                for: 'App\\Domains\\Repositories\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Repositories\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
