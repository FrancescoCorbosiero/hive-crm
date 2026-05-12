<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

enum LostReason: string
{
    case Budget = 'budget';
    case Competitor = 'competitor';
    case Timing = 'timing';
    case NotAFit = 'not_a_fit';
    case NoResponse = 'no_response';
    case Other = 'other';

    public function label(): string
    {
        return __('leads/lost_reason.'.$this->value);
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
