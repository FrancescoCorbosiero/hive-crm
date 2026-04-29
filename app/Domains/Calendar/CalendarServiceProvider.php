<?php

declare(strict_types=1);

namespace App\Domains\Calendar;

use App\Domains\Calendar\Console\Commands\SyncCalcomEventsCommand;
use App\Domains\Calendar\Http\Controllers\CalcomWebhookController;
use App\Domains\Calendar\Http\Middleware\VerifyCalcomSignature;
use App\Domains\Calendar\Services\Public\CalcomService;
use App\Domains\Calendar\Services\Public\CalendarReadService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalcomService::class, fn ($app) => new CalcomService(
            $app->make(HttpFactory::class),
        ));

        $this->app->singleton(CalendarReadService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncCalcomEventsCommand::class,
            ]);
        }

        $this->registerWebhookRoutes();
    }

    /**
     * Webhook routes register themselves here so the domain owns the
     * full ingestion path. routes/webhooks.php stays empty — service
     * providers are the single declaration point.
     *
     * The route group is mounted under prefix `webhooks/` and CSRF-
     * excluded from bootstrap/app.php; we additionally apply the HMAC
     * signature middleware before the controller runs.
     */
    private function registerWebhookRoutes(): void
    {
        Route::middleware('api')
            ->prefix('webhooks')
            ->name('webhooks.')
            ->group(function () {
                Route::post('calcom', CalcomWebhookController::class)
                    ->middleware(VerifyCalcomSignature::class)
                    ->name('calcom');
            });
    }
}
