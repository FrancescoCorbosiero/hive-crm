<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Events\FatturaIssued;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages\CreateFattura;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\Payment;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Cross-domain auto-population from "create Fattura".
 *
 * The event fires for every Fattura issued (manual or recurring) so
 * future listeners (Mail, Calendar) can hook in without modifying
 * the create page. The two opt-in toggles on the create form
 * (auto_render_pdf, mark_as_paid) chain into existing flows:
 *   - render PDF → Document row + document_id back-link
 *   - mark as paid → PaymentsService::record → PaymentRecorded
 *     → existing Finance listener creates the Income entry
 */
it('fires FatturaIssued when FatturaService creates a fattura', function () {
    Event::fake([FatturaIssued::class]);

    $contact = Contact::factory()->create();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'issued_at' => '2026-05-01',
        'lines' => [[
            'description' => 'Domain consulting',
            'qty' => 1,
            'unit_price_cents' => 10_000,
            'vat_rate' => 22,
        ]],
    ]);

    Event::assertDispatched(FatturaIssued::class, fn (FatturaIssued $e) => $e->fatturaId === $fattura->id);
});

it('chains create → payment → income entry when "mark as paid" is on', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateFattura::class)
        ->fillForm([
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'payment_status' => PaymentStatus::Unpaid->value,
            'lines' => [[
                'description' => 'Domain consulting',
                'qty' => 1,
                'unit_price_cents' => '100.00',
                'vat_rate' => 22,
            ]],
            'auto_render_pdf' => false,
            'mark_as_paid' => true,
            'mark_as_paid_method' => PaymentMethod::Stripe->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $fattura = Fattura::query()->latest('id')->firstOrFail();

    expect(Payment::query()->where('fattura_id', $fattura->id)->count())->toBe(1);

    $payment = Payment::query()->where('fattura_id', $fattura->id)->first();
    expect($payment->amount_cents)->toBe(12_200);
    expect($payment->method)->toBe(PaymentMethod::Stripe);

    expect(FinancialEntry::query()->where('source_type', 'fattura')->where('source_id', $fattura->id)->count())->toBe(1);
    $entry = FinancialEntry::query()->where('source_id', $fattura->id)->first();
    expect($entry->type)->toBe(FinancialEntryType::Income);
    expect($entry->amount_cents)->toBe(12_200);

    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('skips the payment chain when "mark as paid" is off', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateFattura::class)
        ->fillForm([
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'payment_status' => PaymentStatus::Unpaid->value,
            'lines' => [[
                'description' => 'Domain consulting',
                'qty' => 1,
                'unit_price_cents' => '50.00',
                'vat_rate' => 0,
            ]],
            'auto_render_pdf' => false,
            'mark_as_paid' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Payment::count())->toBe(0);
    expect(FinancialEntry::count())->toBe(0);
});

it('auto-renders the PDF when the toggle is on', function () {
    Storage::fake();
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateFattura::class)
        ->fillForm([
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'payment_status' => PaymentStatus::Unpaid->value,
            'lines' => [[
                'description' => 'Domain consulting',
                'qty' => 1,
                'unit_price_cents' => '50.00',
                'vat_rate' => 0,
            ]],
            'auto_render_pdf' => true,
            'mark_as_paid' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $fattura = Fattura::query()->latest('id')->firstOrFail();
    expect($fattura->document_id)->not->toBeNull();
});
