<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\Documents\Events\FatturaDeleted;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Services\Public\FinanceService;

/**
 * Entries attributed to a fattura that no longer exists (typically the
 * income entry a fattura was generated from) lose that attribution, so
 * they don't point at a dangling id and can generate a new fattura.
 */
class DetachEntriesFromDeletedFattura
{
    public function __construct(private readonly FinanceService $finance) {}

    public function handle(FatturaDeleted $event): void
    {
        $this->finance->detachSource(FinancialEntrySource::Fattura, $event->fatturaId);
    }
}
