<?php

declare(strict_types=1);

namespace App\Domains\Finance\DTOs;

use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;

final readonly class ProjectedMonth
{
    public function __construct(
        public CarbonImmutable $start,
        public Money $income,
        public Money $loss,
    ) {}

    public function key(): string
    {
        return $this->start->format('Y-m');
    }

    public function net(): Money
    {
        return $this->income->subtract($this->loss);
    }
}
