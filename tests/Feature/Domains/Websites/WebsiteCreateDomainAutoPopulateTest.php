<?php

declare(strict_types=1);

use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages\CreateWebsite;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Livewire\Livewire;

/**
 * Symmetric counterpart to DomainNames\DomainRegistrationAutoPopulateTest:
 * when a Website is created with a domainIntent, the matching
 * DomainName is spawned and back-linked via the scalar website_id FK.
 *
 * Idempotent in two ways:
 *   - existing DomainName with same host → re-link, no duplicate
 *   - UNIQUE(name) DB constraint → re-dispatch can't create twice
 */
it('spawns a DomainName from the website URL and back-links it', function () {
    $website = Website::factory()->create(['url' => 'https://newshop.io']);

    WebsiteCreated::dispatch($website->id, null, [
        'registrar' => Registrar::Cloudflare->value,
        'registered_at' => '2026-05-01',
        'renewal_period_months' => 12,
    ]);

    expect(DomainName::count())->toBe(1);

    $domain = DomainName::first();
    expect($domain->name)->toBe('newshop.io');
    expect($domain->registrar)->toBe(Registrar::Cloudflare);
    expect($domain->website_id)->toBe($website->id);
});

it('normalises https://www.example.com/path → example.com', function () {
    $website = Website::factory()->create(['url' => 'https://www.studio-rossi.it/about']);

    WebsiteCreated::dispatch($website->id, null, [
        'registrar' => Registrar::Aruba->value,
    ]);

    expect(DomainName::query()->where('name', 'studio-rossi.it')->exists())->toBeTrue();
});

it('re-links an existing DomainName instead of creating a duplicate', function () {
    $orphan = DomainName::query()->create([
        'name' => 'reuse.com',
        'registrar' => Registrar::Other->value,
        'status' => 'active',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
    ]);

    $website = Website::factory()->create(['url' => 'https://reuse.com']);

    WebsiteCreated::dispatch($website->id, null, [
        'registrar' => Registrar::Cloudflare->value,
    ]);

    expect(DomainName::count())->toBe(1);
    expect($orphan->fresh()->website_id)->toBe($website->id);
});

it('does not clobber an existing link when the host already points at another website', function () {
    $otherWebsite = Website::factory()->create();
    $linked = DomainName::query()->create([
        'name' => 'locked.io',
        'registrar' => Registrar::Other->value,
        'status' => 'active',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
        'website_id' => $otherWebsite->id,
    ]);

    $newWebsite = Website::factory()->create(['url' => 'https://locked.io']);

    WebsiteCreated::dispatch($newWebsite->id, null, [
        'registrar' => Registrar::Aruba->value,
    ]);

    expect(DomainName::count())->toBe(1);
    expect($linked->fresh()->website_id)->toBe($otherWebsite->id);
});

it('is a no-op when no domain intent is provided', function () {
    $website = Website::factory()->create();

    WebsiteCreated::dispatch($website->id);

    expect(DomainName::count())->toBe(0);
});

it('skips when the registrar value is invalid (defensive)', function () {
    $website = Website::factory()->create(['url' => 'https://safe.test']);

    WebsiteCreated::dispatch($website->id, null, [
        'registrar' => 'nonexistent-registrar',
    ]);

    expect(DomainName::count())->toBe(0);
});

it('wires the create form through both Finance and DomainNames listeners', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateWebsite::class)
        ->fillForm([
            'name' => 'Both Toggles',
            'url' => 'https://both-toggles.dev',
            'status' => WebsiteStatus::Active->value,
            'renewal_period_months' => 12,
            'register_cost_enabled' => true,
            'setup_cost_cents' => '50.00',
            'setup_paid_at' => '2026-05-01',
            'setup_method' => 'bank_transfer',
            'register_domain_enabled' => true,
            'domain_registrar' => Registrar::Spaceship->value,
            'domain_registered_at' => '2026-05-01',
            'domain_renewal_period_months' => 12,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $website = Website::query()->where('url', 'https://both-toggles.dev')->firstOrFail();
    $domain = DomainName::query()->where('name', 'both-toggles.dev')->firstOrFail();

    expect($domain->website_id)->toBe($website->id);
    expect($domain->registrar)->toBe(Registrar::Spaceship);
});
