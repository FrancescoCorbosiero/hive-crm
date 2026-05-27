<?php

declare(strict_types=1);

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Finance\Services\Public\FinanceService;
use Carbon\Carbon;

it('defaults financial entries to taxable when none is provided', function () {
    $entry = FinancialEntry::factory()->income(5000)->create();

    expect($entry->is_taxable)->toBeTrue();
});

it('persists the nonTaxable factory state', function () {
    $entry = FinancialEntry::factory()->income(5000)->nonTaxable()->create();

    expect($entry->is_taxable)->toBeFalse();
    expect($entry->category)->toBe('external');
});

it('excludes non-taxable entries from ytdTotal by default', function () {
    FinancialEntry::factory()->income(10000)->on(now()->startOfYear()->addDay())->create();
    FinancialEntry::factory()->income(50000)->on(now()->startOfYear()->addDays(2))->nonTaxable()->create();

    $ytd = app(FinanceService::class)->ytdTotal(FinancialEntryType::Income);

    expect($ytd->cents)->toBe(10000);
});

it('includes non-taxable entries in ytdTotal when explicitly requested', function () {
    FinancialEntry::factory()->income(10000)->on(now()->startOfYear()->addDay())->create();
    FinancialEntry::factory()->income(50000)->on(now()->startOfYear()->addDays(2))->nonTaxable()->create();

    $ytd = app(FinanceService::class)->ytdTotal(FinancialEntryType::Income, includeNonTaxable: true);

    expect($ytd->cents)->toBe(60000);
});

it('excludes non-taxable entries from monthlyIncomeSeries by default', function () {
    FinancialEntry::factory()->income(10000)->on(now()->startOfMonth())->create();
    FinancialEntry::factory()->income(99999)->on(now()->startOfMonth())->nonTaxable()->create();

    $series = app(FinanceService::class)->monthlyIncomeSeries(12);

    expect($series->last()->cents)->toBe(10000);
});

it('excludes non-taxable entries from breakdownByCategory by default', function () {
    FinancialEntry::factory()->income(10000)->on('2026-04-01')->create(['category' => 'consulting']);
    FinancialEntry::factory()->income(50000)->on('2026-04-05')->nonTaxable()->create(['category' => 'external']);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Income,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($breakdown)->toHaveCount(1);
    expect($breakdown['consulting']->cents)->toBe(10000);
    expect($breakdown->has('external'))->toBeFalse();
});

it('includes the external category when toggled on', function () {
    FinancialEntry::factory()->income(10000)->on('2026-04-01')->create(['category' => 'consulting']);
    FinancialEntry::factory()->income(50000)->on('2026-04-05')->nonTaxable()->create(['category' => 'external']);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Income,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
        includeNonTaxable: true,
    );

    expect($breakdown)->toHaveCount(2);
    expect($breakdown['external']->cents)->toBe(50000);
});

it('refuses to generate a Fattura from a non-taxable income entry', function () {
    $entry = FinancialEntry::factory()
        ->income(10000)
        ->nonTaxable()
        ->create(['contact_id' => \App\Domains\Contacts\Models\Contact::factory()->create()->id]);

    expect(fn () => app(FinanceService::class)->generateFattura($entry->id))
        ->toThrow(DomainException::class);
});

it('persists is_taxable through FinanceService::record', function () {
    $id = app(FinanceService::class)->recordIncome([
        'amount_cents' => 5000,
        'occurred_at' => now(),
        'description' => 'A donation',
        'is_taxable' => false,
    ]);

    $entry = FinancialEntry::query()->findOrFail($id);

    expect($entry->is_taxable)->toBeFalse();
});
