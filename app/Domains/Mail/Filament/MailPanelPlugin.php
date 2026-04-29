<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament;

use App\Domains\Mail\Filament\Pages\MailTestPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MailPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail';
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
                for: 'App\\Domains\\Mail\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__.'/Widgets',
                for: 'App\\Domains\\Mail\\Filament\\Widgets',
            )
            ->pages([
                MailTestPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
