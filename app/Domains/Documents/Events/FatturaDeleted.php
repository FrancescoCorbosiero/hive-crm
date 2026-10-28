<?php

declare(strict_types=1);

namespace App\Domains\Documents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a Fattura row is deleted (its payments have already
 * been deleted one by one, each firing PaymentDeleted). Finance listens
 * for this to detach ledger entries that still point at the fattura as
 * their source.
 */
final class FatturaDeleted
{
    use Dispatchable;

    public function __construct(public readonly int $fatturaId) {}
}
