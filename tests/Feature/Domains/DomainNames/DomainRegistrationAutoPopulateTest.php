<?php

declare(strict_types=1);

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages\CreateDomainName;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Livewire\Livewire;

/**
 * Cross-domain auto-population from "create DomainName".
 *
 * Mirrors the proven RecordIncomeFromPayment pattern but for the
 * outbound (cost) side — registering a domain optionally spawns:
 *   - a LOSS FinancialEntry (Finance domain)
 *   - a sibling Website with back-link (Websites domain)
 *
 * Each listener is independently idempotent.
 */
function makeDomain(array $overrides = []): DomainName
{
    return DomainName::query()->create(array_merge([
        'name' => 'example.com',
        'registrar' => Registrar::Aruba->value,
        'status' => DomainStatus::Active->value,
        'registered_at' => '2026-05-01',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
    ], $overrides));
}

it('mirrors the registration cost as a LOSS FinancialEntry in the ledger', function () {
    $domain = makeDomain();

    DomainRegistered::dispatch($domain->id, [
        'amount_cents' => 1_200,
        'currency' => 'EUR',
        'paid_at' => '2026-05-01',
        'method' => 'bank_transfer',
    ]);

    expect(FinancialEntry::count())->toBe(1);
    $entry = FinancialEntry::first();
    expect($entry->type)->toBe(FinancialEntryType::Loss);
    expect($entry->amount_cents)->toBe(1_200);
    expect($entry->category)->toBe('domains');
    expect($entry->external_ref)->toBe('domain_registration:'.$domain->id);
});

it('is idempotent — replaying DomainRegistered does not double-create the loss entry', function () {
    $domain = makeDomain();

    $intent = ['amount_cents' => 1_200, 'currency' => 'EUR', 'paid_at' => '2026-05-01'];

    DomainRegistered::dispatch($domain->id, $intent);
    DomainRegistered::dispatch($domain->id, $intent);

    expect(FinancialEntry::count())->toBe(1);
});

it('skips the loss entry when no payment intent is provided', function () {
    $domain = makeDomain();

    DomainRegistered::dispatch($domain->id);

    expect(FinancialEntry::count())->toBe(0);
});

it('spawns a sibling Website and back-links domain.website_id', function () {
    $domain = makeDomain(['name' => 'newshop.com']);

    DomainRegistered::dispatch($domain->id, null, [
        'url' => 'https://newshop.com',
        'name' => 'New Shop',
    ]);

    expect(Website::count())->toBe(1);
    $website = Website::first();
    expect($website->url)->toBe('https://newshop.com');

    expect($domain->fresh()->website_id)->toBe($website->id);
});

it('is idempotent — does not spawn a second Website when one already exists by host', function () {
    $domain = makeDomain(['name' => 'shop.it']);
    Website::factory()->create(['url' => 'https://shop.it']);

    DomainRegistered::dispatch($domain->id, null, [
        'url' => 'https://shop.it',
        'name' => 'Existing Shop',
    ]);

    expect(Website::count())->toBe(1);
    expect($domain->fresh()->website_id)->toBe(Website::first()->id);
});

it('also creates a Finance LOSS entry for the new website when cost data is in the intent', function () {
    $domain = makeDomain(['name' => 'sito-nuovo.it']);

    DomainRegistered::dispatch($domain->id, null, [
        'url' => 'https://sito-nuovo.it',
        'name' => 'Sito Nuovo',
        'cost_amount_cents' => 4_500,
        'cost_currency' => 'EUR',
        'cost_paid_at' => '2026-05-01',
        'cost_method' => 'stripe',
    ]);

    $website = Website::query()->where('url', 'https://sito-nuovo.it')->firstOrFail();

    expect(FinancialEntry::count())->toBe(1);
    $entry = FinancialEntry::first();
    expect($entry->type)->toBe(FinancialEntryType::Loss);
    expect($entry->amount_cents)->toBe(4_500);
    expect($entry->category)->toBe('hosting');
    expect($entry->external_ref)->toBe('website_setup:'.$website->id);
});

it('does not create a LOSS entry when re-linking an existing website (no duplicate cost)', function () {
    $existing = Website::factory()->create(['url' => 'https://alreadyhere.com']);
    $domain = makeDomain(['name' => 'alreadyhere.com']);

    DomainRegistered::dispatch($domain->id, null, [
        'url' => 'https://alreadyhere.com',
        'cost_amount_cents' => 9_999,
    ]);

    expect(Website::count())->toBe(1);
    expect($domain->fresh()->website_id)->toBe($existing->id);
    // No new LOSS — the website wasn't actually created here.
    expect(FinancialEntry::count())->toBe(0);
});

it('is idempotent — does not spawn a Website when the domain is already linked', function () {
    $existing = Website::factory()->create(['url' => 'https://already-linked.com']);
    $domain = makeDomain([
        'name' => 'other.com',
        'website_id' => $existing->id,
    ]);

    DomainRegistered::dispatch($domain->id, null, [
        'url' => 'https://other.com',
        'name' => 'Should Not Be Created',
    ]);

    expect(Website::count())->toBe(1);
    expect($domain->fresh()->website_id)->toBe($existing->id);
});

// End-to-end through Filament: validates that the form's transient
// (dehydrated=false) fields survive into $this->data and reach the
// listeners via afterCreate(). The unit tests above exercise the
// listeners in isolation; this one proves the wiring.
it('wires the create form through to both auto-spawn listeners', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateDomainName::class)
        ->fillForm([
            'name' => 'autopop.io',
            'registrar' => Registrar::Cloudflare->value,
            'status' => DomainStatus::Active->value,
            'registered_at' => '2026-05-01',
            'renewal_period_months' => 12,
            'register_payment_enabled' => true,
            'registration_cost_cents' => '8.50',
            'registration_paid_at' => '2026-05-01',
            'registration_method' => 'bank_transfer',
            'create_website_enabled' => true,
            'new_website_url' => 'https://autopop.io',
            'new_website_name' => 'Autopop',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $domain = DomainName::query()->where('name', 'autopop.io')->firstOrFail();
    $website = Website::query()->where('url', 'https://autopop.io')->firstOrFail();
    $entry = FinancialEntry::query()->where('external_ref', 'domain_registration:'.$domain->id)->firstOrFail();

    expect($domain->website_id)->toBe($website->id);
    expect($entry->amount_cents)->toBe(850);
    expect($entry->type)->toBe(FinancialEntryType::Loss);
});
