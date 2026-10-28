<?php

use App\Shared\Billing\LineItems;

it('sums line nets and applies VAT per rate', function () {
    $items = LineItems::fromArray([
        ['description' => 'A', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 22],
        ['description' => 'B', 'qty' => 2, 'unit_price_cents' => 5_000, 'vat_rate' => 10],
    ], 'EUR');

    expect($items->subtotal()->cents)->toBe(20_000);
    expect($items->vat()->cents)->toBe(3_200);
    expect($items->total()->cents)->toBe(23_200);
    expect($items->total()->currency)->toBe('EUR');
});

it('rounds VAT once per rate on the summed taxable amount, not once per line', function () {
    // Ten lines of €0.10 at 22%: per-line rounding would give 10 × 2 = 20
    // cents of VAT, but the SdI checks Imposta against 22% of the €1.00
    // taxable total (= 22 cents) in DatiRiepilogo.
    $lines = array_fill(0, 10, ['description' => 'x', 'qty' => 1, 'unit_price_cents' => 10, 'vat_rate' => 22]);

    $items = LineItems::fromArray($lines);

    expect($items->subtotal()->cents)->toBe(100);
    expect($items->vat()->cents)->toBe(22);
});

it('groups the VAT summary by rate and natura', function () {
    $items = LineItems::fromArray([
        ['description' => 'Taxable', 'qty' => 1, 'unit_price_cents' => 1_000, 'vat_rate' => 22],
        ['description' => 'Exempt A', 'qty' => 1, 'unit_price_cents' => 500, 'vat_rate' => 0],
        ['description' => 'Exempt B', 'qty' => 1, 'unit_price_cents' => 300, 'vat_rate' => 0, 'natura' => 'N4'],
        ['description' => 'Taxable 2', 'qty' => 3, 'unit_price_cents' => 1_000, 'vat_rate' => 22],
    ], 'EUR', defaultNatura: 'N2.2');

    $summary = collect($items->vatSummary())
        ->map(fn ($row) => [$row->vatRate, $row->natura, $row->taxableCents, $row->vatCents])
        ->all();

    expect($summary)->toBe([
        [22.0, null, 4_000, 880],
        [0.0, 'N2.2', 500, 0],
        [0.0, 'N4', 300, 0],
    ]);
});

it('never carries a natura on a taxed line', function () {
    $items = LineItems::fromArray([
        ['description' => 'x', 'qty' => 1, 'unit_price_cents' => 100, 'vat_rate' => 22, 'natura' => 'N2.2'],
    ], defaultNatura: 'N2.2');

    expect($items->items()[0]->natura)->toBeNull();
});

it('rounds fractional quantities half away from zero without float drift', function () {
    // 1.15 × 10 = 11.5 → 12 (a naive float product is 11.4999…).
    expect(LineItems::fromArray([['qty' => 1.15, 'unit_price_cents' => 10, 'vat_rate' => 0]])->subtotal()->cents)->toBe(12);
    // 1.5 × 333 = 499.5 → 500
    expect(LineItems::fromArray([['qty' => '1.5', 'unit_price_cents' => 333, 'vat_rate' => 0]])->subtotal()->cents)->toBe(500);
    // Discount lines round symmetrically: -1.5 × 333 = -499.5 → -500
    expect(LineItems::fromArray([['qty' => -1.5, 'unit_price_cents' => 333, 'vat_rate' => 0]])->subtotal()->cents)->toBe(-500);
});

it('accepts form-shaped input: numeric strings, comma decimals, missing keys', function () {
    $items = LineItems::fromArray([
        ['description' => 'From the form', 'qty' => '2', 'unit_price_cents' => '1250', 'vat_rate' => '22'],
        ['description' => 'Comma qty', 'qty' => '0,5', 'unit_price_cents' => 1_000, 'vat_rate' => 0],
        ['description' => 'Blank'],
    ]);

    expect($items->subtotal()->cents)->toBe(2_500 + 500);
    expect($items->vat()->cents)->toBe(550);
});

it('is empty-safe', function () {
    $items = LineItems::fromArray(null);

    expect($items->items())->toBe([]);
    expect($items->vatSummary())->toBe([]);
    expect($items->total()->cents)->toBe(0);
});

it('back-derives the net unit price from a VAT-inclusive amount', function () {
    expect(LineItems::netFromGross(12_200, 22))->toBe(10_000);
    expect(LineItems::netFromGross(10_000, 0))->toBe(10_000);
    // 10_000 / 1.22 = 8_196.72 → 8_197 net; 8_197 + 22% (1_803) = 10_000 gross.
    expect(LineItems::netFromGross(10_000, 22))->toBe(8_197);
});
