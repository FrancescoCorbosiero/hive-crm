<?php

use App\Domains\Finance\Filament\Resources\TransactionResource\Pages\CreateTransaction;
use App\Domains\Finance\Filament\Resources\TransactionResource\Pages\EditTransaction;
use App\Domains\Finance\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Domains\Finance\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the transactions index page', function () {
    Transaction::factory()->count(3)->create();
    Livewire::test(ListTransactions::class)->assertSuccessful();
});

it('renders the transactions create page', function () {
    Livewire::test(CreateTransaction::class)->assertSuccessful();
});

it('renders the transactions edit page', function () {
    $tx = Transaction::factory()->create();
    Livewire::test(EditTransaction::class, ['record' => $tx->id])->assertSuccessful();
});
