<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Websites\Models\Website;
use App\Shared\Filament\ContactPicker;
use App\Shared\Filament\MoneyInput;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainNameResource extends Resource
{
    protected static ?string $model = DomainName::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.websites');
    }

    public static function getModelLabel(): string
    {
        return __('domain_names/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('domain_names/labels.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        // Expiring within 30 days OR already expired — the badge is the
        // at-a-glance "domains need attention" signal.
        $count = DomainName::query()->needsAttention(30)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Red when something is already past expiry, amber otherwise.
        $expired = DomainName::query()->whereDate('expires_at', '<', now())->exists();

        return $expired ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        // Correlated subqueries keep the list view N+1-free on the
        // cross-domain scalar FKs.
        return parent::getEloquentQuery()
            ->addSelect([
                'owner_name' => Contact::query()
                    ->select('name')
                    ->whereColumn('contacts.id', 'domain_names.owner_contact_id')
                    ->limit(1),
                'website_url' => Website::query()
                    ->select('url')
                    ->whereColumn('websites.id', 'domain_names.website_id')
                    ->limit(1),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('domain_names/labels.sections.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('domain_names/labels.fields.name'))
                        ->placeholder('example.com')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('registrar')
                        ->label(__('domain_names/labels.fields.registrar'))
                        ->options(Registrar::options())
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label(__('domain_names/labels.fields.status'))
                        ->options(DomainStatus::options())
                        ->default(DomainStatus::Active->value)
                        ->required(),
                ]),

            Forms\Components\Section::make(__('domain_names/labels.sections.renewal'))
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('registered_at')
                        ->label(__('domain_names/labels.fields.registered_at'))
                        ->displayFormat('d/m/Y'),
                    Forms\Components\DatePicker::make('expires_at')
                        ->label(__('domain_names/labels.fields.expires_at'))
                        ->displayFormat('d/m/Y'),
                    Forms\Components\TextInput::make('renewal_period_months')
                        ->label(__('domain_names/labels.fields.renewal_period_months'))
                        ->numeric()
                        ->default(12)
                        ->required(),
                    Forms\Components\Toggle::make('auto_renew')
                        ->label(__('domain_names/labels.fields.auto_renew'))
                        ->default(true),
                    MoneyInput::make('renewal_cost_cents')
                        ->label(__('domain_names/labels.fields.renewal_cost')),
                ]),

            Forms\Components\Section::make(__('domain_names/labels.sections.links'))
                ->description(__('domain_names/labels.sections.links_hint'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('website_id')
                        ->label(__('domain_names/labels.fields.website'))
                        ->options(fn () => Website::query()
                            ->orderBy('url')
                            ->pluck('url', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder(__('domain_names/labels.auto_link_placeholder')),
                    ContactPicker::make('owner_contact_id')
                        ->label(__('domain_names/labels.fields.owner_contact'))
                        ->placeholder(__('domain_names/labels.auto_link_placeholder')),
                ]),

            Forms\Components\Section::make(__('domain_names/labels.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('domain_names/labels.fields.notes'))
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('domain_names/labels.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('registrar')
                    ->label(__('domain_names/labels.fields.registrar'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (Registrar $state) => $state->label()),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('domain_names/labels.fields.status'))
                    ->badge()
                    ->color(fn (DomainStatus $state) => $state->color())
                    ->formatStateUsing(fn (DomainStatus $state) => $state->label()),

                Tables\Columns\TextColumn::make('owner_name')
                    ->label(__('domain_names/labels.fields.owner_contact'))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('website_url')
                    ->label(__('domain_names/labels.fields.website'))
                    ->placeholder('—')
                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                    ->limit(32)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('renewal_cost_cents')
                    ->label(__('domain_names/labels.fields.renewal_cost'))
                    ->getStateUsing(fn (DomainName $d) => $d->renewalCost()?->format(app()->getLocale()) ?? '—')
                    ->alignEnd()
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label(__('domain_names/labels.fields.auto_renew'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('domain_names/labels.fields.expires_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->badge()
                    ->color(function (DomainName $d) {
                        $days = $d->daysUntilExpiry();
                        if ($days === null) {
                            return 'gray';
                        }
                        if ($days < 0) {
                            return 'danger';
                        }
                        if ($days <= 14) {
                            return 'danger';
                        }
                        if ($days <= 45) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label(__('domain_names/labels.fields.days_left'))
                    ->getStateUsing(fn (DomainName $d) => $d->daysUntilExpiry())
                    ->placeholder('—')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('registrar')
                    ->label(__('domain_names/labels.fields.registrar'))
                    ->options(Registrar::options()),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('domain_names/labels.fields.status'))
                    ->options(DomainStatus::options()),
                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label(__('domain_names/labels.fields.auto_renew')),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label(__('domain_names/labels.filters.expiring_soon'))
                    ->query(fn (Builder $query) => $query->expiringWithin(30))
                    ->toggle(),
                Tables\Filters\Filter::make('expired')
                    ->label(__('domain_names/labels.filters.expired'))
                    ->query(fn (Builder $query) => $query->whereDate('expires_at', '<', now()))
                    ->toggle(),
            ])
            ->actions([
                self::logRenewalAction(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes(['name', 'created_at', 'updated_at'])
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label(__('domain_names/labels.fields.name'))
                            ->required()
                            ->unique(table: 'domain_names', column: 'name'),
                    ])
                    ->beforeReplicaSaved(function (DomainName $replica, array $data) {
                        $replica->name = $data['name'];
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expires_at', 'asc');
    }

    /**
     * Materialise a domain renewal as a FinancialEntry(loss) and roll
     * `expires_at` forward by the renewal period — the cost-side
     * counterpart to RecurringExpense's "log occurrence".
     *
     * Idempotent: the entry is tagged with a stable external_ref
     * (`domain_renewal:{id}:{expires_at}`), so logging the same cycle
     * twice is a no-op rather than a duplicate loss.
     */
    private static function logRenewalAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('logRenewal')
            ->label(__('domain_names/labels.actions.log_renewal'))
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn (DomainName $d) => $d->expires_at !== null)
            ->requiresConfirmation()
            ->modalDescription(__('domain_names/labels.actions.log_renewal_hint'))
            ->action(function (DomainName $domain): void {
                if ($domain->renewal_cost_cents === null) {
                    Notification::make()
                        ->warning()
                        ->title(__('domain_names/labels.actions.log_renewal_no_cost'))
                        ->send();

                    return;
                }

                $renewedCycle = $domain->expires_at?->toDateString() ?? now()->toDateString();
                $externalRef = sprintf('domain_renewal:%d:%s', $domain->id, $renewedCycle);

                $finance = app(FinanceService::class);
                if ($finance->findIdByExternalRef($externalRef) !== null) {
                    Notification::make()
                        ->warning()
                        ->title(__('domain_names/labels.actions.log_renewal_already'))
                        ->send();

                    return;
                }

                $finance->record(FinancialEntryType::Loss, [
                    'amount_cents' => (int) $domain->renewal_cost_cents,
                    'currency' => $domain->currency,
                    'occurred_at' => $renewedCycle,
                    'description' => __('domain_names/labels.actions.log_renewal_description', [
                        'name' => $domain->name,
                        'registrar' => $domain->registrar->label(),
                    ]),
                    'category' => 'domains',
                    'contact_id' => $domain->owner_contact_id,
                    'external_ref' => $externalRef,
                    'owner_user_id' => $domain->owner_user_id,
                ]);

                $months = max(1, (int) $domain->renewal_period_months);
                $domain->update([
                    'expires_at' => Carbon::parse($renewedCycle)->addMonths($months)->toDateString(),
                    'status' => DomainStatus::Active->value,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('domain_names/labels.actions.log_renewal_success'))
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomainNames::route('/'),
            'create' => Pages\CreateDomainName::route('/create'),
            'edit' => Pages\EditDomainName::route('/{record}/edit'),
        ];
    }
}
