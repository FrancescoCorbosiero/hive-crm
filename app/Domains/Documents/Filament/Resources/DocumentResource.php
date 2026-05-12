<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources;

use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Filament\Resources\DocumentResource\Pages;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Services\Public\DocumentsService;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

/**
 * Read-mostly browser of all documents (uploads + rendered fatture).
 * Creation lives on the matching domain action — fatture come from
 * FatturaResource, free-form uploads come from a custom upload action
 * (out of scope for the v1 list).
 */
class DocumentResource extends Resource
{
    use Translatable;

    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('documents/labels.documents.plural');
    }

    public static function getModelLabel(): string
    {
        return __('documents/labels.documents.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents/labels.documents.plural');
    }

    public static function getTranslatableLocales(): array
    {
        return config('app.supported_locales', ['it', 'en']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('documents/labels.fields.title'))
                    ->getStateUsing(fn (Document $d) => $d->getTranslation('title', app()->getLocale()))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('documents/labels.fields.category'))
                    ->badge()
                    ->color(fn (DocumentCategory $state) => $state->color())
                    ->formatStateUsing(fn (DocumentCategory $state) => $state->label()),
                Tables\Columns\TextColumn::make('file_size')
                    ->label(__('documents/labels.fields.file_size'))
                    ->formatStateUsing(fn (int $state) => self::formatBytes($state))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('mime')
                    ->label(__('documents/labels.fields.mime'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('documents/labels.fields.issued_at'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('related')
                    ->label(__('documents/labels.fields.related'))
                    ->getStateUsing(fn (Document $d) => $d->related_type ? "{$d->related_type}#{$d->related_id}" : '—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('documents/labels.fields.category'))
                    ->options(DocumentCategory::options()),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('documents/labels.actions.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $d) => app(DocumentsService::class)->temporaryUrl($d->id), shouldOpenInNewTab: true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
        ];
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
