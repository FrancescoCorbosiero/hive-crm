<?php

declare(strict_types=1);

namespace App\Domains\Documents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a Payment row is deleted — through PaymentsService,
 * the Filament relation manager, or because its fattura was deleted.
 * Finance listens for this to retract the INCOME entry it mirrored from
 * the payment, so the ledger never keeps cash that was never received.
 */
final class PaymentDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $paymentId,
        public readonly int $fatturaId,
    ) {}
}
