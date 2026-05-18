<?php

declare(strict_types=1);

namespace App\Domains\DomainNames;

use App\Domains\DomainNames\Listeners\CreateDomainFromWebsite;
use App\Domains\DomainNames\Services\Public\DomainNamesService;
use App\Domains\Websites\Events\WebsiteCreated;
use Illuminate\Support\Facades\Event;
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

        // Cross-domain wiring: when a Website is created and the
        // operator opted into "also register the matching domain",
        // spawn the sibling DomainName pointing at it via website_id.
        // Idempotent: re-uses an existing row if the host already
        // matches a registered domain.
        Event::listen(WebsiteCreated::class, CreateDomainFromWebsite::class);
    }
}
