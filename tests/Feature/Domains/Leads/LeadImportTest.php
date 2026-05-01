<?php

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Filament\Imports\LeadImporter;

function leadCol(string $name)
{
    return collect(LeadImporter::getColumns())->firstWhere(fn ($c) => $c->getName() === $name);
}

it('converts CSV decimal estimated values into integer cents', function () {
    expect(leadCol('estimated_value_cents')->castState('1500.00', []))->toBe(150000);
    expect(leadCol('estimated_value_cents')->castState('1500,50', []))->toBe(150050);
    expect(leadCol('estimated_value_cents')->castState(null, []))->toBeNull();
});

it('maps source strings to LeadSource enum values', function () {
    expect(leadCol('source')->castState('referral', []))->toBe('referral');
    expect(leadCol('source')->castState('Cold_Outreach', []))->toBe(LeadSource::ColdOutreach->value);
    expect(leadCol('source')->castState('garbage', []))->toBe(LeadSource::Other->value);
    expect(leadCol('source')->castState(null, []))->toBeNull();
});

it('defaults status to new when blank', function () {
    expect(leadCol('status')->castState(null, []))->toBe(LeadStatus::New->value);
    expect(leadCol('status')->castState('qualified', []))->toBe(LeadStatus::Qualified->value);
});
