<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Enums;

use App\Domains\Documents\Enums\RecurringFrequency;

/**
 * Cadence of a single quotation line.
 *
 * `una_tantum` is a one-shot charge — appears only on the upfront
 * fattura issued at accept time. The other three values match the
 * Documents domain's RecurringFrequency 1:1 (string values are
 * identical) and seed a RecurringFattura when the quotation is
 * accepted, so the customer is billed again at +1 period.
 *
 * Lines without an explicit cadence are treated as `una_tantum`
 * (backward compat with quotations created before this enum existed).
 */
enum LineCadence: string
{
    case UnaTantum = 'una_tantum';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('quotations/labels.cadence.'.$this->value);
    }

    public function isRecurring(): bool
    {
        return $this !== self::UnaTantum;
    }

    /**
     * Map to the Documents RecurringFrequency for the schedule that
     * this line should seed. Returns null for una_tantum (no schedule).
     */
    public function toRecurringFrequency(): ?RecurringFrequency
    {
        return match ($this) {
            self::Monthly => RecurringFrequency::Monthly,
            self::Quarterly => RecurringFrequency::Quarterly,
            self::Yearly => RecurringFrequency::Yearly,
            self::UnaTantum => null,
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
