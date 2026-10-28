<?php

declare(strict_types=1);

namespace App\Domains\Documents\DTOs;

use App\Shared\Billing\LineItem;
use App\Shared\ValueObjects\Money;

/**
 * One fattura line as rendered on the PDF. VAT is deliberately not
 * exposed per line: it is computed per rate on the summed net (see
 * LineItems), so a per-line VAT figure would not add up to the total.
 */
final readonly class FatturaLineDTO
{
    public function __construct(
        public string $description,
        public float $qty,
        public Money $unitPrice,
        public float $vatRate,
        private Money $net,
    ) {}

    public function lineSubtotal(): Money
    {
        return $this->net;
    }

    public static function fromLineItem(LineItem $item, string $currency = 'EUR'): self
    {
        return new self(
            description: $item->description,
            qty: $item->quantity,
            unitPrice: new Money($item->unitPriceCents, $currency),
            vatRate: $item->vatRate,
            net: new Money($item->netCents, $currency),
        );
    }
}
