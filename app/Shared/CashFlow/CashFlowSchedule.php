<?php

declare(strict_types=1);

namespace App\Shared\CashFlow;

use Carbon\CarbonImmutable;

/**
 * A source of money a domain expects to move: recurring invoices,
 * unpaid invoices, renewals, recurring expenses.
 *
 * Each domain registers its own schedules in its service provider
 * (`$this->app->tag([...], CashFlowSchedule::TAG)`). The Finance
 * cash-flow projection sums whatever is registered, so it never reads
 * another domain's tables and a new source is one class away.
 */
interface CashFlowSchedule
{
    public const TAG = 'hive.cash-flow-schedules';

    /**
     * Every flow this schedule still expects, up to and including $until.
     *
     * Starts from the next pending occurrence, which is in the past when
     * it is overdue: a cycle the daily issuer has not caught up on, an
     * invoice past its due date, a renewal nobody has logged. Those are
     * still expected — the projection counts them in its first month.
     *
     * @return iterable<ScheduledFlow>
     */
    public function pending(CarbonImmutable $until): iterable;
}
