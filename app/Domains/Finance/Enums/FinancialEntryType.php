<?php

declare(strict_types=1);

namespace App\Domains\Finance\Enums;

/**
 * Direction of a FinancialEntry. The DB stores the lowercase value;
 * UI/translations map to localized labels. INCOME and LOSS are the
 * only two states — finer-grained accounting categories live in the
 * `category` column.
 */
enum FinancialEntryType: string
{
    case Income = 'income';
    case Loss = 'loss';

    public function label(): string
    {
        return __('finance/entries.type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Income => 'success',
            self::Loss => 'danger',
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
