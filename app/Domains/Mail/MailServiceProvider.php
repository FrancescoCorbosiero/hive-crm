<?php

declare(strict_types=1);

namespace App\Domains\Mail;

use App\Domains\Mail\Http\Controllers\SesWebhookController;
use App\Domains\Mail\Http\Controllers\UnsubscribeController;
use App\Domains\Mail\Services\Public\MailService;
use App\Domains\Mail\Support\SnsMessageValidator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailService::class);
        $this->app->singleton(SnsMessageValidator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $this->registerRateLimiters();
        $this->registerWebhookRoutes();
        $this->registerWebRoutes();
    }

    /**
     * SES sandbox accounts allow ~1 email/sec; production-eligible
     * accounts ramp up to 14/sec by default. We start conservative —
     * raise via config when SES production access is granted.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('ses-send', fn () => Limit::perMinute(
            (int) env('MAIL_SES_PER_MINUTE', 60),
        ));
    }

    private function registerWebhookRoutes(): void
    {
        Route::middleware('api')
            ->prefix('webhooks')
            ->name('webhooks.')
            ->group(function () {
                Route::post('ses', SesWebhookController::class)->name('ses');
            });
    }

    private function registerWebRoutes(): void
    {
        Route::middleware('web')->group(function () {
            // Both GET (link click) and POST (List-Unsubscribe-Post one-click).
            Route::match(['GET', 'POST'], '/unsubscribe/{token}', UnsubscribeController::class)
                ->name('mail.unsubscribe');
        });
    }
}
