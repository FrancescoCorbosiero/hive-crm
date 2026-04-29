<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Services\Public\FatturaService;

it('computes subtotal, VAT and total from line items', function () {
    $contact = Contact::factory()->create();

    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [
            // 1 × €100.00 @ 22% VAT
            ['description' => 'Riga A', 'qty' => 1, 'unit_price_cents' => 10000, 'vat_rate' => 22],
            // 2 × €50.00 @ 10% VAT
            ['description' => 'Riga B', 'qty' => 2, 'unit_price_cents' => 5000, 'vat_rate' => 10],
        ],
    ]);

    // Subtotal: 10000 + 2*5000 = 20000
    expect($fattura->subtotal_cents)->toBe(20000);
    // VAT: round(10000 * 22%) + round(10000 * 10%) = 2200 + 1000 = 3200
    expect($fattura->vat_cents)->toBe(3200);
    // Total: 23200
    expect($fattura->total_cents)->toBe(23200);
});

it('handles zero-VAT exempt lines', function () {
    $contact = Contact::factory()->create();

    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [
            ['description' => 'Esente IVA', 'qty' => 1, 'unit_price_cents' => 50000, 'vat_rate' => 0],
        ],
    ]);

    expect($fattura->subtotal_cents)->toBe(50000);
    expect($fattura->vat_cents)->toBe(0);
    expect($fattura->total_cents)->toBe(50000);
});

it('produces a display number formatted as 0001/YYYY', function () {
    $contact = Contact::factory()->create();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'year' => 2026,
        'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 1000, 'vat_rate' => 22]],
    ]);

    expect($fattura->displayNumber())->toBe('0001/2026');
});
