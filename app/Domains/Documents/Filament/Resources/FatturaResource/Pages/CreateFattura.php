<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\FatturaResource\Pages;

use App\Domains\Documents\Filament\Resources\FatturaResource;
use App\Domains\Documents\Services\Public\FatturaService;
use Filament\Resources\Pages\CreateRecord;

/**
 * Override Filament's default model->create() so we route the row
 * through FatturaService and get race-safe sequential numbering.
 */
class CreateFattura extends CreateRecord
{
    protected static string $resource = FatturaResource::class;

    protected function handleRecordCreation(array $data): \App\Domains\Documents\Models\Fattura
    {
        return app(FatturaService::class)->create([
            'client_contact_id' => (int) $data['client_contact_id'],
            'issued_at' => $data['issued_at'],
            'lines' => $data['lines'] ?? [],
            'payment_status' => $data['payment_status'],
        ]);
    }
}
