<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Quotations\Services\Public\QuotationsService;

function quotationPayload(int $clientId, ?int $year = null, array $overrides = []): array
{
    return array_merge([
        'name' => 'Restyling sito demo',
        'client_contact_id' => $clientId,
        'year' => $year ?? now()->year,
        'lines' => [
            ['description' => 'Restyling', 'qty' => 1, 'unit_price_cents' => 200_000, 'vat_rate' => 22],
            ['description' => 'Hosting 1y', 'qty' => 1, 'unit_price_cents' => 12_000, 'vat_rate' => 22],
        ],
    ], $overrides);
}

it('creates a quotation in Draft and allocates a sequential number', function () {
    $contact = Contact::factory()->create();
    $svc = app(QuotationsService::class);

    $a = $svc->create(quotationPayload($contact->id));
    $b = $svc->create(quotationPayload($contact->id));

    expect($a->status)->toBe(QuotationStatus::Draft);
    expect([$a->number, $b->number])->toBe([1, 2]);
});

it('computes subtotal + VAT + total from lines', function () {
    $contact = Contact::factory()->create();
    $q = app(QuotationsService::class)->create(quotationPayload($contact->id));

    expect($q->subtotal_cents)->toBe(212_000);
    expect($q->vat_cents)->toBe(46_640);  // 200000*22% + 12000*22%
    expect($q->total_cents)->toBe(258_640);
});

it('produces a display number formatted PREV-YYYY-0001', function () {
    $contact = Contact::factory()->create();
    $q = app(QuotationsService::class)->create(quotationPayload($contact->id, 2027));

    expect($q->displayNumber())->toBe('PREV-2027-0001');
});

it('keeps quotation numbering separate from fattura numbering', function () {
    $contact = Contact::factory()->create();

    // Pre-existing fattura #5 in same year
    Fattura::factory()->create(['client_contact_id' => $contact->id, 'year' => 2026, 'number' => 5]);

    $q = app(QuotationsService::class)->create(quotationPayload($contact->id, 2026));

    // Quotation gets its own counter starting at 1.
    expect($q->number)->toBe(1);
});

it('markSent moves Draft → Sent', function () {
    $contact = Contact::factory()->create();
    $q = app(QuotationsService::class)->create(quotationPayload($contact->id));

    app(QuotationsService::class)->markSent($q->id);

    expect($q->fresh()->status)->toBe(QuotationStatus::Sent);
});

it('markSent is a no-op once past Draft', function () {
    $contact = Contact::factory()->create();
    $q = Quotation::factory()->create([
        'client_contact_id' => $contact->id,
        'status' => QuotationStatus::Sent->value,
    ]);

    app(QuotationsService::class)->markSent($q->id);

    expect($q->fresh()->status)->toBe(QuotationStatus::Sent);
});

it('accept creates a draft Fattura with the same lines + client and links it back', function () {
    $contact = Contact::factory()->create();
    $svc = app(QuotationsService::class);
    $q = $svc->create(quotationPayload($contact->id));

    $fatturaId = $svc->accept($q->id);

    $fattura = Fattura::find($fatturaId);
    expect($fattura)->not->toBeNull();
    expect($fattura->client_contact_id)->toBe($contact->id);
    expect((array) $fattura->lines)->toBe((array) $q->fresh()->lines);
    expect($fattura->subtotal_cents)->toBe($q->subtotal_cents);
    expect($fattura->total_cents)->toBe($q->total_cents);
    expect($fattura->payment_status)->toBe(PaymentStatus::Unpaid);

    // Quotation is now Accepted with the linkback set.
    $fresh = $q->fresh();
    expect($fresh->status)->toBe(QuotationStatus::Accepted);
    expect($fresh->fattura_id)->toBe($fatturaId);
});

it('refuses to accept a quotation already in a final state', function () {
    $contact = Contact::factory()->create();
    $q = Quotation::factory()->create([
        'client_contact_id' => $contact->id,
        'status' => QuotationStatus::Accepted->value,
    ]);

    expect(fn () => app(QuotationsService::class)->accept($q->id))
        ->toThrow(DomainException::class);
});

it('reject moves a quotation to Rejected', function () {
    $contact = Contact::factory()->create();
    $q = app(QuotationsService::class)->create(quotationPayload($contact->id));

    app(QuotationsService::class)->reject($q->id);

    expect($q->fresh()->status)->toBe(QuotationStatus::Rejected);
});

it('refuses to reject a quotation already in a final state', function () {
    $contact = Contact::factory()->create();
    $q = Quotation::factory()->create([
        'client_contact_id' => $contact->id,
        'status' => QuotationStatus::Rejected->value,
    ]);

    expect(fn () => app(QuotationsService::class)->reject($q->id))
        ->toThrow(DomainException::class);
});
