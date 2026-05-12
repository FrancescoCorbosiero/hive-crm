<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Finance\Services\Public\FinanceService;

it('generates a Fattura from an INCOME entry with no VAT', function () {
    $contact = Contact::factory()->create();
    $entry = FinancialEntry::factory()->income(50_000)->create([
        'contact_id' => $contact->id,
        'description' => 'Consulting hours April',
        'occurred_at' => '2026-04-10',
    ]);

    $fattura = app(FinanceService::class)->generateFattura($entry->id);

    expect($fattura)->toBeInstanceOf(Fattura::class);
    expect($fattura->total_cents)->toBe(50_000);
    expect($fattura->subtotal_cents)->toBe(50_000);
    expect($fattura->vat_cents)->toBe(0);
    expect($fattura->client_contact_id)->toBe($contact->id);

    // The entry is now linked to the Fattura so analytics can see it.
    $entry->refresh();
    expect($entry->source_type)->toBe(FinancialEntrySource::Fattura->value);
    expect($entry->source_id)->toBe($fattura->id);
});

it('back-derives unit_price_cents so the Fattura total matches the entry amount when VAT is applied', function () {
    $contact = Contact::factory()->create();
    // 122 = 100 + 22% VAT. Entry stores the gross cash amount; the
    // service splits it back to net + VAT inside the Fattura.
    $entry = FinancialEntry::factory()->income(12200)->create([
        'contact_id' => $contact->id,
    ]);

    $fattura = app(FinanceService::class)->generateFattura($entry->id, ['vat_rate' => 22]);

    expect($fattura->subtotal_cents)->toBe(10000);
    expect($fattura->vat_cents)->toBe(2200);
    expect($fattura->total_cents)->toBe(12200);
});

it('refuses to generate a Fattura from a LOSS entry', function () {
    $entry = FinancialEntry::factory()->loss(5000)->create([
        'contact_id' => Contact::factory()->create()->id,
    ]);

    expect(fn () => app(FinanceService::class)->generateFattura($entry->id))
        ->toThrow(\DomainException::class);
});

it('requires either entry.contact_id or an override to generate a Fattura', function () {
    $entry = FinancialEntry::factory()->income(10000)->create(['contact_id' => null]);

    expect(fn () => app(FinanceService::class)->generateFattura($entry->id))
        ->toThrow(\DomainException::class);

    // With an override the call succeeds.
    $contact = Contact::factory()->create();
    $fattura = app(FinanceService::class)->generateFattura($entry->id, [
        'client_contact_id' => $contact->id,
    ]);
    expect($fattura->client_contact_id)->toBe($contact->id);
});

it('records an INCOME entry and a LOSS entry through the service', function () {
    $finance = app(FinanceService::class);

    $incomeId = $finance->recordIncome([
        'amount_cents' => 12345,
        'occurred_at' => '2026-04-01',
        'description' => 'Direct income',
    ]);

    $lossId = $finance->recordLoss([
        'amount_cents' => 678,
        'occurred_at' => '2026-04-02',
        'description' => 'Direct loss',
    ]);

    expect(FinancialEntry::find($incomeId)->type)->toBe(FinancialEntryType::Income);
    expect(FinancialEntry::find($lossId)->type)->toBe(FinancialEntryType::Loss);
});
