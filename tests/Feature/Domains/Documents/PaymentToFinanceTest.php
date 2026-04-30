<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;

it('mirrors a payment as an Income transaction in the Finance domain', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
    ]);

    $payment = app(PaymentsService::class)->record($fattura->id, [
        'amount_cents' => 100_000,
        'paid_at' => '2026-04-15',
    ]);

    expect(Transaction::count())->toBe(1);
    $tx = Transaction::first();
    expect($tx->type)->toBe(TransactionType::Income);
    expect($tx->amount_cents)->toBe(100_000);
    expect($tx->source_type)->toBe('fattura');
    expect($tx->source_id)->toBe($fattura->id);
    expect($tx->contact_id)->toBe($contact->id);

    // The payment row remembers which transaction it spawned.
    expect($payment->fresh()->transaction_id)->toBe($tx->id);
});

it('is idempotent — replaying PaymentRecorded with the same payment id does not double-create', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create(['client_contact_id' => $contact->id, 'total_cents' => 100_000]);
    $payment = app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 100_000]);

    \App\Domains\Documents\Events\PaymentRecorded::dispatch($payment->id);
    \App\Domains\Documents\Events\PaymentRecorded::dispatch($payment->id);

    expect(Transaction::count())->toBe(1);
});
