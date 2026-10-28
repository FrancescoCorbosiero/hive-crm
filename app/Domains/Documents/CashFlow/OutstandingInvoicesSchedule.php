<?php

declare(strict_types=1);

namespace App\Domains\Documents\CashFlow;

use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\ScheduledFlow;
use Carbon\CarbonImmutable;

/**
 * Income: what clients still owe on issued fatture, expected on the due
 * date. An invoice that states none is expected after the statutory
 * default term (D.Lgs. 231/2002, art. 4: 30 days). Overdue balances
 * stay expected until they are paid or the fattura is cancelled.
 */
final class OutstandingInvoicesSchedule implements CashFlowSchedule
{
    private const DEFAULT_PAYMENT_TERM_DAYS = 30;

    public function pending(CarbonImmutable $until): iterable
    {
        $open = Fattura::query()
            ->whereIn('payment_status', [
                PaymentStatus::Unpaid->value,
                PaymentStatus::PartiallyPaid->value,
                PaymentStatus::Overdue->value,
            ])
            ->get();

        foreach ($open as $fattura) {
            $expected = $fattura->due_date
                ?? $fattura->issued_at?->copy()->addDays(self::DEFAULT_PAYMENT_TERM_DAYS);
            $owed = $fattura->outstanding();

            if ($expected === null || $expected->gt($until) || ! $owed->isPositive()) {
                continue;
            }

            yield ScheduledFlow::income($expected, $owed, $fattura->displayNumber(), 'invoice');
        }
    }
}
