<?php

declare(strict_types=1);

namespace App\Shared\Billing;

use App\Shared\ValueObjects\Money;

/**
 * The single home of bill-line arithmetic: fatture, quotations,
 * recurring schedules, the FatturaPA exporter/importer, projections and
 * dashboard widgets all compute totals through this class.
 *
 * Rules, chosen to match what the SdI validates:
 *   - a line's net is qty × unit price, rounded half away from zero to
 *     the cent;
 *   - VAT is computed once per (rate, natura) group on the group's summed
 *     net — the DatiRiepilogo rule. Rounding VAT per line and adding the
 *     results drifts by a cent every few lines, and the SdI rejects an
 *     Imposta that doesn't match ImponibileImporto × AliquotaIVA;
 *   - integer arithmetic only: quantities are scaled to 4 decimals and
 *     rates to hundredths of a percent before multiplying, so results
 *     don't depend on float representation or PHP's round() heuristics.
 */
final class LineItems
{
    /** Quantities keep 4 decimal places. */
    private const QTY_SCALE = 10_000;

    /** Rates are handled in hundredths of a percent (22% → 2200). */
    private const RATE_SCALE = 100;

    /**
     * @param  list<LineItem>  $items
     */
    private function __construct(
        private readonly array $items,
        private readonly string $currency,
    ) {}

    /**
     * @param  iterable<array<string, mixed>>|null  $rows  persisted `lines`
     * @param  ?string  $defaultNatura  natura for 0% lines that carry none
     */
    public static function fromArray(?iterable $rows, string $currency = 'EUR', ?string $defaultNatura = null): self
    {
        $items = [];
        foreach ($rows ?? [] as $row) {
            $items[] = LineItem::fromArray((array) $row, $defaultNatura);
        }

        return new self($items, $currency);
    }

    /**
     * @return list<LineItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * VAT breakdown in order of first appearance.
     *
     * @return list<VatSummary>
     */
    public function vatSummary(): array
    {
        $groups = [];
        foreach ($this->items as $item) {
            $key = self::rateUnits($item->vatRate).'|'.($item->natura ?? '');
            $groups[$key] ??= ['rate' => $item->vatRate, 'natura' => $item->natura, 'taxable' => 0];
            $groups[$key]['taxable'] += $item->netCents;
        }

        return array_values(array_map(fn (array $group) => new VatSummary(
            vatRate: $group['rate'],
            natura: $group['natura'],
            taxableCents: $group['taxable'],
            vatCents: self::vatOn($group['taxable'], $group['rate']),
        ), $groups));
    }

    public function subtotal(): Money
    {
        return new Money(array_sum(array_map(fn (LineItem $item) => $item->netCents, $this->items)), $this->currency);
    }

    public function vat(): Money
    {
        return new Money(array_sum(array_map(fn (VatSummary $row) => $row->vatCents, $this->vatSummary())), $this->currency);
    }

    public function total(): Money
    {
        return $this->subtotal()->add($this->vat());
    }

    /**
     * Net amount of one line: qty × unit price, rounded to the cent.
     */
    public static function net(float $quantity, int $unitPriceCents): int
    {
        $scaledQuantity = (int) round($quantity * self::QTY_SCALE);

        return self::divideRounded($scaledQuantity * $unitPriceCents, self::QTY_SCALE);
    }

    /**
     * VAT due on a taxable amount at the given percentage rate.
     */
    public static function vatOn(int $taxableCents, float $vatRate): int
    {
        return self::divideRounded($taxableCents * self::rateUnits($vatRate), 100 * self::RATE_SCALE);
    }

    /**
     * Inverse of net + VAT: the net amount whose gross (net plus VAT at
     * $vatRate) is as close as possible to $grossCents.
     */
    public static function netFromGross(int $grossCents, float $vatRate): int
    {
        $base = 100 * self::RATE_SCALE;

        return self::divideRounded($grossCents * $base, $base + self::rateUnits($vatRate));
    }

    private static function rateUnits(float $vatRate): int
    {
        return (int) round($vatRate * self::RATE_SCALE);
    }

    /**
     * Integer division rounded half away from zero (commercial rounding,
     * symmetric for negative discount lines). $divisor must be positive.
     */
    private static function divideRounded(int $dividend, int $divisor): int
    {
        $quotient = intdiv($dividend, $divisor);
        if (abs($dividend % $divisor) * 2 >= $divisor) {
            $quotient += $dividend < 0 ? -1 : 1;
        }

        return $quotient;
    }
}
