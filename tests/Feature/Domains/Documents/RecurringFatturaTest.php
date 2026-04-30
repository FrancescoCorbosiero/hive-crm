<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use Carbon\Carbon;

it('creates a recurring schedule that is active by default', function () {
    $contact = Contact::factory()->create();
    $rec = app(RecurringFatturaService::class)->create([
        'name' => 'Hosting mensile Bellavista',
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'lines' => [['description' => 'Hosting', 'qty' => 1, 'unit_price_cents' => 8000, 'vat_rate' => 22]],
        'day_of_month' => 5,
        'next_issue_at' => now()->addMonth()->day(5),
    ]);

    expect($rec->is_active)->toBeTrue();
});

it('issues a Fattura with the recurring lines and advances next_issue_at', function () {
    $contact = Contact::factory()->create();
    $rec = RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => Carbon::parse('2026-04-05'),
        'day_of_month' => 5,
        'lines' => [
            ['description' => 'Hosting', 'qty' => 1, 'unit_price_cents' => 8000, 'vat_rate' => 22],
        ],
    ]);

    $fattura = app(RecurringFatturaService::class)->issue($rec->id);

    expect($fattura)->toBeInstanceOf(Fattura::class);
    expect($fattura->client_contact_id)->toBe($contact->id);
    expect($fattura->subtotal_cents)->toBe(8000);
    expect($fattura->vat_cents)->toBe(1760);
    expect($fattura->total_cents)->toBe(9760);

    $fresh = $rec->fresh();
    expect($fresh->next_issue_at->toDateString())->toBe('2026-05-05');
    expect($fresh->last_issued_at)->not->toBeNull();
});

it('clamps the day_of_month when advancing into a shorter month', function () {
    $contact = Contact::factory()->create();
    $rec = RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'next_issue_at' => Carbon::parse('2026-01-31'),
        'day_of_month' => 31,
    ]);

    app(RecurringFatturaService::class)->issue($rec->id);

    // Feb has 28 days in 2026 — clamp to the 28th.
    expect($rec->fresh()->next_issue_at->toDateString())->toBe('2026-02-28');
});

it('advances by 3 months for quarterly schedules', function () {
    $contact = Contact::factory()->create();
    $rec = RecurringFattura::factory()->create([
        'client_contact_id' => $contact->id,
        'frequency' => RecurringFrequency::Quarterly->value,
        'next_issue_at' => Carbon::parse('2026-01-15'),
        'day_of_month' => null,
    ]);

    app(RecurringFatturaService::class)->issue($rec->id);

    expect($rec->fresh()->next_issue_at->toDateString())->toBe('2026-04-15');
});

it('pauses and resumes a schedule', function () {
    $contact = Contact::factory()->create();
    $rec = RecurringFattura::factory()->create(['client_contact_id' => $contact->id]);

    app(RecurringFatturaService::class)->pause($rec->id);
    expect($rec->fresh()->is_active)->toBeFalse();

    app(RecurringFatturaService::class)->resume($rec->id);
    expect($rec->fresh()->is_active)->toBeTrue();
});

it('issues every active schedule due today via fatture:issue-recurring', function () {
    $contact = Contact::factory()->create();
    RecurringFattura::factory()->dueToday()->count(3)->create(['client_contact_id' => $contact->id]);
    RecurringFattura::factory()->paused()->dueToday()->create(['client_contact_id' => $contact->id]);
    RecurringFattura::factory()->create(['client_contact_id' => $contact->id]); // not due

    $this->artisan('fatture:issue-recurring')->assertExitCode(0);

    expect(Fattura::count())->toBe(3);
});

it('skips paused schedules in the daily issuance command', function () {
    $contact = Contact::factory()->create();
    RecurringFattura::factory()->paused()->dueToday()->count(3)->create([
        'client_contact_id' => $contact->id,
    ]);

    $this->artisan('fatture:issue-recurring')->assertExitCode(0);

    expect(Fattura::count())->toBe(0);
});
