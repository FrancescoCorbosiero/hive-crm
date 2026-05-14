<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

enum WebsiteType: string
{
    case Landing = 'landing';
    case AdvLanding = 'adv_landing';
    case Portfolio = 'portfolio';
    case Blog = 'blog';
    case Business = 'business';
    case Booking = 'booking';
    case Ecommerce = 'ecommerce';

    public function label(): string
    {
        return __('leads/website_type.'.$this->value);
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
