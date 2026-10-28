<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\Payment;

it('sweeps past-due unpaid and partially paid fatture to Overdue, and leaves the rest', function () {
    $contact = Contact::factory()->create();
    $make = fn (array $attributes) => Fattura::factory()->totalling(10_000)->create(array_merge([
        'client_contact_id' => $contact->id,
        'payment_status' => PaymentStatus::Unpaid->value,
    ], $attributes));

    $unpaidLate = $make(['due_date' => now()->subDays(2)]);
    $partialLate = $make(['due_date' => now()->subDays(2), 'payment_status' => PaymentStatus::PartiallyPaid->value, 'paid_amount_cents' => 4_000]);
    Payment::factory()->create(['fattura_id' => $partialLate->id, 'amount_cents' => 4_000]);
    $notYetDue = $make(['due_date' => now()->addDays(5)]);
    $cancelled = $make(['due_date' => now()->subDays(2), 'payment_status' => PaymentStatus::Cancelled->value]);

    $this->artisan('fatture:recompute-overdue')->assertSuccessful();

    expect($unpaidLate->fresh()->payment_status)->toBe(PaymentStatus::Overdue);
    expect($partialLate->fresh()->payment_status)->toBe(PaymentStatus::Overdue);
    expect($notYetDue->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
    expect($cancelled->fresh()->payment_status)->toBe(PaymentStatus::Cancelled);
});
