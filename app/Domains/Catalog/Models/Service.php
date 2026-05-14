<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\ServiceCategory;
use App\Domains\Quotations\Enums\LineCadence;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A sellable service in the catalog. Acts purely as a template source
 * for quotation / fattura lines — see the create-services migration
 * for why the catalog is never authoritative over an issued bill.
 */
class Service extends Model
{
    use BelongsToOwner;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'category',
        'description',
        'default_unit_price_cents',
        'currency',
        'default_vat_rate',
        'default_cadence',
        'is_active',
        'sort_order',
        'notes',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'default_unit_price_cents' => 'integer',
            'default_vat_rate' => 'integer',
            'default_cadence' => LineCadence::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function defaultUnitPrice(): ?Money
    {
        if ($this->default_unit_price_cents === null) {
            return null;
        }

        return new Money((int) $this->default_unit_price_cents, $this->currency ?: config('app.currency', 'EUR'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
