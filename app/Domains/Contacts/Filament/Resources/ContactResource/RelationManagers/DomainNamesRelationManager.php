<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Models\DomainName;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Domains owned by the contact, ordered by expiry so the most urgent
 * row lands at the top. The expiry badge follows the same colour
 * scale as DomainNameResource: red for already-expired or expiring
 * within 14 days, amber within 45, green beyond.
 */
class DomainNamesRelationManager extends RelationManager
{
    protected static string $relationship = 'domainNames';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.domains');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderBy('expires_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('domain_names/labels.fields.name'))
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('registrar')
                    ->label(__('domain_names/labels.fields.registrar'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (Registrar $state) => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('domain_names/labels.fields.status'))
                    ->badge()
                    ->color(fn (DomainStatus $state) => $state->color())
                    ->formatStateUsing(fn (DomainStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('domain_names/labels.fields.expires_at'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->badge()
                    ->color(function (DomainName $d) {
                        $days = $d->daysUntilExpiry();
                        if ($days === null) {
                            return 'gray';
                        }
                        if ($days < 0 || $days <= 14) {
                            return 'danger';
                        }
                        if ($days <= 45) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('contacts/labels.summary.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DomainName $d) => DomainNameResource::getUrl('edit', ['record' => $d->id])),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('contacts/labels.summary.domains_empty'));
    }
}
