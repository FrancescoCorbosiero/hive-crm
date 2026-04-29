<?php

declare(strict_types=1);

namespace App\Domains\Websites\Services\Public;

use App\Domains\Websites\DTOs\WebsiteDTO;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Collection;

class WebsitesService
{
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
