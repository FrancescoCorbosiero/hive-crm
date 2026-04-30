<?php

declare(strict_types=1);

namespace App\Domains\Finance;

use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\Finance\Listeners\RecordIncomeFromPayment;
use App\Domains\Finance\Services\Public\FinanceService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinanceService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Cross-domain wiring: when a payment is recorded in Documents,
        // Finance mirrors it as an Income transaction. The listener is
        // idempotent (checks payment.transaction_id).
        Event::listen(PaymentRecorded::class, RecordIncomeFromPayment::class);
    }
}
