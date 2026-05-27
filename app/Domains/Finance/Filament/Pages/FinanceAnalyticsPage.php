<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Pages;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Websites\Services\Public\WebsitesService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class FinanceAnalyticsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.finance-analytics';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('finance/analytics.page_title');
    }

    public function getTitle(): string
    {
        return __('finance/analytics.page_title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfYear()->toDateString(),
            'until' => now()->toDateString(),
            'include_non_taxable' => false,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('from')
                    ->label(__('finance/analytics.fields.from'))
                    ->displayFormat('d/m/Y')
                    ->required(),
                Forms\Components\DatePicker::make('until')
                    ->label(__('finance/analytics.fields.until'))
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->after('from'),
                Forms\Components\Toggle::make('include_non_taxable')
                    ->label(__('finance/analytics.fields.include_non_taxable'))
                    ->helperText(__('finance/analytics.helpers.include_non_taxable'))
                    ->inline(false)
                    ->columnSpan(2),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function range(): array
    {
        $state = $this->form->getState();
        $from = Carbon::parse($state['from'] ?? now()->startOfYear())->startOfDay();
        $until = Carbon::parse($state['until'] ?? now())->endOfDay();

        return [$from, $until];
    }

    public function includeNonTaxable(): bool
    {
        return (bool) ($this->form->getState()['include_non_taxable'] ?? false);
    }

    public function getViewData(): array
    {
        [$from, $until] = $this->range();
        $includeNonTaxable = $this->includeNonTaxable();
        $finance = app(FinanceService::class);
        $websites = app(WebsitesService::class);
        $locale = app()->getLocale();

        $income = $finance->breakdownByCategory(FinancialEntryType::Income, $from, $until, $includeNonTaxable);
        $loss = $finance->breakdownByCategory(FinancialEntryType::Loss, $from, $until, $includeNonTaxable);
        $byWebsite = $finance->incomeByWebsite($from, $until, $includeNonTaxable);

        $totalIncome = $income->sum(fn ($m) => $m->cents);
        $totalLoss = $loss->sum(fn ($m) => $m->cents);
        $net = $totalIncome - $totalLoss;

        $currency = config('app.currency', 'EUR');

        $perWebsite = $byWebsite->map(function ($money, int $websiteId) use ($websites, $locale) {
            $dto = $websites->find($websiteId);

            return [
                'id' => $websiteId,
                'name' => $dto ? $dto->nameForLocale($locale) : "#{$websiteId}",
                'amount' => $money->format($locale),
            ];
        })->values();

        return [
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
            'include_non_taxable' => $includeNonTaxable,
            'income' => $income->map(fn ($m, $cat) => [
                'category' => __('finance/entries.categories.'.$cat, [], $locale) === 'finance/entries.categories.'.$cat
                    ? $cat
                    : __('finance/entries.categories.'.$cat),
                'amount' => $m->format($locale),
            ])->values(),
            'loss' => $loss->map(fn ($m, $cat) => [
                'category' => __('finance/entries.categories.'.$cat, [], $locale) === 'finance/entries.categories.'.$cat
                    ? $cat
                    : __('finance/entries.categories.'.$cat),
                'amount' => $m->format($locale),
            ])->values(),
            'per_website' => $perWebsite,
            'totalIncome' => (new \App\Shared\ValueObjects\Money($totalIncome, $currency))->format($locale),
            'totalLoss' => (new \App\Shared\ValueObjects\Money($totalLoss, $currency))->format($locale),
            'net' => (new \App\Shared\ValueObjects\Money($net, $currency))->format($locale),
            'netNegative' => $net < 0,
        ];
    }
}
