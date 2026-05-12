<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;

it('mirrors a payment as an Income FinancialEntry in the Finance domain', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
    ]);

    $payment = app(PaymentsService::class)->record($fattura->id, [
        'amount_cents' => 100_000,
        'paid_at' => '2026-04-15',
    ]);

    expect(FinancialEntry::count())->toBe(1);
    $entry = FinancialEntry::first();
    expect($entry->type)->toBe(FinancialEntryType::Income);
    expect($entry->amount_cents)->toBe(100_000);
    expect($entry->source_type)->toBe('fattura');
    expect($entry->source_id)->toBe($fattura->id);
    expect($entry->contact_id)->toBe($contact->id);

    // The payment row remembers which entry it spawned.
    expect($payment->fresh()->financial_entry_id)->toBe($entry->id);
});

it('is idempotent — replaying PaymentRecorded with the same payment id does not double-create', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create(['client_contact_id' => $contact->id, 'total_cents' => 100_000]);
    $payment = app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 100_000]);

    \App\Domains\Documents\Events\PaymentRecorded::dispatch($payment->id);
    \App\Domains\Documents\Events\PaymentRecorded::dispatch($payment->id);

    expect(FinancialEntry::count())->toBe(1);
});
