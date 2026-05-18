<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Filament\Resources\RecurringFatturaResource\Pages\CreateRecurringFattura;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\RecurringFattura;
use App\Models\User;
use Livewire\Livewire;

/**
 * Cross-domain auto-population from "create Recurring Invoice".
 *
 * The "issue the first cycle now" toggle delegates to
 * RecurringFatturaService::issue() — the same code path as the
 * manual "Issue now" row action and the daily scheduler. So the
 * created fattura also fires FatturaIssued, chaining naturally into
 * any future cross-domain listeners on that event without extra
 * wiring here.
 */
it('issues the first invoice immediately when the toggle is on', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateRecurringFattura::class)
        ->fillForm([
            'name' => 'Hosting plan',
            'client_contact_id' => $contact->id,
            'frequency' => RecurringFrequency::Monthly->value,
            'next_issue_at' => '2026-06-01',
            'is_active' => true,
            'lines' => [[
                'description' => 'Hosting',
                'qty' => 1,
                'unit_price_cents' => '15.00',
                'vat_rate' => 22,
            ]],
            'issue_first_cycle_now' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Fattura::count())->toBe(1);

    $schedule = RecurringFattura::query()->latest('id')->firstOrFail();
    expect($schedule->last_issued_at)->not->toBeNull();
    // next_issue_at advanced by one period from the original.
    expect($schedule->next_issue_at->toDateString())->not->toBe('2026-06-01');
});

it('does not issue an invoice when the toggle is off', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    Livewire::test(CreateRecurringFattura::class)
        ->fillForm([
            'name' => 'Hosting plan',
            'client_contact_id' => $contact->id,
            'frequency' => RecurringFrequency::Monthly->value,
            'next_issue_at' => '2026-06-01',
            'is_active' => true,
            'lines' => [[
                'description' => 'Hosting',
                'qty' => 1,
                'unit_price_cents' => '15.00',
                'vat_rate' => 22,
            ]],
            'issue_first_cycle_now' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Fattura::count())->toBe(0);

    $schedule = RecurringFattura::query()->latest('id')->firstOrFail();
    expect($schedule->last_issued_at)->toBeNull();
});
