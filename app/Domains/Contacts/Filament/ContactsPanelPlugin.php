<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Registers the Contacts domain's Filament classes with the admin panel.
 *
 * Convention: every domain ships its own *PanelPlugin and the
 * AdminPanelProvider lists them in ->plugins([]). This is the single point
 * where the panel learns about a domain.
 */
class ContactsPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'contacts';
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
                for: 'App\\Domains\\Contacts\\Filament\\Resources',
            )
            ->discoverPages(
                in: __DIR__.'/Pages',
                for: 'App\\Domains\\Contacts\\Filament\\Pages',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Contacts\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        // No-op for now — domain-specific panel boot logic lives here.
    }
}
