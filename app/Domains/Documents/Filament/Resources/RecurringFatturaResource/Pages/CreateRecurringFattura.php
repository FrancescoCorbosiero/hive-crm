<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\RecurringFatturaResource\Pages;

use App\Domains\Documents\Filament\Resources\RecurringFatturaResource;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringFattura extends CreateRecord
{
    protected static string $resource = RecurringFatturaResource::class;

    /**
     * Optionally issue the first invoice immediately after the
     * schedule is saved. Goes through RecurringFatturaService::issue
     * so next_issue_at advances by one period — same code path as
     * the manual "Issue now" row action and the daily scheduler.
     *
     * Failures surface as warning notifications rather than failing
     * the create (the schedule itself is already persisted).
     */
    protected function afterCreate(): void
    {
        /** @var RecurringFattura $schedule */
        $schedule = $this->record;
        $raw = $this->data;

        if (empty($raw['issue_first_cycle_now'])) {
            return;
        }

        try {
            $fattura = app(RecurringFatturaService::class)->issue($schedule->id);

            Notification::make()
                ->success()
                ->title(__('documents/labels.recurring.auto_populate.issued', [
                    'number' => $fattura->displayNumber(),
                ]))
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->warning()
                ->title(__('documents/labels.recurring.auto_populate.issue_failed'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
