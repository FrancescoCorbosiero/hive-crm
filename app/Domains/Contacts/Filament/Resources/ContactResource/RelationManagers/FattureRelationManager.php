<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Read-only listing of the contact's unpaid / partially paid / overdue
 * fatture, with an inline days-overdue badge so the operator can spot
 * who's late without leaving the contact page.
 */
class FattureRelationManager extends RelationManager
{
    protected static string $relationship = 'fatture';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.fatture');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->whereIn('payment_status', [
                    PaymentStatus::Unpaid->value,
                    PaymentStatus::PartiallyPaid->value,
                    PaymentStatus::Overdue->value,
                ])
                ->orderByDesc('issued_at'))
            ->columns([
                Tables\Columns\TextColumn::make('display_number')
                    ->label(__('documents/labels.fields.fattura_number'))
                    ->getStateUsing(fn (Fattura $f) => $f->displayNumber()),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('documents/labels.fields.issued_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('documents/labels.payment.due_date'))
                    ->date('d/m/Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('outstanding')
                    ->label(__('documents/labels.payment.outstanding'))
                    ->getStateUsing(fn (Fattura $f) => $f->outstanding()->format(app()->getLocale()))
                    ->color(fn (Fattura $f) => $f->outstanding()->isZero() ? 'success' : 'warning')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->label(__('documents/labels.payment.days_overdue'))
                    ->getStateUsing(function (Fattura $f): ?int {
                        if (! $f->due_date || $f->outstanding()->isZero()) {
                            return null;
                        }
                        $days = Carbon::parse($f->due_date)->startOfDay()
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
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('documents/labels.fields.payment_status'))
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->color())
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label()),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('contacts/labels.summary.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Fattura $f) => \App\Domains\Documents\Filament\Resources\FatturaResource::getUrl('edit', ['record' => $f->id])),
            ])
            ->paginated(false);
    }
}
