<?php

declare(strict_types=1);

namespace App\Shared\CashFlow;

use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * One expected movement of money: income or loss, on a day.
 *
 * `source` names the kind of schedule it came from (e.g. "domain") so
 * the projection can label it; `description` names the thing itself.
 */
final readonly class ScheduledFlow
{
    public CarbonImmutable $date;

    private function __construct(
        public bool $isIncome,
        CarbonInterface $date,
        public Money $amount,
        public string $description,
        public string $source,
    ) {
        $this->date = CarbonImmutable::instance($date)->startOfDay();
    }

    public static function income(CarbonInterface $date, Money $amount, string $description, string $source): self
    {
        return new self(true, $date, $amount, $description, $source);
    }

    public static function loss(CarbonInterface $date, Money $amount, string $description, string $source): self
    {
        return new self(false, $date, $amount, $description, $source);
    }
}
