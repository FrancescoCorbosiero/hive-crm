<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use App\Domains\Leads\Services\Public\LeadsService;

it('creates a draft fattura against the lead\'s converted contact', function () {
    $contact = Contact::factory()->create();
    $lead = Lead::factory()->create([
        'status' => LeadStatus::Won->value,
        'converted_contact_id' => $contact->id,
        'converted_at' => now(),
        'estimated_value_cents' => 250_000,
        'estimated_value_currency' => 'EUR',
    ]);

    $fatturaId = app(LeadsService::class)->issueInvoice($lead->id);

    $fattura = Fattura::query()->findOrFail($fatturaId);

    expect($fattura->client_contact_id)->toBe($contact->id);
    expect($fattura->total_cents)->toBeGreaterThan(0);
    expect((array) $fattura->lines)->toHaveCount(1);
    expect($fattura->lines[0]['unit_price_cents'])->toBe(250_000);
});

it('refuses to issue an invoice for an unconverted lead', function () {
    $lead = Lead::factory()->create([
        'converted_contact_id' => null,
    ]);

    app(LeadsService::class)->issueInvoice($lead->id);
})->throws(\DomainException::class);

it('defaults qty to 1 and uses lead name as line description', function () {
    $contact = Contact::factory()->create();
    $lead = Lead::factory()->create([
        'name' => 'Sito vetrina ACME',
        'converted_contact_id' => $contact->id,
        'estimated_value_cents' => 50_000,
    ]);

    $fatturaId = app(LeadsService::class)->issueInvoice($lead->id);
    $fattura = Fattura::query()->findOrFail($fatturaId);

    expect($fattura->lines[0]['qty'])->toBe(1);
    expect($fattura->lines[0]['description'])->toBe('Sito vetrina ACME');
});
