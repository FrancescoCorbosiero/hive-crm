<?php

declare(strict_types=1);

namespace App\Domains\Documents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a Payment row is created. The Finance domain listens
 * for this and creates an Income transaction so the ledger reflects
 * cash actually received (vs. fatture issued).
 */
final class PaymentRecorded
{
    use Dispatchable;

    public function __construct(public readonly int $paymentId) {}
}
