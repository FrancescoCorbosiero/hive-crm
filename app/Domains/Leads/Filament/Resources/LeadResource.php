<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Resources;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Enums\LostReason;
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

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->open()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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
                    Forms\Components\TextInput::make('company_name')
                        ->label(__('leads/labels.fields.company_name'))
                        ->helperText(__('leads/labels.helpers.company_name'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label(__('leads/labels.fields.email'))
                        ->email(),
                    Forms\Components\TextInput::make('phone')
                        ->label(__('leads/labels.fields.phone'))
                        ->tel(),
                    Forms\Components\TextInput::make('social_url')
                        ->label(__('leads/labels.fields.social_url'))
                        ->url()
                        ->placeholder('https://instagram.com/...')
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\Select::make('source')
                        ->label(__('leads/labels.fields.source'))
                        ->options(LeadSource::options())
                        ->searchable(),
                ]),

            Forms\Components\Section::make(__('leads/labels.sections.qualification'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('business_category')
                        ->label(__('leads/labels.fields.business_category'))
                        ->options(\App\Domains\Leads\Enums\BusinessCategory::options())
                        ->searchable(),
                    Forms\Components\Select::make('website_type')
                        ->label(__('leads/labels.fields.website_type'))
                        ->options(\App\Domains\Leads\Enums\WebsiteType::options())
                        ->searchable(),
                    Forms\Components\Select::make('budget_tier')
                        ->label(__('leads/labels.fields.budget_tier'))
                        ->options(\App\Domains\Leads\Enums\BudgetTier::options())
                        ->default(\App\Domains\Leads\Enums\BudgetTier::Unknown->value),
                    Forms\Components\Group::make([
                        Forms\Components\Toggle::make('is_redesign')
                            ->label(__('leads/labels.fields.is_redesign'))
                            ->helperText(__('leads/labels.helpers.is_redesign')),
                        Forms\Components\Toggle::make('is_estero')
                            ->label(__('leads/labels.fields.is_estero'))
                            ->helperText(__('leads/labels.helpers.is_estero')),
                    ]),
                ]),

            Forms\Components\Section::make(__('leads/labels.sections.pipeline'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label(__('leads/labels.fields.status'))
                        ->options(LeadStatus::options())
                        ->default(LeadStatus::New->value)
                        ->required()
                        ->live(),
                    \App\Shared\Filament\MoneyInput::make('estimated_value_cents')
                        ->label(__('leads/labels.fields.estimated_value')),
                    Forms\Components\DateTimePicker::make('next_action_at')
                        ->label(__('leads/labels.fields.next_action_at'))
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false),
                    Forms\Components\Select::make('lost_reason')
                        ->label(__('leads/labels.fields.lost_reason'))
                        ->options(LostReason::options())
                        ->visible(fn (Forms\Get $get) => $get('status') === LeadStatus::Lost->value)
                        ->required(fn (Forms\Get $get) => $get('status') === LeadStatus::Lost->value)
                        ->columnSpan(2),
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
                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('leads/labels.fields.company_name'))
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('leads/labels.fields.email'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('business_category')
                    ->label(__('leads/labels.fields.business_category'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?\App\Domains\Leads\Enums\BusinessCategory $state) => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('website_type')
                    ->label(__('leads/labels.fields.website_type'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?\App\Domains\Leads\Enums\WebsiteType $state) => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('budget_tier')
                    ->label(__('leads/labels.fields.budget_tier'))
                    ->badge()
                    ->color(fn (?\App\Domains\Leads\Enums\BudgetTier $state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?\App\Domains\Leads\Enums\BudgetTier $state) => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_redesign')
                    ->label(__('leads/labels.fields.is_redesign'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_estero')
                    ->label(__('leads/labels.fields.is_estero'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source')
                    ->label(__('leads/labels.fields.source'))
                    ->badge()
                    ->formatStateUsing(fn (?LeadSource $state) => $state?->label() ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Columns\TextColumn::make('last_contacted_at')
                    ->label(__('leads/labels.fields.last_contacted_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder(__('leads/labels.never_contacted')),
                Tables\Columns\TextColumn::make('lost_reason')
                    ->label(__('leads/labels.fields.lost_reason'))
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (?LostReason $state) => $state?->label() ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('converted_at')
                    ->label(__('leads/labels.fields.converted_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('leads/labels.fields.status'))
                    ->options(LeadStatus::options()),
                Tables\Filters\SelectFilter::make('business_category')
                    ->label(__('leads/labels.fields.business_category'))
                    ->options(\App\Domains\Leads\Enums\BusinessCategory::options())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('website_type')
                    ->label(__('leads/labels.fields.website_type'))
                    ->options(\App\Domains\Leads\Enums\WebsiteType::options()),
                Tables\Filters\SelectFilter::make('budget_tier')
                    ->label(__('leads/labels.fields.budget_tier'))
                    ->options(\App\Domains\Leads\Enums\BudgetTier::options()),
                Tables\Filters\TernaryFilter::make('is_redesign')
                    ->label(__('leads/labels.fields.is_redesign')),
                Tables\Filters\TernaryFilter::make('is_estero')
                    ->label(__('leads/labels.fields.is_estero')),
                Tables\Filters\SelectFilter::make('source')
                    ->label(__('leads/labels.fields.source'))
                    ->options(LeadSource::options()),
                Tables\Filters\Filter::make('stale')
                    ->label(__('leads/labels.filters.stale'))
                    ->query(fn ($query) => $query->stale(14))
                    ->toggle(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes([
                        'converted_contact_id', 'converted_at',
                        'last_contacted_at', 'lost_reason',
                        'created_at', 'updated_at', 'deleted_at',
                    ])
                    ->beforeReplicaSaved(function (Lead $replica) {
                        $replica->status = LeadStatus::New->value;
                        $replica->next_action_at = null;
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
                self::convertAction(),
                self::issueInvoiceAction(),
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
            ->defaultSort('next_action_at');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
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

    /**
     * Issue a draft Fattura against the lead's converted Contact. Only
     * visible once the lead has been converted (so a downstream Contact
     * actually exists to invoice). Redirects to the new Fattura's edit
     * page so the operator can fine-tune lines / due_date before saving.
     */
    private static function issueInvoiceAction(): Action
    {
        return Action::make('issueInvoice')
            ->label(__('leads/labels.invoice.action'))
            ->icon('heroicon-o-document-currency-euro')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Lead $lead) => $lead->isConverted())
            ->action(function (Lead $lead) {
                try {
                    $fatturaId = app(LeadsService::class)->issueInvoice($lead->id);
                } catch (DomainException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('leads/labels.invoice.failed'))
                        ->body($e->getMessage())
                        ->send();

                    return null;
                }

                Notification::make()
                    ->success()
                    ->title(__('leads/labels.invoice.success'))
                    ->send();

                return redirect()->to(
                    \App\Domains\Documents\Filament\Resources\FatturaResource::getUrl('edit', ['record' => $fatturaId])
                );
            });
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
