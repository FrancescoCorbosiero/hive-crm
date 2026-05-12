<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only listing of the contact's quotations, filtered to open ones
 * (draft / sent). Anything in a final state is hidden so the Customer
 * 360 doesn't accumulate clutter. Operators jump into the full
 * QuotationResource for edits.
 */
class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.quotations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->whereIn('status', [QuotationStatus::Draft->value, QuotationStatus::Sent->value])
                ->orderByDesc('issued_at'))
            ->columns([
                Tables\Columns\TextColumn::make('display_number')
                    ->label(__('quotations/labels.fields.number'))
                    ->getStateUsing(fn (Quotation $q) => $q->displayNumber()),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('quotations/labels.fields.name'))
                    ->limit(40),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('quotations/labels.fields.issued_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('quotations/labels.fields.valid_until'))
                    ->date('d/m/Y')
                    ->placeholder('—'),
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
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('contacts/labels.summary.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Quotation $q) => \App\Domains\Quotations\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $q->id])),
            ])
            ->paginated(false);
    }
}
