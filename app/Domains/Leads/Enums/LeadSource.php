<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

enum LeadSource: string
{
    case Referral = 'referral';
    case Website = 'website';
    case ColdOutreach = 'cold_outreach';
    case Event = 'event';
    case Inbound = 'inbound';
    case Other = 'other';

    public function label(): string
    {
        return __('leads/source.'.$this->value);
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
