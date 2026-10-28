<?php

declare(strict_types=1);

namespace App\Domains\Finance;

use App\Domains\Documents\Events\FatturaDeleted;
use App\Domains\Documents\Events\PaymentDeleted;
use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\Finance\CashFlow\RecurringExpensesSchedule;
use App\Domains\Finance\Listeners\DetachEntriesFromDeletedFattura;
use App\Domains\Finance\Listeners\RecordIncomeFromPayment;
use App\Domains\Finance\Listeners\RecordLossFromDomainRegistration;
use App\Domains\Finance\Listeners\RecordLossFromWebsiteSetup;
use App\Domains\Finance\Listeners\RetractIncomeForDeletedPayment;
use App\Domains\Finance\Services\Public\CashFlowProjectionService;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Shared\CashFlow\CashFlowSchedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinanceService::class);

        // The projection sums every domain's tagged schedules; it never
        // reads another domain's tables itself.
        $this->app->tag([RecurringExpensesSchedule::class], CashFlowSchedule::TAG);
        $this->app->when(CashFlowProjectionService::class)
            ->needs('$schedules')
            ->giveTagged(CashFlowSchedule::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Cross-domain wiring: when a payment is recorded in Documents,
        // Finance mirrors it as an Income FinancialEntry. The listener
        // is idempotent (external_ref "payment:{id}" is DB-unique)…
        Event::listen(PaymentRecorded::class, RecordIncomeFromPayment::class);

        // …and keeps the ledger in step when payments or fatture are
        // deleted: the mirrored income is retracted, and entries that
        // pointed at a deleted fattura are detached from it.
        Event::listen(PaymentDeleted::class, RetractIncomeForDeletedPayment::class);
        Event::listen(FatturaDeleted::class, DetachEntriesFromDeletedFattura::class);

        // When a domain is registered and the operator opted into
        // "register payment", Finance mirrors that cost as a LOSS entry
        // tagged with external_ref = "domain_registration:{id}" so
        // duplicate dispatch is a no-op.
        Event::listen(DomainRegistered::class, RecordLossFromDomainRegistration::class);

        // Symmetric flow for Websites: setup / first-cycle hosting cost
        // becomes a LOSS entry tagged source_type=website + source_id
        // and external_ref = "website_setup:{id}". Same idempotency
        // discipline as the domain flow.
        Event::listen(WebsiteCreated::class, RecordLossFromWebsiteSetup::class);
    }
}
