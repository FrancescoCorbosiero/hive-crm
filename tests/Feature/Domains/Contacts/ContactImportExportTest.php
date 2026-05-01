<?php

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Filament\Imports\ContactImporter;

function contactCol(string $name)
{
    return collect(ContactImporter::getColumns())->firstWhere(fn ($c) => $c->getName() === $name);
}

it('parses comma-separated roles into the flag-set', function () {
    expect(contactCol('roles')->castState('customer,vendor', []))->toBe(['customer', 'vendor']);
    expect(contactCol('roles')->castState('Customer | Collaborator', []))->toBe(['customer', 'collaborator']);
    expect(contactCol('roles')->castState('', []))->toBe([ContactRole::Customer->value]);
    expect(contactCol('roles')->castState(null, []))->toBe([ContactRole::Customer->value]);
});

it('drops unknown role values silently and keeps the valid ones', function () {
    expect(contactCol('roles')->castState('garbage,customer', []))->toBe(['customer']);
});

it('parses pipe-delimited address into a structured array', function () {
    $result = contactCol('address')->castState('Via Roma 1 | Milano | MI | 20121 | IT', []);

    expect($result)->toBe([
        'street' => 'Via Roma 1',
        'city' => 'Milano',
        'province' => 'MI',
        'postal_code' => '20121',
        'country' => 'IT',
    ]);
});

it('defaults country to IT when address omits the country segment', function () {
    $result = contactCol('address')->castState('Via X | Roma | RM | 00100', []);
    expect($result['country'])->toBe('IT');
});

it('returns null for an empty address column', function () {
    expect(contactCol('address')->castState('', []))->toBeNull();
    expect(contactCol('address')->castState(null, []))->toBeNull();
});
