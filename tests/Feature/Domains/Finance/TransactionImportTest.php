<?php

use App\Domains\Finance\Filament\Imports\TransactionImporter;

function txCol(string $name)
{
    return collect(TransactionImporter::getColumns())->firstWhere(fn ($c) => $c->getName() === $name);
}

it('converts a major-unit decimal amount to absolute integer cents', function () {
    expect(txCol('amount_cents')->castState('125.50', []))->toBe(12550);
    expect(txCol('amount_cents')->castState('-1234.56', []))->toBe(123456);
    expect(txCol('amount_cents')->castState('1 234.56', []))->toBe(123456);
    expect(txCol('amount_cents')->castState(null, []))->toBeNull();
});

it('parses the type column case-insensitively', function () {
    expect(txCol('type')->castState('income', []))->toBe('income');
    expect(txCol('type')->castState('Expense', []))->toBe('expense');
    expect(txCol('type')->castState(' INCOME ', []))->toBe('income');
});

it('defaults type to expense for unrecognized values (caller must validate)', function () {
    expect(txCol('type')->castState('garbage', []))->toBe('expense');
    expect(txCol('type')->castState(null, []))->toBe('expense');
});

it('defaults currency to EUR when blank', function () {
    expect(txCol('currency')->castState(null, []))->toBe('EUR');
    expect(txCol('currency')->castState('', []))->toBe('EUR');
    expect(txCol('currency')->castState('USD', []))->toBe('USD');
});
