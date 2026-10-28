<?php

declare(strict_types=1);

namespace App\Shared\Billing;

/**
 * One bill line (fattura, quotation, recurring schedule) with its net
 * amount already computed. Built by LineItems::fromArray() from the
 * persisted `lines` shape:
 *
 *   {description, qty, unit_price_cents, vat_rate, natura?, …}
 *
 * Extra keys (service_id, cadence, …) are ignored here — they belong
 * to the owning domain, not to the arithmetic.
 */
final readonly class LineItem
{
    public function __construct(
        public string $description,
        public float $quantity,
        public int $unitPriceCents,
        public float $vatRate,
        public ?string $natura,
        public int $netCents,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row, ?string $defaultNatura = null): self
    {
        $quantity = self::number($row['qty'] ?? 0);
        $unitPriceCents = (int) round(self::number($row['unit_price_cents'] ?? 0));
        $vatRate = self::number($row['vat_rate'] ?? 0);

        // Natura (the FatturaPA exemption code) only exists on 0% lines.
        $natura = filled($row['natura'] ?? null) ? (string) $row['natura'] : $defaultNatura;
        if ($vatRate != 0.0) {
            $natura = null;
        }

        return new self(
            description: trim((string) ($row['description'] ?? '')),
            quantity: $quantity,
            unitPriceCents: $unitPriceCents,
            vatRate: $vatRate,
            natura: $natura,
            netCents: LineItems::net($quantity, $unitPriceCents),
        );
    }

    /**
     * Form state arrives as strings ("2", "0,5"); persisted JSON as
     * numbers. Anything non-numeric counts as zero.
     */
    private static function number(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
