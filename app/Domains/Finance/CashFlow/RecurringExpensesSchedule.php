<?php

declare(strict_types=1);

namespace App\Domains\Finance\CashFlow;

use App\Domains\Finance\Models\RecurringExpense;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\Occurrences;
use App\Shared\CashFlow\ScheduledFlow;
use Carbon\CarbonImmutable;

/**
 * Loss: each occurrence of an active recurring expense, from its next
 * due date — including a delayed one nobody has logged yet ("Log
 * occurrence" rolls next_due_at forward).
 */
final class RecurringExpensesSchedule implements CashFlowSchedule
{
    public function pending(CarbonImmutable $until): iterable
    {
        $expenses = RecurringExpense::query()
            ->active()
            ->where('amount_cents', '>', 0)
            ->whereDate('next_due_at', '<=', $until)
            ->get();

        foreach ($expenses as $expense) {
            $amount = $expense->money();

            foreach (Occurrences::until($expense->next_due_at, $expense->dueDateAfter(...), $until) as $date) {
                yield ScheduledFlow::loss($date, $amount, (string) $expense->name, 'recurring_expense');
            }
        }
    }
}
