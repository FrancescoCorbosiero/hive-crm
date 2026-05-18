<?php

declare(strict_types=1);

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Events\LeadConverted;
use App\Domains\Leads\Models\Lead;
use App\Domains\Leads\Services\Public\LeadsService;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use Illuminate\Support\Facades\Event;

/**
 * Step 3 of the cross-domain auto-populate suite: converting a Lead
 * optionally spawns a draft Quotation tied to the new Contact, with
 * the lead's name and estimated value pre-filled as a placeholder
 * line. The operator finishes it in the QuotationResource.
 *
 * Reuses the existing LeadsService::convert() conductor — the
 * quotation creation is a third optional step alongside the
 * Contact (always) and Website (optional). Everything stays inside
 * one transaction so a Quotation failure rolls back the whole
 * conversion.
 */
it('spawns a draft Quotation when quotationAttributes is passed', function () {
    Event::fake([LeadConverted::class]);

    $lead = Lead::factory()->create([
        'name' => 'Pasticceria Conti',
        'estimated_value_cents' => 250_000,
        'estimated_value_currency' => 'EUR',
    ]);

    $result = app(LeadsService::class)->convert($lead->id, null, []);

    expect($result['quotation'])->not->toBeNull();
    expect($result['quotation']->name)->toBe('Pasticceria Conti');
    expect($result['quotation']->status)->toBe(QuotationStatus::Draft->value);
    expect($result['quotation']->leadId)->toBe($lead->id);
    expect($result['quotation']->clientContactId)->toBe($result['contact']->id);

    $model = Quotation::query()->find($result['quotation']->id);
    expect($model->total_cents)->toBe(305_000); // 250_000 + 22% VAT

    Event::assertDispatched(
        LeadConverted::class,
        fn (LeadConverted $e) => $e->quotationId === $result['quotation']->id,
    );
});

it('does not spawn a Quotation when the toggle is off (null)', function () {
    Event::fake([LeadConverted::class]);

    $lead = Lead::factory()->create();

    $result = app(LeadsService::class)->convert($lead->id, null, null);

    expect($result['quotation'])->toBeNull();
    expect(Quotation::count())->toBe(0);

    Event::assertDispatched(
        LeadConverted::class,
        fn (LeadConverted $e) => $e->quotationId === null,
    );
});

it('handles a lead with no estimated value (placeholder line at 0 cents)', function () {
    $lead = Lead::factory()->create([
        'estimated_value_cents' => null,
    ]);

    $result = app(LeadsService::class)->convert($lead->id, null, []);

    $model = Quotation::query()->find($result['quotation']->id);
    expect($model->subtotal_cents)->toBe(0);
    expect($model->total_cents)->toBe(0);
});

it('allows quotation attribute overrides', function () {
    $lead = Lead::factory()->create(['name' => 'Whatever']);

    $result = app(LeadsService::class)->convert($lead->id, null, [
        'name' => 'Custom Proposal Name',
        'currency' => 'USD',
    ]);

    $model = Quotation::query()->find($result['quotation']->id);
    expect($model->name)->toBe('Custom Proposal Name');
    expect($model->currency)->toBe('USD');
    // The placeholder line still comes from the lead defaults.
    expect((array) $model->lines)->toHaveCount(1);
});

it('rolls back the Quotation if the Lead has already been converted', function () {
    $lead = Lead::factory()->converted(42)->create();

    expect(fn () => app(LeadsService::class)->convert($lead->id, null, []))
        ->toThrow(DomainException::class);

    expect(Quotation::count())->toBe(0);
    expect($lead->fresh()->status)->not->toBe(LeadStatus::New); // already converted
});
