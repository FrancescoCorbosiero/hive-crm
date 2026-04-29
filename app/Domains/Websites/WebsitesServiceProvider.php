<?php

declare(strict_types=1);

namespace App\Domains\Websites;

use App\Domains\Websites\Console\Commands\CheckRenewalsCommand;
use App\Domains\Websites\Services\Public\WebsitesService;
use Illuminate\Support\ServiceProvider;

class WebsitesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebsitesService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckRenewalsCommand::class,
            ]);
        }
    }
}
