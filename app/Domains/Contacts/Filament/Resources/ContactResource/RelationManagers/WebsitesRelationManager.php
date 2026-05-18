<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Filament\Resources\WebsiteResource;
use App\Domains\Websites\Models\Website;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Active websites owned by the contact, ordered by next renewal so the
 * most urgent line lands at the top. Suspended / archived sites are
 * hidden — the Customer 360 stays signal-heavy. Operators jump into
 * the full WebsiteResource for edits.
 */
class WebsitesRelationManager extends RelationManager
{
    protected static string $relationship = 'websites';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.websites');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('status', '!=', WebsiteStatus::Archived->value)
                ->orderBy('next_renewal_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('websites/labels.name'))
                    ->getStateUsing(fn (Website $w) => $w->getTranslation('name', app()->getLocale()))
                    ->limit(40),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('websites/labels.url'))
                    ->url(fn (Website $w) => $w->url, shouldOpenInNewTab: true)
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('websites/labels.status'))
                    ->badge()
                    ->color(fn (WebsiteStatus $state) => $state->color())
                    ->formatStateUsing(fn (WebsiteStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('next_renewal_at')
                    ->label(__('websites/labels.next_renewal_at'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->badge()
                    ->color(function (Website $w) {
                        $days = $w->daysUntilRenewal();
                        if ($days === null) {
                            return 'gray';
                        }
                        if ($days < 0 || $days <= 7) {
                            return 'danger';
                        }
                        if ($days <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('contacts/labels.summary.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Website $w) => WebsiteResource::getUrl('edit', ['record' => $w->id])),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('contacts/labels.summary.websites_empty'));
    }
}
