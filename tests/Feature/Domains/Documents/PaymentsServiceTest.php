<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Events\FatturaPaid;
use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\Payment;
use App\Domains\Documents\Services\Public\PaymentsService;
use Illuminate\Support\Facades\Event;

it('records a payment, updates paid_amount_cents, and dispatches PaymentRecorded', function () {
    Event::fake();
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'paid_amount_cents' => 0,
        'payment_status' => PaymentStatus::Unpaid->value,
    ]);

    app(PaymentsService::class)->record($fattura->id, [
        'amount_cents' => 50_000,
        'method' => PaymentMethod::BankTransfer->value,
    ]);

    $fresh = $fattura->fresh();
    expect($fresh->paid_amount_cents)->toBe(50_000);
    expect($fresh->payment_status)->toBe(PaymentStatus::PartiallyPaid);
    Event::assertDispatched(PaymentRecorded::class);
    Event::assertNotDispatched(FatturaPaid::class);
});

it('moves status to Paid when total is fully covered, and dispatches FatturaPaid once', function () {
    Event::fake();
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'paid_amount_cents' => 0,
        'payment_status' => PaymentStatus::Unpaid->value,
    ]);

    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 100_000]);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    Event::assertDispatchedTimes(FatturaPaid::class, 1);
});

it('does NOT re-dispatch FatturaPaid on a payment after the fattura is already Paid', function () {
    Event::fake();
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'paid_amount_cents' => 100_000,
        'payment_status' => PaymentStatus::Paid->value,
    ]);
    Payment::factory()->create(['fattura_id' => $fattura->id, 'amount_cents' => 100_000]);

    // Adding an over-payment of 5000 (e.g. tip) should NOT re-fire FatturaPaid.
    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 5_000]);

    Event::assertNotDispatched(FatturaPaid::class);
});

it('walks status backwards when a payment is deleted', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'payment_status' => PaymentStatus::Unpaid->value,
    ]);
    $payment = app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 100_000]);
    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    app(PaymentsService::class)->delete($payment->id);

    $fresh = $fattura->fresh();
    expect($fresh->payment_status)->toBe(PaymentStatus::Unpaid);
    expect($fresh->paid_amount_cents)->toBe(0);
});

it('marks a fattura Overdue when due_date has passed and nothing has been paid', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'paid_amount_cents' => 0,
        'due_date' => now()->subDays(5),
        'payment_status' => PaymentStatus::Unpaid->value,
    ]);

    // Record + delete a zero-cent payment to force a recompute.
    app(PaymentsService::class)->recomputeFromPayments($fattura);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Overdue);
});

it('refuses to recompute (is sticky) once a fattura is Cancelled', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'payment_status' => PaymentStatus::Cancelled->value,
    ]);

    Payment::factory()->create([
        'fattura_id' => $fattura->id,
        'amount_cents' => 100_000,
    ]);
    app(PaymentsService::class)->recomputeFromPayments($fattura);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Cancelled);
});

it('returns the outstanding amount as a Money instance', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'total_cents' => 100_000,
        'paid_amount_cents' => 30_000,
    ]);

    $outstanding = app(PaymentsService::class)->outstandingAmount($fattura->id);

    expect($outstanding->cents)->toBe(70_000);
    expect($outstanding->currency)->toBe('EUR');
});
