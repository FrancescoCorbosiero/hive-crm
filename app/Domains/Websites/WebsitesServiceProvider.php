<?php

declare(strict_types=1);

namespace App\Domains\Websites;

use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\Websites\Console\Commands\CheckRenewalsCommand;
use App\Domains\Websites\Console\Commands\PingWebsitesCommand;
use App\Domains\Websites\Listeners\CreateWebsiteFromDomainRegistration;
use App\Domains\Websites\Services\Internal\WebsitePinger;
use App\Domains\Websites\Services\Public\WebsitesService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
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

        // Cross-domain wiring: when a domain is registered and the
        // operator opted into "create website", spawn the sibling
        // Website and back-link it via domain_names.website_id.
        // Idempotent (skips when website_id is already set).
        Event::listen(DomainRegistered::class, CreateWebsiteFromDomainRegistration::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckRenewalsCommand::class,
                PingWebsitesCommand::class,
            ]);
        }
    }
}
