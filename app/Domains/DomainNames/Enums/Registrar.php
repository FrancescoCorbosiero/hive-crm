<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Enums;

/**
 * Domain registrars. Curated list of the ones common in the Italian
 * + international market; `Other` is the escape hatch — the actual
 * provider name then goes in the notes field.
 */
enum Registrar: string
{
    case Aruba = 'aruba';
    case Spaceship = 'spaceship';
    case Namecheap = 'namecheap';
    case GoDaddy = 'godaddy';
    case Ovh = 'ovh';
    case Cloudflare = 'cloudflare';
    case RegisterIt = 'register_it';
    case Netsons = 'netsons';
    case Keliweb = 'keliweb';
    case Ionos = 'ionos';
    case Other = 'other';

    public function label(): string
    {
        return __('domain_names/registrar.'.$this->value);
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
