<?php

use App\Shared\ValueObjects\Money;

it('stores integer cents and the currency code', function () {
    $money = new Money(1250, 'EUR');

    expect($money->cents)->toBe(1250);
    expect($money->currency)->toBe('EUR');
});

it('builds from a major-unit decimal without floating-point drift', function () {
    $money = Money::fromMajor('1234.56');

    expect($money->cents)->toBe(123456);
});

it('builds from a major-unit string with comma decimal separator', function () {
    expect(Money::fromMajor('1,99')->cents)->toBe(199);
});

it('rejects a malformed major amount', function () {
    Money::fromMajor('not-a-number');
})->throws(InvalidArgumentException::class);

it('rejects a non-3-letter currency code', function () {
    new Money(100, 'EUROS');
})->throws(InvalidArgumentException::class);

it('adds two amounts with the same currency', function () {
    $sum = (new Money(100))->add(new Money(250));

    expect($sum->cents)->toBe(350);
    expect($sum->currency)->toBe('EUR');
});

it('refuses to add across currencies', function () {
    (new Money(100, 'EUR'))->add(new Money(100, 'USD'));
})->throws(InvalidArgumentException::class);

it('round-trips a major amount through cents and back', function () {
    $original = '987.65';
    $money = Money::fromMajor($original);

    expect($money->cents)->toBe(98765);
    expect($money->toMajor())->toBe(987.65);
});
