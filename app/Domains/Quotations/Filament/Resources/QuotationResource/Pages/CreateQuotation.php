<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Resources\QuotationResource\Pages;

use App\Domains\Quotations\Filament\Resources\QuotationResource;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Quotations\Services\Public\QuotationsService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): Quotation
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

    /**
     * Run the operator's opt-in auto-spawns after the row is persisted.
     * Both are pure status / artefact operations on the quotation
     * itself — nothing is sent externally. Failures surface as
     * warning notifications rather than failing the create (the
     * quotation number is already allocated).
     */
    protected function afterCreate(): void
    {
        /** @var Quotation $quotation */
        $quotation = $this->record;
        $raw = $this->data;

        if (! empty($raw['auto_render_pdf'])) {
            try {
                app(QuotationsService::class)->render($quotation->id);
            } catch (\Throwable $e) {
                Notification::make()
                    ->warning()
                    ->title(__('quotations/labels.auto_populate.render_failed'))
                    ->body($e->getMessage())
                    ->send();
            }
        }

        if (! empty($raw['mark_as_sent'])) {
            try {
                app(QuotationsService::class)->markSent($quotation->id);
            } catch (\Throwable $e) {
                Notification::make()
                    ->warning()
                    ->title(__('quotations/labels.auto_populate.mark_sent_failed'))
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }
}
