<?php

declare(strict_types=1);

namespace App\Shared\Billing;

use ArrayObject;
use Illuminate\Database\Eloquent\Model;

/**
 * Exposes a model's `lines` column (AsArrayObject cast) and `currency`
 * as LineItems, so callers never re-implement the bill arithmetic.
 *
 * @mixin Model
 */
trait HasLineItems
{
    public function lineItems(?string $defaultNatura = null): LineItems
    {
        $lines = $this->lines;

        return LineItems::fromArray(
            $lines instanceof ArrayObject ? $lines->getArrayCopy() : (array) ($lines ?? []),
            $this->currency ?: (string) config('app.currency', 'EUR'),
            $defaultNatura,
        );
    }
}
