<?php

declare(strict_types=1);

namespace App\Domains\Websites\Services\Public;

use App\Domains\Websites\DTOs\WebsiteDTO;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Collection;

class WebsitesService
{
    /**
     * Create a Website from a payload originating outside the domain.
     *
     * Translatable name + notes accept either a string (single locale,
     * stored against the configured app locale) or a [locale => string]
     * map.
     *
     * @param  array{
     *     name: string|array<string,string>,
     *     url: string,
     *     status?: string,
     *     owner_contact_id?: ?int,
     *     tech_stack?: ?array<int,string>,
     *     notes?: string|array<string,string>|null,
     *     subscription_started_at?: \DateTimeInterface|string|null,
     *     next_renewal_at?: \DateTimeInterface|string|null,
     *     renewal_period_months?: int,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function create(array $attributes): WebsiteDTO
    {
        $attributes = array_merge([
            'status' => 'active',
            'renewal_period_months' => 12,
        ], $attributes);

        $defaultLocale = config('app.locale', 'it');

        if (isset($attributes['name']) && is_string($attributes['name'])) {
            $attributes['name'] = [$defaultLocale => $attributes['name']];
        }

        if (isset($attributes['notes']) && is_string($attributes['notes'])) {
            $attributes['notes'] = [$defaultLocale => $attributes['notes']];
        }

        $website = \App\Domains\Websites\Models\Website::query()->create($attributes);

        return WebsiteDTO::fromModel($website);
    }

    public function find(int $id): ?WebsiteDTO
    {
        $website = Website::query()->find($id);

        return $website ? WebsiteDTO::fromModel($website) : null;
    }

    /**
     * Websites belonging to the given Contact (scalar FK by design).
     *
     * @return Collection<int, WebsiteDTO>
     */
    public function forContact(int $contactId): Collection
    {
        return Website::query()
            ->where('owner_contact_id', $contactId)
            ->get()
            ->map(fn (Website $w) => WebsiteDTO::fromModel($w));
    }

    /**
     * Websites whose next_renewal_at falls within the given number of days.
     *
     * @return Collection<int, WebsiteDTO>
     */
    public function renewingWithin(int $days): Collection
    {
        return Website::query()
            ->renewingWithin($days)
            ->orderBy('next_renewal_at')
            ->get()
            ->map(fn (Website $w) => WebsiteDTO::fromModel($w));
    }
}
