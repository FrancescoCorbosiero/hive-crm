<?php

declare(strict_types=1);

namespace App\Domains\Documents\Enums;

use Carbon\CarbonInterface;

enum RecurringFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('documents/labels.frequency.'.$this->value);
    }

    /**
     * Advance a date by one period of this frequency.
     *
     * Never overflows into the following month: Jan 31 + 1 month is
     * Feb 28, not Mar 3 — which would skip February's cycle entirely
     * and leave the schedule on the 3rd from then on.
     */
    public function advance(CarbonInterface $from): CarbonInterface
    {
        return match ($this) {
            self::Monthly => $from->copy()->addMonthsNoOverflow(1),
            self::Quarterly => $from->copy()->addMonthsNoOverflow(3),
            self::Yearly => $from->copy()->addYearsNoOverflow(1),
        };
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
