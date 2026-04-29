<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class DocumentsPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'documents';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'App\\Domains\\Documents\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
