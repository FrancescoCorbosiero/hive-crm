<?php

declare(strict_types=1);

use App\Domains\Finance\Filament\Pages\CashFlowProjectionPage;
use App\Domains\Finance\Filament\Pages\FinanceAnalyticsPage;
use App\Domains\Mail\Filament\Pages\MailTestPage;
use App\Domains\Settings\Filament\Pages\BusinessProfilePage;
use App\Filament\Pages\DemoDataPage;
use App\Models\User;
use Livewire\Livewire;

/**
 * Smoke tests guarding the custom-blade pages that picked up the editorial
 * style (accent bars, hive-display-num KPI cards, accent rules). Blade
 * typos in those templates are caught by Livewire at render time only, so
 * a per-page assertSuccessful() check is the cheapest backstop.
 */
beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the cash-flow projection page with the editorial layout', function () {
    Livewire::test(CashFlowProjectionPage::class)->assertSuccessful();
});

it('renders the finance analytics page with the editorial layout', function () {
    Livewire::test(FinanceAnalyticsPage::class)->assertSuccessful();
});

it('renders the business profile page with the editorial layout', function () {
    Livewire::test(BusinessProfilePage::class)->assertSuccessful();
});

it('renders the demo-data page with the editorial counter strip', function () {
    Livewire::test(DemoDataPage::class)->assertSuccessful();
});

it('renders the mail test page with the editorial layout', function () {
    Livewire::test(MailTestPage::class)->assertSuccessful();
});
