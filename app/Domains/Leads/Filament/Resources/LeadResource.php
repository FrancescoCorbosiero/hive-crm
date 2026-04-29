<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Resources;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages;
use App\Domains\Leads\Models\Lead;
use App\Domains\Leads\Services\Public\LeadsService;
use DomainException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.leads');
    }

    public static function getModelLabel(): string
    {
        return __('leads/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leads/labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('leads/labels.sections.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('leads/labels.fields.name'))
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label(__('leads/labels.fields.email'))
                        ->email(),
                    Forms\Components\TextInput::make('phone')
                        ->label(__('leads/labels.fields.phone'))
                        ->tel(),
                    Forms\Components\Select::make('source')
                        ->label(__('leads/labels.fields.source'))
                        ->options(LeadSource::options())
                        ->searchable(),
                ]),

            Forms\Components\Section::make(__('leads/labels.sections.pipeline'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label(__('leads/labels.fields.status'))
                        ->options(LeadStatus::options())
                        ->default(LeadStatus::New->value)
                        ->required(),
                    Forms\Components\TextInput::make('estimated_value_cents')
                        ->label(__('leads/labels.fields.estimated_value'))
                        ->numeric()
                        ->suffix('¢')
                        ->helperText('Importi in centesimi (€1,00 = 100).'),
                    Forms\Components\DateTimePicker::make('next_action_at')
                        ->label(__('leads/labels.fields.next_action_at'))
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false),
                ]),

            Forms\Components\Section::make(__('leads/labels.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('leads/labels.fields.notes'))
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('leads/labels.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('leads/labels.fields.email'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->label(__('leads/labels.fields.source'))
                    ->badge()
                    ->formatStateUsing(fn (?LeadSource $state) => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('leads/labels.fields.status'))
                    ->badge()
                    ->color(fn (LeadStatus $state) => $state->color())
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('estimated_value_cents')
                    ->label(__('leads/labels.fields.estimated_value'))
                    ->getStateUsing(fn (Lead $lead) => $lead->estimated_value?->format(app()->getLocale()) ?? '—')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('next_action_at')
                    ->label(__('leads/labels.fields.next_action_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('converted_at')
                    ->label(__('leads/labels.fields.converted_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('leads/labels.fields.status'))
                    ->options(LeadStatus::options()),
                Tables\Filters\SelectFilter::make('source')
                    ->label(__('leads/labels.fields.source'))
                    ->options(LeadSource::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::convertAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_action_at');
    }

    /**
     * The "Convert to customer" row action — visible only on open leads.
     * Delegates to LeadsService::convert() so the cross-domain dance
     * happens behind a single transactional public-service call.
     */
    private static function convertAction(): Action
    {
        return Action::make('convert')
            ->label(__('leads/labels.convert.action'))
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->visible(fn (Lead $lead) => ! $lead->isConverted() && $lead->status->isOpen())
            ->modalHeading(__('leads/labels.convert.modal_heading'))
            ->modalDescription(__('leads/labels.convert.modal_description'))
            ->form([
                Forms\Components\Toggle::make('create_website')
                    ->label(__('leads/labels.convert.create_website'))
                    ->live(),
                Forms\Components\TextInput::make('website_name')
                    ->label(__('leads/labels.convert.website_name'))
                    ->visible(fn (Forms\Get $get) => (bool) $get('create_website'))
                    ->required(fn (Forms\Get $get) => (bool) $get('create_website')),
                Forms\Components\TextInput::make('website_url')
                    ->label(__('leads/labels.convert.website_url'))
                    ->url()
                    ->visible(fn (Forms\Get $get) => (bool) $get('create_website'))
                    ->required(fn (Forms\Get $get) => (bool) $get('create_website')),
            ])
            ->action(function (Lead $lead, array $data) {
                $websiteAttributes = ($data['create_website'] ?? false)
                    ? [
                        'name' => $data['website_name'],
                        'url' => $data['website_url'],
                    ]
                    : null;

                try {
                    app(LeadsService::class)->convert($lead->id, $websiteAttributes);
                } catch (DomainException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('leads/labels.convert.already_converted'))
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('leads/labels.convert.success_title'))
                    ->body(__('leads/labels.convert.success_body'))
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
