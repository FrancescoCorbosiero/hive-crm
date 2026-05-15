<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Services\Public;

use App\Domains\Catalog\Models\Service;

/**
 * Public surface of the Catalog domain. Quotations / Documents reach
 * the catalog through this service rather than the Service model, so
 * the cross-domain boundary stays a single, intentional seam.
 */
class CatalogService
{
    public function find(int $id): ?Service
    {
        return Service::query()->find($id);
    }

    /**
     * Active services as a `id => "Name — Category"` map for the line
     * picker Select. Ordered by the catalog's own sort_order, then name.
     *
     * @return array<int,string>
     */
    public function activeOptions(): array
    {
        return Service::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'category'])
            ->mapWithKeys(fn (Service $s): array => [
                $s->id => $s->name.' — '.$s->category->label(),
            ])
            ->all();
    }

    /**
     * Template values a catalog service contributes to a freshly
     * picked bill line. The line owns these values from the moment it
     * is created — nothing here is authoritative afterwards.
     *
     * @return array{description:string, unit_price_cents:?int, vat_rate:int, cadence:?string}|null
     */
    public function lineDefaults(int $id): ?array
    {
        $service = $this->find($id);
        if ($service === null) {
            return null;
        }

        return [
            'description' => $service->name,
            'unit_price_cents' => $service->default_unit_price_cents,
            'vat_rate' => $service->default_vat_rate,
            'cadence' => $service->default_cadence?->value,
        ];
    }
}
