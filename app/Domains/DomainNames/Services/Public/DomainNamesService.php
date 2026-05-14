<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Services\Public;

use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Websites\Services\Public\WebsitesService;
use Illuminate\Support\Collection;

class DomainNamesService
{
    public function __construct(private readonly WebsitesService $websites) {}

    public function find(int $id): ?DomainName
    {
        return DomainName::query()->find($id);
    }

    /**
     * Domains whose expiry falls within the given number of days.
     *
     * @return Collection<int, DomainName>
     */
    public function expiringWithin(int $days): Collection
    {
        return DomainName::query()
            ->expiringWithin($days)
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Resolve the website_id / owner_contact_id links from the domain
     * name where the user left them blank:
     *
     *   - website_id empty  → look up a Website whose host matches the
     *     domain name and link it.
     *   - owner_contact_id empty + a website is now linked → inherit
     *     the website's owner contact.
     *
     * Returns the (possibly enriched) attributes array. Pure — does
     * not persist; the caller saves.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function autoLink(array $attributes): array
    {
        $name = (string) ($attributes['name'] ?? '');
        if ($name === '') {
            return $attributes;
        }

        if (empty($attributes['website_id'])) {
            $website = $this->websites->findByHost($name);
            if ($website !== null) {
                $attributes['website_id'] = $website->id;

                if (empty($attributes['owner_contact_id']) && $website->ownerContactId !== null) {
                    $attributes['owner_contact_id'] = $website->ownerContactId;
                }
            }
        } elseif (empty($attributes['owner_contact_id'])) {
            $website = $this->websites->find((int) $attributes['website_id']);
            if ($website !== null && $website->ownerContactId !== null) {
                $attributes['owner_contact_id'] = $website->ownerContactId;
            }
        }

        return $attributes;
    }
}
