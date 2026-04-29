<?php

use App\Domains\Finance\Enums\TransactionSource;
use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Shared\ValueObjects\Money;

it('round-trips Money through amount_cents + currency columns', function () {
    $tx = Transaction::factory()->create();
    $tx->setMoney(Money::fromMajor('1234.56', 'EUR'));
    $tx->save();

    $fresh = $tx->fresh();

    expect($fresh->money)->toBeInstanceOf(Money::class);
    expect($fresh->money->cents)->toBe(123456);
    expect($fresh->money->currency)->toBe('EUR');
});

it('casts the type column to the TransactionType enum', function () {
    $tx = Transaction::factory()->income()->create();

    expect($tx->fresh()->type)->toBe(TransactionType::Income);
});

it('stores polymorphic source as a (source_type, source_id) pair', function () {
    $tx = Transaction::factory()->forWebsite(42)->create();

    expect($tx->source_type)->toBe('website');
    expect($tx->source_id)->toBe(42);
});

it('filters transactions by polymorphic source via the forSource scope', function () {
    Transaction::factory()->forWebsite(1)->count(3)->create();
    Transaction::factory()->forWebsite(2)->count(1)->create();
    Transaction::factory()->create(); // unattributed

    expect(Transaction::forSource(TransactionSource::Website, 1)->count())->toBe(3);
    expect(Transaction::forSource(TransactionSource::Website, 2)->count())->toBe(1);
});

it('filters transactions by occurredBetween scope', function () {
    Transaction::factory()->on('2026-01-15')->create();
    Transaction::factory()->on('2026-02-15')->create();
    Transaction::factory()->on('2026-03-15')->create();

    $count = Transaction::occurredBetween(
        \Carbon\Carbon::parse('2026-02-01'),
        \Carbon\Carbon::parse('2026-02-28'),
    )->count();

    expect($count)->toBe(1);
});

it('separates incomes from expenses via dedicated scopes', function () {
    Transaction::factory()->income()->count(3)->create();
    Transaction::factory()->expense()->count(2)->create();

    expect(Transaction::incomes()->count())->toBe(3);
    expect(Transaction::expenses()->count())->toBe(2);
});
