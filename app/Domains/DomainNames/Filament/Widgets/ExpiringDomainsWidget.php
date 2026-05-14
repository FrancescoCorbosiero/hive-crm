<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Widgets;

use App\Domains\Contacts\Models\Contact;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Models\DomainName;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringDomainsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    private const WINDOW_DAYS = 60;

    public function getHeading(): string
    {
        return __('domain_names/labels.widgets.expiring', ['days' => self::WINDOW_DAYS]);
    }

    public static function canView(): bool
    {
        return DomainName::query()->needsAttention(self::WINDOW_DAYS)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // needsAttention, not expiringWithin: an already-expired
                // domain must stay in the highlight, not drop out of the
                // forward-looking window.
                DomainName::query()
                    ->needsAttention(self::WINDOW_DAYS)
                    ->addSelect([
                        'owner_name' => Contact::query()
                            ->select('name')
                            ->whereColumn('contacts.id', 'domain_names.owner_contact_id')
                            ->limit(1),
                    ])
                    ->orderBy('expires_at'),
            )
            ->emptyStateHeading(__('domain_names/labels.widgets.no_expiring'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('domain_names/labels.fields.name'))
                    ->weight('bold')
                    ->url(fn (DomainName $d) => DomainNameResource::getUrl('edit', ['record' => $d])),

                Tables\Columns\TextColumn::make('registrar')
                    ->label(__('domain_names/labels.fields.registrar'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (Registrar $state) => $state->label()),

                Tables\Columns\TextColumn::make('owner_name')
                    ->label(__('domain_names/labels.fields.owner_contact'))
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label(__('domain_names/labels.fields.auto_renew'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('domain_names/labels.fields.expires_at'))
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label(__('domain_names/labels.fields.days_left'))
                    ->getStateUsing(fn (DomainName $d) => $d->daysUntilExpiry())
                    ->badge()
                    ->formatStateUsing(fn (?int $state) => $state !== null && $state < 0
                        ? __('domain_names/labels.widgets.expired_badge', ['days' => abs($state)])
                        : $state)
                    ->color(fn ($state) => $state === null
                        ? 'gray'
                        : ($state <= 14 ? 'danger' : ($state <= 30 ? 'warning' : 'success'))),
            ])
            ->paginated([5, 10, 25]);
    }
}
