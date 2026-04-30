<?php

declare(strict_types=1);

namespace App\Domains\Documents\Console\Commands;

use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Daily: issue every active recurring schedule whose next_issue_at
 * has come due. Each issuance is its own transaction inside
 * RecurringFatturaService::issue, so one bad row doesn't stop the rest.
 */
class IssueRecurringFattureCommand extends Command
{
    protected $signature = 'fatture:issue-recurring';

    protected $description = 'Issue fatture for all recurring schedules due today.';

    public function handle(RecurringFatturaService $service): int
    {
        $today = now()->toDateString();
        $count = 0;
        $failures = 0;

        RecurringFattura::query()
            ->dueOn($today)
            ->each(function (RecurringFattura $rec) use ($service, &$count, &$failures) {
                try {
                    $service->issue($rec->id);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('fatture.recurring.issue_failed', [
                        'recurring_id' => $rec->id,
                        'exception' => $e->getMessage(),
                    ]);
                    $failures++;
                }
            });

        $this->info("Issued {$count} recurring fattura(s); {$failures} failure(s).");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
