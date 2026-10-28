<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Pages;

use App\Domains\Finance\DTOs\ProjectedMonth;
use App\Domains\Finance\Services\Public\CashFlowProjectionService;
use App\Shared\CashFlow\ScheduledFlow;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

/**
 * Cash flow projection — what money is expected to move over the next
 * N months, per CashFlowProjectionService (recurring invoices, unpaid
 * invoices, renewals, recurring expenses). Nothing is written: this is
 * the "what's coming" view, and each domain's own issue / log actions
 * stay the source of truth for what actually happened.
 */
class CashFlowProjectionPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.cash-flow-projection';

    /** @var array<string,mixed> */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('finance/projection.page_title');
    }

    public function getTitle(): string
    {
        return __('finance/projection.page_title');
    }

    public function mount(): void
    {
        $this->form->fill(['months' => 12]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('months')
                    ->label(__('finance/projection.fields.window'))
                    ->options([
                        3 => __('finance/projection.windows.3'),
                        6 => __('finance/projection.windows.6'),
                        12 => __('finance/projection.windows.12'),
                        24 => __('finance/projection.windows.24'),
                    ])
                    ->default(12)
                    ->required(),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string,mixed>
     */
    public function getViewData(): array
    {
        $months = (int) ($this->form->getState()['months'] ?? 12);
        $projection = app(CashFlowProjectionService::class)->project(now(), $months);
        $locale = app()->getLocale();

        // Bars are scaled against the busiest month of the window.
        $peak = max(1, ...array_map(
            fn (ProjectedMonth $month) => max($month->income->cents, $month->loss->cents),
            $projection->months,
        ));

        return [
            'window_months' => $months,
            'monthly' => array_map(fn (ProjectedMonth $month) => [
                'key' => $month->key(),
                'label' => $month->start->locale($locale)->isoFormat('MMM YYYY'),
                'income' => $month->income->format($locale),
                'loss' => $month->loss->format($locale),
                'net' => $month->net()->format($locale),
                'net_negative' => $month->net()->isNegative(),
                'income_pct' => (int) round($month->income->cents / $peak * 100),
                'loss_pct' => (int) round($month->loss->cents / $peak * 100),
            ], $projection->months),
            'entries' => array_map(fn (ScheduledFlow $flow) => [
                'type' => $flow->isIncome ? 'income' : 'loss',
                'date' => $flow->date->toDateString(),
                'date_label' => $flow->date->locale($locale)->isoFormat('D MMM YYYY'),
                'overdue' => $projection->isOverdue($flow),
                'description' => $flow->description,
                'amount_cents' => $flow->amount->cents,
                'amount' => $flow->amount->format($locale),
                'source' => __('finance/projection.sources.'.$flow->source),
            ], $projection->flows),
            'totals' => [
                'income' => $projection->income->format($locale),
                'loss' => $projection->loss->format($locale),
                'net' => $projection->net()->format($locale),
                'net_negative' => $projection->net()->isNegative(),
            ],
            'has_data' => ! $projection->isEmpty(),
        ];
    }
}
