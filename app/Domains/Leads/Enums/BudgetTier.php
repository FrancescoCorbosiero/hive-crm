<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

enum BudgetTier: string
{
    case Unknown = 'unknown';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Premium = 'premium';

    public function label(): string
    {
        return __('leads/budget_tier.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unknown => 'gray',
            self::Low => 'gray',
            self::Medium => 'info',
            self::High => 'warning',
            self::Premium => 'success',
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
