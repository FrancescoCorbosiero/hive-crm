<?php

declare(strict_types=1);

namespace App\Domains\Settings;

use App\Domains\Settings\Services\Public\BusinessProfileService;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BusinessProfileService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
