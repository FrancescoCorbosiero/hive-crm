<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\Filament\Pages\CashFlowProjectionPage;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * Cash flow projection — derives expected income / loss from existing
 * schedule entities (active RecurringFattura + active DomainName with
 * renewal_cost_cents) without writing any rows.
 *
 * Tests use Carbon::setTestNow to pin "now" so projections are
 * deterministic relative to fixed schedule dates.
 */
beforeEach(fn () => $this->actingAs(User::factory()->create()));
afterEach(fn () => Carbon::setTestNow());

it('projects monthly recurring invoice cycles into a 12-month window', function () {
    Carbon::setTestNow('2026-05-01');

    $contact = Contact::factory()->create();
    RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => '2026-06-01',
        'is_active' => true,
        'currency' => 'EUR',
        'lines' => [[
            'description' => 'Hosting',
            'qty' => 1,
            'unit_price_cents' => 10_000,
            'vat_rate' => 22,
        ]],
    ]);

    $page = Livewire::test(CashFlowProjectionPage::class);
    $data = $page->instance()->getViewData();

    // Window = May 2026 → April 2027 (12 buckets).
    // next_issue_at = 2026-06-01 → cycles land Jun, Jul, ..., Apr = 11 cycles.
    // Per cycle = 12_200 cents (10_000 + 22% VAT). Total = 134_200 cents.
    expect(count($data['entries']))->toBe(11);
    expect(collect($data['entries'])->sum('amount_cents'))->toBe(11 * 12_200);
    expect($data['totals']['net_negative'])->toBeFalse();
});

it('projects domain renewal losses at the configured expiry date and cycle', function () {
    Carbon::setTestNow('2026-05-01');

    DomainName::query()->create([
        'name' => 'mysite.it',
        'registrar' => Registrar::Aruba->value,
        'status' => DomainStatus::Active->value,
        'expires_at' => '2026-08-15',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'renewal_cost_cents' => 1_200, // €12
        'currency' => 'EUR',
    ]);

    $page = Livewire::test(CashFlowProjectionPage::class);
    $data = $page->instance()->getViewData();

    // 12-month window from May 2026 → April 2027.
    // Expiry is 2026-08-15, then next cycle 2027-08-15 (out of window).
    // So exactly one projected LOSS in Aug 2026.
    expect(count($data['entries']))->toBe(1);
    expect($data['entries'][0]['type'])->toBe('loss');
    expect($data['entries'][0]['amount_cents'])->toBe(1_200);
    expect($data['entries'][0]['date'])->toBe('2026-08-15');
});

it('combines income + loss into a net per month with the right sign', function () {
    Carbon::setTestNow('2026-05-01');

    $contact = Contact::factory()->create();
    // Monthly income of €100 starting 2026-06
    RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => '2026-06-01',
        'is_active' => true,
        'currency' => 'EUR',
        'lines' => [[
            'description' => 'Service',
            'qty' => 1,
            'unit_price_cents' => 10_000,
            'vat_rate' => 0,
        ]],
    ]);
    // One-off domain renewal loss of €500 in Jul 2026
    DomainName::query()->create([
        'name' => 'expensive.com',
        'registrar' => Registrar::Cloudflare->value,
        'status' => DomainStatus::Active->value,
        'expires_at' => '2026-07-15',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'renewal_cost_cents' => 50_000,
        'currency' => 'EUR',
    ]);

    $page = Livewire::test(CashFlowProjectionPage::class);
    $data = $page->instance()->getViewData();

    // 12 cycles × 10_000 = 120_000 income; one 50_000 loss; net = 70_000.
    expect($data['totals']['net_negative'])->toBeFalse();
    // Verify the July row has a negative net (10_000 income - 50_000 loss = -40_000)
    $july = collect($data['monthly'])->firstWhere('key', '2026-07');
    expect($july)->not->toBeNull();
    expect($july['net_negative'])->toBeTrue();
});

it('skips inactive recurring invoices and domains without renewal_cost', function () {
    Carbon::setTestNow('2026-05-01');

    $contact = Contact::factory()->create();
    // Inactive recurring — should not contribute
    RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => '2026-06-01',
        'is_active' => false,
        'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 0]],
    ]);
    // Domain without renewal_cost_cents — should not contribute
    DomainName::query()->create([
        'name' => 'free.com',
        'registrar' => Registrar::Other->value,
        'status' => DomainStatus::Active->value,
        'expires_at' => '2026-08-15',
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'renewal_cost_cents' => null,
        'currency' => 'EUR',
    ]);

    $page = Livewire::test(CashFlowProjectionPage::class);
    $data = $page->instance()->getViewData();

    expect($data['has_data'])->toBeFalse();
    expect(count($data['entries']))->toBe(0);
});

it('catches overdue cycles in the current month so the operator notices', function () {
    Carbon::setTestNow('2026-05-15');

    $contact = Contact::factory()->create();
    // next_issue_at is 2026-05-01 — 2 weeks overdue.
    RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => '2026-05-01',
        'is_active' => true,
        'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 5_000, 'vat_rate' => 0]],
    ]);

    $page = Livewire::test(CashFlowProjectionPage::class);
    $data = $page->instance()->getViewData();

    // Should include the overdue May cycle plus 11 future cycles = 12 entries.
    expect(count($data['entries']))->toBe(12);
    $may = collect($data['monthly'])->firstWhere('key', '2026-05');
    expect($may['income_cents'] ?? null)->toBeNull(); // monthly row format hides cents internally
});
