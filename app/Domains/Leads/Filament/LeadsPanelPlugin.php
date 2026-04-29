<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class LeadsPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'leads';
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
                for: 'App\\Domains\\Leads\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Leads\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
