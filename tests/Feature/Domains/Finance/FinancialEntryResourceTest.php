<?php

use App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages\CreateFinancialEntry;
use App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages\EditFinancialEntry;
use App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages\ListFinancialEntries;
use App\Domains\Finance\Models\FinancialEntry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the financial entries index page', function () {
    FinancialEntry::factory()->count(3)->create();
    Livewire::test(ListFinancialEntries::class)->assertSuccessful();
});

it('renders the financial entries create page', function () {
    Livewire::test(CreateFinancialEntry::class)->assertSuccessful();
});

it('renders the financial entries edit page', function () {
    $entry = FinancialEntry::factory()->create();
    Livewire::test(EditFinancialEntry::class, ['record' => $entry->id])->assertSuccessful();
});
