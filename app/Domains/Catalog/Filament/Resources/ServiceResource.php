<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources;

use App\Domains\Catalog\Enums\ServiceCategory;
use App\Domains\Catalog\Filament\Resources\ServiceResource\Pages;
use App\Domains\Catalog\Models\Service;
use App\Domains\Quotations\Enums\LineCadence;
use App\Shared\Filament\MoneyInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('catalog/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog/labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('catalog/labels.sections.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('catalog/labels.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label(__('catalog/labels.fields.category'))
                        ->options(ServiceCategory::options())
                        ->default(ServiceCategory::Other->value)
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label(__('catalog/labels.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('catalog/labels.sections.defaults'))
                ->description(__('catalog/labels.sections.defaults_hint'))
                ->columns(3)
                ->schema([
                    MoneyInput::make('default_unit_price_cents')
                        ->label(__('catalog/labels.fields.default_unit_price')),
                    Forms\Components\TextInput::make('default_vat_rate')
                        ->label(__('catalog/labels.fields.default_vat_rate'))
                        ->numeric()
                        ->default(22)
                        ->required(),
                    Forms\Components\Select::make('default_cadence')
                        ->label(__('catalog/labels.fields.default_cadence'))
                        ->options(LineCadence::options())
                        ->placeholder(__('catalog/labels.fields.default_cadence_none')),
                ]),

            Forms\Components\Section::make(__('catalog/labels.sections.extras'))
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('catalog/labels.fields.is_active'))
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('catalog/labels.fields.sort_order'))
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('catalog/labels.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('catalog/labels.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('catalog/labels.fields.category'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (ServiceCategory $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_unit_price_cents')
                    ->label(__('catalog/labels.fields.default_unit_price'))
                    ->getStateUsing(fn (Service $s) => $s->defaultUnitPrice()?->format(app()->getLocale()) ?? '—')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('default_vat_rate')
                    ->label(__('catalog/labels.fields.default_vat_rate'))
                    ->suffix('%')
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('default_cadence')
                    ->label(__('catalog/labels.fields.default_cadence'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?LineCadence $state) => $state?->label())
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('catalog/labels.fields.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('catalog/labels.fields.sort_order'))
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label(__('catalog/labels.fields.category'))
                    ->getTitleFromRecordUsing(fn (Service $s) => $s->category->label()),
            ])
            ->defaultGroup('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('catalog/labels.fields.category'))
                    ->options(ServiceCategory::options()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('catalog/labels.fields.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
