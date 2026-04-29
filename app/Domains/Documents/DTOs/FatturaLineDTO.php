<?php

declare(strict_types=1);

namespace App\Domains\Documents\DTOs;

use App\Shared\ValueObjects\Money;

final readonly class FatturaLineDTO
{
    public function __construct(
        public string $description,
        public float $qty,
        public Money $unitPrice,
        public float $vatRate,
    ) {}

    public function lineSubtotal(): Money
    {
        return $this->unitPrice->multiply($this->qty);
    }

    public function lineVat(): Money
    {
        return $this->lineSubtotal()->multiply($this->vatRate / 100);
    }

    public function lineTotal(): Money
    {
        return $this->lineSubtotal()->add($this->lineVat());
    }

    public static function fromArray(array $row, string $currency = 'EUR'): self
    {
        return new self(
            description: (string) ($row['description'] ?? ''),
            qty: (float) ($row['qty'] ?? 0),
            unitPrice: new Money((int) ($row['unit_price_cents'] ?? 0), $currency),
            vatRate: (float) ($row['vat_rate'] ?? 0),
        );
    }
}
