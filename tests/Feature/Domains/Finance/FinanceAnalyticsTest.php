<?php

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

it('breaks down income by category for a date range', function () {
    FinancialEntry::factory()->income(10000)->on('2026-04-01')->create(['category' => 'website_subscription']);
    FinancialEntry::factory()->income(5000)->on('2026-04-15')->create(['category' => 'website_subscription']);
    FinancialEntry::factory()->income(20000)->on('2026-04-20')->create(['category' => 'consulting']);
    FinancialEntry::factory()->income(99999)->on('2026-05-15')->create(['category' => 'consulting']); // out of range
    FinancialEntry::factory()->loss(8000)->on('2026-04-10')->create(['category' => 'hosting']); // wrong type

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Income,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($breakdown)->toHaveCount(2);
    expect($breakdown['consulting']->cents)->toBe(20000);
    expect($breakdown['website_subscription']->cents)->toBe(15000);
    // Sorted descending by amount.
    expect($breakdown->keys()->first())->toBe('consulting');
});

it('breaks down loss by category', function () {
    FinancialEntry::factory()->loss(1500)->on('2026-04-05')->create(['category' => 'hosting']);
    FinancialEntry::factory()->loss(20000)->on('2026-04-20')->create(['category' => 'software']);
    FinancialEntry::factory()->loss(500)->on('2026-04-25')->create(['category' => null]);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Loss,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($breakdown['software']->cents)->toBe(20000);
    expect($breakdown['hosting']->cents)->toBe(1500);
    expect($breakdown['(other)']->cents)->toBe(500);
});

it('returns Money instances in the configured currency', function () {
    FinancialEntry::factory()->income(1000)->on(now())->create(['category' => 'consulting']);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Income,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($breakdown->first())->toBeInstanceOf(Money::class);
    expect($breakdown->first()->currency)->toBe('EUR');
});

it('aggregates income by website source', function () {
    FinancialEntry::factory()->forWebsite(1)->income(8000)->on('2026-04-05')->create();
    FinancialEntry::factory()->forWebsite(1)->income(4000)->on('2026-04-15')->create();
    FinancialEntry::factory()->forWebsite(2)->income(12000)->on('2026-04-10')->create();
    FinancialEntry::factory()->income(50000)->on('2026-04-20')->create();   // unattributed
    FinancialEntry::factory()->forWebsite(1)->income(99999)->on('2026-05-10')->create(); // out of range

    $byWebsite = app(FinanceService::class)->incomeByWebsite(
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($byWebsite)->toHaveCount(2);
    expect($byWebsite[1]->cents)->toBe(12000); // 8000 + 4000
    expect($byWebsite[2]->cents)->toBe(12000);
    // Sort order: ties keep DB order; just verify both present.
});

it('returns an empty collection when no entries match', function () {
    $breakdown = app(FinanceService::class)->breakdownByCategory(
        FinancialEntryType::Income,
        Carbon::parse('2030-01-01'),
        Carbon::parse('2030-12-31'),
    );

    expect($breakdown)->toBeEmpty();
});
