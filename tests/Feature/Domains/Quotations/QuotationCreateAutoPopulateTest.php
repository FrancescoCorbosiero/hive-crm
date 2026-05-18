<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages\CreateQuotation;
use App\Domains\Quotations\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Cross-domain auto-population from "create Quotation". Two opt-in
 * toggles on the create form remove the most common post-create
 * clicks:
 *   - auto_render_pdf → QuotationsService::render → Document row + document_id
 *   - mark_as_sent    → QuotationsService::markSent → status flip Draft → Sent
 *
 * Neither sends any email — Sent is purely a status flag.
 */
it('auto-renders the PDF when the toggle is on', function () {
    Storage::fake();
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'name' => 'PDF-on',
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'valid_until' => '2026-06-01',
            'lines' => [[
                'description' => 'Consulting',
                'qty' => 1,
                'unit_price_cents' => '100.00',
                'vat_rate' => 22,
                'cadence' => 'una_tantum',
            ]],
            'auto_render_pdf' => true,
            'mark_as_sent' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quotation = Quotation::query()->latest('id')->firstOrFail();
    expect($quotation->document_id)->not->toBeNull();
    expect($quotation->status)->toBe(QuotationStatus::Draft);
});

it('flips the status to Sent when "mark as sent" is on', function () {
    Storage::fake();
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'name' => 'Sent-on',
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'valid_until' => '2026-06-01',
            'lines' => [[
                'description' => 'Consulting',
                'qty' => 1,
                'unit_price_cents' => '100.00',
                'vat_rate' => 22,
                'cadence' => 'una_tantum',
            ]],
            'auto_render_pdf' => false,
            'mark_as_sent' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quotation = Quotation::query()->latest('id')->firstOrFail();
    expect($quotation->status)->toBe(QuotationStatus::Sent);
    expect($quotation->document_id)->toBeNull();
});

it('skips both side-effects when both toggles are off', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'name' => 'Defaults-off',
            'client_contact_id' => $contact->id,
            'issued_at' => '2026-05-01',
            'valid_until' => '2026-06-01',
            'lines' => [[
                'description' => 'Consulting',
                'qty' => 1,
                'unit_price_cents' => '100.00',
                'vat_rate' => 22,
                'cadence' => 'una_tantum',
            ]],
            'auto_render_pdf' => false,
            'mark_as_sent' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quotation = Quotation::query()->latest('id')->firstOrFail();
    expect($quotation->status)->toBe(QuotationStatus::Draft);
    expect($quotation->document_id)->toBeNull();
});
