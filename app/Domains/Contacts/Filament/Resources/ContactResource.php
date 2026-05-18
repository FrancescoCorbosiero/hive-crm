<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages;
use App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Filament\Resources\FatturaResource;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\Quotations\Filament\Resources\QuotationResource;
use App\Domains\Quotations\Services\Public\QuotationsService;
use App\Domains\Websites\Filament\Resources\WebsiteResource;
use App\Shared\Filament\HistoryRelationManager;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'ragione_sociale', 'email', 'vat_number', 'tax_code'];
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

                    Forms\Components\TextInput::make('ragione_sociale')
                        ->label(__('contacts/labels.ragione_sociale'))
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

                    Forms\Components\TextInput::make('sdi_code')
                        ->label(__('contacts/labels.sdi_code'))
                        ->helperText(__('contacts/labels.sdi_code_help'))
                        ->maxLength(7),

                    Forms\Components\TextInput::make('pec_email')
                        ->label(__('contacts/labels.pec_email'))
                        ->email()
                        ->maxLength(255),
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

            Forms\Components\Section::make(__('contacts/labels.section.links'))
                ->description(__('contacts/labels.section.links_hint'))
                ->schema([
                    Forms\Components\TextInput::make('trello_board_url')
                        ->label(__('contacts/labels.trello_board_url'))
                        ->placeholder('https://trello.com/b/…')
                        ->url()
                        ->maxLength(255)
                        ->suffixIcon('heroicon-o-arrow-top-right-on-square'),
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
                Tables\Columns\TextColumn::make('trello_board_url')
                    ->label(__('contacts/labels.trello_board_url_short'))
                    ->placeholder('—')
                    ->url(fn (?string $state) => $state, shouldOpenInNewTab: true)
                    ->formatStateUsing(fn (?string $state) => $state ? __('contacts/labels.trello_open') : null)
                    ->icon(fn (?string $state) => $state ? 'heroicon-o-arrow-top-right-on-square' : null)
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('app.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes([
                        // Fiscal identifiers must NOT be cloned —
                        // they're meant to be unique per legal entity.
                        // The user fills them in afresh on the copy.
                        'vat_number', 'tax_code', 'sdi_code', 'pec_email',
                        'created_at', 'updated_at', 'deleted_at',
                    ])
                    ->beforeReplicaSaved(function (Contact $replica) {
                        $replica->name = $replica->name.' '.__('app.actions.copy_suffix');
                    })
                    ->successNotificationTitle(__('app.actions.duplicate_success')),
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
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WebsitesRelationManager::class,
            RelationManagers\DomainNamesRelationManager::class,
            RelationManagers\QuotationsRelationManager::class,
            RelationManagers\FattureRelationManager::class,
            RelationManagers\CalendarEventsRelationManager::class,
            RelationManagers\MailRelationManager::class,
            HistoryRelationManager::class,
        ];
    }

    /**
     * Quick-create header actions for the Customer 360 (View/Edit
     * pages). Each one spawns a related entity pre-filled with this
     * contact:
     *   - Fattura / Quotation: go through their public services so a
     *     real draft row is created + the operator lands on the edit
     *     page ready to refine.
     *   - Website / DomainName: redirect to the create page with the
     *     owner_contact_id pre-baked into the URL (the create page
     *     reads it from the query string on mount).
     *
     * @return array<int, Action>
     */
    public static function quickCreateHeaderActions(Contact $contact): array
    {
        return [
            Action::make('issueFattura')
                ->label(__('contacts/labels.actions.issue_fattura'))
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->action(function () use ($contact) {
                    $fattura = app(FatturaService::class)->create([
                        'client_contact_id' => $contact->id,
                        'issued_at' => now(),
                        'lines' => [],
                        'owner_user_id' => $contact->owner_user_id,
                    ]);

                    return redirect()->to(
                        FatturaResource::getUrl('edit', ['record' => $fattura->id]),
                    );
                }),

            Action::make('createQuotation')
                ->label(__('contacts/labels.actions.create_quotation'))
                ->icon('heroicon-o-document-currency-euro')
                ->color('primary')
                ->action(function () use ($contact) {
                    $quotation = app(QuotationsService::class)->create([
                        'name' => $contact->name,
                        'client_contact_id' => $contact->id,
                        'lines' => [],
                        'owner_user_id' => $contact->owner_user_id,
                    ]);

                    return redirect()->to(
                        QuotationResource::getUrl('edit', ['record' => $quotation->id]),
                    );
                }),

            Action::make('createWebsite')
                ->label(__('contacts/labels.actions.create_website'))
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->url(fn () => WebsiteResource::getUrl('create').'?owner_contact_id='.$contact->id),

            Action::make('registerDomain')
                ->label(__('contacts/labels.actions.register_domain'))
                ->icon('heroicon-o-globe-europe-africa')
                ->color('gray')
                ->url(fn () => DomainNameResource::getUrl('create').'?owner_contact_id='.$contact->id),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
