<?php

declare(strict_types=1);

namespace App\Domains\Documents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a fattura's payment_status transitions to Paid.
 * Fires only on the transition — never on a sustained-paid state.
 */
final class FatturaPaid
{
    use Dispatchable;

    public function __construct(
        public readonly int $fatturaId,
        public readonly int $clientContactId,
    ) {}
}
