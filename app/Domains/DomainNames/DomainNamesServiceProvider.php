<?php

declare(strict_types=1);

namespace App\Domains\DomainNames;

use App\Domains\DomainNames\Services\Public\DomainNamesService;
use Illuminate\Support\ServiceProvider;

class DomainNamesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DomainNamesService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
