<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Quotations\Services\Public\QuotationsService;
use DomainException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Inline correlated subquery to avoid N+1 on the client name column.
        return parent::getEloquentQuery()
            ->addSelect([
                'client_name' => Contact::query()
                    ->select('name')
                    ->whereColumn('contacts.id', 'quotations.client_contact_id')
                    ->limit(1),
            ]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-euro';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('quotations/labels.plural');
    }

    public static function getModelLabel(): string
    {
        return __('quotations/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('quotations/labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('quotations/labels.sections.header'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('quotations/labels.fields.name'))
                        ->required()
                        ->columnSpan(3),
                    \App\Shared\Filament\ContactPicker::make('client_contact_id')
                        ->label(__('quotations/labels.fields.client'))
                        ->required(),
                    Forms\Components\DatePicker::make('issued_at')
                        ->label(__('quotations/labels.fields.issued_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),
                    Forms\Components\DatePicker::make('valid_until')
                        ->label(__('quotations/labels.fields.valid_until'))
                        ->displayFormat('d/m/Y')
                        ->default(now()->addDays(30)),
                ]),

            Forms\Components\Section::make(__('quotations/labels.sections.lines'))
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('service_id')
                                ->label(__('catalog/labels.line_picker.label'))
                                ->helperText(__('catalog/labels.line_picker.hint'))
                                ->options(fn () => app(\App\Domains\Catalog\Services\Public\CatalogService::class)->activeOptions())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                    if (blank($state)) {
                                        return;
                                    }
                                    $defaults = app(\App\Domains\Catalog\Services\Public\CatalogService::class)
                                        ->lineDefaults((int) $state);
                                    if ($defaults === null) {
                                        return;
                                    }
                                    $set('description', $defaults['description']);
                                    if ($defaults['unit_price_cents'] !== null) {
                                        $set('unit_price_cents', \App\Shared\Filament\MoneyInput::centsToMajor($defaults['unit_price_cents']));
                                    }
                                    $set('vat_rate', $defaults['vat_rate']);
                                    if ($defaults['cadence'] !== null) {
                                        $set('cadence', $defaults['cadence']);
                                    }
                                })
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('description')
                                ->label(__('quotations/labels.fields.line_description'))
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('qty')
                                ->label(__('quotations/labels.fields.line_qty'))
                                ->numeric()->default(1)->required(),
                            \App\Shared\Filament\MoneyInput::make('unit_price_cents')
                                ->label(__('quotations/labels.fields.line_unit_price'))
                                ->required(),
                            Forms\Components\TextInput::make('vat_rate')
                                ->label(__('quotations/labels.fields.line_vat_rate'))
                                ->numeric()->default(22)->required(),
                            Forms\Components\Select::make('cadence')
                                ->label(__('quotations/labels.fields.line_cadence'))
                                ->options(\App\Domains\Quotations\Enums\LineCadence::options())
                                ->default(\App\Domains\Quotations\Enums\LineCadence::UnaTantum->value)
                                ->required(),
                        ])
                        ->columns(6)
                        ->defaultItems(1)
                        ->reorderable(),
                ]),

            Forms\Components\Section::make(__('quotations/labels.sections.extras'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_number')
                    ->label(__('quotations/labels.fields.preventivo_number'))
                    ->getStateUsing(fn (Quotation $q) => $q->displayNumber())
                    ->sortable(['year', 'number']),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('quotations/labels.fields.name'))
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('client_name')
                    ->label(__('quotations/labels.fields.client'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('quotations/labels.fields.issued_at'))
                    ->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('quotations/labels.fields.valid_until'))
                    ->date('d/m/Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label(__('quotations/labels.fields.total'))
                    ->getStateUsing(fn (Quotation $q) => $q->total()->format(app()->getLocale()))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('quotations/labels.fields.status'))
                    ->badge()
                    ->color(fn (QuotationStatus $state) => $state->color())
                    ->formatStateUsing(fn (QuotationStatus $state) => $state->label()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(QuotationStatus::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::duplicateAction(),
                self::markSentAction(),
                self::acceptAction(),
                self::rejectAction(),
                self::renderPdfAction(),
                self::downloadPdfAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    private static function markSentAction(): Action
    {
        return Action::make('markSent')
            ->label(__('quotations/labels.actions.mark_sent'))
            ->icon('heroicon-o-paper-airplane')
            ->visible(fn (Quotation $q) => $q->status === QuotationStatus::Draft)
            ->action(function (Quotation $q) {
                app(QuotationsService::class)->markSent($q->id);
            });
    }

    /**
     * Duplicate a quotation as a fresh Draft with a newly-allocated
     * (year, number) pair. The lines, client, currency, notes and
     * cadence are cloned; status/fattura_id/document_id reset.
     */
    private static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('app.actions.duplicate'))
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->modalHeading(__('app.actions.duplicate_heading'))
            ->fillForm(fn (Quotation $q) => [
                'issued_at' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'name' => $q->name.' '.__('app.actions.copy_suffix'),
            ])
            ->form([
                Forms\Components\TextInput::make('name')
                    ->label(__('quotations/labels.fields.name'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('issued_at')
                    ->label(__('quotations/labels.fields.issued_at'))
                    ->displayFormat('d/m/Y')
                    ->required(),
                Forms\Components\DatePicker::make('valid_until')
                    ->label(__('quotations/labels.fields.valid_until'))
                    ->displayFormat('d/m/Y'),
            ])
            ->action(function (Quotation $q, array $data) {
                try {
                    $new = app(QuotationsService::class)->create([
                        'name' => $data['name'],
                        'client_contact_id' => (int) $q->client_contact_id,
                        'lead_id' => $q->lead_id,
                        'issued_at' => $data['issued_at'],
                        'valid_until' => $data['valid_until'] ?? null,
                        'lines' => (array) $q->lines,
                        'currency' => $q->currency,
                        'notes' => $q->notes,
                        'owner_user_id' => $q->owner_user_id,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('app.actions.duplicate_success'))
                        ->body($new->displayNumber())
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('app.actions.duplicate_failure'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    private static function acceptAction(): Action
    {
        return Action::make('accept')
            ->label(__('quotations/labels.actions.accept'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Quotation $q) => ! $q->status->isFinal())
            ->action(function (Quotation $q) {
                try {
                    app(QuotationsService::class)->accept($q->id);
                } catch (DomainException) {
                    Notification::make()->danger()
                        ->title(__('quotations/labels.notifications.cannot_transition'))
                        ->send();

                    return;
                }

                Notification::make()->success()
                    ->title(__('quotations/labels.notifications.accepted_title'))
                    ->body(__('quotations/labels.notifications.accepted_body'))
                    ->send();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('quotations/labels.actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (Quotation $q) => ! $q->status->isFinal())
            ->action(function (Quotation $q) {
                try {
                    app(QuotationsService::class)->reject($q->id);
                } catch (DomainException) {
                    Notification::make()->danger()
                        ->title(__('quotations/labels.notifications.cannot_transition'))
                        ->send();
                }
            });
    }

    private static function renderPdfAction(): Action
    {
        return Action::make('renderPdf')
            ->label(__('quotations/labels.actions.render_pdf'))
            ->icon('heroicon-o-arrow-path')
            ->action(function (Quotation $q) {
                app(QuotationsService::class)->render($q->id);
                Notification::make()->success()->title(__('quotations/labels.actions.render_pdf'))->send();
            });
    }

    private static function downloadPdfAction(): Action
    {
        return Action::make('downloadPdf')
            ->label(__('quotations/labels.actions.download_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn (Quotation $q) => $q->document_id !== null)
            ->url(fn (Quotation $q) => app(DocumentsService::class)->temporaryUrl($q->document_id), shouldOpenInNewTab: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
