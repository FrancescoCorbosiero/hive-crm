<?php

declare(strict_types=1);

namespace App\Domains\Documents\DTOs;

use App\Domains\Documents\Models\Payment;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class PaymentDTO
{
    public function __construct(
        public int $id,
        public int $fatturaId,
        public ?string $fatturaNumber,
        public ?int $clientContactId,
        public Money $amount,
        public ?Carbon $paidAt,
        public ?string $method,
        public ?string $reference,
        public ?int $financialEntryId,
        public ?int $ownerUserId,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        $fattura = $payment->fattura;

        return new self(
            id: $payment->id,
            fatturaId: (int) $payment->fattura_id,
            fatturaNumber: $fattura?->displayNumber(),
            clientContactId: $fattura?->client_contact_id,
            amount: new Money((int) $payment->amount_cents, $payment->currency ?: config('app.currency', 'EUR')),
            paidAt: $payment->paid_at,
            method: $payment->method?->value,
            reference: $payment->reference,
            financialEntryId: $payment->financial_entry_id,
            ownerUserId: $payment->owner_user_id,
        );
    }
}
