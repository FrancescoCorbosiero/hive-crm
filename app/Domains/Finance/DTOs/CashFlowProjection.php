<?php

declare(strict_types=1);

namespace App\Domains\Finance\DTOs;

use App\Shared\CashFlow\ScheduledFlow;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;

final readonly class CashFlowProjection
{
    /**
     * @param  list<ProjectedMonth>  $months  one per calendar month of the window
     * @param  list<ScheduledFlow>  $flows  chronological
     */
    public function __construct(
        public CarbonImmutable $today,
        public array $months,
        public array $flows,
        public Money $income,
        public Money $loss,
    ) {}

    public function net(): Money
    {
        return $this->income->subtract($this->loss);
    }

    public function isEmpty(): bool
    {
        return $this->flows === [];
    }

    public function isOverdue(ScheduledFlow $flow): bool
    {
        return $flow->date->lt($this->today);
    }
}
