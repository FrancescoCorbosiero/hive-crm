<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Resources\QuotationResource\Pages;

use App\Domains\Quotations\Filament\Resources\QuotationResource;
use App\Domains\Quotations\Services\Public\QuotationsService;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): \App\Domains\Quotations\Models\Quotation
    {
        return app(QuotationsService::class)->create([
            'name' => $data['name'],
            'client_contact_id' => (int) $data['client_contact_id'],
            'issued_at' => $data['issued_at'],
            'valid_until' => $data['valid_until'] ?? null,
            'lines' => $data['lines'] ?? [],
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
