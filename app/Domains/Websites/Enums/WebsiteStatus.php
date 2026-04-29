<?php

declare(strict_types=1);

namespace App\Domains\Websites\Enums;

enum WebsiteStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return __('websites/status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Maintenance => 'warning',
            self::Suspended => 'danger',
            self::Archived => 'gray',
        };
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
