<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Finance\Enums\TransactionSource;
use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Filament\Resources\TransactionResource\Pages;
use App\Domains\Finance\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

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
        return __('finance/transactions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance/transactions.plural');
    }

    private static function categoryOptions(): array
    {
        return collect(['website_subscription', 'one_time_project', 'consulting',
            'hosting', 'software', 'tools', 'travel', 'taxes', 'other'])
            ->mapWithKeys(fn (string $key) => [$key => __('finance/transactions.categories.'.$key)])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('finance/transactions.sections.overview'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label(__('finance/transactions.fields.type'))
                        ->options(TransactionType::options())
                        ->default(TransactionType::Income->value)
                        ->required(),

                    \App\Shared\Filament\MoneyInput::make('amount_cents')
                        ->label(__('finance/transactions.fields.amount'))
                        ->required(),

                    Forms\Components\DatePicker::make('occurred_at')
                        ->label(__('finance/transactions.fields.occurred_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(__('finance/transactions.fields.description'))
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('category')
                        ->label(__('finance/transactions.fields.category'))
                        ->options(self::categoryOptions())
                        ->searchable(),
                ]),

            Forms\Components\Section::make(__('finance/transactions.sections.attribution'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('source_type')
                        ->label(__('finance/transactions.fields.source_type'))
                        ->options(collect(TransactionSource::cases())
                            ->mapWithKeys(fn (TransactionSource $s) => [$s->value => ucfirst($s->value)])
                            ->all())
                        ->live(),

                    Forms\Components\TextInput::make('source_id')
                        ->label(__('finance/transactions.fields.source_id'))
                        ->numeric()
                        ->visible(fn (Forms\Get $get) => filled($get('source_type'))),

                    Forms\Components\Select::make('contact_id')
                        ->label(__('finance/transactions.fields.contact'))
                        ->options(fn () => Contact::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ]),

            Forms\Components\Section::make(__('finance/transactions.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('finance/transactions.fields.notes'))
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label(__('finance/transactions.fields.occurred_at'))
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('finance/transactions.fields.type'))
                    ->badge()
                    ->color(fn (TransactionType $state) => $state->color())
                    ->formatStateUsing(fn (TransactionType $state) => $state->label()),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('finance/transactions.fields.description'))
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('finance/transactions.fields.category'))
                    ->formatStateUsing(fn (?string $state) => $state ? __('finance/transactions.categories.'.$state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('finance/transactions.fields.amount'))
                    ->getStateUsing(fn (Transaction $tx) => $tx->money->format(app()->getLocale()))
                    ->alignEnd()
                    ->color(fn (Transaction $tx) => $tx->type->color()),

                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('finance/transactions.fields.source_type'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('finance/transactions.fields.type'))
                    ->options(TransactionType::options()),

                Tables\Filters\SelectFilter::make('category')
                    ->label(__('finance/transactions.fields.category'))
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
