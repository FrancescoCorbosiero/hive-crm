<?php

declare(strict_types=1);

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages\CreateWebsite;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Livewire\Livewire;

/**
 * Cross-domain auto-population from "create Website" — the symmetric
 * counterpart to DomainNames\DomainRegistrationAutoPopulateTest.
 *
 * Triggering: WebsiteCreated event with a payment intent.
 * Listener: Finance\RecordLossFromWebsiteSetup creates a LOSS entry
 * tagged source_type=website + source_id + external_ref =
 * "website_setup:{id}" so duplicate dispatch is a no-op.
 */
it('mirrors the setup cost as a LOSS FinancialEntry tagged with the website source', function () {
    $website = Website::factory()->create();

    WebsiteCreated::dispatch($website->id, [
        'amount_cents' => 12_000,
        'currency' => 'EUR',
        'paid_at' => '2026-05-01',
        'method' => 'bank_transfer',
    ]);

    expect(FinancialEntry::count())->toBe(1);

    $entry = FinancialEntry::first();
    expect($entry->type)->toBe(FinancialEntryType::Loss);
    expect($entry->amount_cents)->toBe(12_000);
    expect($entry->category)->toBe('hosting');
    expect($entry->source_type)->toBe(FinancialEntrySource::Website->value);
    expect($entry->source_id)->toBe($website->id);
    expect($entry->external_ref)->toBe('website_setup:'.$website->id);
});

it('is idempotent — replaying WebsiteCreated does not double-create the loss entry', function () {
    $website = Website::factory()->create();

    $intent = ['amount_cents' => 12_000, 'currency' => 'EUR', 'paid_at' => '2026-05-01'];

    WebsiteCreated::dispatch($website->id, $intent);
    WebsiteCreated::dispatch($website->id, $intent);

    expect(FinancialEntry::count())->toBe(1);
});

it('skips the loss entry when no payment intent is provided', function () {
    $website = Website::factory()->create();

    WebsiteCreated::dispatch($website->id);

    expect(FinancialEntry::count())->toBe(0);
});

it('skips the loss entry when amount_cents is zero', function () {
    $website = Website::factory()->create();

    WebsiteCreated::dispatch($website->id, [
        'amount_cents' => 0,
        'currency' => 'EUR',
        'paid_at' => '2026-05-01',
    ]);

    expect(FinancialEntry::count())->toBe(0);
});

// End-to-end through Filament: validates that the form's transient
// (dehydrated=false) fields reach the listener via afterCreate().
it('wires the create form through to the Finance listener', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateWebsite::class)
        ->fillForm([
            'name' => 'Autopop Hosting',
            'url' => 'https://autopop-hosting.test',
            'status' => WebsiteStatus::Active->value,
            'renewal_period_months' => 12,
            'register_cost_enabled' => true,
            'setup_cost_cents' => '120.00',
            'setup_paid_at' => '2026-05-01',
            'setup_method' => PaymentMethod::Stripe->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $website = Website::query()->where('url', 'https://autopop-hosting.test')->firstOrFail();
    $entry = FinancialEntry::query()->where('external_ref', 'website_setup:'.$website->id)->firstOrFail();

    expect($entry->amount_cents)->toBe(12_000);
    expect($entry->type)->toBe(FinancialEntryType::Loss);
    expect($entry->source_id)->toBe($website->id);
});
