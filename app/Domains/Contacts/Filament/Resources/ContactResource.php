<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages;
use App\Domains\Contacts\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'vat_number', 'tax_code'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return array_filter([
            __('contacts/labels.email') => $record->email,
            __('contacts/labels.vat_number') => $record->vat_number,
        ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.contacts');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('contacts/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contacts/labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('contacts/labels.section.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('contacts/labels.name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label(__('contacts/labels.email'))
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label(__('contacts/labels.phone'))
                        ->tel()
                        ->maxLength(64),

                    Forms\Components\CheckboxList::make('roles')
                        ->label(__('contacts/labels.roles'))
                        ->options(ContactRole::options())
                        ->columns(2)
                        ->required(),
                ]),

            Forms\Components\Section::make(__('contacts/labels.section.tax'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('vat_number')
                        ->label(__('contacts/labels.vat_number'))
                        ->maxLength(32),

                    Forms\Components\TextInput::make('tax_code')
                        ->label(__('contacts/labels.tax_code'))
                        ->maxLength(32),
                ]),

            Forms\Components\Section::make(__('contacts/labels.section.address'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('address.street')
                        ->label(__('contacts/labels.address.street'))
                        ->columnSpan(3),
                    Forms\Components\TextInput::make('address.city')
                        ->label(__('contacts/labels.address.city')),
                    Forms\Components\TextInput::make('address.province')
                        ->label(__('contacts/labels.address.province'))
                        ->maxLength(2),
                    Forms\Components\TextInput::make('address.postal_code')
                        ->label(__('contacts/labels.address.postal_code')),
                    Forms\Components\TextInput::make('address.country')
                        ->label(__('contacts/labels.address.country'))
                        ->default('IT')
                        ->maxLength(2),
                ]),

            Forms\Components\Section::make(__('contacts/labels.section.preferences'))
                ->schema([
                    Forms\Components\Toggle::make('do_not_email')
                        ->label(__('contacts/labels.do_not_email'))
                        ->helperText(__('contacts/labels.do_not_email_help')),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('contacts/labels.notes'))
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('contacts/labels.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('contacts/labels.email'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('contacts/labels.phone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('roles')
                    ->label(__('contacts/labels.roles'))
                    ->badge()
                    ->color(fn (string $state) => ContactRole::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state) => ContactRole::tryFrom($state)?->label() ?? $state),
                Tables\Columns\IconColumn::make('do_not_email')
                    ->label(__('contacts/labels.do_not_email_short'))
                    ->boolean()
                    ->trueIcon('heroicon-o-no-symbol')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-envelope')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('contacts/labels.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('contacts/labels.roles'))
                    ->options(ContactRole::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->withRole($data['value']);
                    }),
                Tables\Filters\TernaryFilter::make('do_not_email')
                    ->label(__('contacts/labels.do_not_email')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Shared\Filament\HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
