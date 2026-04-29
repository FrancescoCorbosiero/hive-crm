<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Pages;

use App\Domains\Finance\Enums\TransactionType;
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

    public function getViewData(): array
    {
        [$from, $until] = $this->range();
        $finance = app(FinanceService::class);
        $websites = app(WebsitesService::class);
        $locale = app()->getLocale();

        $income = $finance->breakdownByCategory(TransactionType::Income, $from, $until);
        $expense = $finance->breakdownByCategory(TransactionType::Expense, $from, $until);
        $byWebsite = $finance->incomeByWebsite($from, $until);

        $totalIncome = $income->sum(fn ($m) => $m->cents);
        $totalExpense = $expense->sum(fn ($m) => $m->cents);
        $net = $totalIncome - $totalExpense;

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
            'income' => $income->map(fn ($m, $cat) => [
                'category' => __('finance/transactions.categories.'.$cat, [], $locale) === 'finance/transactions.categories.'.$cat
                    ? $cat
                    : __('finance/transactions.categories.'.$cat),
                'amount' => $m->format($locale),
            ])->values(),
            'expense' => $expense->map(fn ($m, $cat) => [
                'category' => __('finance/transactions.categories.'.$cat, [], $locale) === 'finance/transactions.categories.'.$cat
                    ? $cat
                    : __('finance/transactions.categories.'.$cat),
                'amount' => $m->format($locale),
            ])->values(),
            'per_website' => $perWebsite,
            'totalIncome' => (new \App\Shared\ValueObjects\Money($totalIncome, $currency))->format($locale),
            'totalExpense' => (new \App\Shared\ValueObjects\Money($totalExpense, $currency))->format($locale),
            'net' => (new \App\Shared\ValueObjects\Money($net, $currency))->format($locale),
            'netNegative' => $net < 0,
        ];
    }
}
