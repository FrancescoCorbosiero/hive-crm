<?php

use App\Shared\Filament\MoneyInput;

it('formats integer cents as a 2-decimal major unit string', function () {
    expect(MoneyInput::centsToMajor(12500))->toBe('125.00');
    expect(MoneyInput::centsToMajor(199))->toBe('1.99');
    expect(MoneyInput::centsToMajor(0))->toBe('0.00');
    expect(MoneyInput::centsToMajor(null))->toBeNull();
});

it('parses major-unit decimals back into integer cents', function () {
    expect(MoneyInput::majorToCents('125.00'))->toBe(12500);
    expect(MoneyInput::majorToCents('1.99'))->toBe(199);
    expect(MoneyInput::majorToCents('0'))->toBe(0);
    expect(MoneyInput::majorToCents(''))->toBeNull();
    expect(MoneyInput::majorToCents(null))->toBeNull();
});

it('accepts comma decimal separators', function () {
    expect(MoneyInput::majorToCents('125,50'))->toBe(12550);
});

it('round-trips losslessly for typical amounts', function () {
    foreach ([100, 19999, 1, 100000000] as $cents) {
        $formatted = MoneyInput::centsToMajor($cents);
        expect(MoneyInput::majorToCents($formatted))->toBe($cents);
    }
});
