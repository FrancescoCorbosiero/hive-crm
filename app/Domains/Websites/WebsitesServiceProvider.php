<?php

declare(strict_types=1);

namespace App\Domains\Websites;

use App\Domains\Websites\Console\Commands\CheckRenewalsCommand;
use App\Domains\Websites\Console\Commands\PingWebsitesCommand;
use App\Domains\Websites\Services\Internal\WebsitePinger;
use App\Domains\Websites\Services\Public\WebsitesService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class WebsitesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebsitesService::class);
        $this->app->singleton(WebsitePinger::class, fn ($app) => new WebsitePinger(
            $app->make(HttpFactory::class),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckRenewalsCommand::class,
                PingWebsitesCommand::class,
            ]);
        }
    }
}
