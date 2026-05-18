<?php

declare(strict_types=1);

namespace App\Domains\Finance;

use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\Finance\Listeners\RecordIncomeFromPayment;
use App\Domains\Finance\Listeners\RecordLossFromDomainRegistration;
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
        // Finance mirrors it as an Income FinancialEntry. The listener
        // is idempotent (checks payment.financial_entry_id).
        Event::listen(PaymentRecorded::class, RecordIncomeFromPayment::class);

        // When a domain is registered and the operator opted into
        // "register payment", Finance mirrors that cost as a LOSS entry
        // tagged with external_ref = "domain_registration:{id}" so
        // duplicate dispatch is a no-op.
        Event::listen(DomainRegistered::class, RecordLossFromDomainRegistration::class);
    }
}
