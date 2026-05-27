<?php

declare(strict_types=1);

use App\Filament\Pages\HomeDashboard;
use App\Filament\Widgets\DashboardKpisWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\WelcomeHeroWidget;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the home dashboard', function () {
    Livewire::test(HomeDashboard::class)->assertSuccessful();
});

it('renders the welcome hero widget', function () {
    Livewire::test(WelcomeHeroWidget::class)->assertSuccessful();
});

it('renders the custom KPI strip widget', function () {
    Livewire::test(DashboardKpisWidget::class)->assertSuccessful();
});

it('renders the quick-actions widget', function () {
    Livewire::test(QuickActionsWidget::class)->assertSuccessful();
});
