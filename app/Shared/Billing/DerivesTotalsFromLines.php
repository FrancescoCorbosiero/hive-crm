<?php

declare(strict_types=1);

namespace App\Shared\Billing;

use Illuminate\Database\Eloquent\Model;

/**
 * For models that persist `lines` alongside cached `subtotal_cents`,
 * `vat_cents` and `total_cents` columns (fatture, quotations).
 *
 * The cached totals are a pure function of the lines: they are
 * re-derived on create and whenever `lines` changes, whatever the write
 * path (public service, Filament edit page, importer, tinker). Without
 * this, editing a document's lines in the panel left its totals — and
 * therefore its PDF, XML, outstanding amount and payment status — stale.
 *
 * @mixin Model
 */
trait DerivesTotalsFromLines
{
    use HasLineItems;

    public static function bootDerivesTotalsFromLines(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            if (! $model->exists || $model->isDirty('lines')) {
                $model->fillTotalsFromLines();
            }
        });
    }

    public function fillTotalsFromLines(): static
    {
        $items = $this->lineItems();

        $this->subtotal_cents = $items->subtotal()->cents;
        $this->vat_cents = $items->vat()->cents;
        $this->total_cents = $items->total()->cents;

        return $this;
    }
}
