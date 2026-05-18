<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Resources;

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages;
use App\Domains\Websites\Filament\Resources\WebsiteResource\RelationManagers\DomainNamesRelationManager;
use App\Domains\Websites\Models\Website;
use App\Shared\Filament\ContactPicker;
use App\Shared\Filament\MoneyInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebsiteResource extends Resource
{
    use Translatable;

    protected static ?string $model = Website::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'url';

    public static function getGloballySearchableAttributes(): array
    {
        return ['url'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Website::query()->active()->where('is_up', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.websites');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.websites');
    }

    public static function getModelLabel(): string
    {
        return __('websites/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('websites/labels.plural');
    }

    public static function getTranslatableLocales(): array
    {
        return config('app.supported_locales', ['it', 'en']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('websites/labels.section.general'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('websites/labels.name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('url')
                        ->label(__('websites/labels.url'))
                        ->url()
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label(__('websites/labels.status'))
                        ->options(WebsiteStatus::options())
                        ->default(WebsiteStatus::Active->value)
                        ->required(),

                    ContactPicker::make('owner_contact_id')
                        ->label(__('websites/labels.owner_contact')),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('websites/labels.notes'))
                        ->columnSpanFull()
                        ->rows(3),

                    Forms\Components\TextInput::make('trello_board_url')
                        ->label(__('websites/labels.trello_board_url'))
                        ->placeholder('https://trello.com/b/…')
                        ->url()
                        ->maxLength(255)
                        ->suffixIcon('heroicon-o-arrow-top-right-on-square')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('websites/labels.section.subscription'))
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('subscription_started_at')
                        ->label(__('websites/labels.subscription_started_at'))
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('next_renewal_at')
                        ->label(__('websites/labels.next_renewal_at'))
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('renewal_period_months')
                        ->label(__('websites/labels.renewal_period_months'))
                        ->numeric()
                        ->default(12)
                        ->minValue(1)
                        ->maxValue(60),
                ]),

            Forms\Components\Section::make(__('websites/labels.section.tech'))
                ->schema([
                    Forms\Components\TagsInput::make('tech_stack')
                        ->label(__('websites/labels.tech_stack'))
                        ->placeholder('Laravel, Tailwind…')
                        ->reorderable(),
                ]),

            // Cross-domain auto-spawn (create only): log the setup or
            // first-cycle hosting cost as a LOSS entry in Finance.
            // Default off because not every Website tracks a discrete
            // setup cost (some are bundled into a larger Quotation).
            // Idempotent in the Finance listener via external_ref =
            // "website_setup:{id}".
            Forms\Components\Section::make(__('websites/labels.section.register_cost'))
                ->description(__('websites/labels.section.register_cost_hint'))
                ->columns(3)
                ->visibleOn('create')
                ->schema([
                    Forms\Components\Toggle::make('register_cost_enabled')
                        ->label(__('websites/labels.cost.toggle'))
                        ->default(false)
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    MoneyInput::make('setup_cost_cents')
                        ->label(__('websites/labels.cost.amount'))
                        ->dehydrated(false)
                        ->visible(fn (Get $get) => (bool) $get('register_cost_enabled'))
                        ->required(fn (Get $get) => (bool) $get('register_cost_enabled')),
                    Forms\Components\DatePicker::make('setup_paid_at')
                        ->label(__('websites/labels.cost.paid_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->dehydrated(false)
                        ->visible(fn (Get $get) => (bool) $get('register_cost_enabled')),
                    Forms\Components\Select::make('setup_method')
                        ->label(__('websites/labels.cost.method'))
                        ->options(PaymentMethod::options())
                        ->default(PaymentMethod::BankTransfer->value)
                        ->dehydrated(false)
                        ->visible(fn (Get $get) => (bool) $get('register_cost_enabled')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('websites/labels.name'))
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn (Website $w) => $w->getTranslation('name', app()->getLocale())),

                Tables\Columns\TextColumn::make('url')
                    ->label(__('websites/labels.url'))
                    ->url(fn (Website $w) => $w->url, true)
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('websites/labels.status'))
                    ->badge()
                    ->color(fn (WebsiteStatus $state) => $state->color())
                    ->formatStateUsing(fn (WebsiteStatus $state) => $state->label()),

                Tables\Columns\IconColumn::make('is_up')
                    ->label(__('websites/labels.ping.is_up'))
                    ->icon(fn (?bool $state) => match ($state) {
                        true => 'heroicon-o-signal',
                        false => 'heroicon-o-signal-slash',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (?bool $state) => match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (Website $w) => $w->last_pinged_at
                        ? __('websites/labels.ping.last_status_code').': '.($w->last_status_code ?? '—')
                        : __('websites/labels.ping.never')),

                Tables\Columns\TextColumn::make('next_renewal_at')
                    ->label(__('websites/labels.next_renewal_at'))
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_until_renewal')
                    ->label(__('websites/labels.days_until_renewal'))
                    ->getStateUsing(fn (Website $w) => $w->daysUntilRenewal())
                    ->badge()
                    ->color(function ($state) {
                        if ($state === null) {
                            return 'gray';
                        }
                        if ($state <= 7) {
                            return 'danger';
                        }
                        if ($state <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('trello_board_url')
                    ->label(__('websites/labels.trello_board_url_short'))
                    ->placeholder('—')
                    ->url(fn (?string $state) => $state, shouldOpenInNewTab: true)
                    ->formatStateUsing(fn (?string $state) => $state ? __('websites/labels.trello_open') : null)
                    ->icon(fn (?string $state) => $state ? 'heroicon-o-arrow-top-right-on-square' : null)
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('websites/labels.status'))
                    ->options(WebsiteStatus::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes([
                        'subscription_started_at', 'next_renewal_at',
                        'is_up', 'last_status_code', 'last_pinged_at',
                        'created_at', 'updated_at',
                    ])
                    ->beforeReplicaSaved(function (Website $replica) {
                        // Translatable name column needs to be mutated
                        // through getTranslations / setTranslation to
                        // append the copy suffix in every locale.
                        foreach ($replica->getTranslations('name') as $locale => $value) {
                            $replica->setTranslation('name', $locale, $value.' '.__('app.actions.copy_suffix'));
                        }
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_renewal_at');
    }

    public static function getRelations(): array
    {
        return [
            DomainNamesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebsites::route('/'),
            'create' => Pages\CreateWebsite::route('/create'),
            'edit' => Pages\EditWebsite::route('/{record}/edit'),
        ];
    }
}
