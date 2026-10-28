<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\Documents\Events\PaymentDeleted;
use App\Domains\Finance\Services\Public\FinanceService;

/**
 * A deleted payment is cash that, as far as the books go, never came
 * in: retract the INCOME entry RecordIncomeFromPayment mirrored from it.
 *
 * Only the mirrored entry (external_ref "payment:{id}") is touched — an
 * entry the operator created themselves and settled a fattura against
 * stays, because it records cash independently of the payment row.
 */
class RetractIncomeForDeletedPayment
{
    public function __construct(private readonly FinanceService $finance) {}

    public function handle(PaymentDeleted $event): void
    {
        $this->finance->deleteByExternalRef(RecordIncomeFromPayment::externalRef($event->paymentId));
    }
}
