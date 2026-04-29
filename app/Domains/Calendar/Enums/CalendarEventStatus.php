<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Enums;

/**
 * Local representation of a Cal.com booking status.
 *
 * Maps from Cal.com's uppercase API/webhook values (ACCEPTED, PENDING,
 * CANCELLED, REJECTED) to our lowercase string column.
 */
enum CalendarEventStatus: string
{
    case Accepted = 'accepted';
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public static function fromCalcom(?string $remote): self
    {
        return match (strtolower((string) $remote)) {
            'accepted' => self::Accepted,
            'pending' => self::Pending,
            'cancelled', 'canceled' => self::Cancelled,
            'rejected' => self::Rejected,
            default => self::Pending,
        };
    }

    public function label(): string
    {
        return __('calendar/status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Accepted => 'success',
            self::Pending => 'warning',
            self::Cancelled => 'gray',
            self::Rejected => 'danger',
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
