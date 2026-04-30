<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Resources\QuotationResource\Pages;

use App\Domains\Quotations\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
