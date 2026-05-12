<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SchedulingPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'scheduling';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'App\\Domains\\Scheduling\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void
    {
        // No-op.
    }
}
