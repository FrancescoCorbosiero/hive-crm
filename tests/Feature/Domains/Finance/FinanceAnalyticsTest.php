<?php

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

it('breaks down income by category for a date range', function () {
    Transaction::factory()->income(10000)->on('2026-04-01')->create(['category' => 'website_subscription']);
    Transaction::factory()->income(5000)->on('2026-04-15')->create(['category' => 'website_subscription']);
    Transaction::factory()->income(20000)->on('2026-04-20')->create(['category' => 'consulting']);
    Transaction::factory()->income(99999)->on('2026-05-15')->create(['category' => 'consulting']); // out of range
    Transaction::factory()->expense(8000)->on('2026-04-10')->create(['category' => 'hosting']); // wrong type

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        TransactionType::Income,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($breakdown)->toHaveCount(2);
    expect($breakdown['consulting']->cents)->toBe(20000);
    expect($breakdown['website_subscription']->cents)->toBe(15000);
    // Sorted descending by amount.
    expect($breakdown->keys()->first())->toBe('consulting');
});

it('breaks down expense by category', function () {
    Transaction::factory()->expense(1500)->on('2026-04-05')->create(['category' => 'hosting']);
    Transaction::factory()->expense(20000)->on('2026-04-20')->create(['category' => 'software']);
    Transaction::factory()->expense(500)->on('2026-04-25')->create(['category' => null]);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        TransactionType::Expense,
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($breakdown['software']->cents)->toBe(20000);
    expect($breakdown['hosting']->cents)->toBe(1500);
    expect($breakdown['(other)']->cents)->toBe(500);
});

it('returns Money instances in the configured currency', function () {
    Transaction::factory()->income(1000)->on(now())->create(['category' => 'consulting']);

    $breakdown = app(FinanceService::class)->breakdownByCategory(
        TransactionType::Income,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($breakdown->first())->toBeInstanceOf(Money::class);
    expect($breakdown->first()->currency)->toBe('EUR');
});

it('aggregates income by website source', function () {
    Transaction::factory()->forWebsite(1)->income(8000)->on('2026-04-05')->create();
    Transaction::factory()->forWebsite(1)->income(4000)->on('2026-04-15')->create();
    Transaction::factory()->forWebsite(2)->income(12000)->on('2026-04-10')->create();
    Transaction::factory()->income(50000)->on('2026-04-20')->create();   // unattributed
    Transaction::factory()->forWebsite(1)->income(99999)->on('2026-05-10')->create(); // out of range

    $byWebsite = app(FinanceService::class)->incomeByWebsite(
        Carbon::parse('2026-04-01'),
        Carbon::parse('2026-04-30'),
    );

    expect($byWebsite)->toHaveCount(2);
    expect($byWebsite[1]->cents)->toBe(12000); // 8000 + 4000
    expect($byWebsite[2]->cents)->toBe(12000);
    // Sort order: ties keep DB order; just verify both present.
});

it('returns an empty collection when no transactions match', function () {
    $breakdown = app(FinanceService::class)->breakdownByCategory(
        TransactionType::Income,
        Carbon::parse('2030-01-01'),
        Carbon::parse('2030-12-31'),
    );

    expect($breakdown)->toBeEmpty();
});
