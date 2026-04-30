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
                    Forms\Components\Select::make('client_contact_id')
                        ->label(__('quotations/labels.fields.client'))
                        ->options(fn () => Contact::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
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
                            Forms\Components\TextInput::make('description')
                                ->label(__('quotations/labels.fields.line_description'))
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('qty')
                                ->label(__('quotations/labels.fields.line_qty'))
                                ->numeric()->default(1)->required(),
                            Forms\Components\TextInput::make('unit_price_cents')
                                ->label(__('quotations/labels.fields.line_unit_price'))
                                ->numeric()->suffix('¢')->required(),
                            Forms\Components\TextInput::make('vat_rate')
                                ->label(__('quotations/labels.fields.line_vat_rate'))
                                ->numeric()->default(22)->required(),
                        ])
                        ->columns(5)
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
                Tables\Columns\TextColumn::make('client_contact_id')
                    ->label(__('quotations/labels.fields.client'))
                    ->getStateUsing(fn (Quotation $q) => Contact::find($q->client_contact_id)?->name ?? '—'),
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
                self::markSentAction(),
                self::acceptAction(),
                self::rejectAction(),
                self::renderPdfAction(),
                self::downloadPdfAction(),
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
