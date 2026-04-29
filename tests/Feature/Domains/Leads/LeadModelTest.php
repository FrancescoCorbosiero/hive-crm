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
