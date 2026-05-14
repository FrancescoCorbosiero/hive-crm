<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class DomainNamesPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'domain-names';
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
                for: 'App\\Domains\\DomainNames\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\DomainNames\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
