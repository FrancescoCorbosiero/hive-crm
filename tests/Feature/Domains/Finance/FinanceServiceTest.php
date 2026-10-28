<?php

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

it('aggregates monthly income for a website', function () {
    $websiteId = 1;
    $month = Carbon::parse('2026-03-15');

    FinancialEntry::factory()->forWebsite($websiteId)->income(10000)->on('2026-03-05')->create();
    FinancialEntry::factory()->forWebsite($websiteId)->income(5000)->on('2026-03-25')->create();
    FinancialEntry::factory()->forWebsite($websiteId)->income(99999)->on('2026-02-28')->create(); // out of month
    FinancialEntry::factory()->forWebsite(2)->income(100000)->on('2026-03-10')->create();         // wrong website

    $total = app(FinanceService::class)->monthlyIncomeForWebsite($websiteId, $month);

    expect($total)->toBeInstanceOf(Money::class);
    expect($total->cents)->toBe(15000);
    expect($total->currency)->toBe('EUR');
});

it('returns zero Money when no entries match the website/month', function () {
    $total = app(FinanceService::class)->monthlyIncomeForWebsite(99, now());

    expect($total->cents)->toBe(0);
});

it('aggregates YTD totals across all sources', function () {
    FinancialEntry::factory()->income(50000)->on(now()->startOfYear()->addDays(2))->create();
    FinancialEntry::factory()->income(25000)->on(now()->startOfYear()->addMonths(2))->create();
    FinancialEntry::factory()->loss(10000)->on(now()->startOfYear()->addDays(5))->create();

    $income = app(FinanceService::class)->ytdTotal(FinancialEntryType::Income);
    $loss = app(FinanceService::class)->ytdTotal(FinancialEntryType::Loss);

    expect($income->cents)->toBe(75000);
    expect($loss->cents)->toBe(10000);
});

it('returns YTD income for a single website', function () {
    $websiteId = 7;

    FinancialEntry::factory()->forWebsite($websiteId)->income(8000)->on(now()->startOfYear()->addDays(1))->create();
    FinancialEntry::factory()->forWebsite($websiteId)->income(8000)->on(now()->startOfYear()->addMonth())->create();
    FinancialEntry::factory()->income(99999)->on(now())->create(); // unattributed

    $total = app(FinanceService::class)->ytdIncomeForWebsite($websiteId);

    expect($total->cents)->toBe(16000);
});

it('returns a 12-month income series indexed by YYYY-MM', function () {
    FinancialEntry::factory()->income(10000)->on(now()->startOfMonth())->create();
    FinancialEntry::factory()->income(20000)->on(now()->startOfMonth()->subMonth())->create();

    $series = app(FinanceService::class)->monthlyIncomeSeries(12);

    expect($series)->toHaveCount(12);
    expect($series->last()->cents)->toBe(10000);

    $secondToLast = $series->slice(-2, 1)->first();
    expect($secondToLast->cents)->toBe(20000);
});

it('builds the monthly income series from a single query', function () {
    FinancialEntry::factory()->income(10000)->on(now()->startOfMonth())->create();
    FinancialEntry::factory()->income(2500)->on(now()->endOfMonth())->create();
    FinancialEntry::factory()->income(20000)->on(now()->startOfMonth()->subMonths(11))->create();
    FinancialEntry::factory()->income(99900)->on(now()->startOfMonth()->subMonths(12))->create(); // outside
    FinancialEntry::factory()->loss(5000)->on(now()->startOfMonth())->create();

    DB::enableQueryLog();
    $series = app(FinanceService::class)->monthlyIncomeSeries(12);

    expect(DB::getQueryLog())->toHaveCount(1); // was one per month
    expect($series->first()->cents)->toBe(20000);
    expect($series->last()->cents)->toBe(12500);
    expect($series->sum(fn (Money $month) => $month->cents))->toBe(32500);
});

it('returns the recent entries as DTOs', function () {
    FinancialEntry::factory()->count(5)->create();

    $dtos = app(FinanceService::class)->recent(3);

    expect($dtos)->toHaveCount(3);
    expect($dtos->first()->amount)->toBeInstanceOf(Money::class);
});
