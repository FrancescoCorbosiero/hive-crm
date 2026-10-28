<?php

declare(strict_types=1);

namespace App\Shared\Billing;

/**
 * One row of the VAT breakdown: every line sharing a rate (and, for 0%
 * lines, a natura) collapses into one taxable total and one VAT amount.
 * Maps 1:1 onto a FatturaPA <DatiRiepilogo> block.
 */
final readonly class VatSummary
{
    public function __construct(
        public float $vatRate,
        public ?string $natura,
        public int $taxableCents,
        public int $vatCents,
    ) {}
}
