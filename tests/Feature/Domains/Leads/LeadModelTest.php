<?php

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use App\Shared\ValueObjects\Money;

it('creates a lead with default open status', function () {
    $lead = Lead::factory()->create();

    expect($lead->status)->toBe(LeadStatus::New);
});

it('casts source to the LeadSource enum', function () {
    $lead = Lead::factory()->create(['source' => LeadSource::Referral->value]);

    expect($lead->fresh()->source)->toBe(LeadSource::Referral);
});

it('returns Money for the estimated_value accessor when cents are set', function () {
    $lead = Lead::factory()->create(['estimated_value_cents' => 12345]);

    expect($lead->estimated_value)->toBeInstanceOf(Money::class);
    expect($lead->estimated_value->cents)->toBe(12345);
    expect($lead->estimated_value->currency)->toBe('EUR');
});

it('returns null for the estimated_value accessor when cents is null', function () {
    $lead = Lead::factory()->create(['estimated_value_cents' => null]);

    expect($lead->estimated_value)->toBeNull();
});

it('writes Money back to both columns via setEstimatedValue', function () {
    $lead = Lead::factory()->create();
    $lead->setEstimatedValue(Money::fromMajor('199.99', 'EUR'))->save();

    expect($lead->fresh()->estimated_value_cents)->toBe(19999);
});

it('filters open leads via the open scope', function () {
    Lead::factory()->status(LeadStatus::New)->create();
    Lead::factory()->status(LeadStatus::Qualified)->create();
    Lead::factory()->status(LeadStatus::Won)->create();
    Lead::factory()->status(LeadStatus::Lost)->create();

    expect(Lead::open()->count())->toBe(2);
});

it('reports converted state correctly', function () {
    $lead = Lead::factory()->converted(99)->create();

    expect($lead->isConverted())->toBeTrue();
});

it('auto-derives company_name from a corporate email domain when blank', function () {
    $lead = Lead::factory()->create([
        'company_name' => null,
        'email' => 'info@studio-bianchi.it',
    ]);

    expect($lead->fresh()->company_name)->toBe('Studio Bianchi');
});

it('does not derive company_name from free email providers', function () {
    $lead = Lead::factory()->create([
        'company_name' => null,
        'email' => 'andrea@gmail.com',
    ]);

    expect($lead->fresh()->company_name)->toBeNull();
});

it('does not overwrite an explicit company_name', function () {
    $lead = Lead::factory()->create([
        'company_name' => 'My Company',
        'email' => 'info@example.com',
    ]);

    expect($lead->fresh()->company_name)->toBe('My Company');
});

it('stamps last_contacted_at when status advances out of new', function () {
    $lead = Lead::factory()->status(LeadStatus::New)->create(['last_contacted_at' => null]);
    expect($lead->last_contacted_at)->toBeNull();

    $lead->status = LeadStatus::Contacted;
    $lead->save();

    expect($lead->fresh()->last_contacted_at)->not->toBeNull();
});

it('finds stale open leads via the stale scope', function () {
    Lead::factory()->status(LeadStatus::Contacted)->create([
        'last_contacted_at' => now()->subDays(30),
    ]);
    Lead::factory()->status(LeadStatus::Contacted)->create([
        'last_contacted_at' => now()->subDays(2),
    ]);
    Lead::factory()->status(LeadStatus::Won)->create([
        'last_contacted_at' => now()->subDays(60),
    ]);

    expect(Lead::stale(14)->count())->toBe(1);
});
