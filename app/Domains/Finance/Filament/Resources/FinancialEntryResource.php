<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages;
use App\Domains\Finance\Models\FinancialEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialEntryResource extends Resource
{
    protected static ?string $model = FinancialEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.finance');
    }

    public static function getModelLabel(): string
    {
        return __('finance/entries.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance/entries.plural');
    }

    private static function categoryOptions(): array
    {
        return collect(['website_subscription', 'one_time_project', 'consulting',
            'hosting', 'domains', 'software', 'tools', 'travel', 'taxes', 'other'])
            ->mapWithKeys(fn (string $key) => [$key => __('finance/entries.categories.'.$key)])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('finance/entries.sections.overview'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label(__('finance/entries.fields.type'))
                        ->options(FinancialEntryType::options())
                        ->default(FinancialEntryType::Income->value)
                        ->required(),

                    \App\Shared\Filament\MoneyInput::make('amount_cents')
                        ->label(__('finance/entries.fields.amount'))
                        ->required(),

                    Forms\Components\DatePicker::make('occurred_at')
                        ->label(__('finance/entries.fields.occurred_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(__('finance/entries.fields.description'))
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('category')
                        ->label(__('finance/entries.fields.category'))
                        ->options(self::categoryOptions())
                        ->searchable(),
                ]),

            Forms\Components\Section::make(__('finance/entries.sections.attribution'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('source_type')
                        ->label(__('finance/entries.fields.source_type'))
                        ->options(collect(FinancialEntrySource::cases())
                            ->mapWithKeys(fn (FinancialEntrySource $s) => [$s->value => ucfirst($s->value)])
                            ->all())
                        ->live(),

                    Forms\Components\TextInput::make('source_id')
                        ->label(__('finance/entries.fields.source_id'))
                        ->numeric()
                        ->visible(fn (Forms\Get $get) => filled($get('source_type'))),

                    \App\Shared\Filament\ContactPicker::make('contact_id')
                        ->label(__('finance/entries.fields.contact')),
                ]),

            Forms\Components\Section::make(__('finance/entries.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('finance/entries.fields.notes'))
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label(__('finance/entries.fields.occurred_at'))
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('finance/entries.fields.type'))
                    ->badge()
                    ->color(fn (FinancialEntryType $state) => $state->color())
                    ->formatStateUsing(fn (FinancialEntryType $state) => $state->label()),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('finance/entries.fields.description'))
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('finance/entries.fields.category'))
                    ->formatStateUsing(fn (?string $state) => $state ? __('finance/entries.categories.'.$state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('finance/entries.fields.amount'))
                    ->getStateUsing(fn (FinancialEntry $entry) => $entry->money->format(app()->getLocale()))
                    ->alignEnd()
                    ->color(fn (FinancialEntry $entry) => $entry->type->color()),

                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('finance/entries.fields.source_type'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('finance/entries.fields.type'))
                    ->options(FinancialEntryType::options()),

                Tables\Filters\SelectFilter::make('category')
                    ->label(__('finance/entries.fields.category'))
                    ->options(self::categoryOptions()),

                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dal'),
                        Forms\Components\DatePicker::make('until')->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('occurred_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('occurred_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateFattura')
                    ->label(__('finance/entries.actions.generate_fattura'))
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (FinancialEntry $entry) => $entry->type === FinancialEntryType::Income
                        && $entry->source_type !== FinancialEntrySource::Fattura->value)
                    ->requiresConfirmation()
                    ->action(function (FinancialEntry $entry): void {
                        try {
                            $fattura = app(\App\Domains\Finance\Services\Public\FinanceService::class)
                                ->generateFattura($entry->id);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('finance/entries.actions.generate_fattura_success', ['number' => $fattura->displayNumber()]))
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title(__('finance/entries.actions.generate_fattura_failure'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialEntries::route('/'),
            'create' => Pages\CreateFinancialEntry::route('/create'),
            'edit' => Pages\EditFinancialEntry::route('/{record}/edit'),
        ];
    }
}
