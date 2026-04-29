<?php

use App\Domains\Leads\DTOs\LeadDTO;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use App\Domains\Leads\Services\Public\LeadsService;

it('returns a LeadDTO when finding an existing lead', function () {
    $lead = Lead::factory()->create();

    $dto = app(LeadsService::class)->find($lead->id);

    expect($dto)->toBeInstanceOf(LeadDTO::class);
    expect($dto->id)->toBe($lead->id);
});

it('returns null for a missing lead', function () {
    expect(app(LeadsService::class)->find(99999))->toBeNull();
});

it('returns counts per pipeline stage', function () {
    Lead::factory()->status(LeadStatus::New)->count(3)->create();
    Lead::factory()->status(LeadStatus::Contacted)->count(2)->create();
    Lead::factory()->status(LeadStatus::Won)->create();

    $counts = app(LeadsService::class)->pipelineCounts();

    expect($counts[LeadStatus::New->value])->toBe(3);
    expect($counts[LeadStatus::Contacted->value])->toBe(2);
    expect($counts[LeadStatus::Qualified->value])->toBe(0);
    // Won is terminal — never appears in the open-pipeline counts.
    expect($counts->has(LeadStatus::Won->value))->toBeFalse();
});
