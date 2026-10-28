<?php

use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\Models\RecurringExpense;
use App\Domains\Websites\Models\Website;
use App\Shared\CashFlow\Occurrences;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

it('steps each renewal by its period without overflowing into the next month', function () {
    $domain = new DomainName(['renewal_period_months' => 12]);
    $website = new Website(['renewal_period_months' => 1]);
    $expense = new RecurringExpense(['frequency' => 'monthly']);

    expect($domain->renewedExpiry(Carbon::parse('2028-02-29'))->toDateString())->toBe('2029-02-28');
    expect($website->renewalDateAfter(Carbon::parse('2026-01-31'))->toDateString())->toBe('2026-02-28');
    expect($expense->dueDateAfter(Carbon::parse('2026-01-31'))->toDateString())->toBe('2026-02-28');
});

it('treats a missing renewal period as monthly rather than looping forever', function () {
    $domain = new DomainName(['renewal_period_months' => null]);

    expect($domain->renewedExpiry(Carbon::parse('2026-03-15'))->toDateString())->toBe('2026-04-15');
});

it('walks occurrences from the first pending date through the end of the window', function () {
    $dates = Occurrences::until(
        CarbonImmutable::parse('2026-01-10'),
        fn (CarbonImmutable $date) => $date->addMonths(2),
        CarbonImmutable::parse('2026-07-10'),
    );

    expect(array_map(fn ($date) => $date->toDateString(), iterator_to_array($dates)))
        ->toBe(['2026-01-10', '2026-03-10', '2026-05-10', '2026-07-10']);
});

it('refuses a schedule step that does not move forward', function () {
    $dates = Occurrences::until(
        CarbonImmutable::parse('2026-01-10'),
        fn (CarbonImmutable $date) => $date,
        CarbonImmutable::parse('2026-12-31'),
    );

    expect(fn () => iterator_to_array($dates))->toThrow(LogicException::class);
});
