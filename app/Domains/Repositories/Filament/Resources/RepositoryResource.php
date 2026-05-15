<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Repositories\Enums\RepositoryProvider;
use App\Domains\Repositories\Filament\Resources\RepositoryResource\Pages;
use App\Domains\Repositories\Models\Repository;
use App\Shared\Filament\ContactPicker;
use App\Domains\Websites\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepositoryResource extends Resource
{
    protected static ?string $model = Repository::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.websites');
    }

    public static function getModelLabel(): string
    {
        return __('repositories/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('repositories/labels.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->addSelect([
                'owner_name' => Contact::query()
                    ->select('name')
                    ->whereColumn('contacts.id', 'repositories.owner_contact_id')
                    ->limit(1),
                'website_url' => Website::query()
                    ->select('url')
                    ->whereColumn('websites.id', 'repositories.website_id')
                    ->limit(1),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('repositories/labels.sections.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('repositories/labels.fields.name'))
                        ->placeholder('owner/repo')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('url')
                        ->label(__('repositories/labels.fields.url'))
                        ->placeholder('https://github.com/owner/repo')
                        ->url()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                            if (blank($state) || filled($get('provider'))) {
                                return;
                            }
                            $set('provider', RepositoryProvider::detect((string) $state)->value);
                        }),
                    Forms\Components\Select::make('provider')
                        ->label(__('repositories/labels.fields.provider'))
                        ->options(RepositoryProvider::options())
                        ->default(RepositoryProvider::Github->value)
                        ->required(),
                ]),

            Forms\Components\Section::make(__('repositories/labels.sections.links'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('website_id')
                        ->label(__('repositories/labels.fields.website'))
                        ->options(fn () => Website::query()->orderBy('url')->pluck('url', 'id'))
                        ->searchable()
                        ->preload(),
                    ContactPicker::make('owner_contact_id')
                        ->label(__('repositories/labels.fields.owner_contact')),
                ]),

            Forms\Components\Section::make(__('repositories/labels.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('repositories/labels.fields.notes'))
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('repositories/labels.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('provider')
                    ->label(__('repositories/labels.fields.provider'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (RepositoryProvider $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label(__('repositories/labels.fields.url'))
                    ->url(fn (Repository $r) => $r->url, shouldOpenInNewTab: true)
                    ->limit(48)
                    ->color('primary')
                    ->icon('heroicon-o-arrow-top-right-on-square'),

                Tables\Columns\TextColumn::make('owner_name')
                    ->label(__('repositories/labels.fields.owner_contact'))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('website_url')
                    ->label(__('repositories/labels.fields.website'))
                    ->placeholder('—')
                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                    ->limit(32)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label(__('repositories/labels.fields.provider'))
                    ->options(RepositoryProvider::options()),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('repositories/labels.actions.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Repository $r) => $r->url, shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes(['url', 'created_at', 'updated_at'])
                    ->form([
                        Forms\Components\TextInput::make('url')
                            ->label(__('repositories/labels.fields.url'))
                            ->url()
                            ->required()
                            ->unique(table: 'repositories', column: 'url'),
                    ])
                    ->beforeReplicaSaved(function (Repository $replica, array $data): void {
                        $replica->url = $data['url'];
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepositories::route('/'),
            'create' => Pages\CreateRepository::route('/create'),
            'edit' => Pages\EditRepository::route('/{record}/edit'),
        ];
    }
}
