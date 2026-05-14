<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Documents\Services\Internal\FatturaPaExporter;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Shared\Filament\MoneyInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class FatturaResource extends Resource
{
    protected static ?string $model = Fattura::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 6;

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'year'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Fattura::query()
            ->whereIn('payment_status', ['unpaid', 'partially_paid', 'overdue'])
            ->whereDate('due_date', '<', now())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Avoid N+1 on the client column: inline a correlated subquery
        // returning the client's name as `client_name` on each row.
        return parent::getEloquentQuery()
            ->addSelect([
                'client_name' => Contact::query()
                    ->select('name')
                    ->whereColumn('contacts.id', 'fatture.client_contact_id')
                    ->limit(1),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('documents/labels.fatture.plural');
    }

    public static function getModelLabel(): string
    {
        return __('documents/labels.fatture.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents/labels.fatture.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('documents/labels.sections.header'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('year')
                        ->label(__('documents/labels.fields.year'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('number')
                        ->label(__('documents/labels.fields.number'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    \App\Shared\Filament\ContactPicker::make('client_contact_id')
                        ->label(__('documents/labels.fields.client'))
                        ->required(),
                    Forms\Components\DatePicker::make('issued_at')
                        ->label(__('documents/labels.fields.issued_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),
                    Forms\Components\DatePicker::make('due_date')
                        ->label(__('documents/labels.payment.due_date'))
                        ->displayFormat('d/m/Y')
                        ->default(now()->addDays(30)),
                    Forms\Components\Select::make('payment_status')
                        ->label(__('documents/labels.fields.payment_status'))
                        ->options(PaymentStatus::options())
                        ->default(PaymentStatus::Unpaid->value)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Aggiornato automaticamente dai pagamenti registrati.'),
                ]),

            Forms\Components\Section::make(__('documents/labels.sections.lines'))
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
                                        $set('unit_price_cents', MoneyInput::centsToMajor($defaults['unit_price_cents']));
                                    }
                                    $set('vat_rate', $defaults['vat_rate']);
                                })
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('description')
                                ->label(__('documents/labels.fields.line_description'))
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('qty')
                                ->label(__('documents/labels.fields.line_qty'))
                                ->numeric()
                                ->default(1)
                                ->required(),
                            \App\Shared\Filament\MoneyInput::make('unit_price_cents')
                                ->label(__('documents/labels.fields.line_unit_price'))
                                ->required(),
                            Forms\Components\TextInput::make('vat_rate')
                                ->label(__('documents/labels.fields.line_vat_rate'))
                                ->numeric()
                                ->default(22)
                                ->required(),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->reorderable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_number')
                    ->label(__('documents/labels.fields.fattura_number'))
                    ->getStateUsing(fn (Fattura $f) => $f->displayNumber())
                    ->sortable(['year', 'number']),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('documents/labels.fields.issued_at'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_name')
                    ->label(__('documents/labels.fields.client'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label(__('documents/labels.fields.total'))
                    ->getStateUsing(fn (Fattura $f) => $f->total()->format(app()->getLocale()))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('paid_amount_cents')
                    ->label(__('documents/labels.payment.outstanding'))
                    ->getStateUsing(fn (Fattura $f) => $f->outstanding()->format(app()->getLocale()))
                    ->color(fn (Fattura $f) => $f->outstanding()->isZero() ? 'success' : 'warning')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('documents/labels.payment.due_date'))
                    ->date('d/m/Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->label(__('documents/labels.payment.days_overdue'))
                    ->getStateUsing(function (Fattura $f): ?int {
                        if (! $f->due_date || $f->outstanding()->isZero()) {
                            return null;
                        }
                        $days = \Illuminate\Support\Carbon::parse($f->due_date)->startOfDay()
                            ->diffInDays(now()->startOfDay(), false);
                        return $days > 0 ? (int) $days : null;
                    })
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state > 90 => 'danger',
                        $state > 30 => 'warning',
                        default => 'info',
                    })
                    ->placeholder('—')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('documents/labels.fields.payment_status'))
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->color())
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('documents/labels.fields.payment_status'))
                    ->options(PaymentStatus::options()),
                Tables\Filters\SelectFilter::make('year')
                    ->label(__('documents/labels.fields.year'))
                    ->options(fn () => Fattura::query()
                        ->select('year')->distinct()->orderByDesc('year')
                        ->pluck('year', 'year')->all()),
            ])
            ->actions([
                self::recordPaymentAction(),
                Tables\Actions\EditAction::make(),
                self::duplicateAction(),
                self::renderPdfAction(),
                self::downloadPdfAction(),
                self::exportXmlAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('recordPaymentsOnIssueDate')
                        ->label(__('documents/labels.actions.record_payments_bulk'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('documents/labels.actions.record_payments_bulk_heading'))
                        ->modalDescription(__('documents/labels.actions.record_payments_bulk_description'))
                        ->modalSubmitActionLabel(__('documents/labels.actions.record_payments_bulk_submit'))
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $service = app(PaymentsService::class);
                            $done = 0;
                            $skipped = 0;
                            $failed = 0;

                            foreach ($records as $fattura) {
                                if (in_array($fattura->payment_status, [PaymentStatus::Paid, PaymentStatus::Cancelled], true)) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    $service->record($fattura->id, [
                                        'amount_cents' => $fattura->outstanding()->cents,
                                        'paid_at' => $fattura->issued_at?->toDateString() ?? now()->toDateString(),
                                        'method' => PaymentMethod::BankTransfer->value,
                                    ]);
                                    $done++;
                                } catch (\Throwable $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title(__('documents/labels.actions.record_payments_bulk_success', [
                                    'done' => $done,
                                ]))
                                ->body(__('documents/labels.actions.record_payments_bulk_summary', [
                                    'skipped' => $skipped,
                                    'failed' => $failed,
                                ]))
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    /**
     * One-click "this fattura was paid" action. Records a Payment via
     * PaymentsService, which fires PaymentRecorded → Finance listener
     * mirrors it as a FinancialEntry (income). So the revenue row in
     * the ledger materializes through this action — fattura issuance
     * alone never creates revenue.
     */
    private static function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label(__('documents/labels.actions.record_payment'))
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (Fattura $f) => ! in_array(
                $f->payment_status,
                [PaymentStatus::Paid, PaymentStatus::Cancelled],
                true,
            ))
            ->modalHeading(fn (Fattura $f) => __('documents/labels.actions.record_payment_heading', [
                'number' => $f->displayNumber(),
            ]))
            ->modalSubmitActionLabel(__('documents/labels.actions.record_payment'))
            ->fillForm(fn (Fattura $f) => [
                'amount_cents' => $f->outstanding()->cents,
                'paid_at' => $f->issued_at?->toDateString() ?? now()->toDateString(),
                'method' => PaymentMethod::BankTransfer->value,
            ])
            ->form([
                MoneyInput::make('amount_cents')
                    ->label(__('documents/labels.payment.amount'))
                    ->required(),

                Forms\Components\DatePicker::make('paid_at')
                    ->label(__('documents/labels.payment.paid_at'))
                    ->displayFormat('d/m/Y')
                    ->required(),

                Forms\Components\Select::make('method')
                    ->label(__('documents/labels.payment.method'))
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn (PaymentMethod $m) => [
                            $m->value => __('documents/labels.payment_method.'.$m->value),
                        ])
                        ->all())
                    ->required(),

                Forms\Components\TextInput::make('reference')
                    ->label(__('documents/labels.payment.reference'))
                    ->maxLength(255),
            ])
            ->action(function (Fattura $f, array $data): void {
                try {
                    app(PaymentsService::class)->record($f->id, [
                        'amount_cents' => (int) $data['amount_cents'],
                        'paid_at' => $data['paid_at'],
                        'method' => $data['method'] ?? PaymentMethod::BankTransfer->value,
                        'reference' => $data['reference'] ?? null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('documents/labels.actions.record_payment_success', [
                            'number' => $f->displayNumber(),
                        ]))
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('documents/labels.actions.record_payment_failure'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    private static function renderPdfAction(): Action
    {
        return Action::make('renderPdf')
            ->label(__('documents/labels.actions.render_pdf'))
            ->icon('heroicon-o-arrow-path')
            ->action(function (Fattura $f) {
                app(FatturaService::class)->render($f->id);
                Notification::make()->success()->title(__('documents/labels.actions.render_pdf'))->send();
            });
    }

    private static function downloadPdfAction(): Action
    {
        return Action::make('downloadPdf')
            ->label(__('documents/labels.actions.download_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn (Fattura $f) => $f->document_id !== null)
            ->url(fn (Fattura $f) => app(DocumentsService::class)->temporaryUrl($f->document_id), shouldOpenInNewTab: true);
    }

    /**
     * Duplicate a fattura as a fresh Unpaid one with a newly-allocated
     * (year, number) pair from FatturaService. Lines, client, currency
     * and fiscal flags are cloned; payment state and document linkage
     * reset. Issuance date defaults to today but is editable.
     */
    private static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('app.actions.duplicate'))
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->modalHeading(__('app.actions.duplicate_heading'))
            ->modalDescription(__('documents/labels.actions.duplicate_description'))
            ->fillForm(fn (Fattura $f) => [
                'issued_at' => now()->toDateString(),
            ])
            ->form([
                Forms\Components\DatePicker::make('issued_at')
                    ->label(__('documents/labels.fields.issued_at'))
                    ->displayFormat('d/m/Y')
                    ->required(),
            ])
            ->action(function (Fattura $f, array $data) {
                try {
                    $attrs = [
                        'client_contact_id' => (int) $f->client_contact_id,
                        'issued_at' => $data['issued_at'],
                        'lines' => (array) $f->lines,
                        'currency' => $f->currency,
                        'owner_user_id' => $f->owner_user_id,
                    ];

                    $new = app(FatturaService::class)->create($attrs);

                    // Carry over the fiscal flags that FatturaService::create
                    // doesn't take as input (regime/natura live on the
                    // fattura row, not in the create payload).
                    if ($f->regime_fiscale || $f->natura) {
                        $new->update(array_filter([
                            'regime_fiscale' => $f->regime_fiscale,
                            'natura' => $f->natura,
                        ], fn ($v) => $v !== null));
                    }

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

    /**
     * Generate the FatturaPA (FPR12) XML for a single fattura and
     * stream it as a download. Validates owner config + Cessionario
     * data BEFORE returning; if anything's missing, surfaces a clear
     * error notification instead of emitting an invalid XML.
     */
    private static function exportXmlAction(): Action
    {
        return Action::make('exportXml')
            ->label(__('documents/labels.actions.export_xml'))
            ->icon('heroicon-o-code-bracket')
            ->color('info')
            ->action(function (Fattura $f) {
                try {
                    $out = app(FatturaPaExporter::class)->export($f->id);

                    return response()->streamDownload(
                        fn () => print($out['xml']),
                        $out['filename'],
                        ['Content-Type' => 'application/xml'],
                    );
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('documents/labels.actions.export_xml_failure'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            \App\Domains\Documents\Filament\Resources\FatturaResource\RelationManagers\PaymentsRelationManager::class,
            \App\Shared\Filament\HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFatture::route('/'),
            'create' => Pages\CreateFattura::route('/create'),
            'edit' => Pages\EditFattura::route('/{record}/edit'),
        ];
    }
}
