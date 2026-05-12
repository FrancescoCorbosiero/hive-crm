<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Filament\Resources\QuotationResource;
use App\Domains\Quotations\Models\Quotation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OpenQuotationsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.open_quotations.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quotation::query()
                    ->whereIn('status', [
                        QuotationStatus::Draft->value,
                        QuotationStatus::Sent->value,
                    ])
                    ->addSelect([
                        'client_name' => Contact::query()
                            ->whereColumn('contacts.id', 'quotations.client_contact_id')
                            ->select('name')
                            ->limit(1),
                    ])
                    ->orderByRaw('valid_until IS NULL, valid_until ASC')
                    ->orderByDesc('issued_at'),
            )
            ->emptyStateHeading(__('dashboard.open_quotations.empty'))
            ->columns([
                Tables\Columns\TextColumn::make('display_number')
                    ->label(__('dashboard.open_quotations.number'))
                    ->getStateUsing(fn (Quotation $q) => $q->displayNumber())
                    ->url(fn (Quotation $q) => QuotationResource::getUrl('edit', ['record' => $q])),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('dashboard.open_quotations.title'))
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label(__('dashboard.open_quotations.client'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label(__('dashboard.open_quotations.total'))
                    ->alignEnd()
                    ->getStateUsing(fn (Quotation $q) => $q->total()->format(app()->getLocale())),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('dashboard.open_quotations.status'))
                    ->badge()
                    ->color(fn (QuotationStatus $state) => $state->color())
                    ->formatStateUsing(fn (QuotationStatus $state) => $state->label()),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('dashboard.open_quotations.valid_until'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->badge()
                    ->color(function (Quotation $q) {
                        if (! $q->valid_until) {
                            return 'gray';
                        }
                        $today = now()->startOfDay();
                        if ($q->valid_until->lt($today)) {
                            return 'danger';
                        }
                        if ($q->valid_until->diffInDays($today) <= 7) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->paginated([5, 10, 25]);
    }
}
