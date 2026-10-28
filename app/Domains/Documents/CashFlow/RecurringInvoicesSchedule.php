<?php

declare(strict_types=1);

namespace App\Domains\Documents\CashFlow;

use App\Domains\Documents\Models\RecurringFattura;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\Occurrences;
use App\Shared\CashFlow\ScheduledFlow;
use Carbon\CarbonImmutable;

/**
 * Income: every cycle an active recurring schedule will issue, on the
 * dates RecurringFatturaService::issue() will issue them — including
 * cycles already due that the daily issuer has yet to catch up on.
 */
final class RecurringInvoicesSchedule implements CashFlowSchedule
{
    public function pending(CarbonImmutable $until): iterable
    {
        $schedules = RecurringFattura::query()
            ->where('is_active', true)
            ->whereDate('next_issue_at', '<=', $until)
            ->get();

        foreach ($schedules as $schedule) {
            $cycle = $schedule->cycleTotal();

            if (! $cycle->isPositive()) {
                continue;
            }

            foreach (Occurrences::until($schedule->next_issue_at, $schedule->issueDateAfter(...), $until) as $date) {
                yield ScheduledFlow::income($date, $cycle, (string) $schedule->name, 'recurring_fattura');
            }
        }
    }
}
