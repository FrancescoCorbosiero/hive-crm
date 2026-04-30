<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages;
use App\Domains\Websites\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebsiteResource extends Resource
{
    use Translatable;

    protected static ?string $model = Website::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

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

                    Forms\Components\Select::make('owner_contact_id')
                        ->label(__('websites/labels.owner_contact'))
                        ->options(fn () => Contact::query()
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('websites/labels.notes'))
                        ->columnSpanFull()
                        ->rows(3),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('websites/labels.status'))
                    ->options(WebsiteStatus::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_renewal_at');
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
