<?php

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

it('aggregates monthly income for a website', function () {
    $websiteId = 1;
    $month = Carbon::parse('2026-03-15');

    Transaction::factory()->forWebsite($websiteId)->income(10000)->on('2026-03-05')->create();
    Transaction::factory()->forWebsite($websiteId)->income(5000)->on('2026-03-25')->create();
    Transaction::factory()->forWebsite($websiteId)->income(99999)->on('2026-02-28')->create(); // out of month
    Transaction::factory()->forWebsite(2)->income(100000)->on('2026-03-10')->create();         // wrong website

    $total = app(FinanceService::class)->monthlyIncomeForWebsite($websiteId, $month);

    expect($total)->toBeInstanceOf(Money::class);
    expect($total->cents)->toBe(15000);
    expect($total->currency)->toBe('EUR');
});

it('returns zero Money when no transactions match the website/month', function () {
    $total = app(FinanceService::class)->monthlyIncomeForWebsite(99, now());

    expect($total->cents)->toBe(0);
});

it('aggregates YTD income across all sources', function () {
    Transaction::factory()->income(50000)->on(now()->startOfYear()->addDays(2))->create();
    Transaction::factory()->income(25000)->on(now()->startOfYear()->addMonths(2))->create();
    Transaction::factory()->expense(10000)->on(now()->startOfYear()->addDays(5))->create();

    $income = app(FinanceService::class)->ytdTotal(TransactionType::Income);
    $expense = app(FinanceService::class)->ytdTotal(TransactionType::Expense);

    expect($income->cents)->toBe(75000);
    expect($expense->cents)->toBe(10000);
});

it('returns YTD income for a single website', function () {
    $websiteId = 7;

    Transaction::factory()->forWebsite($websiteId)->income(8000)->on(now()->startOfYear()->addDays(1))->create();
    Transaction::factory()->forWebsite($websiteId)->income(8000)->on(now()->startOfYear()->addMonth())->create();
    Transaction::factory()->income(99999)->on(now())->create(); // unattributed

    $total = app(FinanceService::class)->ytdIncomeForWebsite($websiteId);

    expect($total->cents)->toBe(16000);
});

it('returns a 12-month income series indexed by YYYY-MM', function () {
    Transaction::factory()->income(10000)->on(now()->startOfMonth())->create();
    Transaction::factory()->income(20000)->on(now()->startOfMonth()->subMonth())->create();

    $series = app(FinanceService::class)->monthlyIncomeSeries(12);

    expect($series)->toHaveCount(12);
    expect($series->last()->cents)->toBe(10000);

    $secondToLast = $series->slice(-2, 1)->first();
    expect($secondToLast->cents)->toBe(20000);
});

it('returns the recent transactions as DTOs', function () {
    Transaction::factory()->count(5)->create();

    $dtos = app(FinanceService::class)->recent(3);

    expect($dtos)->toHaveCount(3);
    expect($dtos->first()->amount)->toBeInstanceOf(Money::class);
});
