<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class QuotationsPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'quotations';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'App\\Domains\\Quotations\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
