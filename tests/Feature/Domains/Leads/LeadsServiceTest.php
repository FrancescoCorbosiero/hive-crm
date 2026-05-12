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

it('returns pipeline value summed by stage with currency', function () {
    Lead::factory()->status(LeadStatus::New)->create(['estimated_value_cents' => 100_00]);
    Lead::factory()->status(LeadStatus::New)->create(['estimated_value_cents' => 250_00]);
    Lead::factory()->status(LeadStatus::Qualified)->create(['estimated_value_cents' => 1_000_00]);
    // Won is terminal and must be excluded.
    Lead::factory()->status(LeadStatus::Won)->create(['estimated_value_cents' => 9_999_00]);

    $stages = app(LeadsService::class)->pipelineValueByStage();

    expect($stages[LeadStatus::New->value]['count'])->toBe(2);
    expect($stages[LeadStatus::New->value]['cents'])->toBe(350_00);
    expect($stages[LeadStatus::New->value]['currency'])->toBe('EUR');
    expect($stages[LeadStatus::Qualified->value]['cents'])->toBe(1_000_00);
    expect($stages[LeadStatus::Contacted->value]['cents'])->toBe(0);
});
