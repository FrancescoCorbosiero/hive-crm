<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Enums;

/**
 * Macro-categories of the services a web-design shop sells. Kept
 * deliberately coarse so the catalog filter UI stays usable and so
 * future "revenue by service category" reporting has a small, stable
 * set of buckets to aggregate on.
 */
enum ServiceCategory: string
{
    case Software = 'software';
    case Websites = 'websites';
    case HostingDomains = 'hosting_domains';
    case BrandingMarketing = 'branding_marketing';
    case Seo = 'seo';
    case Consulting = 'consulting';
    case MaintenanceSupport = 'maintenance_support';
    case Other = 'other';

    public function label(): string
    {
        return __('catalog/category.'.$this->value);
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
