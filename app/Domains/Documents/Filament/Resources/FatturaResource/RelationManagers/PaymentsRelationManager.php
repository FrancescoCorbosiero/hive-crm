<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\FatturaResource\RelationManagers;

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Models\Payment;
use App\Domains\Documents\Services\Public\PaymentsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getRelationshipName(): string
    {
        return 'payments';
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('documents/labels.payment.plural');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('paid_at')
                ->label(__('documents/labels.payment.paid_at'))
                ->displayFormat('d/m/Y')
                ->default(now())
                ->required(),
            \App\Shared\Filament\MoneyInput::make('amount_cents')
                ->label(__('documents/labels.payment.amount'))
                ->required(),
            Forms\Components\Select::make('method')
                ->label(__('documents/labels.payment.method'))
                ->options(PaymentMethod::options())
                ->default(PaymentMethod::BankTransfer->value)
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->label(__('documents/labels.payment.reference')),
            Forms\Components\Textarea::make('notes')
                ->label(__('documents/labels.payment.notes'))
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('documents/labels.payment.paid_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('documents/labels.payment.amount'))
                    ->getStateUsing(fn (Payment $p) => $p->amount()->format(app()->getLocale()))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('method')
                    ->label(__('documents/labels.payment.method'))
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state) => $state->label()),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('documents/labels.payment.reference'))
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('record')
                    ->label(__('documents/labels.actions.record_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->form(fn () => $this->form(\Filament\Forms\Form::make($this))->getComponents())
                    ->action(function (array $data): void {
                        app(PaymentsService::class)->record(
                            (int) $this->getOwnerRecord()->id,
                            $data,
                        );
                    }),
                \Filament\Tables\Actions\ExportAction::make()
                    ->exporter(\App\Domains\Documents\Filament\Exports\PaymentExporter::class),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->action(function (Payment $record) {
                        app(PaymentsService::class)->delete($record->id);
                    }),
            ]);
    }
}
