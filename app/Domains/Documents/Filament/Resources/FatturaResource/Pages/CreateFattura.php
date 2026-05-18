<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\FatturaResource\Pages;

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Filament\Resources\FatturaResource;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\PaymentsService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Override Filament's default model->create() so we route the row
 * through FatturaService and get race-safe sequential numbering.
 *
 * Also executes the create-form opt-in auto-spawns after the row is
 * persisted: render PDF, and / or mark the fattura as paid in full
 * at issuance. Both are dehydrated(false) form fields that never
 * reach the model.
 */
class CreateFattura extends CreateRecord
{
    protected static string $resource = FatturaResource::class;

    protected function handleRecordCreation(array $data): Fattura
    {
        return app(FatturaService::class)->create([
            'client_contact_id' => (int) $data['client_contact_id'],
            'issued_at' => $data['issued_at'],
            'due_date' => $data['due_date'] ?? null,
            'lines' => $data['lines'] ?? [],
            'payment_status' => $data['payment_status'],
        ]);
    }

    /**
     * Run the operator's opt-in auto-spawns. Failures here are
     * surfaced as warning notifications rather than failing the
     * create (the fattura number is already allocated and a tax
     * artefact — we don't undo that for a downstream hiccup).
     */
    protected function afterCreate(): void
    {
        /** @var Fattura $fattura */
        $fattura = $this->record;
        $raw = $this->data;

        if (! empty($raw['auto_render_pdf'])) {
            try {
                app(FatturaService::class)->render($fattura->id);
            } catch (\Throwable $e) {
                Notification::make()
                    ->warning()
                    ->title(__('documents/labels.auto_populate.render_failed'))
                    ->body($e->getMessage())
                    ->send();
            }
        }

        if (! empty($raw['mark_as_paid'])) {
            try {
                app(PaymentsService::class)->record($fattura->id, [
                    'amount_cents' => (int) $fattura->total_cents,
                    'paid_at' => $fattura->issued_at?->toDateString() ?? now()->toDateString(),
                    'method' => (string) ($raw['mark_as_paid_method'] ?? PaymentMethod::BankTransfer->value),
                ]);
            } catch (\Throwable $e) {
                Notification::make()
                    ->warning()
                    ->title(__('documents/labels.auto_populate.mark_paid_failed'))
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }
}
