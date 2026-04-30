<?php

declare(strict_types=1);

namespace App\Shared\Filament;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Reusable relation manager surfacing spatie/laravel-activitylog
 * activity entries on any model that uses LogsActivity. Mount on any
 * resource with:
 *
 *   public static function getRelations(): array {
 *       return [\App\Shared\Filament\HistoryRelationManager::class];
 *   }
 *
 * Read-only — activity rows are an audit trail and never edited from
 * the panel. Filtering/searching by causer or event would be a v2.
 */
class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Storia';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Utente')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Modifiche')
                    ->getStateUsing(function ($record) {
                        $attrs = $record->properties['attributes'] ?? [];
                        $old = $record->properties['old'] ?? [];
                        $diff = [];
                        foreach ($attrs as $key => $new) {
                            $previous = $old[$key] ?? null;
                            $diff[] = is_scalar($new)
                                ? "{$key}: ".($previous ?? '∅')." → {$new}"
                                : $key;
                        }

                        return implode(' · ', $diff) ?: '—';
                    })
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
