<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Enums;

enum DomainStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Transferred = 'transferred';
    case Parked = 'parked';

    public function label(): string
    {
        return __('domain_names/status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'danger',
            self::Transferred => 'gray',
            self::Parked => 'warning',
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
