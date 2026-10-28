<?php

declare(strict_types=1);

namespace App\Domains\Finance\Services\Public;

use App\Domains\Finance\DTOs\CashFlowProjection;
use App\Domains\Finance\DTOs\ProjectedMonth;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\ScheduledFlow;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Expected income and loss, month by month, from every registered
 * CashFlowSchedule. Derived on every call, never stored: fatture are tax
 * documents numbered at issuance, so a projection must not pre-create
 * one. The domains' own "issue" / "log renewal" / "log occurrence"
 * actions remain the source of truth for what actually happened.
 *
 * Single-currency by convention (APP_CURRENCY): amounts are summed as-is.
 */
final class CashFlowProjectionService
{
    /**
     * @param  iterable<CashFlowSchedule>  $schedules
     */
    public function __construct(private readonly iterable $schedules) {}

    /**
     * $months calendar months, starting with the one containing $today.
     * Flows still pending from before it (overdue) count in that first
     * month, so nothing owed silently drops out of the picture.
     */
    public function project(CarbonInterface $today, int $months): CashFlowProjection
    {
        $months = max(1, $months);
        $today = CarbonImmutable::instance($today)->startOfDay();
        $first = $today->startOfMonth();
        $until = $first->addMonthsNoOverflow($months - 1)->endOfMonth();

        $flows = [];
        foreach ($this->schedules as $schedule) {
            foreach ($schedule->pending($until) as $flow) {
                if ($flow->date->lte($until)) {
                    $flows[] = $flow;
                }
            }
        }

        usort($flows, fn (ScheduledFlow $a, ScheduledFlow $b): int => $a->date <=> $b->date);

        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $start = $first->addMonthsNoOverflow($i);
            $buckets[$start->format('Y-m')] = ['start' => $start, 'income' => 0, 'loss' => 0];
        }

        foreach ($flows as $flow) {
            $key = $flow->date->lt($first) ? $first->format('Y-m') : $flow->date->format('Y-m');
            $buckets[$key][$flow->isIncome ? 'income' : 'loss'] += $flow->amount->cents;
        }

        $currency = (string) config('app.currency', 'EUR');

        return new CashFlowProjection(
            today: $today,
            months: array_values(array_map(
                fn (array $bucket) => new ProjectedMonth(
                    $bucket['start'],
                    new Money($bucket['income'], $currency),
                    new Money($bucket['loss'], $currency),
                ),
                $buckets,
            )),
            flows: $flows,
            income: new Money(array_sum(array_column($buckets, 'income')), $currency),
            loss: new Money(array_sum(array_column($buckets, 'loss')), $currency),
        );
    }
}
