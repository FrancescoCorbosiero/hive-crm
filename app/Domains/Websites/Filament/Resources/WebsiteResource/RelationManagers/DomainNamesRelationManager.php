<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Resources\WebsiteResource\RelationManagers;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Models\DomainName;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only listing of the domains pointing at this website — the
 * reverse of the DomainName → website auto-link. Operators jump into
 * the full DomainNameResource for edits / renewals.
 */
class DomainNamesRelationManager extends RelationManager
{
    protected static string $relationship = 'domainNames';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('domain_names/labels.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderBy('expires_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('domain_names/labels.fields.name'))
                    ->weight('bold')
                    ->copyable(),
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
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label(__('domain_names/labels.fields.auto_renew'))
                    ->boolean(),
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

                        return $days <= 14 ? 'danger' : ($days <= 45 ? 'warning' : 'success');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('contacts/labels.summary.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DomainName $d) => DomainNameResource::getUrl('edit', ['record' => $d->id])),
            ])
            ->paginated(false);
    }
}
