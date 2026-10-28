<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages\EditFattura;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Models\User;
use Livewire\Livewire;

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

it('re-derives stored totals when lines are edited through the Filament edit page', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [['description' => 'Sito', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 22]],
    ]);
    expect($fattura->total_cents)->toBe(12_200);

    Livewire::test(EditFattura::class, ['record' => $fattura->id])
        ->fillForm([
            'lines' => [
                ['description' => 'Sito', 'qty' => 2, 'unit_price_cents' => '100.00', 'vat_rate' => 22],
                ['description' => 'Hosting', 'qty' => 1, 'unit_price_cents' => '50.00', 'vat_rate' => 0],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $fattura->fresh();
    expect($fresh->subtotal_cents)->toBe(25_000);
    expect($fresh->vat_cents)->toBe(4_400);
    expect($fresh->total_cents)->toBe(29_400);
});

it('keeps totals derived from lines on any Eloquent update', function () {
    $fattura = Fattura::factory()->create();

    $fattura->update(['lines' => [
        ['description' => 'A', 'qty' => 3, 'unit_price_cents' => 1_000, 'vat_rate' => 22],
    ]]);

    expect($fattura->fresh()->only(['subtotal_cents', 'vat_cents', 'total_cents']))
        ->toBe(['subtotal_cents' => 3_000, 'vat_cents' => 660, 'total_cents' => 3_660]);
});

it('computes VAT on the per-rate taxable total, the way the SdI validates it', function () {
    $contact = Contact::factory()->create();

    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => array_fill(0, 10, ['description' => 'Micro', 'qty' => 1, 'unit_price_cents' => 10, 'vat_rate' => 22]),
    ]);

    expect($fattura->subtotal_cents)->toBe(100);
    expect($fattura->vat_cents)->toBe(22);
    expect($fattura->total_cents)->toBe(122);
});

it('re-derives the payment status when an edit moves the total', function () {
    $contact = Contact::factory()->create();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [['description' => 'Sito', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 0]],
    ]);
    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 10_000]);
    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    $fattura->fresh()->update(['lines' => [
        ['description' => 'Sito + extra', 'qty' => 1, 'unit_price_cents' => 15_000, 'vat_rate' => 0],
    ]]);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::PartiallyPaid);
});

it('lifts an Overdue status when the due date is moved into the future', function () {
    $contact = Contact::factory()->create();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'due_date' => now()->subDays(10),
        'lines' => [['description' => 'Sito', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 0]],
    ]);
    app(PaymentsService::class)->recomputeFromPayments($fattura);
    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Overdue);

    $fattura->fresh()->update(['due_date' => now()->addDays(30)]);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});
