<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Filament\Resources\RecurringFatturaResource\Pages;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class RecurringFatturaResource extends Resource
{
    protected static ?string $model = RecurringFattura::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('documents/labels.recurring.plural');
    }

    public static function getModelLabel(): string
    {
        return __('documents/labels.recurring.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents/labels.recurring.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('documents/labels.sections.header'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('documents/labels.recurring.name'))
                        ->required()
                        ->columnSpan(2),
                    Forms\Components\Select::make('client_contact_id')
                        ->label(__('documents/labels.fields.client'))
                        ->options(fn () => Contact::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('frequency')
                        ->label(__('documents/labels.recurring.frequency'))
                        ->options(RecurringFrequency::options())
                        ->default(RecurringFrequency::Monthly->value)
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('day_of_month')
                        ->label(__('documents/labels.recurring.day_of_month'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(28)
                        ->visible(fn (Forms\Get $get) => $get('frequency') === RecurringFrequency::Monthly->value),
                    Forms\Components\DatePicker::make('next_issue_at')
                        ->label(__('documents/labels.recurring.next_issue_at'))
                        ->displayFormat('d/m/Y')
                        ->default(now()->addMonth())
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('documents/labels.recurring.is_active'))
                        ->default(true),
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
                            Forms\Components\TextInput::make('qty')->numeric()->default(1)->required(),
                            Forms\Components\TextInput::make('unit_price_cents')->numeric()->suffix('¢')->required(),
                            Forms\Components\TextInput::make('vat_rate')->numeric()->default(22)->required(),
                        ])
                        ->columns(5)
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('documents/labels.recurring.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_contact_id')
                    ->label(__('documents/labels.fields.client'))
                    ->getStateUsing(fn (RecurringFattura $r) => Contact::find($r->client_contact_id)?->name ?? '—'),
                Tables\Columns\TextColumn::make('frequency')
                    ->label(__('documents/labels.recurring.frequency'))
                    ->badge()
                    ->formatStateUsing(fn (RecurringFrequency $state) => $state->label()),
                Tables\Columns\TextColumn::make('next_issue_at')
                    ->label(__('documents/labels.recurring.next_issue_at'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('documents/labels.recurring.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_issued_at')
                    ->label(__('documents/labels.recurring.last_issued_at'))
                    ->date('d/m/Y')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('documents/labels.recurring.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('issueNow')
                    ->label(__('documents/labels.actions.issue_now'))
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (RecurringFattura $r) {
                        $f = app(RecurringFatturaService::class)->issue($r->id);
                        Notification::make()->success()->title('Fattura '.$f->displayNumber())->send();
                    }),
                Action::make('pause')
                    ->label(__('documents/labels.actions.pause'))
                    ->icon('heroicon-o-pause')
                    ->visible(fn (RecurringFattura $r) => $r->is_active)
                    ->action(fn (RecurringFattura $r) => app(RecurringFatturaService::class)->pause($r->id)),
                Action::make('resume')
                    ->label(__('documents/labels.actions.resume'))
                    ->icon('heroicon-o-play')
                    ->visible(fn (RecurringFattura $r) => ! $r->is_active)
                    ->action(fn (RecurringFattura $r) => app(RecurringFatturaService::class)->resume($r->id)),
            ])
            ->defaultSort('next_issue_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringFatture::route('/'),
            'create' => Pages\CreateRecurringFattura::route('/create'),
            'edit' => Pages\EditRecurringFattura::route('/{record}/edit'),
        ];
    }
}
