<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Documents\Services\Public\FatturaService;
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
                    Forms\Components\Select::make('client_contact_id')
                        ->label(__('documents/labels.fields.client'))
                        ->options(fn () => Contact::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
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
                Tables\Actions\EditAction::make(),
                self::renderPdfAction(),
                self::downloadPdfAction(),
            ])
            ->defaultSort('issued_at', 'desc');
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
