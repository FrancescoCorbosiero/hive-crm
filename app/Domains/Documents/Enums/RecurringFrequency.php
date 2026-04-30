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
     */
    public function advance(CarbonInterface $from): CarbonInterface
    {
        return match ($this) {
            self::Monthly => $from->copy()->addMonth(),
            self::Quarterly => $from->copy()->addMonths(3),
            self::Yearly => $from->copy()->addYear(),
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
