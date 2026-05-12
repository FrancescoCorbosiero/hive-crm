<?php

use App\Domains\Finance\Filament\Imports\FinancialEntryImporter;

function entryCol(string $name)
{
    return collect(FinancialEntryImporter::getColumns())->firstWhere(fn ($c) => $c->getName() === $name);
}

it('converts a major-unit decimal amount to absolute integer cents', function () {
    expect(entryCol('amount_cents')->castState('125.50', []))->toBe(12550);
    expect(entryCol('amount_cents')->castState('-1234.56', []))->toBe(123456);
    expect(entryCol('amount_cents')->castState('1 234.56', []))->toBe(123456);
    expect(entryCol('amount_cents')->castState(null, []))->toBeNull();
});

it('parses the type column case-insensitively', function () {
    expect(entryCol('type')->castState('income', []))->toBe('income');
    expect(entryCol('type')->castState('Loss', []))->toBe('loss');
    expect(entryCol('type')->castState(' INCOME ', []))->toBe('income');
});

it('defaults type to loss for unrecognized values (caller must validate)', function () {
    expect(entryCol('type')->castState('garbage', []))->toBe('loss');
    expect(entryCol('type')->castState(null, []))->toBe('loss');
});

it('defaults currency to EUR when blank', function () {
    expect(entryCol('currency')->castState(null, []))->toBe('EUR');
    expect(entryCol('currency')->castState('', []))->toBe('EUR');
    expect(entryCol('currency')->castState('USD', []))->toBe('USD');
});
