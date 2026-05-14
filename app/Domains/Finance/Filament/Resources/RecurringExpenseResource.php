<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Finance\Filament\Resources\RecurringExpenseResource\Pages;
use App\Domains\Finance\Models\RecurringExpense;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Shared\Filament\MoneyInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecurringExpenseResource extends Resource
{
    protected static ?string $model = RecurringExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.finance');
    }

    public static function getModelLabel(): string
    {
        return __('finance/recurring_expenses.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance/recurring_expenses.plural');
    }

    private static function categoryOptions(): array
    {
        return collect(['hosting', 'domains', 'software', 'tools', 'website_subscription', 'other'])
            ->mapWithKeys(fn (string $key) => [$key => __('finance/entries.categories.'.$key)])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('finance/recurring_expenses.sections.overview'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('finance/recurring_expenses.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    MoneyInput::make('amount_cents')
                        ->label(__('finance/recurring_expenses.fields.amount'))
                        ->required(),

                    Forms\Components\Select::make('frequency')
                        ->label(__('finance/recurring_expenses.fields.frequency'))
                        ->options(RecurringFrequency::options())
                        ->default(RecurringFrequency::Monthly->value)
                        ->required(),

                    Forms\Components\Select::make('category')
                        ->label(__('finance/recurring_expenses.fields.category'))
                        ->options(self::categoryOptions())
                        ->searchable(),

                    \App\Shared\Filament\ContactPicker::make(
                        'vendor_contact_id',
                        [\App\Domains\Contacts\Enums\ContactRole::Vendor],
                    )
                        ->label(__('finance/recurring_expenses.fields.vendor')),
                ]),

            Forms\Components\Section::make(__('finance/recurring_expenses.sections.schedule'))
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('started_at')
                        ->label(__('finance/recurring_expenses.fields.started_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),

                    Forms\Components\DatePicker::make('next_due_at')
                        ->label(__('finance/recurring_expenses.fields.next_due_at'))
                        ->displayFormat('d/m/Y')
                        ->required(),

                    Forms\Components\DatePicker::make('last_logged_at')
                        ->label(__('finance/recurring_expenses.fields.last_logged_at'))
                        ->displayFormat('d/m/Y'),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('finance/recurring_expenses.fields.is_active'))
                        ->default(true),
                ]),

            Forms\Components\Section::make(__('finance/recurring_expenses.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('finance/recurring_expenses.fields.notes'))
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('finance/recurring_expenses.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('frequency')
                    ->label(__('finance/recurring_expenses.fields.frequency'))
                    ->badge()
                    ->formatStateUsing(fn (RecurringFrequency $state) => $state->label()),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('finance/recurring_expenses.fields.amount'))
                    ->getStateUsing(fn (RecurringExpense $e) => $e->money()->format(app()->getLocale()))
                    ->alignEnd()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('finance/recurring_expenses.fields.category'))
                    ->formatStateUsing(fn (?string $state) => $state ? __('finance/entries.categories.'.$state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('next_due_at')
                    ->label(__('finance/recurring_expenses.fields.next_due_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (RecurringExpense $e) => $e->isDelayed() ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('finance/recurring_expenses.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('finance/recurring_expenses.fields.is_active')),
            ])
            ->actions([
                Tables\Actions\Action::make('logOccurrence')
                    ->label(__('finance/recurring_expenses.actions.log_occurrence'))
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__('finance/recurring_expenses.actions.log_occurrence_hint'))
                    ->action(function (RecurringExpense $expense): void {
                        $occurredAt = $expense->next_due_at ?? now()->toDateString();

                        app(FinanceService::class)->recordLoss([
                            'amount_cents' => (int) $expense->amount_cents,
                            'currency' => $expense->currency,
                            'occurred_at' => $occurredAt,
                            'description' => $expense->name,
                            'category' => $expense->category,
                            'contact_id' => $expense->vendor_contact_id,
                            'owner_user_id' => $expense->owner_user_id,
                        ]);

                        $next = $expense->frequency->advance(\Carbon\Carbon::parse($occurredAt));

                        $expense->update([
                            'last_logged_at' => $occurredAt,
                            'next_due_at' => $next->toDateString(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('finance/recurring_expenses.actions.log_occurrence_success'))
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes(['last_logged_at', 'created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (RecurringExpense $replica) {
                        $replica->name = $replica->name.' '.__('app.actions.copy_suffix');
                        $replica->started_at = now()->toDateString();
                        $replica->next_due_at = now()->toDateString();
                        $replica->is_active = true;
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_due_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringExpenses::route('/'),
            'create' => Pages\CreateRecurringExpense::route('/create'),
            'edit' => Pages\EditRecurringExpense::route('/{record}/edit'),
        ];
    }
}
