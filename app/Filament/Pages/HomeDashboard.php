<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use App\Filament\Widgets\ActiveSubscriptionsWidget;
use App\Filament\Widgets\OpenQuotationsWidget;
use App\Filament\Widgets\TopLeadsWidget;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Throwable;

/**
 * The CRM homepage. Replaces Filament's default Dashboard with a layout
 * optimised for the May 2026 data-entry backlog:
 *
 *   - Three header "Fast Entry" actions (Record Payment / Add Lead / Log
 *     Expense) so each is one click + a small slide-over form away from
 *     anywhere on the panel.
 *   - Widgets stacked underneath: Open Quotations, Active Subscriptions
 *     (revenue + cost, delay-highlighted), Top 5 Leads.
 */
class HomeDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.subtitle');
    }

    public function getColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \Filament\Widgets\AccountWidget::class,
            OpenQuotationsWidget::class,
            ActiveSubscriptionsWidget::class,
            \App\Domains\DomainNames\Filament\Widgets\ExpiringDomainsWidget::class,
            TopLeadsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->recordPaymentAction(),
            $this->addLeadAction(),
            $this->logExpenseAction(),
        ];
    }

    private function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label(__('dashboard.fast_entry.record_payment.label'))
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading(__('dashboard.fast_entry.record_payment.heading'))
            ->modalSubmitActionLabel(__('dashboard.fast_entry.record_payment.submit'))
            ->form([
                Forms\Components\Select::make('fattura_id')
                    ->label(__('dashboard.fast_entry.record_payment.fattura'))
                    ->options(function () {
                        return Fattura::query()
                            ->whereIn('payment_status', ['unpaid', 'partially_paid', 'overdue'])
                            ->orderByDesc('issued_at')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Fattura $f) => [
                                $f->id => $f->displayNumber()
                                    .' — '.$f->total()->format(app()->getLocale())
                                    .' ('.$f->payment_status->label().')',
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->required(),

                MoneyInput::make('amount_cents')
                    ->label(__('dashboard.fast_entry.record_payment.amount'))
                    ->required(),

                Forms\Components\DatePicker::make('paid_at')
                    ->label(__('dashboard.fast_entry.record_payment.paid_at'))
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('reference')
                    ->label(__('dashboard.fast_entry.record_payment.reference'))
                    ->maxLength(255),
            ])
            ->action(function (array $data): void {
                try {
                    $payment = app(PaymentsService::class)->record(
                        (int) $data['fattura_id'],
                        [
                            'amount_cents' => (int) $data['amount_cents'],
                            'paid_at' => $data['paid_at'],
                            'reference' => $data['reference'] ?? null,
                        ],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('dashboard.fast_entry.record_payment.success', [
                            'amount' => $payment->amount_cents / 100,
                        ]))
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('dashboard.fast_entry.failure'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    private function addLeadAction(): Action
    {
        return Action::make('addLead')
            ->label(__('dashboard.fast_entry.add_lead.label'))
            ->icon('heroicon-o-funnel')
            ->color('primary')
            ->modalHeading(__('dashboard.fast_entry.add_lead.heading'))
            ->modalSubmitActionLabel(__('dashboard.fast_entry.add_lead.submit'))
            ->form([
                Forms\Components\TextInput::make('name')
                    ->label(__('dashboard.fast_entry.add_lead.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('company_name')
                    ->label(__('dashboard.fast_entry.add_lead.company'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label(__('dashboard.fast_entry.add_lead.email'))
                    ->email()
                    ->maxLength(255),

                MoneyInput::make('estimated_value_cents')
                    ->label(__('dashboard.fast_entry.add_lead.estimated_value')),

                Forms\Components\Select::make('source')
                    ->label(__('dashboard.fast_entry.add_lead.source'))
                    ->options(LeadSource::options()),

                Forms\Components\Select::make('status')
                    ->label(__('dashboard.fast_entry.add_lead.status'))
                    ->options(LeadStatus::options())
                    ->default(LeadStatus::New->value)
                    ->required(),
            ])
            ->action(function (array $data): void {
                try {
                    Lead::query()->create([
                        'name' => $data['name'],
                        'company_name' => $data['company_name'] ?? null,
                        'email' => $data['email'] ?? null,
                        'estimated_value_cents' => isset($data['estimated_value_cents'])
                            ? (int) $data['estimated_value_cents']
                            : null,
                        'estimated_value_currency' => config('app.currency', 'EUR'),
                        'source' => $data['source'] ?? null,
                        'status' => $data['status'] ?? LeadStatus::New->value,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('dashboard.fast_entry.add_lead.success', ['name' => $data['name']]))
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('dashboard.fast_entry.failure'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    private function logExpenseAction(): Action
    {
        return Action::make('logExpense')
            ->label(__('dashboard.fast_entry.log_expense.label'))
            ->icon('heroicon-o-arrow-trending-down')
            ->color('danger')
            ->modalHeading(__('dashboard.fast_entry.log_expense.heading'))
            ->modalSubmitActionLabel(__('dashboard.fast_entry.log_expense.submit'))
            ->form([
                Forms\Components\TextInput::make('description')
                    ->label(__('dashboard.fast_entry.log_expense.description'))
                    ->required()
                    ->maxLength(255),

                MoneyInput::make('amount_cents')
                    ->label(__('dashboard.fast_entry.log_expense.amount'))
                    ->required(),

                Forms\Components\DatePicker::make('occurred_at')
                    ->label(__('dashboard.fast_entry.log_expense.occurred_at'))
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('category')
                    ->label(__('dashboard.fast_entry.log_expense.category'))
                    ->options(collect([
                        'hosting', 'software', 'tools', 'website_subscription',
                        'travel', 'taxes', 'other',
                    ])->mapWithKeys(fn ($k) => [$k => __('finance/entries.categories.'.$k)])->all())
                    ->searchable(),

                \App\Shared\Filament\ContactPicker::make(
                    'contact_id',
                    [\App\Domains\Contacts\Enums\ContactRole::Vendor],
                )
                    ->label(__('dashboard.fast_entry.log_expense.vendor')),
            ])
            ->action(function (array $data): void {
                try {
                    app(FinanceService::class)->record(FinancialEntryType::Loss, [
                        'amount_cents' => (int) $data['amount_cents'],
                        'occurred_at' => $data['occurred_at'],
                        'description' => $data['description'],
                        'category' => $data['category'] ?? null,
                        'contact_id' => $data['contact_id'] ?? null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('dashboard.fast_entry.log_expense.success'))
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('dashboard.fast_entry.failure'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
