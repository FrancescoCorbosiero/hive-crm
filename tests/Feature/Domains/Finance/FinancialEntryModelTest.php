<?php

use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Shared\ValueObjects\Money;

it('round-trips Money through amount_cents + currency columns', function () {
    $entry = FinancialEntry::factory()->create();
    $entry->setMoney(Money::fromMajor('1234.56', 'EUR'));
    $entry->save();

    $fresh = $entry->fresh();

    expect($fresh->money)->toBeInstanceOf(Money::class);
    expect($fresh->money->cents)->toBe(123456);
    expect($fresh->money->currency)->toBe('EUR');
});

it('casts the type column to the FinancialEntryType enum', function () {
    $entry = FinancialEntry::factory()->income()->create();

    expect($entry->fresh()->type)->toBe(FinancialEntryType::Income);
});

it('stores polymorphic source as a (source_type, source_id) pair', function () {
    $entry = FinancialEntry::factory()->forWebsite(42)->create();

    expect($entry->source_type)->toBe('website');
    expect($entry->source_id)->toBe(42);
});

it('filters entries by polymorphic source via the forSource scope', function () {
    FinancialEntry::factory()->forWebsite(1)->count(3)->create();
    FinancialEntry::factory()->forWebsite(2)->count(1)->create();
    FinancialEntry::factory()->create(); // unattributed

    expect(FinancialEntry::forSource(FinancialEntrySource::Website, 1)->count())->toBe(3);
    expect(FinancialEntry::forSource(FinancialEntrySource::Website, 2)->count())->toBe(1);
});

it('filters entries by occurredBetween scope', function () {
    FinancialEntry::factory()->on('2026-01-15')->create();
    FinancialEntry::factory()->on('2026-02-15')->create();
    FinancialEntry::factory()->on('2026-03-15')->create();

    $count = FinancialEntry::occurredBetween(
        \Carbon\Carbon::parse('2026-02-01'),
        \Carbon\Carbon::parse('2026-02-28'),
    )->count();

    expect($count)->toBe(1);
});

it('separates incomes from losses via dedicated scopes', function () {
    FinancialEntry::factory()->income()->count(3)->create();
    FinancialEntry::factory()->loss()->count(2)->create();

    expect(FinancialEntry::incomes()->count())->toBe(3);
    expect(FinancialEntry::losses()->count())->toBe(2);
});
