<?php

declare(strict_types=1);

namespace App\Domains\Mail\Enums;

enum RecipientStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Unsubscribed = 'unsubscribed';
    case Skipped = 'skipped';
    case Failed = 'failed';

    public function label(): string
    {
        return __('mail/recipients.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Sent, self::Delivered => 'info',
            self::Bounced, self::Complained, self::Failed => 'danger',
            self::Opened, self::Clicked => 'success',
            self::Unsubscribed => 'warning',
            self::Skipped => 'gray',
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
