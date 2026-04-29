<?php

use App\Domains\Finance\Filament\Pages\FinanceAnalyticsPage;
use App\Domains\Finance\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the finance analytics page', function () {
    Livewire::test(FinanceAnalyticsPage::class)->assertSuccessful();
});

it('hydrates the page with the seeded transactions visible in the totals', function () {
    Transaction::factory()->income(50000)->on(now())->create(['category' => 'consulting']);
    Transaction::factory()->expense(10000)->on(now())->create(['category' => 'hosting']);

    Livewire::test(FinanceAnalyticsPage::class)
        ->assertSuccessful()
        ->call('$refresh');
});
